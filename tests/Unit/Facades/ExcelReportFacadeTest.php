<?php

declare(strict_types=1);

namespace SamuelTerra22\ReportGenerator\Tests\Unit\Facades;

use SamuelTerra22\ReportGenerator\Facades\ExcelReport;
use SamuelTerra22\ReportGenerator\ReportMedia\ExcelReport as ExcelReportMedia;
use SamuelTerra22\ReportGenerator\Tests\TestCase;

class ExcelReportFacadeTest extends TestCase
{
    public function test_facade_accessor()
    {
        $method = new \ReflectionMethod(ExcelReport::class, 'getFacadeAccessor');
        $method->setAccessible(true);
        $this->assertEquals('excel.report.generator', $method->invoke(null));
    }

    public function test_facade_resolves_to_excel_report()
    {
        $this->assertInstanceOf(ExcelReportMedia::class, ExcelReport::getFacadeRoot());
    }
}
