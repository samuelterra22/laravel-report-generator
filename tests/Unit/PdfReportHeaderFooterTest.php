<?php

declare(strict_types=1);

namespace SamuelTerra22\ReportGenerator\Tests\Unit;

use Mockery;
use SamuelTerra22\ReportGenerator\ReportMedia\PdfReport;
use SamuelTerra22\ReportGenerator\Tests\TestCase;

class PdfReportHeaderFooterTest extends TestCase
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

    public function test_default_footer_sets_snappy_options()
    {
        $query = $this->makeQueryWithResults([['name' => 'A', 'amount' => 100]]);

        $report = new PdfReport;
        $report->of('Test', [], $query, ['Name' => 'name', 'Amount' => 'amount']);

        $pdfMock = Mockery::mock();
        $pdfMock->shouldReceive('setOption')->with('footer-font-size', 10)->once()->andReturnSelf();
        $pdfMock->shouldReceive('setOption')->with('footer-left', Mockery::on(fn ($v) => str_contains($v, 'Date Printed:')))->once()->andReturnSelf();
        $pdfMock->shouldReceive('setOption')->with('footer-right', Mockery::on(fn ($v) => str_contains($v, 'Page')))->once()->andReturnSelf();
        $pdfMock->shouldReceive('loadHTML')->andReturnSelf();
        $pdfMock->shouldReceive('setPaper')->andReturnSelf();

        $this->app->instance('snappy.pdf.wrapper', $pdfMock);

        $report->make();
    }

    public function test_custom_footer_sets_snappy_options()
    {
        $query = $this->makeQueryWithResults([['name' => 'A', 'amount' => 100]]);

        $report = new PdfReport;
        $report->of('Test Title', [], $query, ['Name' => 'name', 'Amount' => 'amount'])
            ->setFooterContent('Custom Center', 'center');

        $pdfMock = Mockery::mock();
        $pdfMock->shouldReceive('setOption')->with('footer-font-size', 10)->andReturnSelf();
        $pdfMock->shouldReceive('setOption')->with('footer-left', Mockery::any())->andReturnSelf();
        $pdfMock->shouldReceive('setOption')->with('footer-right', Mockery::any())->andReturnSelf();
        $pdfMock->shouldReceive('setOption')->with('footer-center', 'Custom Center')->once()->andReturnSelf();
        $pdfMock->shouldReceive('loadHTML')->andReturnSelf();
        $pdfMock->shouldReceive('setPaper')->andReturnSelf();

        $this->app->instance('snappy.pdf.wrapper', $pdfMock);

        $report->make();
    }

    public function test_clear_footer_removes_snappy_footer_options()
    {
        $query = $this->makeQueryWithResults([['name' => 'A', 'amount' => 100]]);

        $report = new PdfReport;
        $report->of('Test', [], $query, ['Name' => 'name', 'Amount' => 'amount'])
            ->clearFooter();

        $pdfMock = Mockery::mock();
        $pdfMock->shouldReceive('setOption')->with('footer-font-size', 10)->once()->andReturnSelf();
        // No footer-left, footer-right, or footer-center should be set
        $pdfMock->shouldNotReceive('setOption')->with('footer-left', Mockery::any());
        $pdfMock->shouldNotReceive('setOption')->with('footer-right', Mockery::any());
        $pdfMock->shouldReceive('loadHTML')->andReturnSelf();
        $pdfMock->shouldReceive('setPaper')->andReturnSelf();

        $this->app->instance('snappy.pdf.wrapper', $pdfMock);

        $report->make();
    }

    public function test_custom_header_sets_snappy_options()
    {
        $query = $this->makeQueryWithResults([['name' => 'A', 'amount' => 100]]);

        $report = new PdfReport;
        $report->of('Test', [], $query, ['Name' => 'name', 'Amount' => 'amount'])
            ->setHeaderContent('Company Report', 'center');

        $pdfMock = Mockery::mock();
        $pdfMock->shouldReceive('setOption')->with('footer-font-size', 10)->andReturnSelf();
        $pdfMock->shouldReceive('setOption')->with('footer-left', Mockery::any())->andReturnSelf();
        $pdfMock->shouldReceive('setOption')->with('footer-right', Mockery::any())->andReturnSelf();
        $pdfMock->shouldReceive('setOption')->with('header-center', 'Company Report')->once()->andReturnSelf();
        $pdfMock->shouldReceive('loadHTML')->andReturnSelf();
        $pdfMock->shouldReceive('setPaper')->andReturnSelf();

        $this->app->instance('snappy.pdf.wrapper', $pdfMock);

        $report->make();
    }

    public function test_footer_with_title_placeholder()
    {
        $query = $this->makeQueryWithResults([['name' => 'A', 'amount' => 100]]);

        $report = new PdfReport;
        $report->of('My Report', [], $query, ['Name' => 'name', 'Amount' => 'amount'])
            ->setFooterContent('Report: {title}', 'center');

        $pdfMock = Mockery::mock();
        $pdfMock->shouldReceive('setOption')->with('footer-font-size', 10)->andReturnSelf();
        $pdfMock->shouldReceive('setOption')->with('footer-left', Mockery::any())->andReturnSelf();
        $pdfMock->shouldReceive('setOption')->with('footer-right', Mockery::any())->andReturnSelf();
        $pdfMock->shouldReceive('setOption')->with('footer-center', 'Report: My Report')->once()->andReturnSelf();
        $pdfMock->shouldReceive('loadHTML')->andReturnSelf();
        $pdfMock->shouldReceive('setPaper')->andReturnSelf();

        $this->app->instance('snappy.pdf.wrapper', $pdfMock);

        $report->make();
    }
}
