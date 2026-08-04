# Changelog

All notable changes to `livewire-datatables` will be documented in this file

## 3.1.0 ( 2026-08-04 )

### What's Changed

- Harden editable and delete actions by resolving records through the table builder and applying registered model policies.
- Bind selected and pinned record identifiers in database queries and keep pinned records inside the table builder scope.
- Escape plain database values while preserving developer-controlled callback and view HTML.
- Export formula-like spreadsheet values as text.
- Remove the unused public file-export route and controller.
- Support empty model tables and fix optional display hooks and pin action defaults.
- Update to Laravel 13.23+, Livewire 4.3.5+, Laravel Excel 3.1.69+ and relation joins 9.0.1+ on their current major lines.
- Replace the obsolete Travis configuration with a GitHub Actions matrix for Laravel 10–13.
- Remove the obsolete relation-joins patch and Composer patch plugin setup.

## 1.0.0 - 201X-XX-XX ( to be released in the future... )

- initial release

## 0.9.0 ( 2022-03-22 )

- Breaking Change: 'unsortable' has been renamed to 'sortable', which is more intuitive. Please adjust your overwritten views, if any (thyseus).

