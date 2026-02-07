# Changelog

All notable changes to `laravel-report-generator` will be documented in this file.

## v1.0.0 — Complete Package Restructure & Modernization - 2026-02-07

### Why reset releases?

This package has been completely restructured and modernized from the ground up. The scope of changes was significant enough that prior version history no longer accurately represented the current
state of the package. A clean v1.0.0 reflects this new foundation.

### What changed

#### Architecture

- Migrated to **Spatie Laravel Package Skeleton** conventions (`PackageServiceProvider`, standardized config/views publishing)
- Restructured source into `src/ReportMedia/`, `src/Facades/`, and `src/Support/` with proper PSR-4 autoloading
- Made `ReportGenerator` base class abstract with a clean fluent builder API
- Added `ReportExporter` class for multi-format export with extensive customization
- Added `Support\AggregationHelper` and `Support\ColumnFormatter` for modular internals
- Removed Lumen-specific support (Lumen is EOL)

#### Compatibility

- **PHP ^8.2** with `declare(strict_types=1)` across all files
- **Laravel 10, 11 & 12** support (`illuminate/support ^10.0||^11.0||^12.0`)
- Graceful PDF engine fallback: tries Snappy first, falls back to DomPDF

#### Quality

- **93 tests** with **99.6% line coverage** (Pest 3 + PHPUnit 11 + Orchestra Testbench)
- PHPStan static analysis with Larastan
- Laravel Pint code formatting
- GitHub Actions CI pipeline
- Comprehensive test coverage for PDF, Excel, and CSV reports including edge cases, events, caching, aggregation, and conditional formatting

#### CSV Reports

- New `CsvReport` class using `league/csv` with memory-efficient cursor iteration via `SplTempFileObject`

#### Developer Experience

- Docker-based development environment (no local PHP/Composer needed)
- Standardized Composer scripts (`test`, `analyse`, `format`, `test-coverage`)
- Updated and comprehensive README with usage examples

### Upgrading

This is a fresh start. If you were using a previous version, please review the [README](https://github.com/samuelterra22/laravel-report-generator#readme) for the updated API and installation
instructions.

## Unreleased

- Restructured package to follow Spatie Laravel Package Skeleton conventions
- Renamed `ServiceProvider` to `ReportGeneratorServiceProvider`
- Renamed `CSVReport` to `CsvReport` (PascalCase convention)
- Renamed facades: `PdfReportFacade` → `PdfReport`, `ExcelReportFacade` → `ExcelReport`, `CSVReportFacade` → `CsvReport`
- Moved views from `src/views/` to `resources/views/`
- Made `ReportGenerator` base class abstract
- Added `spatie/laravel-package-tools` dependency
- Added `declare(strict_types=1)` to all PHP files
- Removed Lumen support (Lumen is discontinued)
- Added `.editorconfig`, `.gitattributes`, `CHANGELOG.md`, `LICENSE.md`
