<p align="center">
  <h1 align="center">Laravel Report Generator</h1>
  <p align="center">
    Generate PDF, Excel & CSV reports from Eloquent queries with a fluent API.
    <br />
    Zero boilerplate. Full control. Works with Laravel 10, 11 & 12.
  </p>
</p>

<p align="center">
  <a href="https://packagist.org/packages/samuelterra22/laravel-report-generator"><img src="https://img.shields.io/packagist/v/samuelterra22/laravel-report-generator.svg?style=flat-square" alt="Latest Version on Packagist"></a>
  <a href="https://github.com/samuelterra22/laravel-report-generator/actions?query=workflow%3Arun-tests+branch%3Amaster"><img src="https://img.shields.io/github/actions/workflow/status/samuelterra22/laravel-report-generator/run-tests.yml?branch=master&label=tests&style=flat-square" alt="Tests"></a>
  <a href="https://github.com/samuelterra22/laravel-report-generator/actions?query=workflow%3A%22Fix+PHP+code+style+issues%22+branch%3Amaster"><img src="https://img.shields.io/github/actions/workflow/status/samuelterra22/laravel-report-generator/fix-php-code-style-issues.yml?branch=master&label=code%20style&style=flat-square" alt="Code Style"></a>
  <a href="https://packagist.org/packages/samuelterra22/laravel-report-generator"><img src="https://img.shields.io/packagist/dt/samuelterra22/laravel-report-generator.svg?style=flat-square" alt="Total Downloads"></a>
  <a href="https://packagist.org/packages/samuelterra22/laravel-report-generator"><img src="https://img.shields.io/packagist/php-v/samuelterra22/laravel-report-generator.svg?style=flat-square" alt="PHP Version"></a>
  <a href="LICENSE.md"><img src="https://img.shields.io/packagist/l/samuelterra22/laravel-report-generator.svg?style=flat-square" alt="License"></a>
</p>

---

**Laravel Report Generator** is a package that lets you build PDF, Excel (XLSX), and CSV reports directly from Eloquent queries or query builders using a clean, chainable API. Define columns, format values, group rows, add totals, customize styling -- all without writing HTML tables or spreadsheet logic by hand.

**Key features:**

- **Three output formats** -- PDF, Excel (XLSX), and CSV from the same fluent interface
- **Multi-format export** -- Define a report once, export to PDF, Excel, or CSV via `ReportExporter`
- **Column formatting** -- Transform displayed values with callbacks (`displayAs`) or built-in formatters (currency, date, percentage, etc.)
- **Row grouping** -- Group rows by one or more columns with automatic subtotals
- **Advanced aggregations** -- `sum`, `avg`, `min`, `max`, and `count` in total rows
- **Conditional formatting** -- Declarative rules to style cells based on data values
- **Report events/hooks** -- Lifecycle callbacks (`onBeforeRender`, `onRow`, `onAfterRender`, `onComplete`)
- **Custom headers & footers** -- Configurable content with placeholders (`{page}`, `{date}`, `{title}`)
- **Report caching** -- Cache rendered output with TTL and custom keys
- **Custom CSS** -- Inject custom styles into PDF and Excel reports
- **Memory efficient** -- Uses cursor-based iteration for large datasets
- **Customizable templates** -- Publish and modify Blade templates to fit your design
- **PDF engine flexibility** -- Works with either Snappy (wkhtmltopdf) or DomPDF

---

## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Output Examples](#output-examples)
- [Usage](#usage)
  - [PDF Reports](#pdf-reports)
  - [Excel Reports](#excel-reports)
  - [CSV Reports](#csv-reports)
- [API Reference](#api-reference)
  - [Initializing a Report](#initializing-a-report)
  - [Column Formatting](#column-formatting)
  - [Built-in Column Formatters](#built-in-column-formatters)
  - [Grouping & Totals](#grouping--totals)
  - [Conditional Formatting](#conditional-formatting)
  - [Report Events / Hooks](#report-events--hooks)
  - [Custom Headers & Footers](#custom-headers--footers)
  - [Multi-Format Export](#multi-format-export)
  - [Report Caching](#report-caching)
  - [Layout & Styling](#layout--styling)
  - [Performance](#performance)
  - [Output Methods](#output-methods)
- [Configuration](#configuration)
- [Customizing Templates](#customizing-templates)
- [Testing](#testing)
- [Changelog](#changelog)
- [Contributing](#contributing)
- [Credits](#credits)
- [License](#license)

---

## Requirements

| PHP   | Laravel    |
|-------|------------|
| 8.2+  | 10.x      |
| 8.2+  | 11.x      |
| 8.2+  | 12.x      |

## Installation

Install the package via Composer:

```bash
composer require samuelterra22/laravel-report-generator
```

The service provider and facades are auto-discovered -- no manual registration needed.

### PDF engine (pick one)

To generate PDF reports, install one of the supported PDF engines:

```bash
# Option A: DomPDF (pure PHP, no external dependencies)
composer require barryvdh/laravel-dompdf

# Option B: Snappy (uses wkhtmltopdf, better CSS support)
composer require barryvdh/laravel-snappy
```

> If both are installed, Snappy is used by default with an automatic fallback to DomPDF.

### CSV support (optional)

```bash
composer require league/csv
```

## Quick Start

```php
use SamuelTerra22\ReportGenerator\Facades\PdfReport;

public function usersReport()
{
    $query = User::select(['name', 'email', 'city', 'balance'])
                 ->orderBy('city');

    return PdfReport::of('Users Report', ['Date' => now()->format('d M Y')], $query, [
            'Name'    => 'name',
            'Email'   => 'email',
            'City'    => 'city',
            'Balance' => 'balance',
        ])
        ->editColumn('Balance', [
            'displayAs' => fn ($result) => '$' . number_format((float) $result->balance, 2),
        ])
        ->showTotal(['Balance' => 'point'])
        ->groupBy('City')
        ->stream();
}
```

This generates a paginated PDF report with rows grouped by city, formatted currency values, and automatic totals per group.

---

## Output Examples

### Report with Grand Total

A report using `showTotal()` to display an automatic sum at the bottom:

<p align="center">
  <img src="screenshots/report-with-total.png" alt="PDF report with grand total row" width="450">
</p>

### Report with Group By

A report using `groupBy()` combined with `showTotal()` -- rows are grouped by date, with subtotals after each group and a final grand total:

<p align="center">
  <img src="screenshots/report-with-group-by.png" alt="PDF report with group by and subtotals" width="450">
</p>

---

## Usage

### PDF Reports

```php
use SamuelTerra22\ReportGenerator\Facades\PdfReport;

$query = User::select(['name', 'email', 'city', 'balance']);

// Render and return the PDF response
$pdf = PdfReport::of('Sales Report', ['Period' => 'Jan 2025'], $query, [
        'Name'    => 'name',
        'Email'   => 'email',
        'City'    => 'city',
        'Balance' => 'balance',
    ])
    ->editColumn('Balance', [
        'class'     => 'right',
        'displayAs' => fn ($result) => number_format((float) $result->balance, 2),
    ])
    ->showTotal(['Balance' => 'point'])
    ->groupBy('City')
    ->setPaper('a4')
    ->setOrientation('landscape')
    ->setCss([
        '.head-content' => 'border-bottom: 2px solid #333;',
    ])
    ->make();
```

**Output methods for PDF:**

| Method | Description |
|--------|-------------|
| `make()` | Returns the PDF object (for further manipulation) |
| `stream()` | Displays the PDF inline in the browser |
| `download($filename)` | Forces a file download (`.pdf` extension added automatically) |

### Excel Reports

```php
use SamuelTerra22\ReportGenerator\Facades\ExcelReport;

$query = User::select(['name', 'email', 'balance']);

// Standard version (uses Blade template)
ExcelReport::of('Financial Report', ['Quarter' => 'Q1 2025'], $query, [
        'Name'    => 'name',
        'Email'   => 'email',
        'Balance' => 'balance',
    ])
    ->editColumn('Balance', [
        'displayAs' => fn ($result) => number_format((float) $result->balance, 2),
    ])
    ->showTotal(['Balance' => 'point'])
    ->download('financial-report');
```

**Simple version** (direct sheet manipulation, better for large datasets):

```php
ExcelReport::of('Financial Report', ['Quarter' => 'Q1 2025'], $query, [
        'Name'    => 'name',
        'Email'   => 'email',
        'Balance' => 'balance',
    ])
    ->showTotal(['Balance' => 'point'])
    ->simple()
    ->download('financial-report');
```

**Output methods for Excel:**

| Method | Description |
|--------|-------------|
| `make()` | Returns the Excel object for further manipulation |
| `download($filename)` | Exports and downloads the XLSX file |
| `simpleDownload($filename)` | Forces simple mode and downloads |

### CSV Reports

```php
use SamuelTerra22\ReportGenerator\Facades\CsvReport;

$query = User::select(['name', 'email', 'balance']);

CsvReport::of('User Export', ['Date' => now()->format('d M Y')], $query, [
        'Name'    => 'name',
        'Email'   => 'email',
        'Balance' => 'balance',
    ])
    ->editColumn('Balance', [
        'displayAs' => fn ($result) => number_format((float) $result->balance, 2),
    ])
    ->download('user-export');
```

> Requires `league/csv`. The `.csv` extension is added automatically.

**Output methods for CSV:**

| Method | Description |
|--------|-------------|
| `download($filename)` | Outputs the CSV file for download |

---

## API Reference

### Initializing a Report

```php
PdfReport::of(string $title, array $meta, $query, array $columns)
```

| Parameter | Type | Description |
|-----------|------|-------------|
| `$title` | `string` | Report title displayed in the header |
| `$meta` | `array` | Key-value pairs shown below the title (e.g., date range, filters) |
| `$query` | `Builder\|EloquentBuilder` | The query to iterate over |
| `$columns` | `array` | Column mapping: `['Display Name' => 'db_column']` |

**Column mapping** supports two formats:

```php
// Explicit mapping
$columns = [
    'Full Name' => 'name',
    'Email'     => 'email',
];

// Automatic mapping (column name is converted to snake_case for the DB field)
$columns = ['Name', 'Email']; // maps to 'name', 'email'

// Closure-based columns (computed values)
$columns = [
    'Full Name' => fn ($row) => $row->first_name . ' ' . $row->last_name,
    'Email'     => 'email',
];
```

### Column Formatting

#### `editColumn(string $columnName, array $options)`

Customize how a column is displayed:

```php
->editColumn('Balance', [
    'class'     => 'right bold',   // CSS class for the column (PDF/Excel)
    'displayAs' => fn ($result) => '$' . number_format((float) $result->balance, 2),
])
```

| Option | Type | Description |
|--------|------|-------------|
| `class` | `string` | CSS class applied to the column cells (`left`, `right`, `bold`) |
| `displayAs` | `Closure\|string` | Callback receiving the full row, or a static string value |

#### `editColumns(array $columnNames, array $options)`

Apply the same formatting to multiple columns at once:

```php
->editColumns(['Price', 'Tax', 'Total'], [
    'class'     => 'right',
    'displayAs' => fn ($result, $colName) => number_format((float) $result->{strtolower($colName)}, 2),
])
```

### Built-in Column Formatters

Use `formatColumn()` for common formatting without writing closures. If a column has both `editColumn` with `displayAs` and `formatColumn`, the `displayAs` callback takes priority.

#### `formatColumn(string $columnName, string $type, array $options = [])`

```php
->formatColumn('price', 'currency', ['prefix' => 'R$', 'decimals' => 2])
->formatColumn('created_at', 'date', ['format' => 'd/m/Y'])
->formatColumn('rate', 'percentage', ['decimals' => 1])
->formatColumn('active', 'boolean', ['true' => 'Active', 'false' => 'Inactive'])
->formatColumn('quantity', 'number', ['decimals' => 0, 'thousands_separator' => '.'])
```

#### `formatColumns(array $columnNames, string $type, array $options = [])`

Apply the same formatter to multiple columns:

```php
->formatColumns(['Price', 'Total'], 'currency', ['prefix' => '$'])
```

**Available format types:**

| Type | Options | Default output |
|------|---------|----------------|
| `currency` | `prefix` (`$`), `decimals` (`2`), `decimal_separator` (`.`), `thousands_separator` (`,`) | `$ 1,234.56` |
| `number` | `decimals` (`0`), `decimal_separator` (`.`), `thousands_separator` (`,`) | `1,235` |
| `date` | `format` (`Y-m-d`) | `2025-01-15` |
| `datetime` | `format` (`Y-m-d H:i:s`) | `2025-01-15 14:30:00` |
| `percentage` | `decimals` (`1`), `suffix` (`%`) | `75.0%` |
| `boolean` | `true` (`Yes`), `false` (`No`) | `Yes` / `No` |

### Grouping & Totals

#### `groupBy(string|array $column)`

Group rows by one or more columns. When the group value changes, a subtotal row is inserted:

```php
// Single group
->groupBy('City')

// Multiple groups
->groupBy(['Country', 'City'])
```

#### `showTotal(array $columns)`

Display totals for numeric columns. Each entry maps a column name to a display type:

```php
->showTotal([
    'Balance'  => 'point',       // Shows: 1,234.56
    'Quantity' => 'QTY',         // Shows: QTY 1,234.56
    'Revenue'  => 'USD',         // Shows: USD 1,234.56
])
```

| Type | Output format |
|------|---------------|
| `'point'` | `1,234.56` (number only) |
| Any string | `PREFIX 1,234.56` (uppercased prefix + number) |

**Advanced aggregation types** -- beyond `sum`, you can use:

```php
->showTotal([
    'amount'   => 'sum',       // Sum of all values (default)
    'quantity' => 'avg',       // Average
    'price'    => 'max',       // Maximum value
    'discount' => 'min',       // Minimum value
    'orders'   => 'count',     // Number of rows
    'balance'  => 'point',     // Sum, displayed without a label prefix
])
```

| Aggregation | Description |
|-------------|-------------|
| `sum` | Sum of all values (default for unknown types) |
| `avg` | Arithmetic mean |
| `min` | Minimum value |
| `max` | Maximum value |
| `count` | Number of rows |
| `point` | Same as `sum`, but displayed without a label prefix |

### Conditional Formatting

Apply CSS styles to cells based on their values. In PDF/Excel reports the styles are applied as inline CSS. CSV reports ignore formatting gracefully.

#### `conditionalFormat(string $columnName, callable $condition, array $styles)`

```php
->conditionalFormat('amount', fn ($value) => $value > 1000, [
    'class'      => 'bold',
    'background' => '#ffcccc',
])
->conditionalFormat('status', fn ($value) => $value === 'Overdue', [
    'color'       => '#ff0000',
    'font-weight' => 'bold',
])
```

The condition callback receives `($cellValue, $rowObject)`, so you can also style based on other columns:

```php
->conditionalFormat('name', fn ($value, $row) => $row->balance < 0, [
    'color' => 'red',
])
```

### Report Events / Hooks

Register callbacks that fire at specific points in the report lifecycle. Useful for logging, progress tracking, auditing, and post-processing.

```php
->onBeforeRender(function () {
    Log::info('Report generation started');
})
->onRow(function ($row, int $index) {
    // Fires for each row -- useful for progress tracking.    
})
->onAfterRender(function () {
    Log::info('Report rendering complete');
})
->onComplete(function () {
    Notification::send($admin, new ReportReadyNotification);
})
```

Multiple callbacks can be registered for the same event -- they fire in registration order.

### Custom Headers & Footers

Customize the header and footer content of PDF reports. Supports positional placement and placeholders.

#### `setHeaderContent(string $content, string $position = 'center')`

```php
->setHeaderContent('Company Report', 'center')
->setHeaderContent('Confidential', 'left')
```

#### `setFooterContent(string $content, string $position = 'center')`

```php
->setFooterContent('Page {page} of {pages}', 'right')
->setFooterContent('Printed: {date}', 'left')
```

#### `clearHeader()` / `clearFooter()`

Remove all header or footer content:

```php
->clearFooter()  // No footer
```

**Available placeholders:**

| Placeholder | Description |
|-------------|-------------|
| `{page}` | Current page number |
| `{pages}` | Total page count |
| `{date}` | Current date (`Y-m-d`) |
| `{title}` | Report title |

**Defaults:**

```php
// Footer defaults (matches previous behavior)
'left'  => 'Date Printed: {date}'
'right' => 'Page {page} of {pages}'

// Header defaults: empty (no header)
```

### Multi-Format Export

Define a report once and export to multiple formats without duplicating configuration. Use `ReportExporter` to build the report, then call `toPdf()`, `toExcel()`, or `toCsv()`.

```php
use SamuelTerra22\ReportGenerator\Facades\ReportExporter;

$exporter = ReportExporter::of('Sales Report', $meta, $query, $columns)
    ->editColumn('amount', ['displayAs' => fn ($r) => '$' . $r->amount])
    ->formatColumn('date', 'date', ['format' => 'd/m/Y'])
    ->showTotal(['amount' => 'sum'])
    ->groupBy('region');

// Export to any format from the same definition:
$pdf   = $exporter->toPdf()->make();
$excel = $exporter->toExcel()->download('report');
$csv   = $exporter->toCsv()->download('report');
```

`ReportExporter` supports all the same fluent methods as the individual report classes (`editColumn`, `formatColumn`, `groupBy`, `showTotal`, `conditionalFormat`, `cacheFor`, etc.).

### Report Caching

Cache the rendered report output to avoid re-rendering on repeated requests. Cached HTML is stored via Laravel's cache system.

#### `cacheFor(int $minutes)`

Enable caching with a TTL in minutes:

```php
PdfReport::of(...)->cacheFor(60)->make();
```

#### `cacheAs(string $key)`

Set a custom cache key (otherwise an auto-generated key based on title, columns, meta, limit, and groupBy is used):

```php
->cacheFor(60)->cacheAs('monthly-sales-report')
```

#### `cacheUsing(string $store)`

Use a specific cache store (e.g., `redis`, `file`, `array`):

```php
->cacheFor(60)->cacheUsing('redis')
```

#### `noCache()`

Explicitly disable caching (useful to override a previously set `cacheFor`):

```php
->cacheFor(60)->noCache()  // Caching disabled
```

> On cache hit, the template rendering step is skipped entirely and the cached HTML is loaded directly into the PDF/CSV engine.

### Layout & Styling

#### `setPaper(string $size)` *(PDF only)*

Set the paper size. Default: `a4`.

```php
->setPaper('letter')  // letter, legal, a3, a4, a5, etc.
```

#### `setOrientation(string $orientation)` *(PDF only)*

Set page orientation. Default: `portrait`.

```php
->setOrientation('landscape')
```

#### `setCss(array $styles)` *(PDF & Excel)*

Inject custom CSS rules into the report template:

```php
->setCss([
    '.table'    => 'font-size: 11px;',
    'th'        => 'background-color: #4472C4; color: white;',
    'tr.even'   => 'background-color: #D9E2F3;',
])
```

#### `showHeader(bool $value = true)`

Show or hide the column header row. Default: `true`.

```php
->showHeader(false)
```

#### `showMeta(bool $value = true)`

Show or hide the meta information section. Default: `true` (except CSV, which defaults to `false`).

```php
->showMeta(false)
```

#### `showNumColumn(bool $value = true)`

Show or hide the auto-incrementing row number column (`No`). Default: `true`.

```php
->showNumColumn(false)
```

### Performance

#### `limit(int $n)`

Limit the number of rows processed:

```php
->limit(500)
```

#### `withoutManipulation()`

Skip column editing logic entirely for faster generation. Uses a simpler template that renders raw column values:

```php
->withoutManipulation()
```

#### `simple()` *(Excel only)*

Use direct sheet manipulation instead of Blade template rendering. Better for large datasets:

```php
->simple()
```

---

## Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag="report-generator-config"
```

This creates `config/report-generator.php`:

```php
return [
    'flush'        => false,  // Enable output buffering flush during report generation
    'cache_store'  => null,   // Default cache store (null = Laravel default)
    'cache_prefix' => 'report-generator', // Prefix for auto-generated cache keys
];
```

## Customizing Templates

Publish the Blade templates to customize the report layout:

```bash
php artisan vendor:publish --tag="report-generator-views"
```

This publishes four templates to `resources/views/vendor/laravel-report-generator/`:

| Template | Used by |
|----------|---------|
| `general-pdf-template.blade.php` | PDF reports with column manipulation |
| `general-excel-template.blade.php` | Excel reports with column manipulation |
| `without-manipulation-pdf-template.blade.php` | PDF reports using `withoutManipulation()` |
| `without-manipulation-excel-template.blade.php` | Excel reports using `withoutManipulation()` |

---

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Credits

- [Samuel Terra](https://github.com/samuelterra22)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
