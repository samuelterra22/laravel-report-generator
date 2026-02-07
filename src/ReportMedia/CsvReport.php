<?php

declare(strict_types=1);

namespace SamuelTerra22\ReportGenerator\ReportMedia;

use Closure;
use Exception;
use League\Csv\Writer;
use SamuelTerra22\ReportGenerator\ReportGenerator;
use SamuelTerra22\ReportGenerator\Support\AggregationHelper;
use SamuelTerra22\ReportGenerator\Support\ColumnFormatter;

class CsvReport extends ReportGenerator
{
    protected $showMeta = false;

    public function download($filename)
    {
        if (! class_exists(Writer::class)) {
            throw new Exception('Please install league/csv to generate CSV Report!');
        }

        $this->fireCallbacks($this->onBeforeRenderCallbacks);

        if ($this->cacheEnabled) {
            $cached = $this->getCache()->get($this->getCacheKey());
            if ($cached !== null) {
                $this->fireCallbacks($this->onAfterRenderCallbacks);
                $this->fireCallbacks($this->onCompleteCallbacks);

                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="'.$filename.'.csv"');
                echo $cached;

                return;
            }
        }

        $csv = Writer::createFromFileObject(new \SplTempFileObject);

        if ($this->showMeta) {
            foreach ($this->headers['meta'] as $key => $value) {
                $csv->insertOne([
                    $key,
                    $value,
                ]);
            }
            $csv->insertOne([' ']);
        }

        $ctr = 1;

        if ($this->showHeader) {
            $columns = array_keys($this->columns);
            if (! $this->withoutManipulation && $this->showNumColumn) {
                array_unshift($columns, 'No');
            }
            $csv->insertOne($columns);
        }

        $aggState = AggregationHelper::init($this->showTotalColumns);
        $rowIndex = 0;

        foreach ($this->query->take($this->limit ?: null)->cursor() as $result) {
            $this->fireCallbacks($this->onRowCallbacks, $result, $rowIndex);

            if ($this->withoutManipulation) {
                $data = $result->toArray();
                if (count($data) > count($this->columns)) {
                    array_pop($data);
                }
                $csv->insertOne($data);
            } else {
                $formattedRows = $this->formatRow($result);
                if ($this->showNumColumn) {
                    array_unshift($formattedRows, $ctr);
                }
                $csv->insertOne($formattedRows);
            }

            foreach ($this->showTotalColumns as $colName => $type) {
                AggregationHelper::update($aggState, $colName, $result->{$this->columns[$colName]});
            }

            $ctr++;
            $rowIndex++;
        }

        if ($this->showTotalColumns) {
            $totalRow = ['Grand Total'];
            $columnKeys = array_keys($this->columns);
            array_shift($columnKeys);
            foreach ($columnKeys as $columnName) {
                if (array_key_exists($columnName, $this->showTotalColumns)) {
                    $totalRow[] = AggregationHelper::formatResult($aggState, $columnName);
                } else {
                    $totalRow[] = '';
                }
            }
            $csv->insertOne($totalRow);
        }

        $this->fireCallbacks($this->onAfterRenderCallbacks);

        if ($this->cacheEnabled) {
            $this->getCache()->put($this->getCacheKey(), $csv->toString(), $this->cacheDuration * 60);
        }

        $this->fireCallbacks($this->onCompleteCallbacks);

        $csv->output($filename.'.csv');
    }

    private function formatRow($result)
    {
        $rows = [];
        foreach ($this->columns as $colName => $colData) {
            if (is_object($colData) && $colData instanceof Closure) {
                $generatedColData = $colData($result);
            } else {
                $generatedColData = $result->$colData;
            }
            $displayedColValue = $generatedColData;
            if (array_key_exists($colName, $this->editColumns)) {
                if (isset($this->editColumns[$colName]['displayAs'])) {
                    $displayAs = $this->editColumns[$colName]['displayAs'];
                    if (is_object($displayAs) && $displayAs instanceof Closure) {
                        $displayedColValue = $displayAs($result);
                    } elseif (! (is_object($displayAs) && $displayAs instanceof Closure)) {
                        $displayedColValue = $displayAs;
                    }
                }
            } elseif (array_key_exists($colName, $this->columnFormats)) {
                $format = $this->columnFormats[$colName];
                $displayedColValue = ColumnFormatter::format($generatedColData, $format['type'], $format['options']);
            }

            array_push($rows, $displayedColValue);
        }

        return $rows;
    }
}
