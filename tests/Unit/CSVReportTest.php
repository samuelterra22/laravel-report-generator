<?php

namespace SamuelTerra22\ReportGenerator\Tests\Unit;

use SamuelTerra22\ReportGenerator\ReportMedia\CSVReport;
use SamuelTerra22\ReportGenerator\Tests\TestCase;

class CSVReportTest extends TestCase
{
    private function makeReport(array $results = [], array $columns = null): CSVReport
    {
        $resultObjects = array_map(function ($row) {
            return $this->makeResultObject($row);
        }, $results);

        $query = \Mockery::mock('Illuminate\Database\Query\Builder');
        $query->shouldReceive('take')->andReturnSelf();
        $query->shouldReceive('when')->andReturnSelf();
        $query->shouldReceive('cursor')->andReturn(new \ArrayIterator($resultObjects));

        $report = new CSVReport();
        $report->of(
            'Test CSV',
            ['Period' => 'January'],
            $query,
            $columns ?? ['Name' => 'name', 'Amount' => 'amount']
        );

        return $report;
    }

    private function captureCSVOutput(CSVReport $report, string $filename = 'test'): string
    {
        ob_start();
        $report->download($filename);
        return ob_get_clean();
    }

    private function parseCSVLines(string $output): array
    {
        $lines = explode("\n", trim($output));
        return array_values(array_filter($lines, function ($line) {
            return trim($line) !== '';
        }));
    }

    public function test_csv_show_meta_defaults_to_false()
    {
        $report = new CSVReport();
        $reflection = new \ReflectionProperty($report, 'showMeta');
        $reflection->setAccessible(true);
        $this->assertFalse($reflection->getValue($report));
    }

    public function test_download_generates_csv_with_header()
    {
        $report = $this->makeReport([
            ['name' => 'Alice', 'amount' => 100],
        ]);

        $output = $this->captureCSVOutput($report);

        $this->assertStringContainsString('No', $output);
        $this->assertStringContainsString('Name', $output);
        $this->assertStringContainsString('Amount', $output);
        $this->assertStringContainsString('Alice', $output);
        $this->assertStringContainsString('100', $output);
    }

    public function test_download_includes_row_number()
    {
        $report = $this->makeReport([
            ['name' => 'Alice', 'amount' => 100],
            ['name' => 'Bob', 'amount' => 200],
        ]);

        $output = $this->captureCSVOutput($report);
        $lines = $this->parseCSVLines($output);

        // First line = header, then data rows
        $this->assertGreaterThanOrEqual(3, count($lines));
        // Row 1 data should contain Alice
        $this->assertStringContainsString('Alice', $lines[1]);
        // Row 2 data should contain Bob
        $this->assertStringContainsString('Bob', $lines[2]);
    }

    public function test_download_without_num_column()
    {
        $report = $this->makeReport([
            ['name' => 'Alice', 'amount' => 100],
        ]);
        $report->showNumColumn(false);

        $output = $this->captureCSVOutput($report);
        $lines = $this->parseCSVLines($output);

        // Header should not start with 'No'
        $header = str_getcsv($lines[0]);
        $this->assertNotEquals('No', $header[0]);
        $this->assertEquals('Name', $header[0]);
    }

    public function test_download_without_header()
    {
        $report = $this->makeReport([
            ['name' => 'Alice', 'amount' => 100],
        ]);
        $report->showHeader(false);

        $output = $this->captureCSVOutput($report);
        $lines = $this->parseCSVLines($output);

        // First line should be data, not header
        $this->assertStringContainsString('Alice', $lines[0]);
    }

    public function test_download_with_meta()
    {
        $report = $this->makeReport([
            ['name' => 'Alice', 'amount' => 100],
        ]);
        $report->showMeta(true);

        $output = $this->captureCSVOutput($report);

        $this->assertStringContainsString('Period', $output);
        $this->assertStringContainsString('January', $output);
    }

    public function test_download_without_manipulation()
    {
        $report = $this->makeReport([
            ['name' => 'Alice', 'amount' => 100],
        ]);
        $report->withoutManipulation();

        $output = $this->captureCSVOutput($report);

        $this->assertStringContainsString('Alice', $output);
        $this->assertStringContainsString('100', $output);
    }

    public function test_download_without_manipulation_pops_extra_column()
    {
        $resultObjects = [
            $this->makeResultObject(['name' => 'Alice', 'amount' => 100, 'extra' => 'hidden']),
        ];

        $query = \Mockery::mock('Illuminate\Database\Query\Builder');
        $query->shouldReceive('take')->andReturnSelf();
        $query->shouldReceive('when')->andReturnSelf();
        $query->shouldReceive('cursor')->andReturn(new \ArrayIterator($resultObjects));

        $report = new CSVReport();
        $report->of('Test', [], $query, ['Name' => 'name', 'Amount' => 'amount']);
        $report->withoutManipulation();

        $output = $this->captureCSVOutput($report);

        // "hidden" should be removed because result has 3 columns but only 2 defined
        $this->assertStringNotContainsString('hidden', $output);
    }

    public function test_download_with_closure_column()
    {
        $closure = function ($result) {
            return strtoupper($result->name);
        };

        $resultObjects = [
            $this->makeResultObject(['name' => 'alice', 'amount' => 100]),
        ];

        $query = \Mockery::mock('Illuminate\Database\Query\Builder');
        $query->shouldReceive('take')->andReturnSelf();
        $query->shouldReceive('when')->andReturnSelf();
        $query->shouldReceive('cursor')->andReturn(new \ArrayIterator($resultObjects));

        $report = new CSVReport();
        $report->of('Test', [], $query, ['Name' => $closure, 'Amount' => 'amount']);

        $output = $this->captureCSVOutput($report);

        $this->assertStringContainsString('ALICE', $output);
    }

    public function test_download_with_edit_column_display_as_closure()
    {
        $report = $this->makeReport([
            ['name' => 'Alice', 'amount' => 100],
        ]);
        $report->editColumn('Amount', [
            'displayAs' => function ($result) {
                return '$' . $result->amount;
            }
        ]);

        $output = $this->captureCSVOutput($report);

        $this->assertStringContainsString('$100', $output);
    }

    public function test_download_with_edit_column_display_as_static_value()
    {
        $report = $this->makeReport([
            ['name' => 'Alice', 'amount' => 100],
        ]);
        $report->editColumn('Amount', [
            'displayAs' => 'N/A'
        ]);

        $output = $this->captureCSVOutput($report);

        $this->assertStringContainsString('N/A', $output);
    }

    public function test_download_with_limit()
    {
        $report = $this->makeReport([
            ['name' => 'Alice', 'amount' => 100],
            ['name' => 'Bob', 'amount' => 200],
            ['name' => 'Charlie', 'amount' => 300],
        ]);
        $report->limit(2);

        $output = $this->captureCSVOutput($report);

        $this->assertStringContainsString('Alice', $output);
        $this->assertStringContainsString('Bob', $output);
    }

    public function test_download_csv_output_format()
    {
        $report = $this->makeReport([
            ['name' => 'Alice', 'amount' => 100],
        ]);
        $report->showMeta(false);

        $output = $this->captureCSVOutput($report);
        $lines = $this->parseCSVLines($output);

        // Parse CSV header
        $header = str_getcsv($lines[0]);
        $this->assertEquals(['No', 'Name', 'Amount'], $header);

        // Parse CSV data
        $data = str_getcsv($lines[1]);
        $this->assertEquals('1', $data[0]);
        $this->assertEquals('Alice', $data[1]);
        $this->assertEquals('100', $data[2]);
    }
}
