<?php

declare(strict_types=1);

namespace SamuelTerra22\ReportGenerator\Tests\Unit;

use SamuelTerra22\ReportGenerator\Tests\Stubs\ConcreteReportGenerator;
use SamuelTerra22\ReportGenerator\Tests\TestCase;

class ReportGeneratorCacheTest extends TestCase
{
    private ConcreteReportGenerator $report;

    protected function setUp(): void
    {
        parent::setUp();
        $this->report = new ConcreteReportGenerator;
    }

    public function test_cache_for_enables_caching()
    {
        $result = $this->report->cacheFor(60);

        $this->assertSame($this->report, $result);
        $this->assertTrue($this->report->getCacheEnabled());
        $this->assertEquals(60, $this->report->getCacheDuration());
    }

    public function test_cache_as_sets_custom_key()
    {
        $result = $this->report->cacheAs('monthly-sales');

        $this->assertSame($this->report, $result);
        $this->assertEquals('monthly-sales', $this->report->getCacheKeyValue());
    }

    public function test_cache_using_sets_store()
    {
        $result = $this->report->cacheUsing('redis');

        $this->assertSame($this->report, $result);
        $this->assertEquals('redis', $this->report->getCacheStore());
    }

    public function test_no_cache_disables_caching()
    {
        $this->report->cacheFor(60);
        $result = $this->report->noCache();

        $this->assertSame($this->report, $result);
        $this->assertFalse($this->report->getCacheEnabled());
    }

    public function test_default_cache_disabled()
    {
        $this->assertFalse($this->report->getCacheEnabled());
        $this->assertEquals(0, $this->report->getCacheDuration());
        $this->assertNull($this->report->getCacheKeyValue());
        $this->assertNull($this->report->getCacheStore());
    }

    public function test_cache_key_generated_uses_custom_key()
    {
        $query = $this->mockQueryBuilder();
        $this->report->of('Test', ['key' => 'val'], $query, ['Name' => 'name']);
        $this->report->cacheAs('my-custom-key');

        $this->assertEquals('my-custom-key', $this->report->getCacheKeyGenerated());
    }

    public function test_cache_key_generated_auto()
    {
        $query = $this->mockQueryBuilder();
        $this->report->of('Test Report', ['Period' => 'Jan'], $query, ['Name' => 'name', 'Amount' => 'amount']);

        $key = $this->report->getCacheKeyGenerated();
        $this->assertStringStartsWith('report-generator:', $key);
        $this->assertNotEmpty($key);
    }

    public function test_cache_key_deterministic()
    {
        $query = $this->mockQueryBuilder();
        $this->report->of('Test', ['key' => 'val'], $query, ['Name' => 'name']);

        $report2 = new ConcreteReportGenerator;
        $report2->of('Test', ['key' => 'val'], $query, ['Name' => 'name']);

        $this->assertEquals($this->report->getCacheKeyGenerated(), $report2->getCacheKeyGenerated());
    }

    public function test_cache_chaining()
    {
        $result = $this->report
            ->cacheFor(30)
            ->cacheAs('report-key')
            ->cacheUsing('file');

        $this->assertSame($this->report, $result);
        $this->assertTrue($this->report->getCacheEnabled());
        $this->assertEquals(30, $this->report->getCacheDuration());
        $this->assertEquals('report-key', $this->report->getCacheKeyValue());
        $this->assertEquals('file', $this->report->getCacheStore());
    }
}
