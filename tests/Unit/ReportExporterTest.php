<?php

declare(strict_types=1);

namespace SamuelTerra22\ReportGenerator\Tests\Unit;

use Mockery;
use SamuelTerra22\ReportGenerator\ReportExporter;
use SamuelTerra22\ReportGenerator\ReportMedia\CsvReport;
use SamuelTerra22\ReportGenerator\ReportMedia\ExcelReport;
use SamuelTerra22\ReportGenerator\ReportMedia\PdfReport;
use SamuelTerra22\ReportGenerator\Tests\TestCase;

class ReportExporterTest extends TestCase
{
    private function makeQueryWithResults(array $results = []): \Mockery\MockInterface
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

    public function test_of_returns_self()
    {
        $exporter = new ReportExporter;
        $query = $this->makeQueryWithResults();
        $result = $exporter->of('Test', [], $query, ['Name' => 'name']);

        $this->assertSame($exporter, $result);
    }

    public function test_to_pdf_returns_pdf_report()
    {
        $exporter = new ReportExporter;
        $query = $this->makeQueryWithResults();
        $exporter->of('Test', ['Period' => 'Jan'], $query, ['Name' => 'name']);

        $pdf = $exporter->toPdf();
        $this->assertInstanceOf(PdfReport::class, $pdf);
    }

    public function test_to_excel_returns_excel_report()
    {
        $exporter = new ReportExporter;
        $query = $this->makeQueryWithResults();
        $exporter->of('Test', [], $query, ['Name' => 'name']);

        $excel = $exporter->toExcel();
        $this->assertInstanceOf(ExcelReport::class, $excel);
    }

    public function test_to_csv_returns_csv_report()
    {
        $exporter = new ReportExporter;
        $query = $this->makeQueryWithResults();
        $exporter->of('Test', [], $query, ['Name' => 'name']);

        $csv = $exporter->toCsv();
        $this->assertInstanceOf(CsvReport::class, $csv);
    }

    public function test_builder_state_transferred_to_pdf()
    {
        $query = $this->makeQueryWithResults([
            ['name' => 'Alice', 'amount' => 100],
        ]);

        $exporter = new ReportExporter;
        $exporter->of('Sales Report', ['Period' => 'Jan'], $query, ['Name' => 'name', 'Amount' => 'amount'])
            ->formatColumn('Amount', 'currency', ['prefix' => '$'])
            ->showTotal(['Amount' => 'sum'])
            ->setPaper('letter')
            ->setOrientation('landscape')
            ->limit(50);

        $pdf = $exporter->toPdf();

        $pdfMock = Mockery::mock();
        $pdfMock->shouldReceive('setOption')->andReturnSelf();
        $pdfMock->shouldReceive('loadHTML')->once()->with(Mockery::on(function ($html) {
            return str_contains($html, 'Sales Report')
                && str_contains($html, '$100.00');
        }))->andReturnSelf();
        $pdfMock->shouldReceive('setPaper')->with('letter', 'landscape')->once()->andReturnSelf();

        $this->app->instance('snappy.pdf.wrapper', $pdfMock);

        $pdf->make();
    }

    public function test_edit_column_transferred()
    {
        $query = $this->makeQueryWithResults([['name' => 'Bob', 'amount' => 500]]);

        $exporter = new ReportExporter;
        $exporter->of('Test', [], $query, ['Name' => 'name', 'Amount' => 'amount'])
            ->editColumn('Amount', ['displayAs' => fn ($r) => 'CUSTOM:'.$r->amount]);

        $pdf = $exporter->toPdf();

        $pdfMock = Mockery::mock();
        $pdfMock->shouldReceive('setOption')->andReturnSelf();
        $pdfMock->shouldReceive('loadHTML')->once()->with(Mockery::on(function ($html) {
            return str_contains($html, 'CUSTOM:500');
        }))->andReturnSelf();
        $pdfMock->shouldReceive('setPaper')->andReturnSelf();

        $this->app->instance('snappy.pdf.wrapper', $pdfMock);

        $pdf->make();
    }

    public function test_fluent_chaining()
    {
        $exporter = new ReportExporter;
        $query = $this->makeQueryWithResults();

        $result = $exporter
            ->of('Report', [], $query, ['Name' => 'name'])
            ->showHeader(true)
            ->showMeta(false)
            ->showNumColumn(false)
            ->setPaper('legal')
            ->setOrientation('landscape')
            ->limit(100)
            ->groupBy('Name')
            ->showTotal(['Name' => 'sum'])
            ->editColumn('Name', ['class' => 'bold'])
            ->setCss(['.header' => 'color: blue'])
            ->formatColumn('Name', 'number')
            ->onBeforeRender(function () {})
            ->onRow(function () {})
            ->onAfterRender(function () {})
            ->onComplete(function () {})
            ->conditionalFormat('Name', fn ($v) => true, ['class' => 'bold'])
            ->setHeaderContent('Header', 'center')
            ->setFooterContent('Footer', 'left')
            ->cacheFor(60)
            ->cacheAs('key')
            ->cacheUsing('file');

        $this->assertSame($exporter, $result);
    }

    public function test_multiple_exports_from_same_definition()
    {
        $query = $this->makeQueryWithResults([['name' => 'Alice', 'amount' => 100]]);

        $exporter = new ReportExporter;
        $exporter->of('Test', [], $query, ['Name' => 'name', 'Amount' => 'amount'])
            ->formatColumn('Amount', 'currency');

        $pdf = $exporter->toPdf();
        $csv = $exporter->toCsv();
        $excel = $exporter->toExcel();

        $this->assertInstanceOf(PdfReport::class, $pdf);
        $this->assertInstanceOf(CsvReport::class, $csv);
        $this->assertInstanceOf(ExcelReport::class, $excel);
    }

    public function test_clear_footer_and_header()
    {
        $exporter = new ReportExporter;
        $query = $this->makeQueryWithResults();

        $result = $exporter->of('Test', [], $query, ['Name' => 'name'])
            ->setHeaderContent('Test')
            ->setFooterContent('Footer')
            ->clearFooter()
            ->clearHeader();

        $this->assertSame($exporter, $result);
    }

    public function test_no_cache()
    {
        $exporter = new ReportExporter;
        $query = $this->makeQueryWithResults();

        $result = $exporter->of('Test', [], $query, ['Name' => 'name'])
            ->cacheFor(60)
            ->noCache();

        $this->assertSame($exporter, $result);
    }

    public function test_without_manipulation()
    {
        $exporter = new ReportExporter;
        $query = $this->makeQueryWithResults();

        $result = $exporter->of('Test', [], $query, ['Name' => 'name'])
            ->withoutManipulation();

        $this->assertSame($exporter, $result);
    }

    public function test_simple()
    {
        $exporter = new ReportExporter;
        $query = $this->makeQueryWithResults();

        $result = $exporter->of('Test', [], $query, ['Name' => 'name'])
            ->simple();

        $this->assertSame($exporter, $result);
    }
}
