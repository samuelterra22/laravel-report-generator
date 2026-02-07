<?php

namespace SamuelTerra22\ReportGenerator\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use SamuelTerra22\ReportGenerator\ServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            ServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app)
    {
        return [
            'PdfReport' => \SamuelTerra22\ReportGenerator\Facades\PdfReportFacade::class,
            'ExcelReport' => \SamuelTerra22\ReportGenerator\Facades\ExcelReportFacade::class,
            'CSVReport' => \SamuelTerra22\ReportGenerator\Facades\CSVReportFacade::class,
        ];
    }

    protected function mockQueryBuilder(array $results = [])
    {
        $items = collect($results)->map(function ($row) {
            return (object) $row;
        });

        $cursor = new \ArrayIterator($items->all());

        $query = \Mockery::mock('Illuminate\Database\Query\Builder');
        $query->shouldReceive('take')->andReturnSelf();
        $query->shouldReceive('when')->andReturnUsing(function ($condition, $callback) use ($query) {
            if ($condition) {
                $callback($query);
            }
            return $query;
        });
        $query->shouldReceive('cursor')->andReturn($cursor);

        return $query;
    }

    protected function makeResultObject(array $data)
    {
        return new class($data) {
            private $data;

            public function __construct(array $data)
            {
                $this->data = $data;
                foreach ($data as $key => $value) {
                    $this->$key = $value;
                }
            }

            public function toArray()
            {
                return $this->data;
            }

            public function __get($name)
            {
                return $this->data[$name] ?? null;
            }
        };
    }
}
