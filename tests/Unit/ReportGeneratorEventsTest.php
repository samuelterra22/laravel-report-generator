<?php

declare(strict_types=1);

namespace SamuelTerra22\ReportGenerator\Tests\Unit;

use SamuelTerra22\ReportGenerator\Tests\Stubs\ConcreteReportGenerator;
use SamuelTerra22\ReportGenerator\Tests\TestCase;

class ReportGeneratorEventsTest extends TestCase
{
    private ConcreteReportGenerator $report;

    protected function setUp(): void
    {
        parent::setUp();
        $this->report = new ConcreteReportGenerator;
    }

    public function test_on_before_render_registers_callback()
    {
        $callback = function () {};
        $result = $this->report->onBeforeRender($callback);

        $this->assertSame($this->report, $result);
        $this->assertCount(1, $this->report->getOnBeforeRenderCallbacks());
        $this->assertSame($callback, $this->report->getOnBeforeRenderCallbacks()[0]);
    }

    public function test_on_row_registers_callback()
    {
        $callback = function ($row, $index) {};
        $result = $this->report->onRow($callback);

        $this->assertSame($this->report, $result);
        $this->assertCount(1, $this->report->getOnRowCallbacks());
    }

    public function test_on_after_render_registers_callback()
    {
        $callback = function () {};
        $result = $this->report->onAfterRender($callback);

        $this->assertSame($this->report, $result);
        $this->assertCount(1, $this->report->getOnAfterRenderCallbacks());
    }

    public function test_on_complete_registers_callback()
    {
        $callback = function () {};
        $result = $this->report->onComplete($callback);

        $this->assertSame($this->report, $result);
        $this->assertCount(1, $this->report->getOnCompleteCallbacks());
    }

    public function test_multiple_callbacks_per_event()
    {
        $this->report
            ->onBeforeRender(function () {})
            ->onBeforeRender(function () {});

        $this->assertCount(2, $this->report->getOnBeforeRenderCallbacks());
    }

    public function test_default_callbacks_are_empty()
    {
        $this->assertEquals([], $this->report->getOnBeforeRenderCallbacks());
        $this->assertEquals([], $this->report->getOnRowCallbacks());
        $this->assertEquals([], $this->report->getOnAfterRenderCallbacks());
        $this->assertEquals([], $this->report->getOnCompleteCallbacks());
    }

    public function test_chaining_all_events()
    {
        $result = $this->report
            ->onBeforeRender(function () {})
            ->onRow(function () {})
            ->onAfterRender(function () {})
            ->onComplete(function () {});

        $this->assertSame($this->report, $result);
    }
}
