<?php

declare(strict_types=1);

namespace SamuelTerra22\ReportGenerator\Tests\Unit;

use SamuelTerra22\ReportGenerator\Tests\Stubs\ConcreteReportGenerator;
use SamuelTerra22\ReportGenerator\Tests\TestCase;

class ReportGeneratorConditionalFormatTest extends TestCase
{
    private ConcreteReportGenerator $report;

    protected function setUp(): void
    {
        parent::setUp();
        $this->report = new ConcreteReportGenerator;
    }

    public function test_conditional_format_stores_rule()
    {
        $condition = fn ($value) => $value > 1000;
        $styles = ['class' => 'bold', 'background' => '#ffcccc'];

        $result = $this->report->conditionalFormat('amount', $condition, $styles);

        $this->assertSame($this->report, $result);
        $formats = $this->report->getConditionalFormats();
        $this->assertArrayHasKey('amount', $formats);
        $this->assertCount(1, $formats['amount']);
        $this->assertSame($condition, $formats['amount'][0]['condition']);
        $this->assertEquals($styles, $formats['amount'][0]['styles']);
    }

    public function test_multiple_rules_per_column()
    {
        $this->report
            ->conditionalFormat('amount', fn ($v) => $v > 1000, ['class' => 'bold'])
            ->conditionalFormat('amount', fn ($v) => $v < 0, ['color' => '#ff0000']);

        $formats = $this->report->getConditionalFormats();
        $this->assertCount(2, $formats['amount']);
    }

    public function test_rules_for_different_columns()
    {
        $this->report
            ->conditionalFormat('amount', fn ($v) => $v > 1000, ['class' => 'bold'])
            ->conditionalFormat('status', fn ($v) => $v === 'Overdue', ['color' => '#ff0000']);

        $formats = $this->report->getConditionalFormats();
        $this->assertArrayHasKey('amount', $formats);
        $this->assertArrayHasKey('status', $formats);
    }

    public function test_default_conditional_formats_empty()
    {
        $this->assertEquals([], $this->report->getConditionalFormats());
    }

    public function test_condition_receives_value_and_row()
    {
        $receivedArgs = [];
        $this->report->conditionalFormat('amount', function ($value, $row) use (&$receivedArgs) {
            $receivedArgs = ['value' => $value, 'row' => $row];

            return true;
        }, ['class' => 'bold']);

        $formats = $this->report->getConditionalFormats();
        $condition = $formats['amount'][0]['condition'];

        $row = (object) ['amount' => 500, 'name' => 'Test'];
        $condition(500, $row);

        $this->assertEquals(500, $receivedArgs['value']);
        $this->assertEquals('Test', $receivedArgs['row']->name);
    }
}
