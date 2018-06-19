<?php

namespace SamuelTerra22\ReportGenerator\Facades;

use Illuminate\Support\Facades\Facade as IlluminateFacade;

/**
 * @see \SamuelTerra22\ReportGenerator\ReportMedia\PdfReport
 */
class PdfReportFacade extends IlluminateFacade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'pdf.report.generator';
    }
}
