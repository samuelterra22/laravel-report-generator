<?php

declare(strict_types=1);

namespace SamuelTerra22\ReportGenerator\Tests\Unit;

use SamuelTerra22\ReportGenerator\ReportMedia\CsvReport;
use SamuelTerra22\ReportGenerator\Tests\TestCase;

class CSVReportUnhappyTest extends TestCase
{
    private function makeReport(array $results = [], ?array $columns = null): CsvReport
    {
        $resultObjects = array_map(function ($row) {
            return $this->makeResultObject($row);
        }, $results);

        $query = \Mockery::mock('Illuminate\Database\Query\Builder');
        $query->shouldReceive('take')->andReturnSelf();
        $query->shouldReceive('when')->andReturnSelf();
        $query->shouldReceive('cursor')->andReturn(new \ArrayIterator($resultObjects));

        $report = new CsvReport;
        $report->of(
            'Test CSV',
            ['Period' => 'January'],
            $query,
            $columns ?? ['Name' => 'name', 'Amount' => 'amount']
        );

        return $report;
    }

    private function captureCSVOutput(CsvReport $report, string $filename = 'test'): string
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

    public function test_download_with_empty_result_set()
    {
        $report = $this->makeReport([]);

        $output = $this->captureCSVOutput($report);
        $lines = $this->parseCSVLines($output);

        // Should only have header row, no data
        $this->assertCount(1, $lines);
        $header = str_getcsv($lines[0]);
        $this->assertEquals(['No', 'Name', 'Amount'], $header);
    }

    public function test_download_without_manipulation_data_count_equals_columns()
    {
        // Data has exactly same number of fields as columns — no array_pop needed
        $resultObjects = [
            $this->makeResultObject(['name' => 'Alice', 'amount' => 100]),
        ];

        $query = \Mockery::mock('Illuminate\Database\Query\Builder');
        $query->shouldReceive('take')->andReturnSelf();
        $query->shouldReceive('when')->andReturnSelf();
        $query->shouldReceive('cursor')->andReturn(new \ArrayIterator($resultObjects));

        $report = new CsvReport;
        $report->of('Test', [], $query, ['Name' => 'name', 'Amount' => 'amount']);
        $report->withoutManipulation();

        $output = $this->captureCSVOutput($report);

        // Both fields should be present (not popped)
        $this->assertStringContainsString('Alice', $output);
        $this->assertStringContainsString('100', $output);
    }

    public function test_download_without_manipulation_and_show_num_column_false()
    {
        $report = $this->makeReport([
            ['name' => 'Alice', 'amount' => 100],
        ]);
        $report->withoutManipulation();
        $report->showNumColumn(false);

        $output = $this->captureCSVOutput($report);
        $lines = $this->parseCSVLines($output);

        // Header should not have 'No' (withoutManipulation skips num column insertion)
        $header = str_getcsv($lines[0]);
        $this->assertNotContains('No', $header);
        $this->assertEquals(['Name', 'Amount'], $header);
    }

    public function test_download_with_edit_column_class_only_no_display_as()
    {
        $report = $this->makeReport([
            ['name' => 'Alice', 'amount' => 100],
        ]);
        // Only 'class' set, no 'displayAs' — original value should be used
        $report->editColumn('Amount', ['class' => 'right']);

        $output = $this->captureCSVOutput($report);

        // Original value should be displayed, not modified
        $this->assertStringContainsString('100', $output);
        $this->assertStringNotContainsString('right', $output); // CSS class not in CSV
    }

    public function test_download_with_special_characters_in_data()
    {
        $report = $this->makeReport([
            ['name' => 'Alice, Bob', 'amount' => 100],
            ['name' => '"Charlie"', 'amount' => 200],
            ['name' => "Eve\nFrank", 'amount' => 300],
        ]);

        $output = $this->captureCSVOutput($report);

        // CSV should properly escape commas and quotes
        $this->assertStringContainsString('"Alice, Bob"', $output);
        $this->assertStringContainsString('Charlie', $output);
    }

    public function test_download_with_closure_column_and_edit_column_on_same_column()
    {
        $resultObjects = [
            $this->makeResultObject(['name' => 'alice', 'amount' => 100]),
        ];

        $query = \Mockery::mock('Illuminate\Database\Query\Builder');
        $query->shouldReceive('take')->andReturnSelf();
        $query->shouldReceive('when')->andReturnSelf();
        $query->shouldReceive('cursor')->andReturn(new \ArrayIterator($resultObjects));

        $report = new CsvReport;
        $report->of('Test', [], $query, [
            'Name' => function ($result) {
                return strtoupper($result->name);
            },
            'Amount' => 'amount',
        ]);
        // editColumn displayAs overrides the closure output
        $report->editColumn('Name', [
            'displayAs' => function ($result) {
                return 'Mr. '.$result->name;
            },
        ]);

        $output = $this->captureCSVOutput($report);

        // displayAs closure takes precedence
        $this->assertStringContainsString('Mr. alice', $output);
        $this->assertStringNotContainsString('ALICE', $output);
    }

    public function test_download_with_multiple_meta_items()
    {
        $resultObjects = [
            $this->makeResultObject(['name' => 'Alice', 'amount' => 100]),
        ];

        $query = \Mockery::mock('Illuminate\Database\Query\Builder');
        $query->shouldReceive('take')->andReturnSelf();
        $query->shouldReceive('when')->andReturnSelf();
        $query->shouldReceive('cursor')->andReturn(new \ArrayIterator($resultObjects));

        $report = new CsvReport;
        $report->of('Report', [
            'Period' => 'Q1',
            'Company' => 'Acme',
            'Department' => 'Sales',
        ], $query, ['Name' => 'name', 'Amount' => 'amount']);
        $report->showMeta(true);

        $output = $this->captureCSVOutput($report);

        $this->assertStringContainsString('Period', $output);
        $this->assertStringContainsString('Q1', $output);
        $this->assertStringContainsString('Company', $output);
        $this->assertStringContainsString('Acme', $output);
        $this->assertStringContainsString('Department', $output);
        $this->assertStringContainsString('Sales', $output);
    }

    public function test_download_with_meta_and_no_header()
    {
        $report = $this->makeReport([
            ['name' => 'Alice', 'amount' => 100],
        ]);
        $report->showMeta(true);
        $report->showHeader(false);

        $output = $this->captureCSVOutput($report);
        $lines = $this->parseCSVLines($output);

        // Should have meta rows, space, then data (no column header row)
        $this->assertStringContainsString('Period', $lines[0]);
        // Data row should exist without header
        $foundAlice = false;
        foreach ($lines as $line) {
            if (str_contains($line, 'Alice')) {
                $foundAlice = true;
            }
        }
        $this->assertTrue($foundAlice);
    }

    public function test_download_empty_result_set_without_header()
    {
        $report = $this->makeReport([]);
        $report->showHeader(false);

        $output = $this->captureCSVOutput($report);

        // Output should be essentially empty (no header, no data)
        $this->assertEquals('', trim($output));
    }

    public function test_download_empty_result_set_with_meta()
    {
        $report = $this->makeReport([]);
        $report->showMeta(true);

        $output = $this->captureCSVOutput($report);
        $lines = $this->parseCSVLines($output);

        // Should have meta rows + header, but no data rows
        $this->assertStringContainsString('Period', $output);
        $this->assertStringContainsString('January', $output);
    }

    public function test_download_row_numbering_increments_correctly()
    {
        $report = $this->makeReport([
            ['name' => 'Alice', 'amount' => 100],
            ['name' => 'Bob', 'amount' => 200],
            ['name' => 'Charlie', 'amount' => 300],
        ]);

        $output = $this->captureCSVOutput($report);
        $lines = $this->parseCSVLines($output);

        // Verify row numbers 1, 2, 3
        $row1 = str_getcsv($lines[1]);
        $this->assertEquals('1', $row1[0]);

        $row2 = str_getcsv($lines[2]);
        $this->assertEquals('2', $row2[0]);

        $row3 = str_getcsv($lines[3]);
        $this->assertEquals('3', $row3[0]);
    }

    public function test_download_without_manipulation_with_fewer_data_fields()
    {
        // Data has fewer fields than columns — no pop, data passed as-is
        $resultObjects = [
            $this->makeResultObject(['name' => 'Alice']),
        ];

        $query = \Mockery::mock('Illuminate\Database\Query\Builder');
        $query->shouldReceive('take')->andReturnSelf();
        $query->shouldReceive('when')->andReturnSelf();
        $query->shouldReceive('cursor')->andReturn(new \ArrayIterator($resultObjects));

        $report = new CsvReport;
        $report->of('Test', [], $query, ['Name' => 'name', 'Amount' => 'amount', 'Extra' => 'extra']);
        $report->withoutManipulation();

        $output = $this->captureCSVOutput($report);

        $this->assertStringContainsString('Alice', $output);
    }
}
