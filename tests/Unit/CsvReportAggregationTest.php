<?php

declare(strict_types=1);

namespace SamuelTerra22\ReportGenerator\Tests\Unit;

use Mockery;
use SamuelTerra22\ReportGenerator\ReportMedia\CsvReport;
use SamuelTerra22\ReportGenerator\Tests\TestCase;

class CsvReportAggregationTest extends TestCase
{
    private function makeQueryWithResults(array $results): \Mockery\MockInterface
    {
        $resultObjects = array_map(fn ($row) => $this->makeResultObject($row), $results);

        $query = Mockery::mock('Illuminate\Database\Query\Builder');
        $query->shouldReceive('take')->andReturnSelf();
        $query->shouldReceive('cursor')->andReturn(new \ArrayIterator($resultObjects));

        return $query;
    }

    public function test_sum_total_row_in_csv()
    {
        $query = $this->makeQueryWithResults([
            ['name' => 'A', 'amount' => 100],
            ['name' => 'B', 'amount' => 200],
        ]);

        $report = new CsvReport;
        $report->of('Test', [], $query, ['Name' => 'name', 'Amount' => 'amount'])
            ->showTotal(['Amount' => 'sum']);

        ob_start();
        $report->download('test');
        $output = ob_get_clean();

        $this->assertStringContainsString('Grand Total', $output);
        $this->assertStringContainsString('SUM 300.00', $output);
    }

    public function test_avg_total_row_in_csv()
    {
        $query = $this->makeQueryWithResults([
            ['name' => 'A', 'amount' => 100],
            ['name' => 'B', 'amount' => 200],
        ]);

        $report = new CsvReport;
        $report->of('Test', [], $query, ['Name' => 'name', 'Amount' => 'amount'])
            ->showTotal(['Amount' => 'avg']);

        ob_start();
        $report->download('test');
        $output = ob_get_clean();

        $this->assertStringContainsString('AVG 150.00', $output);
    }

    public function test_count_total_row_in_csv()
    {
        $query = $this->makeQueryWithResults([
            ['name' => 'A', 'orders' => 5],
            ['name' => 'B', 'orders' => 3],
        ]);

        $report = new CsvReport;
        $report->of('Test', [], $query, ['Name' => 'name', 'Orders' => 'orders'])
            ->showTotal(['Orders' => 'count']);

        ob_start();
        $report->download('test');
        $output = ob_get_clean();

        $this->assertStringContainsString('COUNT 2', $output);
    }

    public function test_point_total_row_in_csv()
    {
        $query = $this->makeQueryWithResults([
            ['name' => 'A', 'balance' => 100],
            ['name' => 'B', 'balance' => 200],
        ]);

        $report = new CsvReport;
        $report->of('Test', [], $query, ['Name' => 'name', 'Balance' => 'balance'])
            ->showTotal(['Balance' => 'point']);

        ob_start();
        $report->download('test');
        $output = ob_get_clean();

        $this->assertStringContainsString('300.00', $output);
        $this->assertStringNotContainsString('POINT', $output);
    }
}
