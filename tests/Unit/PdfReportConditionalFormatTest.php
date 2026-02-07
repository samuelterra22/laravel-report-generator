<?php

declare(strict_types=1);

namespace SamuelTerra22\ReportGenerator\Tests\Unit;

use Mockery;
use SamuelTerra22\ReportGenerator\ReportMedia\PdfReport;
use SamuelTerra22\ReportGenerator\Tests\TestCase;

class PdfReportConditionalFormatTest extends TestCase
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

    public function test_conditional_format_applies_css_class()
    {
        $query = $this->makeQueryWithResults([
            ['name' => 'Alice', 'amount' => 1500],
        ]);

        $report = new PdfReport;
        $report->of('Test', [], $query, ['Name' => 'name', 'Amount' => 'amount'])
            ->conditionalFormat('Amount', fn ($value, $row) => $value > 1000, ['class' => 'bold']);

        $pdfMock = Mockery::mock();
        $pdfMock->shouldReceive('setOption')->andReturnSelf();
        $pdfMock->shouldReceive('loadHTML')->once()->with(Mockery::on(function ($html) {
            return str_contains($html, 'bold');
        }))->andReturnSelf();
        $pdfMock->shouldReceive('setPaper')->once()->andReturnSelf();

        $this->app->instance('snappy.pdf.wrapper', $pdfMock);

        $report->make();
    }

    public function test_conditional_format_applies_inline_styles()
    {
        $query = $this->makeQueryWithResults([
            ['name' => 'Bob', 'amount' => -50],
        ]);

        $report = new PdfReport;
        $report->of('Test', [], $query, ['Name' => 'name', 'Amount' => 'amount'])
            ->conditionalFormat('Amount', fn ($value, $row) => $value < 0, ['color' => '#ff0000']);

        $pdfMock = Mockery::mock();
        $pdfMock->shouldReceive('setOption')->andReturnSelf();
        $pdfMock->shouldReceive('loadHTML')->once()->with(Mockery::on(function ($html) {
            return str_contains($html, 'color:#ff0000');
        }))->andReturnSelf();
        $pdfMock->shouldReceive('setPaper')->once()->andReturnSelf();

        $this->app->instance('snappy.pdf.wrapper', $pdfMock);

        $report->make();
    }

    public function test_conditional_format_not_applied_when_condition_false()
    {
        $query = $this->makeQueryWithResults([
            ['name' => 'Charlie', 'amount' => 50],
        ]);

        $report = new PdfReport;
        $report->of('Test', [], $query, ['Name' => 'name', 'Amount' => 'amount'])
            ->conditionalFormat('Amount', fn ($value, $row) => $value > 1000, ['color' => '#ff0000']);

        $pdfMock = Mockery::mock();
        $pdfMock->shouldReceive('setOption')->andReturnSelf();
        $pdfMock->shouldReceive('loadHTML')->once()->with(Mockery::on(function ($html) {
            return ! str_contains($html, 'color:#ff0000');
        }))->andReturnSelf();
        $pdfMock->shouldReceive('setPaper')->once()->andReturnSelf();

        $this->app->instance('snappy.pdf.wrapper', $pdfMock);

        $report->make();
    }

    public function test_conditional_format_receives_row_object()
    {
        $query = $this->makeQueryWithResults([
            ['name' => 'Dave', 'amount' => 500, 'status' => 'overdue'],
        ]);

        $report = new PdfReport;
        $report->of('Test', [], $query, ['Name' => 'name', 'Amount' => 'amount'])
            ->conditionalFormat('Amount', fn ($value, $row) => $row->status === 'overdue', ['background' => '#ffcccc']);

        $pdfMock = Mockery::mock();
        $pdfMock->shouldReceive('setOption')->andReturnSelf();
        $pdfMock->shouldReceive('loadHTML')->once()->with(Mockery::on(function ($html) {
            return str_contains($html, 'background:#ffcccc');
        }))->andReturnSelf();
        $pdfMock->shouldReceive('setPaper')->once()->andReturnSelf();

        $this->app->instance('snappy.pdf.wrapper', $pdfMock);

        $report->make();
    }
}
