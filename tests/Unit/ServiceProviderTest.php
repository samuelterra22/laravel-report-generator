<?php

declare(strict_types=1);

namespace SamuelTerra22\ReportGenerator\Tests\Unit;

use SamuelTerra22\ReportGenerator\ReportMedia\CsvReport;
use SamuelTerra22\ReportGenerator\ReportMedia\ExcelReport;
use SamuelTerra22\ReportGenerator\ReportMedia\PdfReport;
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
        $this->assertInstanceOf(CsvReport::class, $this->app->make('csv.report.generator'));
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
}
