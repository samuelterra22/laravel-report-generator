<?php

declare(strict_types=1);

namespace SamuelTerra22\ReportGenerator\Tests\Unit\Facades;

use SamuelTerra22\ReportGenerator\Facades\ReportExporter;
use SamuelTerra22\ReportGenerator\ReportExporter as ReportExporterClass;
use SamuelTerra22\ReportGenerator\Tests\TestCase;

class ReportExporterFacadeTest extends TestCase
{
    public function test_facade_resolves_to_report_exporter()
    {
        $instance = ReportExporter::getFacadeRoot();
        $this->assertInstanceOf(ReportExporterClass::class, $instance);
    }

    public function test_facade_accessor()
    {
        $reflection = new \ReflectionMethod(ReportExporter::class, 'getFacadeAccessor');

        $this->assertEquals('report.exporter', $reflection->invoke(null));
    }
}
