<?php

declare(strict_types=1);

namespace SamuelTerra22\ReportGenerator;

use Config;
use Illuminate\Support\Str;

abstract class ReportGenerator
{
    protected $applyFlush;

    protected $headers;

    protected $columns;

    protected $query;

    protected $limit = null;

    protected $groupByArr = [];

    protected $paper = 'a4';

    protected $orientation = 'portrait';

    protected $editColumns = [];

    protected $showNumColumn = true;

    protected $showTotalColumns = [];

    protected $styles = [];

    protected $simpleVersion = false;

    protected $withoutManipulation = false;

    protected $showMeta = true;

    protected $showHeader = true;

    // Feature 1: Column Formatting
    protected array $columnFormats = [];

    // Feature 3: Report Events/Hooks
    protected array $onBeforeRenderCallbacks = [];

    protected array $onRowCallbacks = [];

    protected array $onAfterRenderCallbacks = [];

    protected array $onCompleteCallbacks = [];

    // Feature 4: Conditional Formatting
    protected array $conditionalFormats = [];

    // Feature 5: Custom Headers & Footers
    protected array $headerContent = [];

    protected array $footerContent = [
        'left' => 'Date Printed: {date}',
        'right' => 'Page {page} of {pages}',
    ];

    // Feature 7: Report Caching
    protected bool $cacheEnabled = false;

    protected int $cacheDuration = 0;

    protected ?string $cacheKey = null;

    protected ?string $cacheStore = null;

    public function __construct()
    {
        $this->applyFlush = (bool) Config::get('report-generator.flush', true);
    }

    public function of($title, array $meta, $query, array $columns)
    {
        $this->headers = [
            'title' => $title,
            'meta' => $meta,
        ];

        $this->query = $query;
        $this->columns = $this->mapColumns($columns);

        return $this;
    }

    public function showHeader($value = true)
    {
        $this->showHeader = $value;

        return $this;
    }

    public function showMeta($value = true)
    {
        $this->showMeta = $value;

        return $this;
    }

    public function showNumColumn($value = true)
    {
        $this->showNumColumn = $value;

        return $this;
    }

    public function simple()
    {
        $this->simpleVersion = true;

        return $this;
    }

    public function withoutManipulation()
    {
        $this->withoutManipulation = true;

        return $this;
    }

    private function mapColumns(array $columns)
    {
        $result = [];

        foreach ($columns as $name => $data) {
            if (is_int($name)) {
                $result[$data] = Str::snake($data);
            } else {
                $result[$name] = $data;
            }
        }

        return $result;
    }

    public function setPaper($paper)
    {
        $this->paper = strtolower($paper);

        return $this;
    }

    public function editColumn($columnName, array $options)
    {
        foreach ($options as $option => $value) {
            $this->editColumns[$columnName][$option] = $value;
        }

        return $this;
    }

    public function editColumns(array $columnNames, array $options)
    {
        foreach ($columnNames as $columnName) {
            $this->editColumn($columnName, $options);
        }

        return $this;
    }

    public function showTotal(array $columns)
    {
        $this->showTotalColumns = $columns;

        return $this;
    }

    public function groupBy($column)
    {
        if (is_array($column)) {
            $this->groupByArr = $column;
        } else {
            array_push($this->groupByArr, $column);
        }

        return $this;
    }

    public function limit($limit)
    {
        $this->limit = $limit;

        return $this;
    }

    public function setOrientation($orientation)
    {
        $this->orientation = strtolower($orientation);

        return $this;
    }

    public function setCss(array $styles)
    {
        foreach ($styles as $selector => $style) {
            array_push($this->styles, [
                'selector' => $selector,
                'style' => $style,
            ]);
        }

        return $this;
    }

    // Feature 1: Column Formatting

    public function formatColumn(string $columnName, string $type, array $options = [])
    {
        $this->columnFormats[$columnName] = [
            'type' => $type,
            'options' => $options,
        ];

        return $this;
    }

    public function formatColumns(array $columnNames, string $type, array $options = [])
    {
        foreach ($columnNames as $columnName) {
            $this->formatColumn($columnName, $type, $options);
        }

        return $this;
    }

    // Feature 3: Report Events/Hooks

    public function onBeforeRender(callable $callback)
    {
        $this->onBeforeRenderCallbacks[] = $callback;

        return $this;
    }

    public function onRow(callable $callback)
    {
        $this->onRowCallbacks[] = $callback;

        return $this;
    }

    public function onAfterRender(callable $callback)
    {
        $this->onAfterRenderCallbacks[] = $callback;

        return $this;
    }

    public function onComplete(callable $callback)
    {
        $this->onCompleteCallbacks[] = $callback;

        return $this;
    }

    protected function fireCallbacks(array $callbacks, mixed ...$args): void
    {
        foreach ($callbacks as $callback) {
            $callback(...$args);
        }
    }

    // Feature 4: Conditional Formatting

    public function conditionalFormat(string $columnName, callable $condition, array $styles)
    {
        $this->conditionalFormats[$columnName][] = [
            'condition' => $condition,
            'styles' => $styles,
        ];

        return $this;
    }

    // Feature 5: Custom Headers & Footers

    public function setHeaderContent(string $content, string $position = 'center')
    {
        $this->headerContent[$position] = $content;

        return $this;
    }

    public function setFooterContent(string $content, string $position = 'center')
    {
        $this->footerContent[$position] = $content;

        return $this;
    }

    public function clearFooter()
    {
        $this->footerContent = [];

        return $this;
    }

    public function clearHeader()
    {
        $this->headerContent = [];

        return $this;
    }

    protected function resolveFooterPlaceholders(string $content): string
    {
        return str_replace(
            ['{date}', '{title}'],
            [date('d M Y H:i:s'), $this->headers['title'] ?? ''],
            $content
        );
    }

    // Feature 7: Report Caching

    public function cacheFor(int $minutes)
    {
        $this->cacheEnabled = true;
        $this->cacheDuration = $minutes;

        return $this;
    }

    public function cacheAs(string $key)
    {
        $this->cacheKey = $key;

        return $this;
    }

    public function cacheUsing(string $store)
    {
        $this->cacheStore = $store;

        return $this;
    }

    public function noCache()
    {
        $this->cacheEnabled = false;

        return $this;
    }

    protected function getCacheKey(): string
    {
        if ($this->cacheKey) {
            return $this->cacheKey;
        }

        $prefix = Config::get('report-generator.cache_prefix', 'report-generator');
        $title = $this->headers['title'] ?? '';
        $columnKeys = is_array($this->columns) ? implode(',', array_keys($this->columns)) : '';
        $meta = is_array($this->headers['meta'] ?? null) ? implode(',', $this->headers['meta']) : '';
        $limit = (string) ($this->limit ?? '');
        $groupBy = implode(',', $this->groupByArr);

        return $prefix.':'.md5($title.$columnKeys.$meta.$limit.$groupBy);
    }

    protected function getCache(): \Illuminate\Contracts\Cache\Repository
    {
        $store = $this->cacheStore ?? Config::get('report-generator.cache_store');

        return \Cache::store($store);
    }

    // Feature 6: Multi-Format Export (state transfer)

    public function getBuilderState(): array
    {
        return [
            'headers' => $this->headers,
            'columns' => $this->columns,
            'query' => $this->query,
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
    }

    public function applyBuilderState(array $state)
    {
        foreach ($state as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }

        return $this;
    }
}
