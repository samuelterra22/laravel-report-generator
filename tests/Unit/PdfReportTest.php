<?php

declare(strict_types=1);

namespace SamuelTerra22\ReportGenerator\Tests\Unit;

use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use SamuelTerra22\ReportGenerator\ReportMedia\PdfReport;
use SamuelTerra22\ReportGenerator\Tests\TestCase;

class PdfReportTest extends TestCase
{
    private function makeQueryWithResults(array $results): \Mockery\MockInterface
    {
        $resultObjects = array_map(function ($row) {
            return $this->makeResultObject($row);
        }, $results);

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

    private function makeReport(array $results = [], bool $withoutManipulation = false): PdfReport
    {
        $query = $this->makeQueryWithResults($results);

        $report = new PdfReport;
        $report->of(
            'Test PDF',
            ['Period' => 'January'],
            $query,
            ['Name' => 'name', 'Amount' => 'amount']
        );

        if ($withoutManipulation) {
            $report->withoutManipulation();
        }

        return $report;
    }

    public function test_make_with_snappy()
    {
        $report = $this->makeReport([
            ['name' => 'Alice', 'amount' => 100],
        ]);

        $pdfMock = Mockery::mock();
        $pdfMock->shouldReceive('setOption')->times(3)->andReturnSelf();
        $pdfMock->shouldReceive('loadHTML')->once()->andReturnSelf();
        $pdfMock->shouldReceive('setPaper')->once()->with('a4', 'portrait')->andReturnSelf();

        $this->app->instance('snappy.pdf.wrapper', $pdfMock);

        $result = $report->make();
        $this->assertSame($pdfMock, $result);
    }

    public function test_make_falls_back_to_dompdf_on_reflection_exception()
    {
        $report = $this->makeReport([
            ['name' => 'Bob', 'amount' => 200],
        ]);

        $this->app->bind('snappy.pdf.wrapper', function () {
            throw new \ReflectionException('Not found');
        });

        $pdfMock = Mockery::mock();
        $pdfMock->shouldReceive('loadHTML')->once()->andReturnSelf();
        $pdfMock->shouldReceive('setPaper')->once()->andReturnSelf();

        $this->app->instance('dompdf.wrapper', $pdfMock);

        $result = $report->make();
        $this->assertSame($pdfMock, $result);
    }

    public function test_make_falls_back_to_dompdf_on_binding_resolution_exception()
    {
        $report = $this->makeReport([
            ['name' => 'Charlie', 'amount' => 300],
        ]);

        $this->app->bind('snappy.pdf.wrapper', function () {
            throw new BindingResolutionException('Not bound');
        });

        $pdfMock = Mockery::mock();
        $pdfMock->shouldReceive('loadHTML')->once()->andReturnSelf();
        $pdfMock->shouldReceive('setPaper')->once()->andReturnSelf();

        $this->app->instance('dompdf.wrapper', $pdfMock);

        $result = $report->make();
        $this->assertSame($pdfMock, $result);
    }

    public function test_make_throws_when_no_pdf_engine()
    {
        $report = $this->makeReport([
            ['name' => 'Dave', 'amount' => 400],
        ]);

        $this->app->bind('snappy.pdf.wrapper', function () {
            throw new \ReflectionException('Not found');
        });

        $this->app->bind('dompdf.wrapper', function () {
            throw new \ReflectionException('Not found');
        });

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Please install either barryvdh/laravel-snappy or laravel-dompdf');

        $report->make();
    }

    public function test_make_with_without_manipulation()
    {
        $report = $this->makeReport([
            ['name' => 'Eve', 'amount' => 500],
        ], true);

        $pdfMock = Mockery::mock();
        $pdfMock->shouldReceive('setOption')->andReturnSelf();
        $pdfMock->shouldReceive('loadHTML')->once()->andReturnSelf();
        $pdfMock->shouldReceive('setPaper')->once()->andReturnSelf();

        $this->app->instance('snappy.pdf.wrapper', $pdfMock);

        $result = $report->make();
        $this->assertSame($pdfMock, $result);
    }

    public function test_stream_calls_make_then_stream()
    {
        $report = $this->makeReport([
            ['name' => 'Frank', 'amount' => 600],
        ]);

        $pdfMock = Mockery::mock();
        $pdfMock->shouldReceive('setOption')->andReturnSelf();
        $pdfMock->shouldReceive('loadHTML')->once()->andReturnSelf();
        $pdfMock->shouldReceive('setPaper')->once()->andReturnSelf();
        $pdfMock->shouldReceive('stream')->once()->andReturn('streamed-content');

        $this->app->instance('snappy.pdf.wrapper', $pdfMock);

        $result = $report->stream();
        $this->assertEquals('streamed-content', $result);
    }

    public function test_download_appends_pdf_extension()
    {
        $report = $this->makeReport([
            ['name' => 'Grace', 'amount' => 700],
        ]);

        $pdfMock = Mockery::mock();
        $pdfMock->shouldReceive('setOption')->andReturnSelf();
        $pdfMock->shouldReceive('loadHTML')->once()->andReturnSelf();
        $pdfMock->shouldReceive('setPaper')->once()->andReturnSelf();
        $pdfMock->shouldReceive('download')->once()->with('report.pdf')->andReturn('downloaded');

        $this->app->instance('snappy.pdf.wrapper', $pdfMock);

        $result = $report->download('report');
        $this->assertEquals('downloaded', $result);
    }

    public function test_make_respects_paper_and_orientation()
    {
        $report = $this->makeReport([
            ['name' => 'Hank', 'amount' => 800],
        ]);
        $report->setPaper('Letter')->setOrientation('Landscape');

        $pdfMock = Mockery::mock();
        $pdfMock->shouldReceive('setOption')->andReturnSelf();
        $pdfMock->shouldReceive('loadHTML')->once()->andReturnSelf();
        $pdfMock->shouldReceive('setPaper')->once()->with('letter', 'landscape')->andReturnSelf();

        $this->app->instance('snappy.pdf.wrapper', $pdfMock);

        $report->make();
    }

    public function test_make_throws_binding_resolution_for_both_engines()
    {
        $report = $this->makeReport([
            ['name' => 'Ivy', 'amount' => 900],
        ]);

        $this->app->bind('snappy.pdf.wrapper', function () {
            throw new BindingResolutionException('Snappy not bound');
        });

        $this->app->bind('dompdf.wrapper', function () {
            throw new BindingResolutionException('DomPDF not bound');
        });

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Please install either barryvdh/laravel-snappy or laravel-dompdf');

        $report->make();
    }
}
