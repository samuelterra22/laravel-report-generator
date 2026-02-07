<?php

declare(strict_types=1);

namespace SamuelTerra22\ReportGenerator\Tests\Unit\Support;

use PHPUnit\Framework\TestCase;
use SamuelTerra22\ReportGenerator\Support\AggregationHelper;

class AggregationHelperTest extends TestCase
{
    public function test_init_creates_state_for_each_column()
    {
        $state = AggregationHelper::init(['amount' => 'sum', 'quantity' => 'avg']);

        $this->assertArrayHasKey('amount', $state);
        $this->assertArrayHasKey('quantity', $state);
        $this->assertEquals('sum', $state['amount']['type']);
        $this->assertEquals('avg', $state['quantity']['type']);
        $this->assertEquals(0, $state['amount']['sum']);
        $this->assertEquals(0, $state['amount']['count']);
        $this->assertNull($state['amount']['min']);
        $this->assertNull($state['amount']['max']);
    }

    public function test_update_accumulates_sum_and_count()
    {
        $state = AggregationHelper::init(['amount' => 'sum']);

        AggregationHelper::update($state, 'amount', 100);
        AggregationHelper::update($state, 'amount', 200);
        AggregationHelper::update($state, 'amount', 300);

        $this->assertEquals(600, $state['amount']['sum']);
        $this->assertEquals(3, $state['amount']['count']);
    }

    public function test_update_tracks_min_and_max()
    {
        $state = AggregationHelper::init(['price' => 'min']);

        AggregationHelper::update($state, 'price', 50);
        AggregationHelper::update($state, 'price', 10);
        AggregationHelper::update($state, 'price', 30);

        $this->assertEquals(10, $state['price']['min']);
        $this->assertEquals(50, $state['price']['max']);
    }

    public function test_update_ignores_unknown_columns()
    {
        $state = AggregationHelper::init(['amount' => 'sum']);
        AggregationHelper::update($state, 'unknown', 100);

        $this->assertArrayNotHasKey('unknown', $state);
    }

    public function test_result_sum()
    {
        $state = AggregationHelper::init(['amount' => 'sum']);
        AggregationHelper::update($state, 'amount', 100);
        AggregationHelper::update($state, 'amount', 200);

        $this->assertEquals(300, AggregationHelper::result($state, 'amount'));
    }

    public function test_result_avg()
    {
        $state = AggregationHelper::init(['score' => 'avg']);
        AggregationHelper::update($state, 'score', 80);
        AggregationHelper::update($state, 'score', 90);
        AggregationHelper::update($state, 'score', 100);

        $this->assertEquals(90, AggregationHelper::result($state, 'score'));
    }

    public function test_result_avg_empty()
    {
        $state = AggregationHelper::init(['score' => 'avg']);
        $this->assertEquals(0, AggregationHelper::result($state, 'score'));
    }

    public function test_result_min()
    {
        $state = AggregationHelper::init(['price' => 'min']);
        AggregationHelper::update($state, 'price', 50);
        AggregationHelper::update($state, 'price', 10);
        AggregationHelper::update($state, 'price', 30);

        $this->assertEquals(10, AggregationHelper::result($state, 'price'));
    }

    public function test_result_max()
    {
        $state = AggregationHelper::init(['price' => 'max']);
        AggregationHelper::update($state, 'price', 50);
        AggregationHelper::update($state, 'price', 10);
        AggregationHelper::update($state, 'price', 30);

        $this->assertEquals(50, AggregationHelper::result($state, 'price'));
    }

    public function test_result_count()
    {
        $state = AggregationHelper::init(['orders' => 'count']);
        AggregationHelper::update($state, 'orders', 1);
        AggregationHelper::update($state, 'orders', 1);
        AggregationHelper::update($state, 'orders', 1);

        $this->assertEquals(3, AggregationHelper::result($state, 'orders'));
    }

    public function test_result_point()
    {
        $state = AggregationHelper::init(['balance' => 'point']);
        AggregationHelper::update($state, 'balance', 100);
        AggregationHelper::update($state, 'balance', 200);

        $this->assertEquals(300, AggregationHelper::result($state, 'balance'));
    }

    public function test_result_unknown_column()
    {
        $state = AggregationHelper::init(['amount' => 'sum']);
        $this->assertEquals(0, AggregationHelper::result($state, 'unknown'));
    }

    public function test_format_result_sum()
    {
        $state = AggregationHelper::init(['amount' => 'sum']);
        AggregationHelper::update($state, 'amount', 1234.5);

        $this->assertEquals('SUM 1,234.50', AggregationHelper::formatResult($state, 'amount'));
    }

    public function test_format_result_point()
    {
        $state = AggregationHelper::init(['balance' => 'point']);
        AggregationHelper::update($state, 'balance', 1234.5);

        $this->assertEquals('1,234.50', AggregationHelper::formatResult($state, 'balance'));
    }

    public function test_format_result_count()
    {
        $state = AggregationHelper::init(['orders' => 'count']);
        AggregationHelper::update($state, 'orders', 1);
        AggregationHelper::update($state, 'orders', 1);

        $this->assertEquals('COUNT 2', AggregationHelper::formatResult($state, 'orders'));
    }

    public function test_format_result_avg()
    {
        $state = AggregationHelper::init(['score' => 'avg']);
        AggregationHelper::update($state, 'score', 80);
        AggregationHelper::update($state, 'score', 100);

        $this->assertEquals('AVG 90.00', AggregationHelper::formatResult($state, 'score'));
    }

    public function test_format_result_min()
    {
        $state = AggregationHelper::init(['price' => 'min']);
        AggregationHelper::update($state, 'price', 50);
        AggregationHelper::update($state, 'price', 10);

        $this->assertEquals('MIN 10.00', AggregationHelper::formatResult($state, 'price'));
    }

    public function test_format_result_max()
    {
        $state = AggregationHelper::init(['price' => 'max']);
        AggregationHelper::update($state, 'price', 50);
        AggregationHelper::update($state, 'price', 10);

        $this->assertEquals('MAX 50.00', AggregationHelper::formatResult($state, 'price'));
    }

    public function test_reset_clears_state()
    {
        $state = AggregationHelper::init(['amount' => 'sum', 'price' => 'min']);
        AggregationHelper::update($state, 'amount', 100);
        AggregationHelper::update($state, 'price', 50);

        AggregationHelper::reset($state);

        $this->assertEquals(0, $state['amount']['sum']);
        $this->assertEquals(0, $state['amount']['count']);
        $this->assertNull($state['amount']['min']);
        $this->assertNull($state['amount']['max']);
        $this->assertEquals(0, $state['price']['sum']);
        $this->assertNull($state['price']['min']);
    }

    public function test_unknown_type_defaults_to_sum()
    {
        $state = AggregationHelper::init(['amount' => 'unknown_type']);
        AggregationHelper::update($state, 'amount', 100);
        AggregationHelper::update($state, 'amount', 200);

        $this->assertEquals(300, AggregationHelper::result($state, 'amount'));
    }
}
