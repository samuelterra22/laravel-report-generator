<?php

declare(strict_types=1);

namespace SamuelTerra22\ReportGenerator\Tests\Unit;

use Mockery;
use SamuelTerra22\ReportGenerator\ReportExporter;
use SamuelTerra22\ReportGenerator\Tests\TestCase;

class ReportExporterAdvancedTest extends TestCase
{
    private function makeQueryWithResults(array $results = []): \Mockery\MockInterface
    {
        $resultObjects = array_map(fn ($row) => $this->makeResultObject($row), $results);

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

    public function test_edit_columns_applies_to_multiple()
    {
        $exporter = new ReportExporter;
        $query = $this->makeQueryWithResults();

        $result = $exporter->of('Test', [], $query, ['Name' => 'name', 'Amount' => 'amount'])
            ->editColumns(['Name', 'Amount'], ['class' => 'right']);

        $this->assertSame($exporter, $result);
    }

    public function test_format_columns_applies_to_multiple()
    {
        $exporter = new ReportExporter;
        $query = $this->makeQueryWithResults();

        $result = $exporter->of('Test', [], $query, ['Price' => 'price', 'Total' => 'total'])
            ->formatColumns(['Price', 'Total'], 'currency', ['prefix' => '$']);

        $this->assertSame($exporter, $result);
    }

    public function test_group_by_with_array()
    {
        $exporter = new ReportExporter;
        $query = $this->makeQueryWithResults();

        $result = $exporter->of('Test', [], $query, ['Name' => 'name'])
            ->groupBy(['dept', 'city']);

        $this->assertSame($exporter, $result);
    }

    public function test_group_by_chained_string()
    {
        $exporter = new ReportExporter;
        $query = $this->makeQueryWithResults();

        $result = $exporter->of('Test', [], $query, ['Name' => 'name'])
            ->groupBy('dept')
            ->groupBy('city');

        $this->assertSame($exporter, $result);
    }

    public function test_conditional_format_transferred_to_csv()
    {
        $query = $this->makeQueryWithResults([
            ['name' => 'Alice', 'amount' => 500],
        ]);

        $exporter = new ReportExporter;
        $exporter->of('Test', [], $query, ['Name' => 'name', 'Amount' => 'amount'])
            ->conditionalFormat('Amount', fn ($v) => $v > 100, ['class' => 'bold']);

        $csv = $exporter->toCsv();

        // CSV should work without error (conditional format gracefully ignored)
        ob_start();
        $csv->download('test');
        $output = ob_get_clean();

        $this->assertStringContainsString('Alice', $output);
    }

    public function test_cache_settings_transferred()
    {
        $query = $this->makeQueryWithResults([['name' => 'A', 'amount' => 100]]);

        $exporter = new ReportExporter;
        $exporter->of('Test', [], $query, ['Name' => 'name', 'Amount' => 'amount'])
            ->cacheFor(60)
            ->cacheAs('exporter-key');

        $pdf = $exporter->toPdf();

        $pdfMock = Mockery::mock();
        $pdfMock->shouldReceive('setOption')->andReturnSelf();
        $pdfMock->shouldReceive('loadHTML')->andReturnSelf();
        $pdfMock->shouldReceive('setPaper')->andReturnSelf();

        $this->app->instance('snappy.pdf.wrapper', $pdfMock);

        $pdf->make();

        // Verify cache was stored
        $cached = \Cache::get('exporter-key');
        $this->assertNotNull($cached);
    }

    public function test_header_footer_transferred()
    {
        $query = $this->makeQueryWithResults([['name' => 'A', 'amount' => 100]]);

        $exporter = new ReportExporter;
        $exporter->of('Test', [], $query, ['Name' => 'name', 'Amount' => 'amount'])
            ->setHeaderContent('My Header', 'center')
            ->setFooterContent('My Footer', 'center');

        $pdf = $exporter->toPdf();

        $pdfMock = Mockery::mock();
        $pdfMock->shouldReceive('setOption')->with('footer-font-size', 10)->andReturnSelf();
        $pdfMock->shouldReceive('setOption')->with('footer-left', Mockery::any())->andReturnSelf();
        $pdfMock->shouldReceive('setOption')->with('footer-right', Mockery::any())->andReturnSelf();
        $pdfMock->shouldReceive('setOption')->with('footer-center', 'My Footer')->andReturnSelf();
        $pdfMock->shouldReceive('setOption')->with('header-center', 'My Header')->andReturnSelf();
        $pdfMock->shouldReceive('loadHTML')->andReturnSelf();
        $pdfMock->shouldReceive('setPaper')->andReturnSelf();

        $this->app->instance('snappy.pdf.wrapper', $pdfMock);

        $pdf->make();
    }
}
