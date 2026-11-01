# Changelog

All notable changes to `livewire-datatables` will be documented in this file

## 2.3.10 ( 2026-11-02 )

### Security

- Require server-side authorization for built-in delete and editable mutations.
- Resolve mutation targets through the datatable builder scope and reject unconfigured editable fields.
- Prevent clients from replacing the datatable model during Livewire hydration.

## 1.0.0 - 201X-XX-XX ( to be released in the future... )

- initial release

## 0.9.0 ( 2022-03-22 )

- Breaking Change: 'unsortable' has been renamed to 'sortable', which is more intuitive. Please adjust your overwritten views, if any (thyseus).

