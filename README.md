# Laravel Report Generator

Rapidly generate PDF, Excel (XLSX), and CSV reports from Eloquent queries or query builders in Laravel. Provides a fluent, chainable API for building reports with grouping, column editing, totals, and custom styling.

## Compatibility

| Laravel | PHP    | Status |
|---------|--------|--------|
| 10.x    | 8.2+   | Supported |
| 11.x    | 8.2+   | Supported |
| 12.x    | 8.2+   | Supported |

## Installation

```bash
composer require samuelterra22/laravel-report-generator
```

The package auto-discovers its service provider and facades in Laravel 5.5+.

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

Publish the config and/or view templates:

```bash
php artisan vendor:publish --tag=laravel-report:config
php artisan vendor:publish --tag=laravel-report:view-template
```

## Usage

### PDF Report

```php
use PdfReport;

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
use ExcelReport;

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
use CSVReport;

$query = User::select(['name', 'email', 'balance']);

$report = CSVReport::of('User Report', ['Created' => now()->format('d M Y')], $query, [
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

## Lumen Support

The package detects Lumen automatically and loads polyfills for `config_path()` and `public_path()`. Register the service provider manually in `bootstrap/app.php`:

```php
$app->register(SamuelTerra22\ReportGenerator\ServiceProvider::class);
```

## License

MIT
