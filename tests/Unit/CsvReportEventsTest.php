<?php

declare(strict_types=1);

namespace SamuelTerra22\ReportGenerator\Tests\Unit;

use Mockery;
use SamuelTerra22\ReportGenerator\ReportMedia\CsvReport;
use SamuelTerra22\ReportGenerator\Tests\TestCase;

class CsvReportEventsTest extends TestCase
{
    private function makeQueryWithResults(array $results): \Mockery\MockInterface
    {
        $resultObjects = array_map(fn ($row) => $this->makeResultObject($row), $results);

        $query = Mockery::mock('Illuminate\Database\Query\Builder');
        $query->shouldReceive('take')->andReturnSelf();
        $query->shouldReceive('cursor')->andReturn(new \ArrayIterator($resultObjects));

        return $query;
    }

    public function test_on_before_render_fires_in_csv()
    {
        $query = $this->makeQueryWithResults([['name' => 'A', 'amount' => 100]]);
        $fired = false;

        $report = new CsvReport;
        $report->of('Test', [], $query, ['Name' => 'name', 'Amount' => 'amount'])
            ->onBeforeRender(function () use (&$fired) {
                $fired = true;
            });

        ob_start();
        $report->download('test');
        ob_get_clean();

        $this->assertTrue($fired);
    }

    public function test_on_row_fires_for_each_row_in_csv()
    {
        $query = $this->makeQueryWithResults([
            ['name' => 'A', 'amount' => 100],
            ['name' => 'B', 'amount' => 200],
        ]);

        $rowCount = 0;
        $report = new CsvReport;
        $report->of('Test', [], $query, ['Name' => 'name', 'Amount' => 'amount'])
            ->onRow(function ($row, $index) use (&$rowCount) {
                $rowCount++;
            });

        ob_start();
        $report->download('test');
        ob_get_clean();

        $this->assertEquals(2, $rowCount);
    }

    public function test_on_after_render_fires_in_csv()
    {
        $query = $this->makeQueryWithResults([['name' => 'A', 'amount' => 100]]);
        $fired = false;

        $report = new CsvReport;
        $report->of('Test', [], $query, ['Name' => 'name', 'Amount' => 'amount'])
            ->onAfterRender(function () use (&$fired) {
                $fired = true;
            });

        ob_start();
        $report->download('test');
        ob_get_clean();

        $this->assertTrue($fired);
    }

    public function test_on_complete_fires_in_csv()
    {
        $query = $this->makeQueryWithResults([['name' => 'A', 'amount' => 100]]);
        $fired = false;

        $report = new CsvReport;
        $report->of('Test', [], $query, ['Name' => 'name', 'Amount' => 'amount'])
            ->onComplete(function () use (&$fired) {
                $fired = true;
            });

        ob_start();
        $report->download('test');
        ob_get_clean();

        $this->assertTrue($fired);
    }

    public function test_event_order_in_csv()
    {
        $query = $this->makeQueryWithResults([['name' => 'A', 'amount' => 100]]);
        $order = [];

        $report = new CsvReport;
        $report->of('Test', [], $query, ['Name' => 'name', 'Amount' => 'amount'])
            ->onBeforeRender(function () use (&$order) {
                $order[] = 'before';
            })
            ->onRow(function () use (&$order) {
                $order[] = 'row';
            })
            ->onAfterRender(function () use (&$order) {
                $order[] = 'after';
            })
            ->onComplete(function () use (&$order) {
                $order[] = 'complete';
            });

        ob_start();
        $report->download('test');
        ob_get_clean();

        $this->assertEquals(['before', 'row', 'after', 'complete'], $order);
    }
}
