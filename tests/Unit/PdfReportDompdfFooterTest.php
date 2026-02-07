<?php

declare(strict_types=1);

namespace SamuelTerra22\ReportGenerator\Tests\Unit;

use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use SamuelTerra22\ReportGenerator\ReportMedia\PdfReport;
use SamuelTerra22\ReportGenerator\Tests\TestCase;

class PdfReportDompdfFooterTest extends TestCase
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

    public function test_dompdf_fallback_with_custom_footer()
    {
        $query = $this->makeQueryWithResults([['name' => 'A', 'amount' => 100]]);

        $report = new PdfReport;
        $report->of('My Report', [], $query, ['Name' => 'name', 'Amount' => 'amount'])
            ->setFooterContent('Custom Left', 'left')
            ->setFooterContent('Page {page}', 'right');

        $this->app->bind('snappy.pdf.wrapper', function () {
            throw new BindingResolutionException('Not bound');
        });

        $pdfMock = Mockery::mock();
        $pdfMock->shouldReceive('loadHTML')->once()->with(Mockery::on(function ($html) {
            // DomPDF template should contain the footer script
            return str_contains($html, 'Custom Left');
        }))->andReturnSelf();
        $pdfMock->shouldReceive('setPaper')->once()->andReturnSelf();

        $this->app->instance('dompdf.wrapper', $pdfMock);

        $report->make();
    }

    public function test_dompdf_with_header_left_and_right()
    {
        $query = $this->makeQueryWithResults([['name' => 'A', 'amount' => 100]]);

        $report = new PdfReport;
        $report->of('Report', [], $query, ['Name' => 'name', 'Amount' => 'amount'])
            ->setHeaderContent('Left Header', 'left')
            ->setHeaderContent('Right Header', 'right');

        $pdfMock = Mockery::mock();
        $pdfMock->shouldReceive('setOption')->with('footer-font-size', 10)->andReturnSelf();
        $pdfMock->shouldReceive('setOption')->with('footer-left', Mockery::any())->andReturnSelf();
        $pdfMock->shouldReceive('setOption')->with('footer-right', Mockery::any())->andReturnSelf();
        $pdfMock->shouldReceive('setOption')->with('header-left', 'Left Header')->andReturnSelf();
        $pdfMock->shouldReceive('setOption')->with('header-right', 'Right Header')->andReturnSelf();
        $pdfMock->shouldReceive('loadHTML')->andReturnSelf();
        $pdfMock->shouldReceive('setPaper')->andReturnSelf();

        $this->app->instance('snappy.pdf.wrapper', $pdfMock);

        $report->make();
    }

    public function test_without_manipulation_template_with_custom_footer()
    {
        $query = $this->makeQueryWithResults([['name' => 'A', 'amount' => 100]]);

        $report = new PdfReport;
        $report->of('Test', [], $query, ['Name' => 'name', 'Amount' => 'amount'])
            ->withoutManipulation()
            ->setFooterContent('WM Footer', 'center');

        $pdfMock = Mockery::mock();
        $pdfMock->shouldReceive('setOption')->andReturnSelf();
        $pdfMock->shouldReceive('loadHTML')->once()->andReturnSelf();
        $pdfMock->shouldReceive('setPaper')->once()->andReturnSelf();

        $this->app->instance('snappy.pdf.wrapper', $pdfMock);

        $report->make();
    }

    public function test_cache_with_custom_store()
    {
        $query = $this->makeQueryWithResults([['name' => 'A', 'amount' => 100]]);

        $report = new PdfReport;
        $report->of('Test', [], $query, ['Name' => 'name', 'Amount' => 'amount'])
            ->cacheFor(60)
            ->cacheAs('store-test-key')
            ->cacheUsing('array');

        $pdfMock = Mockery::mock();
        $pdfMock->shouldReceive('setOption')->andReturnSelf();
        $pdfMock->shouldReceive('loadHTML')->andReturnSelf();
        $pdfMock->shouldReceive('setPaper')->andReturnSelf();

        $this->app->instance('snappy.pdf.wrapper', $pdfMock);

        $report->make();

        $cached = \Cache::store('array')->get('store-test-key');
        $this->assertNotNull($cached);
    }
}
