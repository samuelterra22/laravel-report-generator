<?php

declare(strict_types=1);

namespace SamuelTerra22\ReportGenerator\Tests\Feature;

use Mockery;
use SamuelTerra22\ReportGenerator\ReportMedia\PdfReport;
use SamuelTerra22\ReportGenerator\Tests\TestCase;

class PdfReportIntegrationTest extends TestCase
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

    private function makeReportWithHtml(array $results, ?array $columns = null, array $options = []): string
    {
        $query = $this->makeQueryWithResults($results);

        $report = new PdfReport;
        $report->of(
            $options['title'] ?? 'Integration Report',
            $options['meta'] ?? ['Period' => 'January'],
            $query,
            $columns ?? ['Name' => 'name', 'Amount' => 'amount']
        );

        if (! empty($options['editColumns'])) {
            foreach ($options['editColumns'] as $col => $opts) {
                $report->editColumn($col, $opts);
            }
        }

        if (! empty($options['showTotal'])) {
            $report->showTotal($options['showTotal']);
        }

        if (! empty($options['groupBy'])) {
            $report->groupBy($options['groupBy']);
        }

        if (isset($options['showHeader'])) {
            $report->showHeader($options['showHeader']);
        }

        if (isset($options['showMeta'])) {
            $report->showMeta($options['showMeta']);
        }

        if (isset($options['showNumColumn'])) {
            $report->showNumColumn($options['showNumColumn']);
        }

        if (! empty($options['withoutManipulation'])) {
            $report->withoutManipulation();
        }

        if (! empty($options['css'])) {
            $report->setCss($options['css']);
        }

        // Capture the HTML via a mock PDF engine
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

    public function test_renders_title_in_html()
    {
        $html = $this->makeReportWithHtml([
            ['name' => 'Alice', 'amount' => 100],
        ], null, ['title' => 'My Report']);

        $this->assertStringContainsString('My Report', $html);
    }

    public function test_renders_meta_in_html()
    {
        $html = $this->makeReportWithHtml([
            ['name' => 'Alice', 'amount' => 100],
        ], null, ['meta' => ['Period' => 'january', 'Company' => 'acme']]);

        $this->assertStringContainsString('Period', $html);
        $this->assertStringContainsString('January', $html); // ucwords
        $this->assertStringContainsString('Company', $html);
        $this->assertStringContainsString('Acme', $html); // ucwords
    }

    public function test_renders_column_headers()
    {
        $html = $this->makeReportWithHtml([
            ['name' => 'Alice', 'amount' => 100],
        ]);

        $this->assertStringContainsString('<th', $html);
        $this->assertStringContainsString('Name', $html);
        $this->assertStringContainsString('Amount', $html);
        $this->assertStringContainsString('No', $html);
    }

    public function test_renders_data_rows()
    {
        $html = $this->makeReportWithHtml([
            ['name' => 'Alice', 'amount' => 100],
            ['name' => 'Bob', 'amount' => 200],
        ]);

        $this->assertStringContainsString('Alice', $html);
        $this->assertStringContainsString('100', $html);
        $this->assertStringContainsString('Bob', $html);
        $this->assertStringContainsString('200', $html);
    }

    public function test_renders_totals()
    {
        $html = $this->makeReportWithHtml([
            ['name' => 'Alice', 'amount' => 100],
            ['name' => 'Bob', 'amount' => 200],
        ], null, ['showTotal' => ['Amount' => 'point']]);

        $this->assertStringContainsString('Grand Total', $html);
        $this->assertStringContainsString('300.00', $html);
    }

    public function test_renders_without_header()
    {
        $html = $this->makeReportWithHtml([
            ['name' => 'Alice', 'amount' => 100],
        ], null, ['showHeader' => false]);

        $this->assertStringNotContainsString('<thead', $html);
    }

    public function test_renders_without_meta()
    {
        $html = $this->makeReportWithHtml([
            ['name' => 'Alice', 'amount' => 100],
        ], null, ['showMeta' => false, 'meta' => ['Period' => 'jan']]);

        // The CSS class 'head-content' is in the <style> block always, check that the div is not rendered
        $this->assertStringNotContainsString('<div class="head-content">', $html);
    }

    public function test_renders_custom_css()
    {
        $html = $this->makeReportWithHtml([
            ['name' => 'Alice', 'amount' => 100],
        ], null, ['css' => ['.custom' => 'font-size: 20px']]);

        $this->assertStringContainsString('.custom', $html);
        $this->assertStringContainsString('font-size: 20px', $html);
    }

    public function test_renders_edit_column_class()
    {
        $html = $this->makeReportWithHtml([
            ['name' => 'Alice', 'amount' => 100],
        ], null, [
            'editColumns' => [
                'Amount' => ['class' => 'right'],
            ],
        ]);

        $this->assertStringContainsString('right', $html);
    }

    public function test_renders_without_manipulation_template()
    {
        $html = $this->makeReportWithHtml([
            ['name' => 'Alice', 'amount' => 100],
        ], null, ['withoutManipulation' => true]);

        $this->assertStringContainsString('Alice', $html);
        $this->assertStringContainsString('100', $html);
    }

    public function test_renders_without_num_column()
    {
        $html = $this->makeReportWithHtml([
            ['name' => 'Alice', 'amount' => 100],
        ], null, ['showNumColumn' => false]);

        // 'No' header cell should not be present
        $this->assertStringNotContainsString('<th class="left">No</th>', $html);
    }
}
