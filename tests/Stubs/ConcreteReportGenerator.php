<?php

declare(strict_types=1);

namespace SamuelTerra22\ReportGenerator\Tests\Stubs;

use SamuelTerra22\ReportGenerator\ReportGenerator;

class ConcreteReportGenerator extends ReportGenerator
{
    public function getHeaders()
    {
        return $this->headers;
    }

    public function getColumns()
    {
        return $this->columns;
    }

    public function getQuery()
    {
        return $this->query;
    }

    public function getLimit()
    {
        return $this->limit;
    }

    public function getGroupByArr()
    {
        return $this->groupByArr;
    }

    public function getPaper()
    {
        return $this->paper;
    }

    public function getOrientation()
    {
        return $this->orientation;
    }

    public function getEditColumns()
    {
        return $this->editColumns;
    }

    public function getShowNumColumn()
    {
        return $this->showNumColumn;
    }

    public function getShowTotalColumns()
    {
        return $this->showTotalColumns;
    }

    public function getStyles()
    {
        return $this->styles;
    }

    public function getSimpleVersion()
    {
        return $this->simpleVersion;
    }

    public function getWithoutManipulation()
    {
        return $this->withoutManipulation;
    }

    public function getShowMeta()
    {
        return $this->showMeta;
    }

    public function getShowHeader()
    {
        return $this->showHeader;
    }

    public function getApplyFlush()
    {
        return $this->applyFlush;
    }

    public function getColumnFormats()
    {
        return $this->columnFormats;
    }

    public function getOnBeforeRenderCallbacks()
    {
        return $this->onBeforeRenderCallbacks;
    }

    public function getOnRowCallbacks()
    {
        return $this->onRowCallbacks;
    }

    public function getOnAfterRenderCallbacks()
    {
        return $this->onAfterRenderCallbacks;
    }

    public function getOnCompleteCallbacks()
    {
        return $this->onCompleteCallbacks;
    }

    public function getConditionalFormats()
    {
        return $this->conditionalFormats;
    }

    public function getHeaderContent()
    {
        return $this->headerContent;
    }

    public function getFooterContent()
    {
        return $this->footerContent;
    }

    public function getCacheEnabled()
    {
        return $this->cacheEnabled;
    }

    public function getCacheDuration()
    {
        return $this->cacheDuration;
    }

    public function getCacheKeyValue()
    {
        return $this->cacheKey;
    }

    public function getCacheStore()
    {
        return $this->cacheStore;
    }

    public function getCacheKeyGenerated()
    {
        return parent::getCacheKey();
    }

    public function getBuilderStatePublic()
    {
        return $this->getBuilderState();
    }
}
