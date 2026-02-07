<?php

declare(strict_types=1);

namespace SamuelTerra22\ReportGenerator\ReportMedia;

use Illuminate\Contracts\Container\BindingResolutionException;
use SamuelTerra22\ReportGenerator\ReportGenerator;

class PdfReport extends ReportGenerator
{
    public function make()
    {
        $this->fireCallbacks($this->onBeforeRenderCallbacks);

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
        $showNumColumn = $this->showNumColumn;
        $applyFlush = $this->applyFlush;
        $columnFormats = $this->columnFormats;
        $conditionalFormats = $this->conditionalFormats;
        $onRowCallbacks = $this->onRowCallbacks;
        $footerContent = $this->footerContent;
        $headerContent = $this->headerContent;

        $html = null;

        if ($this->cacheEnabled) {
            $html = $this->getCache()->get($this->getCacheKey());
        }

        if ($html === null) {
            if ($this->withoutManipulation) {
                $html = \View::make('laravel-report-generator::without-manipulation-pdf-template',
                    compact('headers', 'columns', 'showTotalColumns', 'query', 'limit', 'groupByArr', 'orientation',
                        'showHeader', 'showMeta', 'applyFlush', 'showNumColumn', 'columnFormats',
                        'conditionalFormats', 'onRowCallbacks', 'footerContent', 'headerContent'))->render();
            } else {
                $html = \View::make('laravel-report-generator::general-pdf-template',
                    compact('headers', 'columns', 'editColumns', 'showTotalColumns', 'styles', 'query', 'limit',
                        'groupByArr', 'orientation', 'showHeader', 'showMeta', 'applyFlush', 'showNumColumn',
                        'columnFormats', 'conditionalFormats', 'onRowCallbacks', 'footerContent', 'headerContent'))->render();
            }

            if ($this->cacheEnabled) {
                $this->getCache()->put($this->getCacheKey(), $html, $this->cacheDuration * 60);
            }
        }

        $this->fireCallbacks($this->onAfterRenderCallbacks);

        try {
            $pdf = \App::make('snappy.pdf.wrapper');
            $pdf->setOption('footer-font-size', 10);

            if (! empty($this->footerContent['left'])) {
                $pdf->setOption('footer-left', $this->resolveSnappyPlaceholders($this->footerContent['left']));
            }
            if (! empty($this->footerContent['right'])) {
                $pdf->setOption('footer-right', $this->resolveSnappyPlaceholders($this->footerContent['right']));
            }
            if (! empty($this->footerContent['center'])) {
                $pdf->setOption('footer-center', $this->resolveSnappyPlaceholders($this->footerContent['center']));
            }

            if (! empty($this->headerContent['left'])) {
                $pdf->setOption('header-left', $this->resolveSnappyPlaceholders($this->headerContent['left']));
            }
            if (! empty($this->headerContent['right'])) {
                $pdf->setOption('header-right', $this->resolveSnappyPlaceholders($this->headerContent['right']));
            }
            if (! empty($this->headerContent['center'])) {
                $pdf->setOption('header-center', $this->resolveSnappyPlaceholders($this->headerContent['center']));
            }
        } catch (\ReflectionException|BindingResolutionException $e) {
            try {
                $pdf = \App::make('dompdf.wrapper');
            } catch (\ReflectionException|BindingResolutionException $e) {
                throw new \Exception('Please install either barryvdh/laravel-snappy or laravel-dompdf to generate PDF Report!');
            }
        }

        $result = $pdf->loadHTML($html)->setPaper($this->paper, $orientation);

        $this->fireCallbacks($this->onCompleteCallbacks);

        return $result;
    }

    public function stream()
    {
        return $this->make()->stream();
    }

    public function download($filename)
    {
        return $this->make()->download($filename.'.pdf');
    }

    private function resolveSnappyPlaceholders(string $content): string
    {
        return str_replace(
            ['{page}', '{pages}', '{date}', '{title}'],
            ['[page]', '[topage]', date('d M Y H:i:s'), $this->headers['title'] ?? ''],
            $content
        );
    }
}
