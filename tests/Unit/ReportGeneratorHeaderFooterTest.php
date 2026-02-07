<?php

declare(strict_types=1);

namespace SamuelTerra22\ReportGenerator\Tests\Unit;

use SamuelTerra22\ReportGenerator\Tests\Stubs\ConcreteReportGenerator;
use SamuelTerra22\ReportGenerator\Tests\TestCase;

class ReportGeneratorHeaderFooterTest extends TestCase
{
    private ConcreteReportGenerator $report;

    protected function setUp(): void
    {
        parent::setUp();
        $this->report = new ConcreteReportGenerator;
    }

    public function test_default_footer_content()
    {
        $footer = $this->report->getFooterContent();
        $this->assertEquals('Date Printed: {date}', $footer['left']);
        $this->assertEquals('Page {page} of {pages}', $footer['right']);
    }

    public function test_default_header_content_is_empty()
    {
        $this->assertEquals([], $this->report->getHeaderContent());
    }

    public function test_set_footer_content()
    {
        $result = $this->report->setFooterContent('Custom Footer', 'center');

        $this->assertSame($this->report, $result);
        $footer = $this->report->getFooterContent();
        $this->assertEquals('Custom Footer', $footer['center']);
        // Defaults still present
        $this->assertEquals('Date Printed: {date}', $footer['left']);
    }

    public function test_set_footer_content_default_position()
    {
        $this->report->setFooterContent('Center Footer');

        $footer = $this->report->getFooterContent();
        $this->assertEquals('Center Footer', $footer['center']);
    }

    public function test_set_header_content()
    {
        $result = $this->report->setHeaderContent('Company Report', 'center');

        $this->assertSame($this->report, $result);
        $header = $this->report->getHeaderContent();
        $this->assertEquals('Company Report', $header['center']);
    }

    public function test_set_header_content_default_position()
    {
        $this->report->setHeaderContent('Company Report');

        $header = $this->report->getHeaderContent();
        $this->assertEquals('Company Report', $header['center']);
    }

    public function test_clear_footer()
    {
        $result = $this->report->clearFooter();

        $this->assertSame($this->report, $result);
        $this->assertEquals([], $this->report->getFooterContent());
    }

    public function test_clear_header()
    {
        $this->report->setHeaderContent('Test');
        $result = $this->report->clearHeader();

        $this->assertSame($this->report, $result);
        $this->assertEquals([], $this->report->getHeaderContent());
    }

    public function test_footer_override_left()
    {
        $this->report->setFooterContent('Custom Left', 'left');

        $footer = $this->report->getFooterContent();
        $this->assertEquals('Custom Left', $footer['left']);
    }

    public function test_chaining_header_and_footer()
    {
        $result = $this->report
            ->setHeaderContent('Header', 'center')
            ->setFooterContent('Footer Left', 'left')
            ->setFooterContent('Footer Right', 'right');

        $this->assertSame($this->report, $result);
        $this->assertEquals('Header', $this->report->getHeaderContent()['center']);
        $this->assertEquals('Footer Left', $this->report->getFooterContent()['left']);
        $this->assertEquals('Footer Right', $this->report->getFooterContent()['right']);
    }
}
