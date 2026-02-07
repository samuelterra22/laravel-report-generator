<?php

namespace SamuelTerra22\ReportGenerator\Tests\Unit\Facades;

use SamuelTerra22\ReportGenerator\Facades\CSVReportFacade;
use SamuelTerra22\ReportGenerator\ReportMedia\CSVReport;
use SamuelTerra22\ReportGenerator\Tests\TestCase;

class CSVReportFacadeTest extends TestCase
{
    public function test_facade_accessor()
    {
        $method = new \ReflectionMethod(CSVReportFacade::class, 'getFacadeAccessor');
        $method->setAccessible(true);
        $this->assertEquals('csv.report.generator', $method->invoke(null));
    }

    public function test_facade_resolves_to_csv_report()
    {
        $this->assertInstanceOf(CSVReport::class, CSVReportFacade::getFacadeRoot());
    }
}
