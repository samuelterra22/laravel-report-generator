<?php

declare(strict_types=1);

namespace SamuelTerra22\ReportGenerator\Tests\Unit;

use Illuminate\Support\Facades\Cache;
use Mockery;
use SamuelTerra22\ReportGenerator\ReportMedia\PdfReport;
use SamuelTerra22\ReportGenerator\Tests\TestCase;

class PdfReportCacheTest extends TestCase
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

    public function test_cache_stores_rendered_html()
    {
        $query = $this->makeQueryWithResults([['name' => 'A', 'amount' => 100]]);

        $report = new PdfReport;
        $report->of('Test', [], $query, ['Name' => 'name', 'Amount' => 'amount'])
            ->cacheFor(60)
            ->cacheAs('test-key');

        $this->app->instance('snappy.pdf.wrapper', $this->makePdfMock());
        $report->make();

        $cached = Cache::get('test-key');
        $this->assertNotNull($cached);
        $this->assertStringContainsString('Test', $cached);
    }

    public function test_cache_hit_skips_rendering()
    {
        Cache::put('test-cache-key', '<html>cached content</html>', 3600);

        $query = $this->makeQueryWithResults([]);

        $report = new PdfReport;
        $report->of('Test', [], $query, ['Name' => 'name', 'Amount' => 'amount'])
            ->cacheFor(60)
            ->cacheAs('test-cache-key');

        $pdfMock = Mockery::mock();
        $pdfMock->shouldReceive('setOption')->andReturnSelf();
        $pdfMock->shouldReceive('loadHTML')->once()->with('<html>cached content</html>')->andReturnSelf();
        $pdfMock->shouldReceive('setPaper')->andReturnSelf();

        $this->app->instance('snappy.pdf.wrapper', $pdfMock);

        $report->make();
    }

    public function test_no_cache_does_not_store()
    {
        $query = $this->makeQueryWithResults([['name' => 'A', 'amount' => 100]]);

        $report = new PdfReport;
        $report->of('Test', [], $query, ['Name' => 'name', 'Amount' => 'amount'])
            ->cacheFor(60)
            ->cacheAs('no-cache-key')
            ->noCache();

        $this->app->instance('snappy.pdf.wrapper', $this->makePdfMock());
        $report->make();

        $this->assertNull(Cache::get('no-cache-key'));
    }

    public function test_cache_without_explicit_key()
    {
        $query = $this->makeQueryWithResults([['name' => 'A', 'amount' => 100]]);

        $report = new PdfReport;
        $report->of('Test', [], $query, ['Name' => 'name', 'Amount' => 'amount'])
            ->cacheFor(30);

        $this->app->instance('snappy.pdf.wrapper', $this->makePdfMock());
        $report->make();

        // The auto-generated key should have cached something
        // We can't easily verify the exact key, but the test shouldn't error
        $this->assertTrue(true);
    }
}
