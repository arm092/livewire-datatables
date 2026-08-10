# Changelog

All notable changes to `livewire-datatables` will be documented in this file

## 3.2.3 ( 2026-08-11 )

### What's Changed

- Fix overlapping desktop pagination controls that made the hover background appear shifted.
- Give desktop and mobile pagination controls stable sizing, spacing, focus states, and accessible labels.
- Add a Chromium regression that verifies hover and keyboard focus do not move pagination buttons.
- Refresh the README structure, links, badges, compatibility details, and default Apricode-theme screenshot.

## 3.2.2 ( 2026-08-11 )

### What's Changed

- Keep the default theme inside the datatable's single Livewire root so Livewire 4 attaches `wire:id` and `wire:snapshot` to `.ld-table` instead of the preceding `<style>` element.
- Keep the column picker styles inside its own container rather than emitting a sibling after the partial.
- Add a Laravel 13 / Livewire 4 regression covering the root DOM, sort, search, and pagination interactions.
- Document the view refresh required for applications with published package templates.

## 3.2.1 ( 2026-08-09 )

### What's Changed

- Fix editable-cell input flashes after Livewire hydration and morphing when Livewire CSP-safe mode is enabled.
- Replace CSP-unsafe inline Alpine objects, callbacks, and class maps with an automatically loaded registered Alpine data component.
- Preserve independent editable-cell state, focus, click-away, blur, Enter, and the five-second `fieldEdited` indicator across morphs.
- Add a Chromium regression running the real Livewire CSP bundle under a restrictive Content Security Policy.
- Document the required editable-view refresh for applications that previously published package views.

## 3.2.0 ( 2026-08-07 )

### What's Changed

- Add Laravel validation rules to editable columns through `Column::rules()` and display validation feedback inside the edited cell.
- Support model-aware validation rule closures for record-dependent constraints such as unique values that ignore the current model.
- Add opt-in [strict mutation authorization](README.md#strict-mutation-authorization), which denies built-in editable and delete mutations when the target model has no registered policy.
- Cover editable validation and strict mutation authorization with regression tests.

## 3.1.1 ( 2026-08-07 )

### What's Changed

- Restyle the default datatable views with the Apricode graphite, paper, orange, green, pink, and cyan palette.
- Add a self-contained scoped theme that works without consumer Tailwind configuration and leaves custom callback/view HTML untouched.
- Improve table hierarchy, controls, filter states, row hover feedback, summaries, empty states, pagination, responsive toolbars, focus visibility, and reduced-motion behavior.
- Add accessible labels to previously icon-only or ambiguous toolbar actions.
- Fix malformed markup in the inline-hide header template and cover the default theme tokens with a regression test.

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

