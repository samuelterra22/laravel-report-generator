<?php

declare(strict_types=1);

namespace SamuelTerra22\ReportGenerator;

use SamuelTerra22\ReportGenerator\ReportMedia\CsvReport;
use SamuelTerra22\ReportGenerator\ReportMedia\ExcelReport;
use SamuelTerra22\ReportGenerator\ReportMedia\PdfReport;

class ReportExporter
{
    protected ?array $headers = null;

    protected $query;

    protected array $columns = [];

    protected ?int $limit = null;

    protected array $groupByArr = [];

    protected string $paper = 'a4';

    protected string $orientation = 'portrait';

    protected array $editColumns = [];

    protected bool $showNumColumn = true;

    protected array $showTotalColumns = [];

    protected array $styles = [];

    protected bool $simpleVersion = false;

    protected bool $withoutManipulation = false;

    protected bool $showMeta = true;

    protected bool $showHeader = true;

    protected array $columnFormats = [];

    protected array $onBeforeRenderCallbacks = [];

    protected array $onRowCallbacks = [];

    protected array $onAfterRenderCallbacks = [];

    protected array $onCompleteCallbacks = [];

    protected array $conditionalFormats = [];

    protected array $headerContent = [];

    protected array $footerContent = [
        'left' => 'Date Printed: {date}',
        'right' => 'Page {page} of {pages}',
    ];

    protected bool $cacheEnabled = false;

    protected int $cacheDuration = 0;

    protected ?string $cacheKey = null;

    protected ?string $cacheStore = null;

    public function of(string $title, array $meta, $query, array $columns): static
    {
        $this->headers = [
            'title' => $title,
            'meta' => $meta,
        ];
        $this->query = $query;
        $this->columns = $columns;

        return $this;
    }

    public function editColumn(string $columnName, array $options): static
    {
        foreach ($options as $option => $value) {
            $this->editColumns[$columnName][$option] = $value;
        }

        return $this;
    }

    public function editColumns(array $columnNames, array $options): static
    {
        foreach ($columnNames as $columnName) {
            $this->editColumn($columnName, $options);
        }

        return $this;
    }

    public function formatColumn(string $columnName, string $type, array $options = []): static
    {
        $this->columnFormats[$columnName] = [
            'type' => $type,
            'options' => $options,
        ];

        return $this;
    }

    public function formatColumns(array $columnNames, string $type, array $options = []): static
    {
        foreach ($columnNames as $columnName) {
            $this->formatColumn($columnName, $type, $options);
        }

        return $this;
    }

    public function showTotal(array $columns): static
    {
        $this->showTotalColumns = $columns;

        return $this;
    }

    public function showHeader(bool $value = true): static
    {
        $this->showHeader = $value;

        return $this;
    }

    public function showMeta(bool $value = true): static
    {
        $this->showMeta = $value;

        return $this;
    }

    public function showNumColumn(bool $value = true): static
    {
        $this->showNumColumn = $value;

        return $this;
    }

    public function simple(): static
    {
        $this->simpleVersion = true;

        return $this;
    }

    public function withoutManipulation(): static
    {
        $this->withoutManipulation = true;

        return $this;
    }

    public function setPaper(string $paper): static
    {
        $this->paper = strtolower($paper);

        return $this;
    }

    public function setOrientation(string $orientation): static
    {
        $this->orientation = strtolower($orientation);

        return $this;
    }

    public function setCss(array $styles): static
    {
        foreach ($styles as $selector => $style) {
            $this->styles[] = [
                'selector' => $selector,
                'style' => $style,
            ];
        }

        return $this;
    }

    public function groupBy($column): static
    {
        if (is_array($column)) {
            $this->groupByArr = $column;
        } else {
            $this->groupByArr[] = $column;
        }

        return $this;
    }

    public function limit(int $limit): static
    {
        $this->limit = $limit;

        return $this;
    }

    public function onBeforeRender(callable $callback): static
    {
        $this->onBeforeRenderCallbacks[] = $callback;

        return $this;
    }

    public function onRow(callable $callback): static
    {
        $this->onRowCallbacks[] = $callback;

        return $this;
    }

    public function onAfterRender(callable $callback): static
    {
        $this->onAfterRenderCallbacks[] = $callback;

        return $this;
    }

    public function onComplete(callable $callback): static
    {
        $this->onCompleteCallbacks[] = $callback;

        return $this;
    }

    public function conditionalFormat(string $columnName, callable $condition, array $styles): static
    {
        $this->conditionalFormats[$columnName][] = [
            'condition' => $condition,
            'styles' => $styles,
        ];

        return $this;
    }

    public function setHeaderContent(string $content, string $position = 'center'): static
    {
        $this->headerContent[$position] = $content;

        return $this;
    }

    public function setFooterContent(string $content, string $position = 'center'): static
    {
        $this->footerContent[$position] = $content;

        return $this;
    }

    public function clearFooter(): static
    {
        $this->footerContent = [];

        return $this;
    }

    public function clearHeader(): static
    {
        $this->headerContent = [];

        return $this;
    }

    public function cacheFor(int $minutes): static
    {
        $this->cacheEnabled = true;
        $this->cacheDuration = $minutes;

        return $this;
    }

    public function cacheAs(string $key): static
    {
        $this->cacheKey = $key;

        return $this;
    }

    public function cacheUsing(string $store): static
    {
        $this->cacheStore = $store;

        return $this;
    }

    public function noCache(): static
    {
        $this->cacheEnabled = false;

        return $this;
    }

    public function toPdf(): PdfReport
    {
        $report = new PdfReport;
        $this->applyState($report);

        return $report;
    }

    public function toExcel(): ExcelReport
    {
        $report = new ExcelReport;
        $this->applyState($report);

        return $report;
    }

    public function toCsv(): CsvReport
    {
        $report = new CsvReport;
        $this->applyState($report);

        return $report;
    }

    protected function applyState(ReportGenerator $report): void
    {
        $report->of(
            $this->headers['title'] ?? '',
            $this->headers['meta'] ?? [],
            $this->query,
            $this->columns
        );

        $state = [
            'limit' => $this->limit,
            'groupByArr' => $this->groupByArr,
            'paper' => $this->paper,
            'orientation' => $this->orientation,
            'editColumns' => $this->editColumns,
            'showNumColumn' => $this->showNumColumn,
            'showTotalColumns' => $this->showTotalColumns,
            'styles' => $this->styles,
            'simpleVersion' => $this->simpleVersion,
            'withoutManipulation' => $this->withoutManipulation,
            'showMeta' => $this->showMeta,
            'showHeader' => $this->showHeader,
            'columnFormats' => $this->columnFormats,
            'onBeforeRenderCallbacks' => $this->onBeforeRenderCallbacks,
            'onRowCallbacks' => $this->onRowCallbacks,
            'onAfterRenderCallbacks' => $this->onAfterRenderCallbacks,
            'onCompleteCallbacks' => $this->onCompleteCallbacks,
            'conditionalFormats' => $this->conditionalFormats,
            'headerContent' => $this->headerContent,
            'footerContent' => $this->footerContent,
            'cacheEnabled' => $this->cacheEnabled,
            'cacheDuration' => $this->cacheDuration,
            'cacheKey' => $this->cacheKey,
            'cacheStore' => $this->cacheStore,
        ];

        $report->applyBuilderState($state);
    }
}
