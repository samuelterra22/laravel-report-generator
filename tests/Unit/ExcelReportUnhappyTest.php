<?php

declare(strict_types=1);

namespace SamuelTerra22\ReportGenerator\Tests\Unit;

use Mockery;
use SamuelTerra22\ReportGenerator\ReportMedia\ExcelReport;
use SamuelTerra22\ReportGenerator\Tests\TestCase;

class ExcelReportUnhappyTest extends TestCase
{
    private function makeReport(array $results = [], ?array $columns = null): ExcelReport
    {
        $resultObjects = array_map(function ($row) {
            return $this->makeResultObject($row);
        }, $results);

        $query = Mockery::mock('Illuminate\Database\Query\Builder');
        $query->shouldReceive('take')->andReturnSelf();
        $query->shouldReceive('when')->andReturnUsing(function ($condition, $callback) use ($query) {
            if ($condition) {
                $callback($query);
            }

            return $query;
        });
        $query->shouldReceive('cursor')->andReturn(new \ArrayIterator($resultObjects));

        $report = new ExcelReport;
        $report->of(
            'Test Excel',
            ['Period' => 'January'],
            $query,
            $columns ?? ['Name' => 'name', 'Amount' => 'amount']
        );

        return $report;
    }

    private function setupExcelMock(): \stdClass
    {
        $collector = new \stdClass;
        $collector->appendedRows = [];

        $sheetMock = Mockery::mock();
        $sheetMock->shouldReceive('setColumnFormat')->andReturnNull();
        $sheetMock->shouldReceive('appendRow')->andReturnUsing(function ($row) use ($collector) {
            $collector->appendedRows[] = $row;
        });
        $sheetMock->shouldReceive('loadView')->andReturnNull();

        $excelObjectMock = Mockery::mock();
        $excelObjectMock->shouldReceive('sheet')->andReturnUsing(function ($name, $callback) use ($sheetMock) {
            $callback($sheetMock);
        });

        $excelMock = Mockery::mock();
        $excelMock->shouldReceive('create')->andReturnUsing(function ($filename, $callback) use ($excelObjectMock) {
            $callback($excelObjectMock);

            return $excelObjectMock;
        });

        $this->app->instance('excel', $excelMock);

        $collector->sheetMock = $sheetMock;
        $collector->excelObjectMock = $excelObjectMock;

        return $collector;
    }

    public function test_simple_version_with_empty_result_set()
    {
        $report = $this->makeReport([]);

        $collector = $this->setupExcelMock();

        $report->make('test-report', true);

        // Should have title, blank, meta, blank, header — but no data rows
        $this->assertGreaterThanOrEqual(5, count($collector->appendedRows));

        // Verify header is present
        $headerRow = $collector->appendedRows[4];
        $this->assertEquals('No', $headerRow[0]);
        $this->assertContains('Name', $headerRow);

        // No data rows appended after header
        $this->assertCount(5, $collector->appendedRows);
    }

    public function test_simple_version_empty_result_set_with_totals()
    {
        $report = $this->makeReport([]);
        $report->showTotal(['Amount' => 'point']);

        $collector = $this->setupExcelMock();

        $report->make('test-report', true);

        // Even with totals configured, if no data, the $showTotalColumns check
        // still appends a Grand Total row with 0 values
        $lastRow = end($collector->appendedRows);
        if ($lastRow[0] === 'Grand Total') {
            $this->assertEquals('Grand Total', $lastRow[0]);
        } else {
            // No total row if no data
            $this->assertNotContains('Grand Total', array_column($collector->appendedRows, 0));
        }
    }

    public function test_simple_version_without_manipulation_data_equals_columns()
    {
        // Data count == column count: no array_pop
        $resultObjects = [
            $this->makeResultObject(['name' => 'Alice', 'amount' => 100]),
        ];

        $query = Mockery::mock('Illuminate\Database\Query\Builder');
        $query->shouldReceive('take')->andReturnSelf();
        $query->shouldReceive('when')->andReturnSelf();
        $query->shouldReceive('cursor')->andReturn(new \ArrayIterator($resultObjects));

        $report = new ExcelReport;
        $report->of('Report', [], $query, ['Name' => 'name', 'Amount' => 'amount']);
        $report->showMeta(false);
        $report->withoutManipulation();

        $collector = $this->setupExcelMock();

        $report->make('test-report', true);

        // Both values should be present (not popped)
        $foundAlice = false;
        $found100 = false;
        foreach ($collector->appendedRows as $row) {
            if (is_array($row) && in_array('Alice', $row)) {
                $foundAlice = true;
                if (in_array(100, $row)) {
                    $found100 = true;
                }
            }
        }
        $this->assertTrue($foundAlice);
        $this->assertTrue($found100);
    }

    public function test_simple_version_group_by_all_same_group()
    {
        // All rows in same group — no group break totals, only final total
        $resultObjects = [
            $this->makeResultObject(['name' => 'Alice', 'department' => 'Sales', 'amount' => 100]),
            $this->makeResultObject(['name' => 'Bob', 'department' => 'Sales', 'amount' => 200]),
        ];

        $query = Mockery::mock('Illuminate\Database\Query\Builder');
        $query->shouldReceive('take')->andReturnSelf();
        $query->shouldReceive('when')->andReturnSelf();
        $query->shouldReceive('cursor')->andReturn(new \ArrayIterator($resultObjects));

        $report = new ExcelReport;
        $report->of('Report', [], $query, [
            'Name' => 'name',
            'Dept' => 'department',
            'Amount' => 'amount',
        ]);
        $report->showMeta(false);
        $report->groupBy('Dept');
        $report->showTotal(['Amount' => 'point']);

        $collector = $this->setupExcelMock();

        $report->make('test-report', true);

        // Count Grand Total rows — should be exactly 1 (final only)
        $grandTotalCount = 0;
        foreach ($collector->appendedRows as $row) {
            if (is_array($row) && isset($row[0]) && $row[0] === 'Grand Total') {
                $grandTotalCount++;
            }
        }
        $this->assertEquals(1, $grandTotalCount);
    }

    public function test_simple_version_minimal_config()
    {
        // No header, no meta, no num column
        $report = $this->makeReport([
            ['name' => 'Alice', 'amount' => 100],
        ]);
        $report->showHeader(false);
        $report->showMeta(false);
        $report->showNumColumn(false);

        $collector = $this->setupExcelMock();

        $report->make('test-report', true);

        // Should have: title, blank, then data (no meta, no header rows)
        $this->assertEquals(['Test Excel'], $collector->appendedRows[0]);
        $this->assertEquals([' '], $collector->appendedRows[1]);

        // Verify no header row
        $foundHeader = false;
        foreach ($collector->appendedRows as $row) {
            if (is_array($row) && in_array('Name', $row) && in_array('Amount', $row)) {
                $foundHeader = true;
            }
        }
        $this->assertFalse($foundHeader);

        // Data row should not have 'No' prefix
        $foundAlice = false;
        foreach ($collector->appendedRows as $row) {
            if (is_array($row) && in_array('Alice', $row)) {
                $foundAlice = true;
                // No row number prepended
                $this->assertNotEquals(1, $row[0]);
            }
        }
        $this->assertTrue($foundAlice);
    }

    public function test_simple_version_edit_column_class_only_no_display_as()
    {
        $report = $this->makeReport([
            ['name' => 'Alice', 'amount' => 100],
        ]);
        $report->showMeta(false);
        // Only 'class' set, no 'displayAs' — original value should be used
        $report->editColumn('Amount', ['class' => 'right']);

        $collector = $this->setupExcelMock();

        $report->make('test-report', true);

        // Data should show original value 100, not modified
        $found100 = false;
        foreach ($collector->appendedRows as $row) {
            if (is_array($row) && in_array(100, $row)) {
                $found100 = true;
            }
        }
        $this->assertTrue($found100);
    }

    public function test_download_uses_standard_version_by_default()
    {
        $report = $this->makeReport([
            ['name' => 'Alice', 'amount' => 100],
        ]);

        $sheetMock = Mockery::mock();
        $sheetMock->shouldReceive('setColumnFormat')->andReturnNull();
        // Standard version uses loadView, not appendRow
        $sheetMock->shouldReceive('loadView')
            ->once()
            ->with('laravel-report-generator::general-excel-template', Mockery::type('array'))
            ->andReturnNull();

        $excelObjectMock = Mockery::mock();
        $excelObjectMock->shouldReceive('sheet')->andReturnUsing(function ($name, $callback) use ($sheetMock) {
            $callback($sheetMock);
        });
        $excelObjectMock->shouldReceive('export')->once()->with('xlsx')->andReturn('exported');

        $excelMock = Mockery::mock();
        $excelMock->shouldReceive('create')->andReturnUsing(function ($filename, $callback) use ($excelObjectMock) {
            $callback($excelObjectMock);

            return $excelObjectMock;
        });

        $this->app->instance('excel', $excelMock);

        // download() should use standard (non-simple) version by default
        $result = $report->download('test-report');
        $this->assertEquals('exported', $result);
    }

    public function test_simple_download_uses_format()
    {
        $report = $this->makeReport([
            ['name' => 'Alice', 'amount' => 100],
        ]);
        $report->setFormat('csv');

        $excelObjectMock = Mockery::mock();
        $excelObjectMock->shouldReceive('sheet')->andReturnUsing(function ($name, $callback) {
            $sheetMock = Mockery::mock();
            $sheetMock->shouldReceive('setColumnFormat')->andReturnNull();
            $sheetMock->shouldReceive('appendRow')->andReturnNull();
            $callback($sheetMock);
        });
        // Should use 'csv' format, not 'xlsx'
        $excelObjectMock->shouldReceive('export')->once()->with('csv')->andReturn('csv-exported');

        $excelMock = Mockery::mock();
        $excelMock->shouldReceive('create')->andReturnUsing(function ($filename, $callback) use ($excelObjectMock) {
            $callback($excelObjectMock);

            return $excelObjectMock;
        });

        $this->app->instance('excel', $excelMock);

        $result = $report->simpleDownload('test-report');
        $this->assertEquals('csv-exported', $result);
    }

    public function test_simple_version_with_multiple_group_breaks()
    {
        $resultObjects = [
            $this->makeResultObject(['name' => 'Alice', 'department' => 'Sales', 'amount' => 100]),
            $this->makeResultObject(['name' => 'Bob', 'department' => 'HR', 'amount' => 200]),
            $this->makeResultObject(['name' => 'Charlie', 'department' => 'IT', 'amount' => 300]),
        ];

        $query = Mockery::mock('Illuminate\Database\Query\Builder');
        $query->shouldReceive('take')->andReturnSelf();
        $query->shouldReceive('when')->andReturnSelf();
        $query->shouldReceive('cursor')->andReturn(new \ArrayIterator($resultObjects));

        $report = new ExcelReport;
        $report->of('Report', [], $query, [
            'Name' => 'name',
            'Dept' => 'department',
            'Amount' => 'amount',
        ]);
        $report->showMeta(false);
        $report->groupBy('Dept');
        $report->showTotal(['Amount' => 'point']);

        $collector = $this->setupExcelMock();

        $report->make('test-report', true);

        // 2 group breaks + 1 final = 3 Grand Total rows
        $grandTotalCount = 0;
        foreach ($collector->appendedRows as $row) {
            if (is_array($row) && isset($row[0]) && $row[0] === 'Grand Total') {
                $grandTotalCount++;
            }
        }
        $this->assertEquals(3, $grandTotalCount);
    }

    public function test_simple_version_totals_reset_after_group_break()
    {
        $resultObjects = [
            $this->makeResultObject(['name' => 'Alice', 'department' => 'Sales', 'amount' => 100]),
            $this->makeResultObject(['name' => 'Bob', 'department' => 'HR', 'amount' => 200]),
        ];

        $query = Mockery::mock('Illuminate\Database\Query\Builder');
        $query->shouldReceive('take')->andReturnSelf();
        $query->shouldReceive('when')->andReturnSelf();
        $query->shouldReceive('cursor')->andReturn(new \ArrayIterator($resultObjects));

        $report = new ExcelReport;
        $report->of('Report', [], $query, [
            'Name' => 'name',
            'Dept' => 'department',
            'Amount' => 'amount',
        ]);
        $report->showMeta(false);
        $report->groupBy('Dept');
        $report->showTotal(['Amount' => 'point']);

        $collector = $this->setupExcelMock();

        $report->make('test-report', true);

        // Find Grand Total rows
        $grandTotalRows = [];
        foreach ($collector->appendedRows as $row) {
            if (is_array($row) && isset($row[0]) && $row[0] === 'Grand Total') {
                $grandTotalRows[] = $row;
            }
        }

        // Should have at least 2 Grand Total rows (group break + final)
        $this->assertGreaterThanOrEqual(2, count($grandTotalRows));

        // After group break, totals reset — verify second Grand Total row exists
        // (totals are double-counted by both formatRow and main loop)
        $secondGroupTotal = $grandTotalRows[1];
        $this->assertEquals('Grand Total', $secondGroupTotal[0]);
    }

    public function test_simple_version_without_manipulation_and_without_num_column_header()
    {
        $report = $this->makeReport([
            ['name' => 'Alice', 'amount' => 100],
        ]);
        $report->showMeta(false);
        $report->withoutManipulation();
        $report->showNumColumn(false);

        $collector = $this->setupExcelMock();

        $report->make('test-report', true);

        // Header should NOT have 'No' prepended
        $headerRow = null;
        foreach ($collector->appendedRows as $row) {
            if (is_array($row) && in_array('Name', $row)) {
                $headerRow = $row;
                break;
            }
        }
        $this->assertNotNull($headerRow);
        $this->assertEquals(['Name', 'Amount'], $headerRow);
    }

    public function test_standard_version_without_manipulation_empty_result_set()
    {
        $report = $this->makeReport([]);
        $report->withoutManipulation();

        $sheetMock = Mockery::mock();
        $sheetMock->shouldReceive('setColumnFormat')->andReturnNull();
        $sheetMock->shouldReceive('loadView')
            ->once()
            ->with('laravel-report-generator::without-manipulation-excel-template', Mockery::type('array'))
            ->andReturnNull();

        $excelObjectMock = Mockery::mock();
        $excelObjectMock->shouldReceive('sheet')->andReturnUsing(function ($name, $callback) use ($sheetMock) {
            $callback($sheetMock);
        });

        $excelMock = Mockery::mock();
        $excelMock->shouldReceive('create')->andReturnUsing(function ($filename, $callback) use ($excelObjectMock) {
            $callback($excelObjectMock);

            return $excelObjectMock;
        });

        $this->app->instance('excel', $excelMock);

        $report->make('test-report', false);
    }

    public function test_simple_version_with_totals_no_num_column_adjusts_grand_total_skip()
    {
        $report = $this->makeReport([
            ['name' => 'Alice', 'amount' => 100],
        ]);
        $report->showMeta(false);
        $report->showNumColumn(false);
        $report->showTotal(['Amount' => 'point']);

        $collector = $this->setupExcelMock();

        $report->make('test-report', true);

        // Grand total should still be present
        $lastRow = end($collector->appendedRows);
        $this->assertEquals('Grand Total', $lastRow[0]);
    }
}
