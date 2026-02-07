# Changelog

All notable changes to `laravel-report-generator` will be documented in this file.

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
