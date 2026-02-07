<?php

declare(strict_types=1);

namespace SamuelTerra22\ReportGenerator\Tests\Unit;

use SamuelTerra22\ReportGenerator\ReportMedia\ExcelReport;
use SamuelTerra22\ReportGenerator\Tests\TestCase;

class ExcelReportEventsTest extends TestCase
{
    public function test_on_before_render_registers()
    {
        $report = new ExcelReport;
        $query = $this->mockQueryBuilder();
        $report->of('Test', [], $query, ['Name' => 'name']);

        $result = $report->onBeforeRender(function () {});
        $this->assertSame($report, $result);
    }

    public function test_on_row_registers()
    {
        $report = new ExcelReport;
        $query = $this->mockQueryBuilder();
        $report->of('Test', [], $query, ['Name' => 'name']);

        $result = $report->onRow(function ($row, $index) {});
        $this->assertSame($report, $result);
    }

    public function test_on_after_render_registers()
    {
        $report = new ExcelReport;
        $query = $this->mockQueryBuilder();
        $report->of('Test', [], $query, ['Name' => 'name']);

        $result = $report->onAfterRender(function () {});
        $this->assertSame($report, $result);
    }

    public function test_on_complete_registers()
    {
        $report = new ExcelReport;
        $query = $this->mockQueryBuilder();
        $report->of('Test', [], $query, ['Name' => 'name']);

        $result = $report->onComplete(function () {});
        $this->assertSame($report, $result);
    }

    public function test_event_chaining()
    {
        $report = new ExcelReport;
        $query = $this->mockQueryBuilder();

        $result = $report->of('Test', [], $query, ['Name' => 'name'])
            ->onBeforeRender(function () {})
            ->onRow(function () {})
            ->onAfterRender(function () {})
            ->onComplete(function () {});

        $this->assertSame($report, $result);
    }
}
