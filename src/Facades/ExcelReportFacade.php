<?php

namespace SamuelTerra22\ReportGenerator\Facades;

use Illuminate\Support\Facades\Facade as IlluminateFacade;

/**
 * @see \SamuelTerra22\ReportGenerator\ReportMedia\ExcelReport
 */
class ExcelReportFacade extends IlluminateFacade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'excel.report.generator';
    }
}
