<?php

declare(strict_types=1);

namespace SamuelTerra22\ReportGenerator\ReportMedia;

use App;
use Closure;
use SamuelTerra22\ReportGenerator\ReportGenerator;
use SamuelTerra22\ReportGenerator\Support\AggregationHelper;
use SamuelTerra22\ReportGenerator\Support\ColumnFormatter;

class ExcelReport extends ReportGenerator
{
    private $format = 'xlsx';

    private $total = [];

    public function setFormat($format)
    {
        $this->format = $format;

        return $this;
    }

    public function make($filename, $simpleVersion = false)
    {
        $this->fireCallbacks($this->onBeforeRenderCallbacks);

        if ($simpleVersion) {
            return App::make('excel')->create($filename, function ($excel) {
                $excel->sheet('Sheet 1', function ($sheet) {
                    $sheet->setColumnFormat(['A:Z' => '@']);
                    $ctr = 1;
                    $grandTotalSkip = 1;
                    $currentGroupByData = [];
                    $isOnSameGroup = true;
                    $aggState = AggregationHelper::init($this->showTotalColumns);
                    if ($this->showTotalColumns != []) {
                        foreach ($this->columns as $colName => $colData) {
                            if (! array_key_exists($colName, $this->showTotalColumns)) {
                                $grandTotalSkip++;
                            } else {
                                break;
                            }
                        }
                    }
                    $grandTotalSkip = ! $this->showNumColumn ? $grandTotalSkip - 1 : $grandTotalSkip;

                    $sheet->appendRow([$this->headers['title']]);
                    $sheet->appendRow([' ']);

                    if ($this->showMeta) {
                        foreach ($this->headers['meta'] as $key => $value) {
                            $sheet->appendRow([
                                $key,
                                $value,
                            ]);
                        }
                        $sheet->appendRow([' ']);
                    }

                    if ($this->showHeader) {
                        $columns = array_keys($this->columns);
                        if (! $this->withoutManipulation && $this->showNumColumn) {
                            array_unshift($columns, 'No');
                        }
                        $sheet->appendRow($columns);
                    }

                    $rowIndex = 0;
                    foreach ($this->query->take($this->limit ?: null)->cursor() as $result) {
                        if ($this->groupByArr) {
                            $isOnSameGroup = true;
                            foreach ($this->groupByArr as $groupBy) {
                                if (is_object($this->columns[$groupBy]) && $this->columns[$groupBy] instanceof Closure) {
                                    $thisGroupByData[$groupBy] = $this->columns[$groupBy]($result);
                                } else {
                                    $thisGroupByData[$groupBy] = $result->{$this->columns[$groupBy]};
                                }

                                if (isset($currentGroupByData[$groupBy])) {
                                    if ($thisGroupByData[$groupBy] != $currentGroupByData[$groupBy]) {
                                        $isOnSameGroup = false;
                                    }
                                }

                                $currentGroupByData[$groupBy] = $thisGroupByData[$groupBy];
                            }

                            if ($isOnSameGroup === false) {
                                $totalRows = collect(['Grand Total']);
                                foreach ($columns as $columnName) {
                                    if ($columnName == $columns[0]) {
                                        continue;
                                    }
                                    if (array_key_exists($columnName, $this->showTotalColumns)) {
                                        $totalRows->push(AggregationHelper::formatResult($aggState, $columnName));
                                    } else {
                                        $totalRows->push(null);
                                    }
                                }
                                $sheet->appendRow($totalRows->toArray());

                                // Reset No, Reset Grand Total
                                $no = 1;
                                AggregationHelper::reset($aggState);
                                $isOnSameGroup = true;
                            }
                        }

                        $this->fireCallbacks($this->onRowCallbacks, $result, $rowIndex);

                        if ($this->withoutManipulation) {
                            $data = $result->toArray();
                            if (count($data) > count($this->columns)) {
                                array_pop($data);
                            }
                            $sheet->appendRow($data);
                        } else {
                            $formattedRows = $this->formatRow($result);
                            if ($this->showNumColumn) {
                                array_unshift($formattedRows, $ctr);
                            }
                            $sheet->appendRow($formattedRows);
                        }

                        foreach ($this->showTotalColumns as $colName => $type) {
                            AggregationHelper::update($aggState, $colName, $result->{$this->columns[$colName]});
                        }
                        $ctr++;
                        $rowIndex++;
                    }

                    if ($this->showTotalColumns) {
                        $totalRows = collect(['Grand Total']);
                        array_shift($columns);
                        foreach ($columns as $columnName) {
                            if (array_key_exists($columnName, $this->showTotalColumns)) {
                                $totalRows->push(AggregationHelper::formatResult($aggState, $columnName));
                            } else {
                                $totalRows->push(null);
                            }
                        }
                        $sheet->appendRow($totalRows->toArray());
                    }
                });

                $this->fireCallbacks($this->onAfterRenderCallbacks);
            });
        } else {
            return App::make('excel')->create($filename, function ($excel) {
                $excel->sheet('Sheet 1', function ($sheet) {
                    $headers = $this->headers;
                    $query = $this->query;
                    $columns = $this->columns;
                    $limit = $this->limit;
                    $groupByArr = $this->groupByArr;
                    $orientation = $this->orientation;
                    $editColumns = $this->editColumns;
                    $showTotalColumns = $this->showTotalColumns;
                    $styles = $this->styles;
                    $showHeader = $this->showHeader;
                    $showMeta = $this->showMeta;
                    $applyFlush = $this->applyFlush;
                    $showNumColumn = $this->showNumColumn;
                    $columnFormats = $this->columnFormats;
                    $conditionalFormats = $this->conditionalFormats;
                    $onRowCallbacks = $this->onRowCallbacks;

                    $sheet->setColumnFormat(['A:Z' => '@']);

                    if ($this->withoutManipulation) {
                        $sheet->loadView('laravel-report-generator::without-manipulation-excel-template',
                            compact('headers', 'columns', 'showTotalColumns', 'query', 'limit', 'groupByArr',
                                'orientation', 'showHeader', 'showMeta', 'applyFlush', 'showNumColumn',
                                'columnFormats', 'conditionalFormats', 'onRowCallbacks'));
                    } else {
                        $sheet->loadView('laravel-report-generator::general-excel-template',
                            compact('headers', 'columns', 'editColumns', 'showTotalColumns', 'styles', 'query', 'limit',
                                'groupByArr', 'orientation', 'showHeader', 'showMeta', 'applyFlush', 'showNumColumn',
                                'columnFormats', 'conditionalFormats', 'onRowCallbacks'));
                    }
                });

                $this->fireCallbacks($this->onAfterRenderCallbacks);
            });
        }
    }

    public function download($filename)
    {
        $result = $this->make($filename, $this->simpleVersion)->export($this->format);

        $this->fireCallbacks($this->onCompleteCallbacks);

        return $result;
    }

    public function simpleDownload($filename)
    {
        $result = $this->make($filename, true)->export($this->format);

        $this->fireCallbacks($this->onCompleteCallbacks);

        return $result;
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

            if (array_key_exists($colName, $this->showTotalColumns)) {
                if (! isset($this->total[$colName])) {
                    $this->total[$colName] = 0;
                }
                $this->total[$colName] += $generatedColData;
            }

            array_push($rows, $displayedColValue);
        }

        return $rows;
    }
}
