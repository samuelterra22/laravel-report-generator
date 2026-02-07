<?php

declare(strict_types=1);

namespace SamuelTerra22\ReportGenerator\Tests\Unit;

use Illuminate\Support\Facades\Cache;
use Mockery;
use SamuelTerra22\ReportGenerator\ReportMedia\CsvReport;
use SamuelTerra22\ReportGenerator\Tests\TestCase;

class CsvReportCacheTest extends TestCase
{
    private function makeQueryWithResults(array $results): \Mockery\MockInterface
    {
        $resultObjects = array_map(fn ($row) => $this->makeResultObject($row), $results);

        $query = Mockery::mock('Illuminate\Database\Query\Builder');
        $query->shouldReceive('take')->andReturnSelf();
        $query->shouldReceive('cursor')->andReturn(new \ArrayIterator($resultObjects));

        return $query;
    }

    public function test_cache_stores_csv_content()
    {
        $query = $this->makeQueryWithResults([
            ['name' => 'Alice', 'amount' => 100],
        ]);

        $report = new CsvReport;
        $report->of('Test', [], $query, ['Name' => 'name', 'Amount' => 'amount'])
            ->cacheFor(60)
            ->cacheAs('csv-test-key');

        ob_start();
        $report->download('test');
        ob_get_clean();

        $cached = Cache::get('csv-test-key');
        $this->assertNotNull($cached);
        $this->assertStringContainsString('Alice', $cached);
    }

    public function test_cache_hit_returns_cached_content()
    {
        $csvContent = "Name,Amount\nAlice,100\n";
        Cache::put('csv-cache-hit', $csvContent, 3600);

        $query = $this->makeQueryWithResults([]);

        $report = new CsvReport;
        $report->of('Test', [], $query, ['Name' => 'name', 'Amount' => 'amount'])
            ->cacheFor(60)
            ->cacheAs('csv-cache-hit');

        ob_start();
        $report->download('test');
        $output = ob_get_clean();

        $this->assertStringContainsString('Alice', $output);
    }

    public function test_no_cache_does_not_store_csv()
    {
        $query = $this->makeQueryWithResults([
            ['name' => 'Bob', 'amount' => 200],
        ]);

        $report = new CsvReport;
        $report->of('Test', [], $query, ['Name' => 'name', 'Amount' => 'amount'])
            ->cacheFor(60)
            ->cacheAs('csv-no-cache-key')
            ->noCache();

        ob_start();
        $report->download('test');
        ob_get_clean();

        $this->assertNull(Cache::get('csv-no-cache-key'));
    }
}
