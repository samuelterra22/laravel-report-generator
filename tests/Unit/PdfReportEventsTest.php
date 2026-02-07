<?php

declare(strict_types=1);

namespace SamuelTerra22\ReportGenerator\Tests\Unit;

use Mockery;
use SamuelTerra22\ReportGenerator\ReportMedia\PdfReport;
use SamuelTerra22\ReportGenerator\Tests\TestCase;

class PdfReportEventsTest extends TestCase
{
    private function makeQueryWithResults(array $results): \Mockery\MockInterface
    {
        $resultObjects = array_map(fn ($row) => $this->makeResultObject($row), $results);

        $query = Mockery::mock('Illuminate\Database\Query\Builder');
        $query->shouldReceive('take')->andReturnSelf();
        $query->shouldReceive('when')->andReturnUsing(function ($condition, $callback) use ($query) {
            if ($condition) {
                $callback($query);
            }

            return $query;
        });
        $query->shouldReceive('cursor')->andReturn(new \ArrayIterator($resultObjects));

        return $query;
    }

    private function makePdfMock(): Mockery\MockInterface
    {
        $pdfMock = Mockery::mock();
        $pdfMock->shouldReceive('setOption')->andReturnSelf();
        $pdfMock->shouldReceive('loadHTML')->andReturnSelf();
        $pdfMock->shouldReceive('setPaper')->andReturnSelf();

        return $pdfMock;
    }

    public function test_on_before_render_fires()
    {
        $query = $this->makeQueryWithResults([['name' => 'A', 'amount' => 100]]);
        $fired = false;

        $report = new PdfReport;
        $report->of('Test', [], $query, ['Name' => 'name', 'Amount' => 'amount'])
            ->onBeforeRender(function () use (&$fired) {
                $fired = true;
            });

        $this->app->instance('snappy.pdf.wrapper', $this->makePdfMock());
        $report->make();

        $this->assertTrue($fired);
    }

    public function test_on_after_render_fires()
    {
        $query = $this->makeQueryWithResults([['name' => 'A', 'amount' => 100]]);
        $fired = false;

        $report = new PdfReport;
        $report->of('Test', [], $query, ['Name' => 'name', 'Amount' => 'amount'])
            ->onAfterRender(function () use (&$fired) {
                $fired = true;
            });

        $this->app->instance('snappy.pdf.wrapper', $this->makePdfMock());
        $report->make();

        $this->assertTrue($fired);
    }

    public function test_on_complete_fires()
    {
        $query = $this->makeQueryWithResults([['name' => 'A', 'amount' => 100]]);
        $fired = false;

        $report = new PdfReport;
        $report->of('Test', [], $query, ['Name' => 'name', 'Amount' => 'amount'])
            ->onComplete(function () use (&$fired) {
                $fired = true;
            });

        $this->app->instance('snappy.pdf.wrapper', $this->makePdfMock());
        $report->make();

        $this->assertTrue($fired);
    }

    public function test_on_row_fires_for_each_row()
    {
        $query = $this->makeQueryWithResults([
            ['name' => 'A', 'amount' => 100],
            ['name' => 'B', 'amount' => 200],
        ]);

        $rowData = [];
        $report = new PdfReport;
        $report->of('Test', [], $query, ['Name' => 'name', 'Amount' => 'amount'])
            ->onRow(function ($row, $index) use (&$rowData) {
                $rowData[] = ['name' => $row->name, 'index' => $index];
            });

        $this->app->instance('snappy.pdf.wrapper', $this->makePdfMock());
        $report->make();

        $this->assertCount(2, $rowData);
        $this->assertEquals('A', $rowData[0]['name']);
        $this->assertEquals(0, $rowData[0]['index']);
        $this->assertEquals('B', $rowData[1]['name']);
        $this->assertEquals(1, $rowData[1]['index']);
    }

    public function test_event_order()
    {
        $query = $this->makeQueryWithResults([['name' => 'A', 'amount' => 100]]);
        $order = [];

        $report = new PdfReport;
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

        $this->app->instance('snappy.pdf.wrapper', $this->makePdfMock());
        $report->make();

        $this->assertEquals(['before', 'row', 'after', 'complete'], $order);
    }

    public function test_multiple_callbacks_for_same_event()
    {
        $query = $this->makeQueryWithResults([['name' => 'A', 'amount' => 100]]);
        $count = 0;

        $report = new PdfReport;
        $report->of('Test', [], $query, ['Name' => 'name', 'Amount' => 'amount'])
            ->onBeforeRender(function () use (&$count) {
                $count++;
            })
            ->onBeforeRender(function () use (&$count) {
                $count++;
            });

        $this->app->instance('snappy.pdf.wrapper', $this->makePdfMock());
        $report->make();

        $this->assertEquals(2, $count);
    }
}
