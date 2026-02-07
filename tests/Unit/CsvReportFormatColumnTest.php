<?php

declare(strict_types=1);

namespace SamuelTerra22\ReportGenerator\Tests\Unit;

use Mockery;
use SamuelTerra22\ReportGenerator\ReportMedia\CsvReport;
use SamuelTerra22\ReportGenerator\Tests\TestCase;

class CsvReportFormatColumnTest extends TestCase
{
    private function makeQueryWithResults(array $results): \Mockery\MockInterface
    {
        $resultObjects = array_map(fn ($row) => $this->makeResultObject($row), $results);

        $query = Mockery::mock('Illuminate\Database\Query\Builder');
        $query->shouldReceive('take')->andReturnSelf();
        $query->shouldReceive('cursor')->andReturn(new \ArrayIterator($resultObjects));

        return $query;
    }

    public function test_format_column_applied_in_csv()
    {
        $query = $this->makeQueryWithResults([
            ['name' => 'Alice', 'amount' => 1234.5],
        ]);

        $report = new CsvReport;
        $report->of('Test', [], $query, ['Name' => 'name', 'Amount' => 'amount'])
            ->formatColumn('Amount', 'currency', ['prefix' => '$']);

        ob_start();
        $report->download('test');
        $output = ob_get_clean();

        $this->assertStringContainsString('$1,234.50', $output);
    }

    public function test_edit_column_takes_priority_in_csv()
    {
        $query = $this->makeQueryWithResults([
            ['name' => 'Bob', 'amount' => 500],
        ]);

        $report = new CsvReport;
        $report->of('Test', [], $query, ['Name' => 'name', 'Amount' => 'amount'])
            ->editColumn('Amount', ['displayAs' => fn ($r) => 'CUSTOM:'.$r->amount])
            ->formatColumn('Amount', 'currency');

        ob_start();
        $report->download('test');
        $output = ob_get_clean();

        $this->assertStringContainsString('CUSTOM:500', $output);
        $this->assertStringNotContainsString('$500', $output);
    }

    public function test_format_column_percentage_in_csv()
    {
        $query = $this->makeQueryWithResults([
            ['name' => 'Charlie', 'rate' => 75.5],
        ]);

        $report = new CsvReport;
        $report->of('Test', [], $query, ['Name' => 'name', 'Rate' => 'rate'])
            ->formatColumn('Rate', 'percentage', ['decimals' => 1]);

        ob_start();
        $report->download('test');
        $output = ob_get_clean();

        $this->assertStringContainsString('75.5%', $output);
    }
}
