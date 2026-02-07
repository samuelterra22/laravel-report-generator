<?php

namespace SamuelTerra22\ReportGenerator\Tests\Unit\Facades;

use SamuelTerra22\ReportGenerator\Facades\PdfReportFacade;
use SamuelTerra22\ReportGenerator\ReportMedia\PdfReport;
use SamuelTerra22\ReportGenerator\Tests\TestCase;

class PdfReportFacadeTest extends TestCase
{
    public function test_facade_accessor()
    {
        $method = new \ReflectionMethod(PdfReportFacade::class, 'getFacadeAccessor');
        $method->setAccessible(true);
        $this->assertEquals('pdf.report.generator', $method->invoke(null));
    }

    public function test_facade_resolves_to_pdf_report()
    {
        $this->assertInstanceOf(PdfReport::class, PdfReportFacade::getFacadeRoot());
    }
}
