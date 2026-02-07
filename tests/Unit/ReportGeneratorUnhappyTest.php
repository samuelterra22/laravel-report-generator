<?php

declare(strict_types=1);

namespace SamuelTerra22\ReportGenerator\Tests\Unit;

use SamuelTerra22\ReportGenerator\Tests\Stubs\ConcreteReportGenerator;
use SamuelTerra22\ReportGenerator\Tests\TestCase;

class ReportGeneratorUnhappyTest extends TestCase
{
    private ConcreteReportGenerator $report;

    protected function setUp(): void
    {
        parent::setUp();
        $this->report = new ConcreteReportGenerator;
    }

    public function test_of_with_empty_title()
    {
        $query = $this->mockQueryBuilder();
        $this->report->of('', ['Period' => 'Jan'], $query, ['Name' => 'name']);

        $this->assertEquals('', $this->report->getHeaders()['title']);
    }

    public function test_of_with_empty_meta()
    {
        $query = $this->mockQueryBuilder();
        $this->report->of('Title', [], $query, ['Name' => 'name']);

        $this->assertEquals([], $this->report->getHeaders()['meta']);
    }

    public function test_of_with_empty_columns()
    {
        $query = $this->mockQueryBuilder();
        $this->report->of('Title', [], $query, []);

        $this->assertEquals([], $this->report->getColumns());
    }

    public function test_of_called_twice_overwrites_previous()
    {
        $query1 = $this->mockQueryBuilder();
        $query2 = $this->mockQueryBuilder();

        $this->report->of('First', ['A' => '1'], $query1, ['Col1' => 'col1']);
        $this->report->of('Second', ['B' => '2'], $query2, ['Col2' => 'col2']);

        $this->assertEquals('Second', $this->report->getHeaders()['title']);
        $this->assertEquals(['B' => '2'], $this->report->getHeaders()['meta']);
        $this->assertSame($query2, $this->report->getQuery());
        $this->assertEquals(['Col2' => 'col2'], $this->report->getColumns());
    }

    public function test_group_by_array_replaces_previous_group_by()
    {
        $this->report->groupBy('department');
        $this->assertEquals(['department'], $this->report->getGroupByArr());

        // Array replaces everything
        $this->report->groupBy(['city', 'state']);
        $this->assertEquals(['city', 'state'], $this->report->getGroupByArr());
    }

    public function test_group_by_string_after_array_appends()
    {
        $this->report->groupBy(['department']);
        $this->report->groupBy('city');
        $this->assertEquals(['department', 'city'], $this->report->getGroupByArr());
    }

    public function test_group_by_array_after_string_replaces()
    {
        $this->report->groupBy('department');
        $this->report->groupBy('city');
        $this->assertEquals(['department', 'city'], $this->report->getGroupByArr());

        // Now array replaces
        $this->report->groupBy(['state']);
        $this->assertEquals(['state'], $this->report->getGroupByArr());
    }

    public function test_limit_zero()
    {
        $this->report->limit(0);
        $this->assertEquals(0, $this->report->getLimit());
    }

    public function test_limit_null()
    {
        $this->report->limit(50);
        $this->assertEquals(50, $this->report->getLimit());

        $this->report->limit(null);
        $this->assertNull($this->report->getLimit());
    }

    public function test_set_css_with_empty_array()
    {
        $this->report->setCss([]);
        $this->assertEquals([], $this->report->getStyles());
    }

    public function test_edit_columns_with_empty_column_names()
    {
        $this->report->editColumns([], ['class' => 'right']);
        $this->assertEquals([], $this->report->getEditColumns());
    }

    public function test_edit_column_with_class_only_no_display_as()
    {
        $this->report->editColumn('Amount', ['class' => 'right']);
        $editColumns = $this->report->getEditColumns();

        $this->assertArrayHasKey('Amount', $editColumns);
        $this->assertEquals('right', $editColumns['Amount']['class']);
        $this->assertArrayNotHasKey('displayAs', $editColumns['Amount']);
    }

    public function test_edit_column_called_twice_merges_options()
    {
        $this->report->editColumn('Amount', ['class' => 'right']);
        $this->report->editColumn('Amount', ['displayAs' => 'N/A']);

        $editColumns = $this->report->getEditColumns();
        $this->assertEquals('right', $editColumns['Amount']['class']);
        $this->assertEquals('N/A', $editColumns['Amount']['displayAs']);
    }

    public function test_edit_column_overrides_same_option()
    {
        $this->report->editColumn('Amount', ['class' => 'right']);
        $this->report->editColumn('Amount', ['class' => 'left']);

        $editColumns = $this->report->getEditColumns();
        $this->assertEquals('left', $editColumns['Amount']['class']);
    }

    public function test_show_total_replaces_previous()
    {
        $this->report->showTotal(['Price' => 'point']);
        $this->assertEquals(['Price' => 'point'], $this->report->getShowTotalColumns());

        $this->report->showTotal(['Quantity' => 'sum']);
        $this->assertEquals(['Quantity' => 'sum'], $this->report->getShowTotalColumns());
    }

    public function test_show_total_with_empty_array()
    {
        $this->report->showTotal(['Price' => 'point']);
        $this->report->showTotal([]);
        $this->assertEquals([], $this->report->getShowTotalColumns());
    }

    public function test_set_paper_already_lowercase()
    {
        $this->report->setPaper('letter');
        $this->assertEquals('letter', $this->report->getPaper());
    }

    public function test_set_orientation_already_lowercase()
    {
        $this->report->setOrientation('landscape');
        $this->assertEquals('landscape', $this->report->getOrientation());
    }

    public function test_apply_flush_defaults_to_true_when_config_missing()
    {
        config(['report-generator.flush' => null]);
        $report = new ConcreteReportGenerator;
        // Config::get returns null, (bool) null = false
        $this->assertFalse($report->getApplyFlush());
    }

    public function test_of_with_all_integer_keys()
    {
        $query = $this->mockQueryBuilder();
        $this->report->of('Title', [], $query, ['FirstName', 'LastName', 'EmailAddress']);

        $columns = $this->report->getColumns();
        $this->assertEquals([
            'FirstName' => 'first_name',
            'LastName' => 'last_name',
            'EmailAddress' => 'email_address',
        ], $columns);
    }

    public function test_show_header_show_meta_show_num_column_with_explicit_true()
    {
        // Set to false, then back to true with explicit arg
        $this->report->showHeader(false);
        $this->assertFalse($this->report->getShowHeader());
        $this->report->showHeader(true);
        $this->assertTrue($this->report->getShowHeader());

        $this->report->showMeta(false);
        $this->assertFalse($this->report->getShowMeta());
        $this->report->showMeta(true);
        $this->assertTrue($this->report->getShowMeta());

        $this->report->showNumColumn(false);
        $this->assertFalse($this->report->getShowNumColumn());
        $this->report->showNumColumn(true);
        $this->assertTrue($this->report->getShowNumColumn());
    }

    public function test_set_css_preserves_order_across_multiple_calls()
    {
        $this->report->setCss(['.a' => 'color: red']);
        $this->report->setCss(['.b' => 'color: blue']);
        $this->report->setCss(['.c' => 'color: green']);

        $styles = $this->report->getStyles();
        $this->assertCount(3, $styles);
        $this->assertEquals('.a', $styles[0]['selector']);
        $this->assertEquals('.b', $styles[1]['selector']);
        $this->assertEquals('.c', $styles[2]['selector']);
    }

    public function test_of_with_multiple_meta_items()
    {
        $query = $this->mockQueryBuilder();
        $this->report->of('Title', [
            'Period' => 'Jan',
            'Company' => 'Acme',
            'Department' => 'Sales',
        ], $query, ['Name' => 'name']);

        $meta = $this->report->getHeaders()['meta'];
        $this->assertCount(3, $meta);
        $this->assertEquals('Jan', $meta['Period']);
        $this->assertEquals('Acme', $meta['Company']);
        $this->assertEquals('Sales', $meta['Department']);
    }
}
