<?php

declare(strict_types=1);

namespace SamuelTerra22\ReportGenerator\Tests\Unit;

use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use SamuelTerra22\ReportGenerator\ReportMedia\PdfReport;
use SamuelTerra22\ReportGenerator\Tests\TestCase;

class PdfReportUnhappyTest extends TestCase
{
    private function makeQueryWithResults(array $results): \Mockery\MockInterface
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

        return $query;
    }

    private function captureHtml(PdfReport $report): string
    {
        $capturedHtml = '';
        $pdfMock = Mockery::mock();
        $pdfMock->shouldReceive('setOption')->andReturnSelf();
        $pdfMock->shouldReceive('loadHTML')->andReturnUsing(function ($html) use (&$capturedHtml, $pdfMock) {
            $capturedHtml = $html;

            return $pdfMock;
        });
        $pdfMock->shouldReceive('setPaper')->andReturnSelf();

        $this->app->instance('snappy.pdf.wrapper', $pdfMock);

        $report->make();

        return $capturedHtml;
    }

    public function test_make_with_empty_result_set()
    {
        $query = $this->makeQueryWithResults([]);

        $report = new PdfReport;
        $report->of('Empty Report', ['Period' => 'Jan'], $query, ['Name' => 'name', 'Amount' => 'amount']);

        $html = $this->captureHtml($report);

        $this->assertStringContainsString('Empty Report', $html);
        $this->assertStringContainsString('Name', $html);
        $this->assertStringContainsString('Amount', $html);
        // No data rows, no grand total
        $this->assertStringNotContainsString('Grand Total', $html);
    }

    public function test_make_with_empty_result_set_and_totals_configured()
    {
        $query = $this->makeQueryWithResults([]);

        $report = new PdfReport;
        $report->of('Empty Report', [], $query, ['Name' => 'name', 'Amount' => 'amount']);
        $report->showTotal(['Amount' => 'point']);

        $html = $this->captureHtml($report);

        // No data processed, ctr stays at 1, so $ctr > 1 is false — no total row
        $this->assertStringNotContainsString('Grand Total', $html);
    }

    public function test_make_with_odd_number_of_meta_items()
    {
        $query = $this->makeQueryWithResults([
            ['name' => 'Alice', 'amount' => 100],
        ]);

        $report = new PdfReport;
        $report->of('Report', [
            'Period' => 'January',
            'Company' => 'Acme',
            'Department' => 'Sales',
        ], $query, ['Name' => 'name', 'Amount' => 'amount']);

        $html = $this->captureHtml($report);

        // All 3 meta items should render (odd count means last row has only 1 cell)
        $this->assertStringContainsString('Period', $html);
        $this->assertStringContainsString('January', $html);
        $this->assertStringContainsString('Company', $html);
        $this->assertStringContainsString('Acme', $html);
        $this->assertStringContainsString('Department', $html);
        $this->assertStringContainsString('Sales', $html);
    }

    public function test_make_with_totals_sum_type()
    {
        $query = $this->makeQueryWithResults([
            ['name' => 'Alice', 'amount' => 100],
            ['name' => 'Bob', 'amount' => 200],
        ]);

        $report = new PdfReport;
        $report->of('Report', [], $query, ['Name' => 'name', 'Amount' => 'amount']);
        $report->showTotal(['Amount' => 'sum']);

        $html = $this->captureHtml($report);

        $this->assertStringContainsString('Grand Total', $html);
        $this->assertStringContainsString('SUM', $html);
        $this->assertStringContainsString('300.00', $html);
    }

    public function test_make_with_show_num_column_false_and_totals()
    {
        $query = $this->makeQueryWithResults([
            ['name' => 'Alice', 'amount' => 100],
        ]);

        $report = new PdfReport;
        $report->of('Report', [], $query, ['Name' => 'name', 'Amount' => 'amount']);
        $report->showNumColumn(false);
        $report->showTotal(['Amount' => 'point']);

        $html = $this->captureHtml($report);

        $this->assertStringNotContainsString('<th class="left">No</th>', $html);
        // Total value should be rendered even when numColumn is off
        $this->assertStringContainsString('100.00', $html);
    }

    public function test_make_with_show_num_column_false_and_totals_with_extra_columns()
    {
        // With more columns before the total, grandTotalSkip > 1 so "Grand Total" label renders
        $query = $this->makeQueryWithResults([
            ['name' => 'Alice', 'department' => 'Sales', 'amount' => 100],
        ]);

        $report = new PdfReport;
        $report->of('Report', [], $query, [
            'Name' => 'name',
            'Dept' => 'department',
            'Amount' => 'amount',
        ]);
        $report->showNumColumn(false);
        $report->showTotal(['Amount' => 'point']);

        $html = $this->captureHtml($report);

        $this->assertStringContainsString('Grand Total', $html);
        $this->assertStringContainsString('100.00', $html);
    }

    public function test_make_snappy_reflection_dompdf_binding_exception()
    {
        // Test mixed exception types: snappy throws ReflectionException, dompdf throws BindingResolutionException
        $query = $this->makeQueryWithResults([
            ['name' => 'Alice', 'amount' => 100],
        ]);

        $report = new PdfReport;
        $report->of('Report', [], $query, ['Name' => 'name', 'Amount' => 'amount']);

        $this->app->bind('snappy.pdf.wrapper', function () {
            throw new \ReflectionException('Snappy not found');
        });

        $this->app->bind('dompdf.wrapper', function () {
            throw new BindingResolutionException('DomPDF not bound');
        });

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Please install either barryvdh/laravel-snappy or laravel-dompdf');

        $report->make();
    }

    public function test_make_snappy_binding_dompdf_reflection_exception()
    {
        // Reverse: snappy throws BindingResolutionException, dompdf throws ReflectionException
        $query = $this->makeQueryWithResults([
            ['name' => 'Alice', 'amount' => 100],
        ]);

        $report = new PdfReport;
        $report->of('Report', [], $query, ['Name' => 'name', 'Amount' => 'amount']);

        $this->app->bind('snappy.pdf.wrapper', function () {
            throw new BindingResolutionException('Snappy not bound');
        });

        $this->app->bind('dompdf.wrapper', function () {
            throw new \ReflectionException('DomPDF not found');
        });

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Please install either barryvdh/laravel-snappy or laravel-dompdf');

        $report->make();
    }

    public function test_make_with_group_by_and_totals()
    {
        $query = $this->makeQueryWithResults([
            ['name' => 'Alice', 'department' => 'Sales', 'amount' => 100],
            ['name' => 'Bob', 'department' => 'Sales', 'amount' => 200],
            ['name' => 'Charlie', 'department' => 'HR', 'amount' => 300],
        ]);

        $report = new PdfReport;
        $report->of('Report', [], $query, [
            'Name' => 'name',
            'Dept' => 'department',
            'Amount' => 'amount',
        ]);
        $report->groupBy('Dept');
        $report->showTotal(['Amount' => 'point']);

        $html = $this->captureHtml($report);

        $this->assertStringContainsString('Alice', $html);
        $this->assertStringContainsString('Bob', $html);
        $this->assertStringContainsString('Charlie', $html);
        // Should have Grand Total rows (group break + final)
        $this->assertStringContainsString('Grand Total', $html);
    }

    public function test_make_without_manipulation_with_group_by_and_totals()
    {
        $query = $this->makeQueryWithResults([
            ['name' => 'Alice', 'department' => 'Sales', 'amount' => 100],
            ['name' => 'Bob', 'department' => 'HR', 'amount' => 200],
        ]);

        $report = new PdfReport;
        $report->of('Report', [], $query, [
            'Name' => 'name',
            'Dept' => 'department',
            'Amount' => 'amount',
        ]);
        $report->withoutManipulation();
        $report->groupBy('Dept');
        $report->showTotal(['Amount' => 'point']);

        $html = $this->captureHtml($report);

        $this->assertStringContainsString('Alice', $html);
        $this->assertStringContainsString('Bob', $html);
        $this->assertStringContainsString('Grand Total', $html);
    }

    public function test_make_with_multiple_group_breaks()
    {
        $query = $this->makeQueryWithResults([
            ['name' => 'Alice', 'department' => 'Sales', 'amount' => 100],
            ['name' => 'Bob', 'department' => 'HR', 'amount' => 200],
            ['name' => 'Charlie', 'department' => 'IT', 'amount' => 300],
        ]);

        $report = new PdfReport;
        $report->of('Report', [], $query, [
            'Name' => 'name',
            'Dept' => 'department',
            'Amount' => 'amount',
        ]);
        $report->groupBy('Dept');
        $report->showTotal(['Amount' => 'point']);

        $html = $this->captureHtml($report);

        // Count Grand Total occurrences (2 group breaks + 1 final = 3)
        $grandTotalCount = substr_count($html, 'Grand Total');
        $this->assertEquals(3, $grandTotalCount);
    }

    public function test_make_with_edit_column_display_as_closure()
    {
        $query = $this->makeQueryWithResults([
            ['name' => 'Alice', 'amount' => 100],
        ]);

        $report = new PdfReport;
        $report->of('Report', [], $query, ['Name' => 'name', 'Amount' => 'amount']);
        $report->editColumn('Amount', [
            'displayAs' => function ($result) {
                return '$'.number_format($result->amount, 2);
            },
        ]);

        $html = $this->captureHtml($report);

        $this->assertStringContainsString('$100.00', $html);
    }

    public function test_make_with_edit_column_static_display_as()
    {
        $query = $this->makeQueryWithResults([
            ['name' => 'Alice', 'amount' => 100],
        ]);

        $report = new PdfReport;
        $report->of('Report', [], $query, ['Name' => 'name', 'Amount' => 'amount']);
        $report->editColumn('Amount', [
            'displayAs' => 'REDACTED',
        ]);

        $html = $this->captureHtml($report);

        $this->assertStringContainsString('REDACTED', $html);
    }

    public function test_make_with_closure_column()
    {
        $query = $this->makeQueryWithResults([
            ['first' => 'Alice', 'last' => 'Smith', 'amount' => 100],
        ]);

        $report = new PdfReport;
        $report->of('Report', [], $query, [
            'Full Name' => function ($result) {
                return $result->first.' '.$result->last;
            },
            'Amount' => 'amount',
        ]);

        $html = $this->captureHtml($report);

        $this->assertStringContainsString('Alice Smith', $html);
    }

    public function test_make_with_all_options_disabled()
    {
        $query = $this->makeQueryWithResults([
            ['name' => 'Alice', 'amount' => 100],
        ]);

        $report = new PdfReport;
        $report->of('Report', ['Period' => 'Jan'], $query, ['Name' => 'name', 'Amount' => 'amount']);
        $report->showHeader(false);
        $report->showMeta(false);
        $report->showNumColumn(false);

        $html = $this->captureHtml($report);

        $this->assertStringNotContainsString('<thead', $html);
        $this->assertStringNotContainsString('<div class="head-content">', $html);
        $this->assertStringNotContainsString('<th class="left">No</th>', $html);
        // Data should still be there
        $this->assertStringContainsString('Alice', $html);
    }

    public function test_make_with_group_by_sum_type_totals()
    {
        $query = $this->makeQueryWithResults([
            ['name' => 'Alice', 'department' => 'Sales', 'amount' => 100],
            ['name' => 'Bob', 'department' => 'HR', 'amount' => 200],
        ]);

        $report = new PdfReport;
        $report->of('Report', [], $query, [
            'Name' => 'name',
            'Dept' => 'department',
            'Amount' => 'amount',
        ]);
        $report->groupBy('Dept');
        $report->showTotal(['Amount' => 'sum']);

        $html = $this->captureHtml($report);

        $this->assertStringContainsString('SUM', $html);
        $this->assertStringContainsString('Grand Total', $html);
    }

    public function test_make_group_by_all_same_group()
    {
        $query = $this->makeQueryWithResults([
            ['name' => 'Alice', 'department' => 'Sales', 'amount' => 100],
            ['name' => 'Bob', 'department' => 'Sales', 'amount' => 200],
        ]);

        $report = new PdfReport;
        $report->of('Report', [], $query, [
            'Name' => 'name',
            'Dept' => 'department',
            'Amount' => 'amount',
        ]);
        $report->groupBy('Dept');
        $report->showTotal(['Amount' => 'point']);

        $html = $this->captureHtml($report);

        // Only 1 grand total (final), no group break totals
        $grandTotalCount = substr_count($html, 'Grand Total');
        $this->assertEquals(1, $grandTotalCount);
        $this->assertStringContainsString('300.00', $html);
    }

    public function test_download_with_empty_filename()
    {
        $query = $this->makeQueryWithResults([
            ['name' => 'Alice', 'amount' => 100],
        ]);

        $report = new PdfReport;
        $report->of('Report', [], $query, ['Name' => 'name', 'Amount' => 'amount']);

        $pdfMock = Mockery::mock();
        $pdfMock->shouldReceive('setOption')->andReturnSelf();
        $pdfMock->shouldReceive('loadHTML')->andReturnSelf();
        $pdfMock->shouldReceive('setPaper')->andReturnSelf();
        $pdfMock->shouldReceive('download')->once()->with('.pdf')->andReturn('downloaded');

        $this->app->instance('snappy.pdf.wrapper', $pdfMock);

        $result = $report->download('');
        $this->assertEquals('downloaded', $result);
    }
}
