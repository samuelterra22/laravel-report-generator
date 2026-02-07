<?php

namespace SamuelTerra22\ReportGenerator\Tests\Unit;

use SamuelTerra22\ReportGenerator\ReportMedia\CSVReport;
use SamuelTerra22\ReportGenerator\ReportMedia\ExcelReport;
use SamuelTerra22\ReportGenerator\ReportMedia\PdfReport;
use SamuelTerra22\ReportGenerator\ServiceProvider;
use SamuelTerra22\ReportGenerator\Tests\TestCase;

class ServiceProviderTest extends TestCase
{
    public function test_pdf_report_is_bound()
    {
        $this->assertTrue($this->app->bound('pdf.report.generator'));
        $this->assertInstanceOf(PdfReport::class, $this->app->make('pdf.report.generator'));
    }

    public function test_excel_report_is_bound()
    {
        $this->assertTrue($this->app->bound('excel.report.generator'));
        $this->assertInstanceOf(ExcelReport::class, $this->app->make('excel.report.generator'));
    }

    public function test_csv_report_is_bound()
    {
        $this->assertTrue($this->app->bound('csv.report.generator'));
        $this->assertInstanceOf(CSVReport::class, $this->app->make('csv.report.generator'));
    }

    public function test_config_is_merged()
    {
        $this->assertNotNull(config('report-generator'));
        $this->assertArrayHasKey('flush', config('report-generator'));
    }

    public function test_views_are_loaded()
    {
        $viewFinder = $this->app['view']->getFinder();
        $hints = $viewFinder->getHints();
        $this->assertArrayHasKey('laravel-report-generator', $hints);
    }

    public function test_aliases_are_registered()
    {
        $loader = \Illuminate\Foundation\AliasLoader::getInstance();
        $aliases = $loader->getAliases();
        $this->assertArrayHasKey('PdfReport', $aliases);
        $this->assertArrayHasKey('ExcelReport', $aliases);
        $this->assertArrayHasKey('CSVReport', $aliases);
    }

    public function test_provides_returns_empty_array()
    {
        $provider = new ServiceProvider($this->app);
        $this->assertEquals([], $provider->provides());
    }

    public function test_service_provider_is_deferred_false()
    {
        $provider = new ServiceProvider($this->app);
        $reflection = new \ReflectionProperty($provider, 'defer');
        $reflection->setAccessible(true);
        $this->assertFalse($reflection->getValue($provider));
    }
}
