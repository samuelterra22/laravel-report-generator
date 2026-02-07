<?php

declare(strict_types=1);

namespace SamuelTerra22\ReportGenerator\Tests\Unit;

use SamuelTerra22\ReportGenerator\Tests\Stubs\ConcreteReportGenerator;
use SamuelTerra22\ReportGenerator\Tests\TestCase;

class ReportGeneratorFormatColumnTest extends TestCase
{
    private ConcreteReportGenerator $report;

    protected function setUp(): void
    {
        parent::setUp();
        $this->report = new ConcreteReportGenerator;
    }

    public function test_format_column_stores_format()
    {
        $result = $this->report->formatColumn('amount', 'currency', ['prefix' => 'R$']);

        $this->assertSame($this->report, $result);
        $formats = $this->report->getColumnFormats();
        $this->assertArrayHasKey('amount', $formats);
        $this->assertEquals('currency', $formats['amount']['type']);
        $this->assertEquals(['prefix' => 'R$'], $formats['amount']['options']);
    }

    public function test_format_column_default_options()
    {
        $this->report->formatColumn('date', 'date');

        $formats = $this->report->getColumnFormats();
        $this->assertEquals([], $formats['date']['options']);
    }

    public function test_format_columns_applies_to_multiple()
    {
        $result = $this->report->formatColumns(['price', 'total'], 'currency', ['prefix' => '$']);

        $this->assertSame($this->report, $result);
        $formats = $this->report->getColumnFormats();
        $this->assertArrayHasKey('price', $formats);
        $this->assertArrayHasKey('total', $formats);
        $this->assertEquals('currency', $formats['price']['type']);
        $this->assertEquals('currency', $formats['total']['type']);
    }

    public function test_format_column_chaining()
    {
        $this->report
            ->formatColumn('amount', 'currency')
            ->formatColumn('date', 'date', ['format' => 'd/m/Y'])
            ->formatColumn('rate', 'percentage');

        $formats = $this->report->getColumnFormats();
        $this->assertCount(3, $formats);
    }

    public function test_format_column_overwrites_previous()
    {
        $this->report->formatColumn('amount', 'currency');
        $this->report->formatColumn('amount', 'number', ['decimals' => 0]);

        $formats = $this->report->getColumnFormats();
        $this->assertEquals('number', $formats['amount']['type']);
    }

    public function test_default_column_formats_is_empty()
    {
        $this->assertEquals([], $this->report->getColumnFormats());
    }
}
