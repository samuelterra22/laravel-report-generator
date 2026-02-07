<?php

declare(strict_types=1);

namespace SamuelTerra22\ReportGenerator\Tests\Unit;

use Mockery;
use SamuelTerra22\ReportGenerator\ReportMedia\CsvReport;
use SamuelTerra22\ReportGenerator\Tests\TestCase;

class CsvReportConditionalFormatTest extends TestCase
{
    private function makeQueryWithResults(array $results): \Mockery\MockInterface
    {
        $resultObjects = array_map(fn ($row) => $this->makeResultObject($row), $results);

        $query = Mockery::mock('Illuminate\Database\Query\Builder');
        $query->shouldReceive('take')->andReturnSelf();
        $query->shouldReceive('cursor')->andReturn(new \ArrayIterator($resultObjects));

        return $query;
    }

    public function test_conditional_format_ignored_in_csv()
    {
        $query = $this->makeQueryWithResults([
            ['name' => 'Alice', 'amount' => 1500],
        ]);

        $report = new CsvReport;
        $report->of('Test', [], $query, ['Name' => 'name', 'Amount' => 'amount'])
            ->conditionalFormat('Amount', fn ($value, $row) => $value > 1000, ['class' => 'bold', 'color' => 'red']);

        ob_start();
        $report->download('test');
        $output = ob_get_clean();

        // CSV should contain data but not CSS styling
        $this->assertStringContainsString('1500', $output);
        $this->assertStringNotContainsString('bold', $output);
        $this->assertStringNotContainsString('red', $output);
    }

    public function test_conditional_format_preserves_data()
    {
        $query = $this->makeQueryWithResults([
            ['name' => 'Bob', 'amount' => 500],
        ]);

        $report = new CsvReport;
        $report->of('Test', [], $query, ['Name' => 'name', 'Amount' => 'amount'])
            ->conditionalFormat('Amount', fn ($v, $r) => true, ['background' => '#ff0000']);

        ob_start();
        $report->download('test');
        $output = ob_get_clean();

        $this->assertStringContainsString('Bob', $output);
        $this->assertStringContainsString('500', $output);
    }
}
