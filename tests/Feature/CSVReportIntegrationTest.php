<?php

namespace SamuelTerra22\ReportGenerator\Tests\Feature;

use Mockery;
use SamuelTerra22\ReportGenerator\ReportMedia\CSVReport;
use SamuelTerra22\ReportGenerator\Tests\TestCase;

class CSVReportIntegrationTest extends TestCase
{
    private function captureCSVOutput(CSVReport $report, string $filename = 'test'): string
    {
        ob_start();
        $report->download($filename);
        return ob_get_clean();
    }

    private function makeQuery(array $resultObjects): \Mockery\MockInterface
    {
        $query = Mockery::mock('Illuminate\Database\Query\Builder');
        $query->shouldReceive('take')->andReturnSelf();
        $query->shouldReceive('when')->andReturnSelf();
        $query->shouldReceive('cursor')->andReturn(new \ArrayIterator($resultObjects));
        return $query;
    }

    private function parseCSVLines(string $output): array
    {
        $lines = explode("\n", trim($output));
        return array_values(array_filter($lines, function ($line) {
            return trim($line) !== '';
        }));
    }

    public function test_full_csv_generation_with_multiple_rows()
    {
        $results = [
            $this->makeResultObject(['name' => 'Alice', 'amount' => 100]),
            $this->makeResultObject(['name' => 'Bob', 'amount' => 200]),
            $this->makeResultObject(['name' => 'Charlie', 'amount' => 300]),
        ];

        $query = $this->makeQuery($results);

        $report = new CSVReport();
        $report->of('Sales Report', ['Period' => 'Q1'], $query, ['Name' => 'name', 'Amount' => 'amount']);

        $output = $this->captureCSVOutput($report);
        $lines = $this->parseCSVLines($output);

        $this->assertCount(4, $lines); // header + 3 data rows

        $header = str_getcsv($lines[0]);
        $this->assertEquals(['No', 'Name', 'Amount'], $header);

        $row1 = str_getcsv($lines[1]);
        $this->assertEquals('1', $row1[0]);
        $this->assertEquals('Alice', $row1[1]);
        $this->assertEquals('100', $row1[2]);

        $row3 = str_getcsv($lines[3]);
        $this->assertEquals('3', $row3[0]);
        $this->assertEquals('Charlie', $row3[1]);
    }

    public function test_csv_with_closure_columns_and_edit_columns()
    {
        $results = [
            $this->makeResultObject(['first' => 'Alice', 'last' => 'Smith', 'amount' => 100]),
        ];

        $query = $this->makeQuery($results);

        $report = new CSVReport();
        $report->of('Report', [], $query, [
            'Full Name' => function ($r) { return $r->first . ' ' . $r->last; },
            'Amount' => 'amount',
        ]);
        $report->editColumn('Amount', [
            'displayAs' => function ($r) { return '$' . number_format($r->amount, 2); }
        ]);

        $output = $this->captureCSVOutput($report);

        $this->assertStringContainsString('Alice Smith', $output);
        $this->assertStringContainsString('$100.00', $output);
    }

    public function test_csv_without_manipulation_mode()
    {
        $results = [
            $this->makeResultObject(['name' => 'Alice', 'amount' => 100]),
            $this->makeResultObject(['name' => 'Bob', 'amount' => 200]),
        ];

        $query = $this->makeQuery($results);

        $report = new CSVReport();
        $report->of('Report', [], $query, ['Name' => 'name', 'Amount' => 'amount']);
        $report->withoutManipulation();

        $output = $this->captureCSVOutput($report);
        $lines = $this->parseCSVLines($output);

        // Header should not have 'No' in withoutManipulation mode
        $header = str_getcsv($lines[0]);
        $this->assertEquals(['Name', 'Amount'], $header);

        $row1 = str_getcsv($lines[1]);
        $this->assertEquals('Alice', $row1[0]);
        $this->assertEquals('100', $row1[1]);
    }

    public function test_csv_with_meta_enabled()
    {
        $results = [
            $this->makeResultObject(['name' => 'Alice', 'amount' => 100]),
        ];

        $query = $this->makeQuery($results);

        $report = new CSVReport();
        $report->of('Report', ['Company' => 'Acme', 'Period' => 'Q1'], $query, ['Name' => 'name', 'Amount' => 'amount']);
        $report->showMeta(true);

        $output = $this->captureCSVOutput($report);
        $lines = $this->parseCSVLines($output);

        // First lines should be meta
        $meta1 = str_getcsv($lines[0]);
        $this->assertEquals('Company', $meta1[0]);
        $this->assertEquals('Acme', $meta1[1]);
    }

    public function test_csv_respects_limit()
    {
        $results = [
            $this->makeResultObject(['name' => 'Alice', 'amount' => 100]),
            $this->makeResultObject(['name' => 'Bob', 'amount' => 200]),
            $this->makeResultObject(['name' => 'Charlie', 'amount' => 300]),
        ];

        $query = $this->makeQuery($results);

        $report = new CSVReport();
        $report->of('Report', [], $query, ['Name' => 'name', 'Amount' => 'amount']);
        $report->limit(2);

        $output = $this->captureCSVOutput($report);

        $this->assertStringContainsString('Alice', $output);
        $this->assertStringContainsString('Bob', $output);
    }
}
