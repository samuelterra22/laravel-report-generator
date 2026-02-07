<?php

declare(strict_types=1);

namespace SamuelTerra22\ReportGenerator\Tests\Unit;

use SamuelTerra22\ReportGenerator\Tests\Stubs\ConcreteReportGenerator;
use SamuelTerra22\ReportGenerator\Tests\TestCase;

class ReportGeneratorAggregationTest extends TestCase
{
    private ConcreteReportGenerator $report;

    protected function setUp(): void
    {
        parent::setUp();
        $this->report = new ConcreteReportGenerator;
    }

    public function test_show_total_accepts_advanced_types()
    {
        $result = $this->report->showTotal([
            'amount' => 'sum',
            'quantity' => 'avg',
            'price' => 'max',
            'orders' => 'count',
            'discount' => 'min',
            'balance' => 'point',
        ]);

        $this->assertSame($this->report, $result);
        $totals = $this->report->getShowTotalColumns();
        $this->assertEquals('sum', $totals['amount']);
        $this->assertEquals('avg', $totals['quantity']);
        $this->assertEquals('max', $totals['price']);
        $this->assertEquals('count', $totals['orders']);
        $this->assertEquals('min', $totals['discount']);
        $this->assertEquals('point', $totals['balance']);
    }

    public function test_show_total_backwards_compatible()
    {
        $this->report->showTotal(['amount' => 'sum', 'balance' => 'point']);

        $totals = $this->report->getShowTotalColumns();
        $this->assertEquals('sum', $totals['amount']);
        $this->assertEquals('point', $totals['balance']);
    }
}
