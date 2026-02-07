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
}
