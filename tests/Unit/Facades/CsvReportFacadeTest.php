<?php

declare(strict_types=1);

namespace SamuelTerra22\ReportGenerator\Tests\Unit\Facades;

use SamuelTerra22\ReportGenerator\Facades\CsvReport;
use SamuelTerra22\ReportGenerator\ReportMedia\CsvReport as CsvReportMedia;
use SamuelTerra22\ReportGenerator\Tests\TestCase;

class CsvReportFacadeTest extends TestCase
{
    public function test_facade_accessor()
    {
        $method = new \ReflectionMethod(CsvReport::class, 'getFacadeAccessor');
        $method->setAccessible(true);
        $this->assertEquals('csv.report.generator', $method->invoke(null));
    }

    public function test_facade_resolves_to_csv_report()
    {
        $this->assertInstanceOf(CsvReportMedia::class, CsvReport::getFacadeRoot());
    }
}
