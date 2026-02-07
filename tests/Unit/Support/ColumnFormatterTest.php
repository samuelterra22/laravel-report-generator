<?php

declare(strict_types=1);

namespace SamuelTerra22\ReportGenerator\Tests\Unit\Support;

use PHPUnit\Framework\TestCase;
use SamuelTerra22\ReportGenerator\Support\ColumnFormatter;

class ColumnFormatterTest extends TestCase
{
    public function test_format_currency_default_options()
    {
        $this->assertEquals('$1,234.56', ColumnFormatter::format(1234.56, 'currency'));
    }

    public function test_format_currency_custom_prefix()
    {
        $this->assertEquals('R$1,234.56', ColumnFormatter::format(1234.56, 'currency', ['prefix' => 'R$']));
    }

    public function test_format_currency_custom_decimals()
    {
        $this->assertEquals('$1,235', ColumnFormatter::format(1234.56, 'currency', ['decimals' => 0]));
    }

    public function test_format_currency_custom_separators()
    {
        $this->assertEquals('$1.234,56', ColumnFormatter::format(1234.56, 'currency', [
            'decimal_separator' => ',',
            'thousands_separator' => '.',
        ]));
    }

    public function test_format_number_default()
    {
        $this->assertEquals('1,235', ColumnFormatter::format(1234.56, 'number'));
    }

    public function test_format_number_with_decimals()
    {
        $this->assertEquals('1,234.56', ColumnFormatter::format(1234.56, 'number', ['decimals' => 2]));
    }

    public function test_format_number_custom_thousands()
    {
        $this->assertEquals('1.235', ColumnFormatter::format(1234.56, 'number', ['thousands_separator' => '.']));
    }

    public function test_format_date_default()
    {
        $this->assertEquals('2024-01-15', ColumnFormatter::format('2024-01-15 10:30:00', 'date'));
    }

    public function test_format_date_custom_format()
    {
        $this->assertEquals('15/01/2024', ColumnFormatter::format('2024-01-15', 'date', ['format' => 'd/m/Y']));
    }

    public function test_format_date_with_datetime_object()
    {
        $date = new \DateTime('2024-01-15');
        $this->assertEquals('15/01/2024', ColumnFormatter::format($date, 'date', ['format' => 'd/m/Y']));
    }

    public function test_format_date_with_empty_string()
    {
        $this->assertEquals('', ColumnFormatter::format('', 'date'));
    }

    public function test_format_datetime_default()
    {
        $result = ColumnFormatter::format('2024-01-15 10:30:45', 'datetime');
        $this->assertEquals('2024-01-15 10:30:45', $result);
    }

    public function test_format_datetime_custom_format()
    {
        $result = ColumnFormatter::format('2024-01-15 10:30:00', 'datetime', ['format' => 'd/m/Y H:i']);
        $this->assertEquals('15/01/2024 10:30', $result);
    }

    public function test_format_percentage_default()
    {
        $this->assertEquals('75.50%', ColumnFormatter::format(75.5, 'percentage'));
    }

    public function test_format_percentage_custom_decimals()
    {
        $this->assertEquals('75.5%', ColumnFormatter::format(75.5, 'percentage', ['decimals' => 1]));
    }

    public function test_format_percentage_custom_suffix()
    {
        $this->assertEquals('75.50 %', ColumnFormatter::format(75.5, 'percentage', ['suffix' => ' %']));
    }

    public function test_format_boolean_true_default()
    {
        $this->assertEquals('Yes', ColumnFormatter::format(true, 'boolean'));
    }

    public function test_format_boolean_false_default()
    {
        $this->assertEquals('No', ColumnFormatter::format(false, 'boolean'));
    }

    public function test_format_boolean_custom_labels()
    {
        $this->assertEquals('Active', ColumnFormatter::format(1, 'boolean', ['true' => 'Active', 'false' => 'Inactive']));
        $this->assertEquals('Inactive', ColumnFormatter::format(0, 'boolean', ['true' => 'Active', 'false' => 'Inactive']));
    }

    public function test_format_unknown_type_returns_string()
    {
        $this->assertEquals('hello', ColumnFormatter::format('hello', 'unknown'));
    }

    public function test_format_currency_with_zero()
    {
        $this->assertEquals('$0.00', ColumnFormatter::format(0, 'currency'));
    }

    public function test_format_currency_with_negative()
    {
        $this->assertEquals('$-1,234.56', ColumnFormatter::format(-1234.56, 'currency'));
    }
}
