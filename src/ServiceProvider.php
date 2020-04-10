<?php

namespace SamuelTerra22\ReportGenerator;

use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;
use Illuminate\Support\Str;
use SamuelTerra22\ReportGenerator\ReportMedia\CSVReport;
use SamuelTerra22\ReportGenerator\ReportMedia\ExcelReport;
use SamuelTerra22\ReportGenerator\ReportMedia\PdfReport;

class ServiceProvider extends IlluminateServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $configPath = __DIR__ . '/../config/report-generator.php';
        $this->mergeConfigFrom($configPath, 'report-generator');

        $this->app->bind('pdf.report.generator', function ($app) {
            return new PdfReport ($app);
        });
        $this->app->bind('excel.report.generator', function ($app) {
            return new ExcelReport ($app);
        });
        $this->app->bind('csv.report.generator', function ($app) {
            return new CSVReport ($app);
        });
        $this->app->register('Maatwebsite\Excel\ExcelServiceProvider');

        $this->registerAliases();
    }

    public function boot()
    {
        if ($this->isLumen()) {
            require_once 'Lumen.php';
        }

        $this->loadViewsFrom(__DIR__ . '/views', 'laravel-report-generator');

        $this->publishes([
            __DIR__ . '/../config/report-generator.php' => config_path('report-generator.php')
        ], 'laravel-report:config');

        $this->publishes([
            __DIR__ . '/views' => base_path('resources/views/vendor/laravel-report-generator')
        ], 'laravel-report:view-template');
    }

    protected function registerAliases()
    {
        if (class_exists('Illuminate\Foundation\AliasLoader')) {
            $loader = \Illuminate\Foundation\AliasLoader::getInstance();
            $loader->alias('PdfReport', \SamuelTerra22\ReportGenerator\Facades\PdfReportFacade::class);
            $loader->alias('ExcelReport', \SamuelTerra22\ReportGenerator\Facades\ExcelReportFacade::class);
            $loader->alias('CSVReport', \SamuelTerra22\ReportGenerator\Facades\CSVReportFacade::class);
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }

    protected function isLumen()
    {
        return Str::contains($this->app->version(), 'Lumen');
    }
}
