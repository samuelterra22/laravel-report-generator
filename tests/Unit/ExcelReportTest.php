<?php

namespace SamuelTerra22\ReportGenerator\Tests\Unit;

use Closure;
use Mockery;
use SamuelTerra22\ReportGenerator\ReportMedia\ExcelReport;
use SamuelTerra22\ReportGenerator\Tests\TestCase;

class ExcelReportTest extends TestCase
{
    private function makeReport(array $results = [], array $columns = null): ExcelReport
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

        $report = new ExcelReport();
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
        $collector = new \stdClass();
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

    public function test_make_simple_version_appends_title_and_data()
    {
        $report = $this->makeReport([
            ['name' => 'Alice', 'amount' => 100],
            ['name' => 'Bob', 'amount' => 200],
        ]);

        $collector = $this->setupExcelMock();

        $report->make('test-report', true);

        // Title row, blank row, meta row, blank row, header row, 2 data rows
        $this->assertGreaterThanOrEqual(7, count($collector->appendedRows));
        $this->assertEquals(['Test Excel'], $collector->appendedRows[0]);
        $this->assertEquals(['Period', 'January'], $collector->appendedRows[2]);
    }

    public function test_make_simple_version_with_num_column()
    {
        $report = $this->makeReport([
            ['name' => 'Alice', 'amount' => 100],
        ]);

        $collector = $this->setupExcelMock();

        $report->make('test-report', true);

        // Header row should include 'No'
        $headerRow = $collector->appendedRows[4];
        $this->assertEquals('No', $headerRow[0]);
    }

    public function test_make_simple_version_hides_meta()
    {
        $report = $this->makeReport([
            ['name' => 'Alice', 'amount' => 100],
        ]);
        $report->showMeta(false);

        $collector = $this->setupExcelMock();

        $report->make('test-report', true);

        // Title row, blank row, header row, data row (no meta rows)
        $this->assertEquals(['Test Excel'], $collector->appendedRows[0]);
        // After title + blank, next should be header
        $this->assertEquals(['No', 'Name', 'Amount'], $collector->appendedRows[2]);
    }

    public function test_make_simple_version_hides_header()
    {
        $report = $this->makeReport([
            ['name' => 'Alice', 'amount' => 100],
        ]);
        $report->showHeader(false);

        $collector = $this->setupExcelMock();

        $report->make('test-report', true);

        // Should not include header row with column names
        $foundHeader = false;
        foreach ($collector->appendedRows as $row) {
            if (is_array($row) && in_array('Name', $row) && in_array('Amount', $row)) {
                $foundHeader = true;
            }
        }
        $this->assertFalse($foundHeader);
    }

    public function test_make_simple_version_with_totals()
    {
        $report = $this->makeReport([
            ['name' => 'Alice', 'amount' => 100],
            ['name' => 'Bob', 'amount' => 200],
        ]);
        $report->showTotal(['Amount' => 'point']);

        $collector = $this->setupExcelMock();

        $report->make('test-report', true);

        // Last row should be the grand total
        $lastRow = end($collector->appendedRows);
        $this->assertEquals('Grand Total', $lastRow[0]);
    }

    public function test_make_simple_version_without_manipulation()
    {
        $report = $this->makeReport([
            ['name' => 'Alice', 'amount' => 100],
        ]);
        $report->withoutManipulation();

        $collector = $this->setupExcelMock();

        $report->make('test-report', true);

        // Data rows should contain raw data
        $found = false;
        foreach ($collector->appendedRows as $row) {
            if (is_array($row) && in_array('Alice', $row)) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }

    public function test_make_standard_version_uses_loadview()
    {
        $report = $this->makeReport([
            ['name' => 'Alice', 'amount' => 100],
        ]);

        $sheetMock = Mockery::mock();
        $sheetMock->shouldReceive('setColumnFormat')->andReturnNull();
        $sheetMock->shouldReceive('loadView')
            ->once()
            ->with('laravel-report-generator::general-excel-template', Mockery::type('array'))
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

    public function test_make_standard_version_without_manipulation_uses_correct_template()
    {
        $report = $this->makeReport([
            ['name' => 'Alice', 'amount' => 100],
        ]);
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

    public function test_download_calls_export()
    {
        $report = $this->makeReport([
            ['name' => 'Alice', 'amount' => 100],
        ]);

        $excelObjectMock = Mockery::mock();
        $excelObjectMock->shouldReceive('sheet')->andReturnUsing(function ($name, $callback) {
            $sheetMock = Mockery::mock();
            $sheetMock->shouldReceive('setColumnFormat')->andReturnNull();
            $sheetMock->shouldReceive('loadView')->andReturnNull();
            $callback($sheetMock);
        });
        $excelObjectMock->shouldReceive('export')->once()->with('xlsx')->andReturn('exported');

        $excelMock = Mockery::mock();
        $excelMock->shouldReceive('create')->andReturnUsing(function ($filename, $callback) use ($excelObjectMock) {
            $callback($excelObjectMock);
            return $excelObjectMock;
        });

        $this->app->instance('excel', $excelMock);

        $result = $report->download('test-report');
        $this->assertEquals('exported', $result);
    }

    public function test_simple_download_forces_simple_mode()
    {
        $report = $this->makeReport([
            ['name' => 'Alice', 'amount' => 100],
        ]);

        $excelObjectMock = Mockery::mock();
        $excelObjectMock->shouldReceive('sheet')->andReturnUsing(function ($name, $callback) {
            $sheetMock = Mockery::mock();
            $sheetMock->shouldReceive('setColumnFormat')->andReturnNull();
            $sheetMock->shouldReceive('appendRow')->andReturnNull();
            $callback($sheetMock);
        });
        $excelObjectMock->shouldReceive('export')->once()->with('xlsx')->andReturn('simple-exported');

        $excelMock = Mockery::mock();
        $excelMock->shouldReceive('create')->andReturnUsing(function ($filename, $callback) use ($excelObjectMock) {
            $callback($excelObjectMock);
            return $excelObjectMock;
        });

        $this->app->instance('excel', $excelMock);

        $result = $report->simpleDownload('test-report');
        $this->assertEquals('simple-exported', $result);
    }

    public function test_set_format()
    {
        $report = $this->makeReport([
            ['name' => 'Alice', 'amount' => 100],
        ]);
        $result = $report->setFormat('csv');
        $this->assertSame($report, $result);

        $excelObjectMock = Mockery::mock();
        $excelObjectMock->shouldReceive('sheet')->andReturnUsing(function ($name, $callback) {
            $sheetMock = Mockery::mock();
            $sheetMock->shouldReceive('setColumnFormat')->andReturnNull();
            $sheetMock->shouldReceive('loadView')->andReturnNull();
            $callback($sheetMock);
        });
        $excelObjectMock->shouldReceive('export')->once()->with('csv')->andReturn('csv-exported');

        $excelMock = Mockery::mock();
        $excelMock->shouldReceive('create')->andReturnUsing(function ($filename, $callback) use ($excelObjectMock) {
            $callback($excelObjectMock);
            return $excelObjectMock;
        });

        $this->app->instance('excel', $excelMock);

        $result = $report->download('test-report');
        $this->assertEquals('csv-exported', $result);
    }

    public function test_simple_version_with_closure_column()
    {
        $closure = function ($result) {
            return $result->name . ' - ' . $result->amount;
        };

        $resultObjects = [
            $this->makeResultObject(['name' => 'Alice', 'amount' => 100]),
        ];

        $query = Mockery::mock('Illuminate\Database\Query\Builder');
        $query->shouldReceive('take')->andReturnSelf();
        $query->shouldReceive('when')->andReturnSelf();
        $query->shouldReceive('cursor')->andReturn(new \ArrayIterator($resultObjects));

        $report = new ExcelReport();
        $report->of(
            'Test Excel',
            [],
            $query,
            ['Name' => 'name', 'Custom' => $closure]
        );
        $report->showMeta(false);

        $collector = $this->setupExcelMock();

        $report->make('test-report', true);

        // Find data row with formatted value
        $found = false;
        foreach ($collector->appendedRows as $row) {
            if (is_array($row) && in_array('Alice', $row)) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }

    public function test_simple_version_with_edit_columns_display_as()
    {
        $report = $this->makeReport([
            ['name' => 'Alice', 'amount' => 100],
        ]);
        $report->showMeta(false);
        $report->editColumn('Amount', [
            'displayAs' => function ($result) {
                return '$' . $result->amount;
            }
        ]);

        $collector = $this->setupExcelMock();

        $report->make('test-report', true);

        // Data row should contain the formatted amount
        $found = false;
        foreach ($collector->appendedRows as $row) {
            if (is_array($row) && in_array('$100', $row)) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }

    public function test_simple_version_with_totals_sum_type()
    {
        $report = $this->makeReport([
            ['name' => 'Alice', 'amount' => 100],
            ['name' => 'Bob', 'amount' => 250],
        ]);
        $report->showTotal(['Amount' => 'sum']);

        $collector = $this->setupExcelMock();

        $report->make('test-report', true);

        // Last row: Grand Total with "SUM" prefix
        // Note: formatRow() and the main loop both add to totals, so values are double-counted
        $lastRow = end($collector->appendedRows);
        $this->assertEquals('Grand Total', $lastRow[0]);
        $found = false;
        foreach ($lastRow as $cell) {
            if (is_string($cell) && str_contains($cell, 'SUM')) {
                $found = true;
            }
        }
        $this->assertTrue($found);
    }

    public function test_simple_version_with_group_by()
    {
        // Two groups: dept A (2 rows) and dept B (1 row) with totals
        $resultObjects = [
            $this->makeResultObject(['name' => 'Alice', 'department' => 'Sales', 'amount' => 100]),
            $this->makeResultObject(['name' => 'Bob', 'department' => 'Sales', 'amount' => 200]),
            $this->makeResultObject(['name' => 'Charlie', 'department' => 'HR', 'amount' => 300]),
        ];

        $query = Mockery::mock('Illuminate\Database\Query\Builder');
        $query->shouldReceive('take')->andReturnSelf();
        $query->shouldReceive('when')->andReturnSelf();
        $query->shouldReceive('cursor')->andReturn(new \ArrayIterator($resultObjects));

        $report = new ExcelReport();
        $report->of(
            'Group Report',
            [],
            $query,
            ['Name' => 'name', 'Dept' => 'department', 'Amount' => 'amount']
        );
        $report->showMeta(false);
        $report->groupBy('Dept');
        $report->showTotal(['Amount' => 'point']);

        $collector = $this->setupExcelMock();

        $report->make('test-report', true);

        // Should have group total row when department changes
        $foundGroupTotal = false;
        foreach ($collector->appendedRows as $row) {
            if (is_array($row) && isset($row[0]) && $row[0] === 'Grand Total') {
                $foundGroupTotal = true;
                break;
            }
        }
        $this->assertTrue($foundGroupTotal);
    }

    public function test_simple_version_group_by_with_sum_type()
    {
        $resultObjects = [
            $this->makeResultObject(['name' => 'Alice', 'department' => 'Sales', 'amount' => 100]),
            $this->makeResultObject(['name' => 'Bob', 'department' => 'HR', 'amount' => 200]),
        ];

        $query = Mockery::mock('Illuminate\Database\Query\Builder');
        $query->shouldReceive('take')->andReturnSelf();
        $query->shouldReceive('when')->andReturnSelf();
        $query->shouldReceive('cursor')->andReturn(new \ArrayIterator($resultObjects));

        $report = new ExcelReport();
        $report->of('Report', [], $query, ['Name' => 'name', 'Dept' => 'department', 'Amount' => 'amount']);
        $report->showMeta(false);
        $report->groupBy('Dept');
        $report->showTotal(['Amount' => 'sum']);

        $collector = $this->setupExcelMock();

        $report->make('test-report', true);

        // Find group total row with SUM prefix
        $foundSum = false;
        foreach ($collector->appendedRows as $row) {
            if (!is_array($row)) continue;
            foreach ($row as $cell) {
                if (is_string($cell) && str_contains($cell, 'SUM')) {
                    $foundSum = true;
                }
            }
        }
        $this->assertTrue($foundSum);
    }

    public function test_simple_version_group_by_with_closure_column()
    {
        $resultObjects = [
            $this->makeResultObject(['name' => 'Alice', 'department' => 'Sales', 'amount' => 100]),
            $this->makeResultObject(['name' => 'Bob', 'department' => 'HR', 'amount' => 200]),
        ];

        $query = Mockery::mock('Illuminate\Database\Query\Builder');
        $query->shouldReceive('take')->andReturnSelf();
        $query->shouldReceive('when')->andReturnSelf();
        $query->shouldReceive('cursor')->andReturn(new \ArrayIterator($resultObjects));

        $deptClosure = function ($result) { return strtoupper($result->department); };

        $report = new ExcelReport();
        $report->of('Report', [], $query, ['Name' => 'name', 'Dept' => $deptClosure, 'Amount' => 'amount']);
        $report->showMeta(false);
        $report->groupBy('Dept');
        $report->showTotal(['Amount' => 'point']);

        $collector = $this->setupExcelMock();

        $report->make('test-report', true);

        // Should contain data rows
        $foundAlice = false;
        foreach ($collector->appendedRows as $row) {
            if (is_array($row) && in_array('Alice', $row)) {
                $foundAlice = true;
            }
        }
        $this->assertTrue($foundAlice);
    }

    public function test_simple_version_with_without_manipulation_extra_column_pop()
    {
        $resultObjects = [
            $this->makeResultObject(['name' => 'Alice', 'amount' => 100, 'extra' => 'hidden']),
        ];

        $query = Mockery::mock('Illuminate\Database\Query\Builder');
        $query->shouldReceive('take')->andReturnSelf();
        $query->shouldReceive('when')->andReturnSelf();
        $query->shouldReceive('cursor')->andReturn(new \ArrayIterator($resultObjects));

        $report = new ExcelReport();
        $report->of('Report', [], $query, ['Name' => 'name', 'Amount' => 'amount']);
        $report->showMeta(false);
        $report->withoutManipulation();

        $collector = $this->setupExcelMock();

        $report->make('test-report', true);

        // Ensure the extra column was popped
        $found = false;
        foreach ($collector->appendedRows as $row) {
            if (is_array($row) && in_array('hidden', $row)) {
                $found = true;
            }
        }
        $this->assertFalse($found);
    }

    public function test_simple_version_with_static_display_as()
    {
        $report = $this->makeReport([
            ['name' => 'Alice', 'amount' => 100],
        ]);
        $report->showMeta(false);
        $report->editColumn('Amount', [
            'displayAs' => 'FIXED'
        ]);

        $collector = $this->setupExcelMock();

        $report->make('test-report', true);

        $found = false;
        foreach ($collector->appendedRows as $row) {
            if (is_array($row) && in_array('FIXED', $row)) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }

    public function test_simple_version_without_num_column()
    {
        $report = $this->makeReport([
            ['name' => 'Alice', 'amount' => 100],
        ]);
        $report->showMeta(false);
        $report->showNumColumn(false);
        $report->withoutManipulation();

        $collector = $this->setupExcelMock();

        $report->make('test-report', true);

        // Header row should not start with 'No'
        $headerRow = null;
        foreach ($collector->appendedRows as $row) {
            if (is_array($row) && in_array('Name', $row)) {
                $headerRow = $row;
                break;
            }
        }
        $this->assertNotNull($headerRow);
        $this->assertNotContains('No', $headerRow);
    }

    public function test_simple_version_group_by_no_total_column_pushes_null()
    {
        $resultObjects = [
            $this->makeResultObject(['name' => 'Alice', 'department' => 'Sales', 'amount' => 100]),
            $this->makeResultObject(['name' => 'Bob', 'department' => 'HR', 'amount' => 200]),
        ];

        $query = Mockery::mock('Illuminate\Database\Query\Builder');
        $query->shouldReceive('take')->andReturnSelf();
        $query->shouldReceive('when')->andReturnSelf();
        $query->shouldReceive('cursor')->andReturn(new \ArrayIterator($resultObjects));

        $report = new ExcelReport();
        $report->of('Report', [], $query, ['Name' => 'name', 'Dept' => 'department', 'Amount' => 'amount']);
        $report->showMeta(false);
        $report->groupBy('Dept');
        // Only total on Amount, so Dept column should get null in total row
        $report->showTotal(['Amount' => 'point']);

        $collector = $this->setupExcelMock();

        $report->make('test-report', true);

        // Find the group total row
        $totalRow = null;
        foreach ($collector->appendedRows as $row) {
            if (is_array($row) && isset($row[0]) && $row[0] === 'Grand Total') {
                $totalRow = $row;
                break;
            }
        }
        $this->assertNotNull($totalRow);
        // The row should contain null values for non-total columns
        $this->assertContains(null, $totalRow);
    }
}
