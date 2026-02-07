<?php

declare(strict_types=1);

namespace SamuelTerra22\ReportGenerator;

use SamuelTerra22\ReportGenerator\ReportMedia\CsvReport;
use SamuelTerra22\ReportGenerator\ReportMedia\ExcelReport;
use SamuelTerra22\ReportGenerator\ReportMedia\PdfReport;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ReportGeneratorServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('report-generator')
            ->hasConfigFile()
            ->hasViews('laravel-report-generator');
    }

    public function packageRegistered(): void
    {
        $this->app->bind('pdf.report.generator', fn ($app) => new PdfReport);
        $this->app->bind('excel.report.generator', fn ($app) => new ExcelReport);
        $this->app->bind('csv.report.generator', fn ($app) => new CsvReport);
        $this->app->bind('report.exporter', fn ($app) => new ReportExporter);
        $this->app->register(\Maatwebsite\Excel\ExcelServiceProvider::class);
    }
}
