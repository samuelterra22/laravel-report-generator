# Laravel Report Generator

[![Latest Version on Packagist](https://img.shields.io/packagist/v/samuelterra22/laravel-report-generator.svg?style=flat-square)](https://packagist.org/packages/samuelterra22/laravel-report-generator)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/samuelterra22/laravel-report-generator/run-tests.yml?branch=master&label=tests&style=flat-square)](https://github.com/samuelterra22/laravel-report-generator/actions?query=workflow%3Arun-tests+branch%3Amaster)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/samuelterra22/laravel-report-generator/fix-php-code-style-issues.yml?branch=master&label=code%20style&style=flat-square)](https://github.com/samuelterra22/laravel-report-generator/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amaster)
[![Total Downloads](https://img.shields.io/packagist/dt/samuelterra22/laravel-report-generator.svg?style=flat-square)](https://packagist.org/packages/samuelterra22/laravel-report-generator)

Rapidly generate PDF, Excel (XLSX), and CSV reports from Eloquent queries or query builders in Laravel. Provides a fluent, chainable API for building reports with grouping, column editing, totals, and custom styling.

## Compatibility

| Laravel | PHP    |
|---------|--------|
| 10.x    | 8.2+   |
| 11.x    | 8.2+   |
| 12.x    | 8.2+   |

## Installation

You can install the package via composer:

```bash
composer require samuelterra22/laravel-report-generator
```

The package auto-discovers its service provider and facades.

### PDF Engine (pick one)

```bash
# Option A: DomPDF
composer require barryvdh/laravel-dompdf

# Option B: Snappy (wkhtmltopdf)
composer require barryvdh/laravel-snappy
```

### CSV Support (optional)

```bash
composer require league/csv
```

## Configuration

You can publish the config file with:

```bash
php artisan vendor:publish --tag="report-generator-config"
```

You can publish the views with:

```bash
php artisan vendor:publish --tag="report-generator-views"
```

## Usage

### PDF Report

```php
use SamuelTerra22\ReportGenerator\Facades\PdfReport;

$query = User::select(['name', 'email', 'city', 'balance']);

$report = PdfReport::of('User Report', ['Created' => now()->format('d M Y')], $query, [
        'Name'    => 'name',
        'Email'   => 'email',
        'City'    => 'city',
        'Balance' => 'balance',
    ])
    ->editColumn('Balance', ['displayAs' => fn($value) => number_format($value, 2)])
    ->showTotal(['Balance' => 'point'])
    ->groupBy('City')
    ->setPaper('a4')
    ->setOrientation('landscape')
    ->make();
```

Available methods: `make()`, `stream()`, `download()`.

### Excel Report

```php
use SamuelTerra22\ReportGenerator\Facades\ExcelReport;

$query = User::select(['name', 'email', 'balance']);

$report = ExcelReport::of('User Report', ['Created' => now()->format('d M Y')], $query, [
        'Name'    => 'name',
        'Email'   => 'email',
        'Balance' => 'balance',
    ])
    ->editColumn('Balance', ['displayAs' => fn($value) => number_format($value, 2)])
    ->showTotal(['Balance' => 'point'])
    ->make();
```

Available methods: `make()`, `download()`, `simpleDownload()`.

### CSV Report

```php
use SamuelTerra22\ReportGenerator\Facades\CsvReport;

$query = User::select(['name', 'email', 'balance']);

CsvReport::of('User Report', ['Created' => now()->format('d M Y')], $query, [
        'Name'    => 'name',
        'Email'   => 'email',
        'Balance' => 'balance',
    ])
    ->download('users');
```

### Fluent API

All report types support these chainable methods:

| Method | Description |
|--------|-------------|
| `of($title, $meta, $query, $columns)` | Initialize the report |
| `editColumn($name, $options)` | Modify column display (e.g. `displayAs` callback) |
| `editColumns($names, $options)` | Modify multiple columns at once |
| `showTotal($columns)` | Show totals for specified columns |
| `groupBy($column)` | Group rows by a column value |
| `limit($n)` | Limit number of rows |
| `setPaper($size)` | Set paper size (PDF only, default: `a4`) |
| `setOrientation($orientation)` | Set orientation (PDF only, default: `portrait`) |
| `setCss($styles)` | Add custom CSS styles (PDF/Excel) |
| `showNumColumn($bool)` | Show/hide row number column |
| `showHeader($bool)` | Show/hide report header |
| `showMeta($bool)` | Show/hide meta information |
| `withoutManipulation()` | Skip column editing for faster generation |
| `simple()` | Use simple Excel version (direct sheet manipulation) |

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

- [Samuel Terra](https://github.com/samuelterra22)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
