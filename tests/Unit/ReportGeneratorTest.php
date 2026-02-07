<?php

declare(strict_types=1);

namespace SamuelTerra22\ReportGenerator\Tests\Unit;

use SamuelTerra22\ReportGenerator\Tests\Stubs\ConcreteReportGenerator;
use SamuelTerra22\ReportGenerator\Tests\TestCase;

class ReportGeneratorTest extends TestCase
{
    private ConcreteReportGenerator $report;

    protected function setUp(): void
    {
        parent::setUp();
        $this->report = new ConcreteReportGenerator;
    }

    public function test_default_values()
    {
        $this->assertNull($this->report->getLimit());
        $this->assertEquals([], $this->report->getGroupByArr());
        $this->assertEquals('a4', $this->report->getPaper());
        $this->assertEquals('portrait', $this->report->getOrientation());
        $this->assertEquals([], $this->report->getEditColumns());
        $this->assertTrue($this->report->getShowNumColumn());
        $this->assertEquals([], $this->report->getShowTotalColumns());
        $this->assertEquals([], $this->report->getStyles());
        $this->assertFalse($this->report->getSimpleVersion());
        $this->assertFalse($this->report->getWithoutManipulation());
        $this->assertTrue($this->report->getShowMeta());
        $this->assertTrue($this->report->getShowHeader());
    }

    public function test_apply_flush_reads_config()
    {
        config(['report-generator.flush' => true]);
        $report = new ConcreteReportGenerator;
        $this->assertTrue($report->getApplyFlush());

        config(['report-generator.flush' => false]);
        $report = new ConcreteReportGenerator;
        $this->assertFalse($report->getApplyFlush());
    }

    public function test_of_sets_title_meta_query_and_columns()
    {
        $query = $this->mockQueryBuilder();
        $result = $this->report->of('Test Title', ['Period' => 'Jan'], $query, ['Name' => 'name', 'Email' => 'email']);

        $this->assertSame($this->report, $result);
        $this->assertEquals('Test Title', $this->report->getHeaders()['title']);
        $this->assertEquals(['Period' => 'Jan'], $this->report->getHeaders()['meta']);
        $this->assertSame($query, $this->report->getQuery());
        $this->assertEquals(['Name' => 'name', 'Email' => 'email'], $this->report->getColumns());
    }

    public function test_of_maps_integer_keys_to_snake_case()
    {
        $query = $this->mockQueryBuilder();
        $this->report->of('Title', [], $query, ['FirstName', 'LastName']);

        $columns = $this->report->getColumns();
        $this->assertEquals(['FirstName' => 'first_name', 'LastName' => 'last_name'], $columns);
    }

    public function test_of_maps_mixed_keys()
    {
        $query = $this->mockQueryBuilder();
        $this->report->of('Title', [], $query, ['Name' => 'name', 'CreatedAt']);

        $columns = $this->report->getColumns();
        $this->assertEquals(['Name' => 'name', 'CreatedAt' => 'created_at'], $columns);
    }

    public function test_of_maps_closure_columns()
    {
        $query = $this->mockQueryBuilder();
        $closure = function ($result) {
            return $result->first.' '.$result->last;
        };
        $this->report->of('Title', [], $query, ['Full Name' => $closure]);

        $columns = $this->report->getColumns();
        $this->assertSame($closure, $columns['Full Name']);
    }

    public function test_show_header()
    {
        $result = $this->report->showHeader(false);
        $this->assertSame($this->report, $result);
        $this->assertFalse($this->report->getShowHeader());

        $this->report->showHeader();
        $this->assertTrue($this->report->getShowHeader());
    }

    public function test_show_meta()
    {
        $result = $this->report->showMeta(false);
        $this->assertSame($this->report, $result);
        $this->assertFalse($this->report->getShowMeta());

        $this->report->showMeta();
        $this->assertTrue($this->report->getShowMeta());
    }

    public function test_show_num_column()
    {
        $result = $this->report->showNumColumn(false);
        $this->assertSame($this->report, $result);
        $this->assertFalse($this->report->getShowNumColumn());

        $this->report->showNumColumn();
        $this->assertTrue($this->report->getShowNumColumn());
    }

    public function test_simple()
    {
        $result = $this->report->simple();
        $this->assertSame($this->report, $result);
        $this->assertTrue($this->report->getSimpleVersion());
    }

    public function test_without_manipulation()
    {
        $result = $this->report->withoutManipulation();
        $this->assertSame($this->report, $result);
        $this->assertTrue($this->report->getWithoutManipulation());
    }

    public function test_set_paper()
    {
        $result = $this->report->setPaper('Letter');
        $this->assertSame($this->report, $result);
        $this->assertEquals('letter', $this->report->getPaper());
    }

    public function test_set_orientation()
    {
        $result = $this->report->setOrientation('Landscape');
        $this->assertSame($this->report, $result);
        $this->assertEquals('landscape', $this->report->getOrientation());
    }

    public function test_edit_column()
    {
        $result = $this->report->editColumn('Price', ['class' => 'right', 'displayAs' => function ($r) {
            return '$'.$r->price;
        }]);
        $this->assertSame($this->report, $result);

        $editColumns = $this->report->getEditColumns();
        $this->assertArrayHasKey('Price', $editColumns);
        $this->assertEquals('right', $editColumns['Price']['class']);
        $this->assertInstanceOf(\Closure::class, $editColumns['Price']['displayAs']);
    }

    public function test_edit_columns_applies_to_multiple()
    {
        $result = $this->report->editColumns(['Price', 'Tax'], ['class' => 'right']);
        $this->assertSame($this->report, $result);

        $editColumns = $this->report->getEditColumns();
        $this->assertArrayHasKey('Price', $editColumns);
        $this->assertArrayHasKey('Tax', $editColumns);
        $this->assertEquals('right', $editColumns['Price']['class']);
        $this->assertEquals('right', $editColumns['Tax']['class']);
    }

    public function test_show_total()
    {
        $result = $this->report->showTotal(['Price' => 'point', 'Quantity' => 'sum']);
        $this->assertSame($this->report, $result);
        $this->assertEquals(['Price' => 'point', 'Quantity' => 'sum'], $this->report->getShowTotalColumns());
    }

    public function test_group_by_string()
    {
        $result = $this->report->groupBy('department');
        $this->assertSame($this->report, $result);
        $this->assertEquals(['department'], $this->report->getGroupByArr());
    }

    public function test_group_by_array()
    {
        $result = $this->report->groupBy(['department', 'city']);
        $this->assertSame($this->report, $result);
        $this->assertEquals(['department', 'city'], $this->report->getGroupByArr());
    }

    public function test_group_by_chained()
    {
        $this->report->groupBy('department')->groupBy('city');
        $this->assertEquals(['department', 'city'], $this->report->getGroupByArr());
    }

    public function test_limit()
    {
        $result = $this->report->limit(50);
        $this->assertSame($this->report, $result);
        $this->assertEquals(50, $this->report->getLimit());
    }

    public function test_set_css()
    {
        $result = $this->report->setCss(['.table' => 'border: 1px solid', 'td' => 'padding: 5px']);
        $this->assertSame($this->report, $result);

        $styles = $this->report->getStyles();
        $this->assertCount(2, $styles);
        $this->assertEquals([
            ['selector' => '.table', 'style' => 'border: 1px solid'],
            ['selector' => 'td', 'style' => 'padding: 5px'],
        ], $styles);
    }

    public function test_set_css_appends()
    {
        $this->report->setCss(['.table' => 'border: 1px solid']);
        $this->report->setCss(['td' => 'padding: 5px']);

        $styles = $this->report->getStyles();
        $this->assertCount(2, $styles);
    }

    public function test_method_chaining()
    {
        $query = $this->mockQueryBuilder();
        $result = $this->report
            ->of('Report', ['Date' => 'Today'], $query, ['Name' => 'name'])
            ->showHeader(true)
            ->showMeta(true)
            ->showNumColumn(false)
            ->setPaper('legal')
            ->setOrientation('landscape')
            ->limit(100)
            ->groupBy('Name')
            ->showTotal(['Name' => 'point'])
            ->editColumn('Name', ['class' => 'bold'])
            ->setCss(['.header' => 'color: blue']);

        $this->assertSame($this->report, $result);
        $this->assertEquals('legal', $this->report->getPaper());
        $this->assertEquals('landscape', $this->report->getOrientation());
        $this->assertEquals(100, $this->report->getLimit());
    }
}
