<?php

declare(strict_types=1);

namespace SamuelTerra22\ReportGenerator\Tests\Unit;

use Mockery;
use SamuelTerra22\ReportGenerator\ReportMedia\PdfReport;
use SamuelTerra22\ReportGenerator\Tests\TestCase;

class PdfReportFormatColumnTest extends TestCase
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

    public function test_format_column_applied_in_pdf_output()
    {
        $query = $this->makeQueryWithResults([
            ['name' => 'Alice', 'amount' => 1234.5],
        ]);

        $report = new PdfReport;
        $report->of('Test', ['Period' => 'Jan'], $query, ['Name' => 'name', 'Amount' => 'amount'])
            ->formatColumn('Amount', 'currency', ['prefix' => '$']);

        $pdfMock = Mockery::mock();
        $pdfMock->shouldReceive('setOption')->andReturnSelf();
        $pdfMock->shouldReceive('loadHTML')->once()->with(Mockery::on(function ($html) {
            return str_contains($html, '$1,234.50');
        }))->andReturnSelf();
        $pdfMock->shouldReceive('setPaper')->once()->andReturnSelf();

        $this->app->instance('snappy.pdf.wrapper', $pdfMock);

        $report->make();
    }

    public function test_edit_column_display_as_takes_priority_over_format()
    {
        $query = $this->makeQueryWithResults([
            ['name' => 'Bob', 'amount' => 500],
        ]);

        $report = new PdfReport;
        $report->of('Test', ['Period' => 'Jan'], $query, ['Name' => 'name', 'Amount' => 'amount'])
            ->editColumn('Amount', ['displayAs' => fn ($r) => 'CUSTOM:'.$r->amount])
            ->formatColumn('Amount', 'currency');

        $pdfMock = Mockery::mock();
        $pdfMock->shouldReceive('setOption')->andReturnSelf();
        $pdfMock->shouldReceive('loadHTML')->once()->with(Mockery::on(function ($html) {
            return str_contains($html, 'CUSTOM:500') && ! str_contains($html, '$500');
        }))->andReturnSelf();
        $pdfMock->shouldReceive('setPaper')->once()->andReturnSelf();

        $this->app->instance('snappy.pdf.wrapper', $pdfMock);

        $report->make();
    }

    public function test_format_column_date_in_pdf()
    {
        $query = $this->makeQueryWithResults([
            ['name' => 'Charlie', 'created_at' => '2024-01-15'],
        ]);

        $report = new PdfReport;
        $report->of('Test', ['Period' => 'Jan'], $query, ['Name' => 'name', 'Date' => 'created_at'])
            ->formatColumn('Date', 'date', ['format' => 'd/m/Y']);

        $pdfMock = Mockery::mock();
        $pdfMock->shouldReceive('setOption')->andReturnSelf();
        $pdfMock->shouldReceive('loadHTML')->once()->with(Mockery::on(function ($html) {
            return str_contains($html, '15/01/2024');
        }))->andReturnSelf();
        $pdfMock->shouldReceive('setPaper')->once()->andReturnSelf();

        $this->app->instance('snappy.pdf.wrapper', $pdfMock);

        $report->make();
    }

    public function test_format_column_boolean_in_pdf()
    {
        $query = $this->makeQueryWithResults([
            ['name' => 'Alice', 'active' => 1],
            ['name' => 'Bob', 'active' => 0],
        ]);

        $report = new PdfReport;
        $report->of('Test', [], $query, ['Name' => 'name', 'Status' => 'active'])
            ->formatColumn('Status', 'boolean', ['true' => 'Active', 'false' => 'Inactive']);

        $pdfMock = Mockery::mock();
        $pdfMock->shouldReceive('setOption')->andReturnSelf();
        $pdfMock->shouldReceive('loadHTML')->once()->with(Mockery::on(function ($html) {
            return str_contains($html, 'Active') && str_contains($html, 'Inactive');
        }))->andReturnSelf();
        $pdfMock->shouldReceive('setPaper')->once()->andReturnSelf();

        $this->app->instance('snappy.pdf.wrapper', $pdfMock);

        $report->make();
    }
}
