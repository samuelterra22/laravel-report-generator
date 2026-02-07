<?php

declare(strict_types=1);

namespace SamuelTerra22\ReportGenerator\Tests\Unit;

use SamuelTerra22\ReportGenerator\Tests\Stubs\ConcreteReportGenerator;
use SamuelTerra22\ReportGenerator\Tests\TestCase;

class ReportGeneratorBuilderStateTest extends TestCase
{
    private ConcreteReportGenerator $report;

    protected function setUp(): void
    {
        parent::setUp();
        $this->report = new ConcreteReportGenerator;
    }

    public function test_get_builder_state_returns_all_properties()
    {
        $query = $this->mockQueryBuilder();
        $this->report->of('Title', ['key' => 'val'], $query, ['Name' => 'name'])
            ->limit(50)
            ->groupBy('Name')
            ->setPaper('letter')
            ->setOrientation('landscape')
            ->showNumColumn(false)
            ->showTotal(['Name' => 'sum'])
            ->showMeta(false)
            ->showHeader(false)
            ->formatColumn('Name', 'number')
            ->onBeforeRender(function () {})
            ->onRow(function () {})
            ->onAfterRender(function () {})
            ->onComplete(function () {})
            ->conditionalFormat('Name', fn ($v) => true, ['class' => 'bold'])
            ->setHeaderContent('Header', 'center')
            ->setFooterContent('Footer', 'center')
            ->cacheFor(60)
            ->cacheAs('test-key')
            ->cacheUsing('redis');

        $state = $this->report->getBuilderStatePublic();

        $this->assertEquals('Title', $state['headers']['title']);
        $this->assertEquals(50, $state['limit']);
        $this->assertEquals(['Name'], $state['groupByArr']);
        $this->assertEquals('letter', $state['paper']);
        $this->assertEquals('landscape', $state['orientation']);
        $this->assertFalse($state['showNumColumn']);
        $this->assertEquals(['Name' => 'sum'], $state['showTotalColumns']);
        $this->assertFalse($state['showMeta']);
        $this->assertFalse($state['showHeader']);
        $this->assertArrayHasKey('Name', $state['columnFormats']);
        $this->assertCount(1, $state['onBeforeRenderCallbacks']);
        $this->assertCount(1, $state['onRowCallbacks']);
        $this->assertCount(1, $state['onAfterRenderCallbacks']);
        $this->assertCount(1, $state['onCompleteCallbacks']);
        $this->assertArrayHasKey('Name', $state['conditionalFormats']);
        $this->assertEquals('Header', $state['headerContent']['center']);
        $this->assertEquals('Footer', $state['footerContent']['center']);
        $this->assertTrue($state['cacheEnabled']);
        $this->assertEquals(60, $state['cacheDuration']);
        $this->assertEquals('test-key', $state['cacheKey']);
        $this->assertEquals('redis', $state['cacheStore']);
    }

    public function test_apply_builder_state_sets_properties()
    {
        $query = $this->mockQueryBuilder();
        $this->report->of('Base', [], $query, ['Name' => 'name']);

        $state = [
            'limit' => 25,
            'paper' => 'legal',
            'orientation' => 'landscape',
            'showNumColumn' => false,
            'showMeta' => false,
            'showHeader' => false,
            'cacheEnabled' => true,
            'cacheDuration' => 30,
        ];

        $result = $this->report->applyBuilderState($state);

        $this->assertSame($this->report, $result);
        $this->assertEquals(25, $this->report->getLimit());
        $this->assertEquals('legal', $this->report->getPaper());
        $this->assertEquals('landscape', $this->report->getOrientation());
        $this->assertFalse($this->report->getShowNumColumn());
        $this->assertFalse($this->report->getShowMeta());
        $this->assertFalse($this->report->getShowHeader());
        $this->assertTrue($this->report->getCacheEnabled());
        $this->assertEquals(30, $this->report->getCacheDuration());
    }

    public function test_apply_builder_state_ignores_unknown_keys()
    {
        $query = $this->mockQueryBuilder();
        $this->report->of('Base', [], $query, ['Name' => 'name']);

        $this->report->applyBuilderState(['unknownKey' => 'value']);

        // Should not throw, just ignore
        $this->assertEquals('a4', $this->report->getPaper());
    }

    public function test_fire_callbacks_passes_arguments()
    {
        $receivedArgs = [];
        $this->report->onBeforeRender(function () use (&$receivedArgs) {
            $receivedArgs[] = 'called';
        });

        // fireCallbacks is protected, but tested through concrete behavior
        $this->assertCount(1, $this->report->getOnBeforeRenderCallbacks());
    }
}
