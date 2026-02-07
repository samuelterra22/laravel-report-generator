<?php

declare(strict_types=1);

namespace SamuelTerra22\ReportGenerator\Tests\Unit;

use Mockery;
use SamuelTerra22\ReportGenerator\ReportMedia\PdfReport;
use SamuelTerra22\ReportGenerator\Tests\TestCase;

class PdfReportAggregationTest extends TestCase
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

    public function test_sum_aggregation_in_pdf()
    {
        $query = $this->makeQueryWithResults([
            ['name' => 'A', 'amount' => 100],
            ['name' => 'B', 'amount' => 200],
        ]);

        $report = new PdfReport;
        $report->of('Test', [], $query, ['Name' => 'name', 'Amount' => 'amount'])
            ->showTotal(['Amount' => 'sum']);

        $pdfMock = Mockery::mock();
        $pdfMock->shouldReceive('setOption')->andReturnSelf();
        $pdfMock->shouldReceive('loadHTML')->once()->with(Mockery::on(function ($html) {
            return str_contains($html, 'SUM 300.00') && str_contains($html, 'Grand Total');
        }))->andReturnSelf();
        $pdfMock->shouldReceive('setPaper')->once()->andReturnSelf();

        $this->app->instance('snappy.pdf.wrapper', $pdfMock);

        $report->make();
    }

    public function test_avg_aggregation_in_pdf()
    {
        $query = $this->makeQueryWithResults([
            ['name' => 'A', 'amount' => 100],
            ['name' => 'B', 'amount' => 200],
        ]);

        $report = new PdfReport;
        $report->of('Test', [], $query, ['Name' => 'name', 'Amount' => 'amount'])
            ->showTotal(['Amount' => 'avg']);

        $pdfMock = Mockery::mock();
        $pdfMock->shouldReceive('setOption')->andReturnSelf();
        $pdfMock->shouldReceive('loadHTML')->once()->with(Mockery::on(function ($html) {
            return str_contains($html, 'AVG 150.00');
        }))->andReturnSelf();
        $pdfMock->shouldReceive('setPaper')->once()->andReturnSelf();

        $this->app->instance('snappy.pdf.wrapper', $pdfMock);

        $report->make();
    }

    public function test_min_max_aggregation_in_pdf()
    {
        $query = $this->makeQueryWithResults([
            ['name' => 'A', 'amount' => 100],
            ['name' => 'B', 'amount' => 300],
            ['name' => 'C', 'amount' => 200],
        ]);

        $report = new PdfReport;
        $report->of('Test', [], $query, ['Name' => 'name', 'Amount' => 'amount'])
            ->showTotal(['Amount' => 'min']);

        $pdfMock = Mockery::mock();
        $pdfMock->shouldReceive('setOption')->andReturnSelf();
        $pdfMock->shouldReceive('loadHTML')->once()->with(Mockery::on(function ($html) {
            return str_contains($html, 'MIN 100.00');
        }))->andReturnSelf();
        $pdfMock->shouldReceive('setPaper')->once()->andReturnSelf();

        $this->app->instance('snappy.pdf.wrapper', $pdfMock);

        $report->make();
    }

    public function test_count_aggregation_in_pdf()
    {
        $query = $this->makeQueryWithResults([
            ['name' => 'A', 'orders' => 5],
            ['name' => 'B', 'orders' => 3],
            ['name' => 'C', 'orders' => 7],
        ]);

        $report = new PdfReport;
        $report->of('Test', [], $query, ['Name' => 'name', 'Orders' => 'orders'])
            ->showTotal(['Orders' => 'count']);

        $pdfMock = Mockery::mock();
        $pdfMock->shouldReceive('setOption')->andReturnSelf();
        $pdfMock->shouldReceive('loadHTML')->once()->with(Mockery::on(function ($html) {
            return str_contains($html, 'COUNT 3');
        }))->andReturnSelf();
        $pdfMock->shouldReceive('setPaper')->once()->andReturnSelf();

        $this->app->instance('snappy.pdf.wrapper', $pdfMock);

        $report->make();
    }

    public function test_point_aggregation_backward_compatible()
    {
        $query = $this->makeQueryWithResults([
            ['name' => 'A', 'balance' => 100],
            ['name' => 'B', 'balance' => 200],
        ]);

        $report = new PdfReport;
        $report->of('Test', [], $query, ['Name' => 'name', 'Balance' => 'balance'])
            ->showTotal(['Balance' => 'point']);

        $pdfMock = Mockery::mock();
        $pdfMock->shouldReceive('setOption')->andReturnSelf();
        $pdfMock->shouldReceive('loadHTML')->once()->with(Mockery::on(function ($html) {
            // point type should show just the number, no label prefix
            return str_contains($html, '300.00') && ! str_contains($html, 'POINT');
        }))->andReturnSelf();
        $pdfMock->shouldReceive('setPaper')->once()->andReturnSelf();

        $this->app->instance('snappy.pdf.wrapper', $pdfMock);

        $report->make();
    }
}
