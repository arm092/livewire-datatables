@once
    <style>
        .ld-table {
            --ld-ink: #060606;
            --ld-graphite: #272822;
            --ld-paper: #f8f8f2;
            --ld-white: #ffffff;
            --ld-primary: #fd971f;
            --ld-danger: #f92672;
            --ld-success: #a6e22e;
            --ld-info: #66d9ef;
            --ld-muted: #75715e;
            --ld-border: #dddcd7;
            color: var(--ld-graphite);
        }

        .ld-table *,
        .ld-table *::before,
        .ld-table *::after {
            box-sizing: border-box;
        }

        .ld-table :is(.ld-search, .ld-toolbar-actions, .ld-filter-control, .ld-query, .ld-delete, .ld-column-picker, .ld-pagination, .ld-row-checkbox) :is(button, input, select),
        .ld-table :is(.ld-button, .ld-header-cell, .ld-per-page) {
            transition: border-color 150ms ease, box-shadow 150ms ease, color 150ms ease, background-color 150ms ease, opacity 150ms ease;
        }

        .ld-table :is(.ld-search, .ld-toolbar-actions, .ld-filter-control, .ld-query, .ld-delete, .ld-column-picker) :is(input:not([type="checkbox"]), select),
        .ld-table select.ld-per-page {
            border-color: var(--ld-border) !important;
            background-color: var(--ld-white) !important;
            color: var(--ld-graphite) !important;
        }

        .ld-table :is(.ld-search, .ld-toolbar-actions, .ld-filter-control, .ld-query, .ld-delete, .ld-column-picker, .ld-pagination, .ld-row-checkbox) :is(button, input, select, a):focus-visible,
        .ld-table :is(.ld-button, .ld-header-cell, .ld-per-page):focus-visible {
            border-color: var(--ld-primary) !important;
            box-shadow: 0 0 0 3px rgb(253 151 31 / 22%) !important;
            outline: none !important;
        }

        .ld-table :is(.ld-row-checkbox, .ld-filter-control) input[type="checkbox"] {
            accent-color: var(--ld-primary);
            color: var(--ld-primary) !important;
        }

        .ld-table .ld-toolbar {
            gap: .75rem;
            margin-bottom: .75rem;
        }

        .ld-table .ld-toolbar-actions {
            gap: .5rem;
        }

        .ld-table .ld-search {
            min-width: min(24rem, 100%);
        }

        .ld-table .ld-search input {
            min-height: 2.75rem;
            border-radius: .75rem !important;
            box-shadow: 0 1px 2px rgb(39 40 34 / 6%);
        }

        .ld-table .ld-button {
            min-height: 2.5rem;
            border: 1px solid var(--ld-border) !important;
            border-radius: .625rem !important;
            background: var(--ld-white) !important;
            color: var(--ld-graphite) !important;
            box-shadow: 0 1px 2px rgb(39 40 34 / 8%);
        }

        .ld-table .ld-button:hover {
            border-color: var(--ld-primary) !important;
            background: var(--ld-primary) !important;
            color: var(--ld-ink) !important;
        }

        .ld-table .ld-button--primary {
            border-color: var(--ld-primary) !important;
            color: var(--ld-primary) !important;
        }

        .ld-table .ld-button--primary:hover {
            background: var(--ld-primary) !important;
            color: var(--ld-white) !important;
        }

        .ld-table .ld-button--success {
            border-color: var(--ld-success) !important;
            color: var(--ld-success) !important;
        }

        .ld-table .ld-button--success:hover {
            border-color: var(--ld-success) !important;
            background: var(--ld-success) !important;
            color: var(--ld-white) !important;
        }

        .ld-table .ld-button--danger {
            border-color: var(--ld-danger) !important;
            color: var(--ld-danger) !important;
        }

        .ld-table .ld-button--danger:hover {
            border-color: var(--ld-danger) !important;
            background: var(--ld-danger) !important;
            color: var(--ld-white) !important;
        }

        .ld-table .ld-panel {
            overflow-x: auto;
            overflow-y: hidden;
            border: 1px solid var(--ld-border) !important;
            border-radius: 1rem !important;
            background: var(--ld-white) !important;
            box-shadow: 0 12px 30px rgb(39 40 34 / 9%) !important;
        }

        .ld-table .ld-panel--filtered {
            border-color: var(--ld-primary) !important;
            box-shadow: 0 0 0 2px rgb(253 151 31 / 14%), 0 12px 30px rgb(39 40 34 / 9%) !important;
        }

        .ld-table .ld-panel.rounded-b-none {
            border-radius: 1rem 1rem 0 0 !important;
        }

        .ld-table .ld-header-row,
        .ld-table .ld-header-row > .table-cell,
        .ld-table .ld-header-row .bg-gray-50,
        .ld-table .ld-header-cell {
            background: var(--ld-graphite) !important;
            border-color: #464438 !important;
            color: var(--ld-paper) !important;
        }

        .ld-table .ld-header-row .text-gray-500 {
            color: var(--ld-paper) !important;
        }

        .ld-table .ld-header-row :is(.text-blue-400, .text-blue-500, .text-blue-600) {
            color: var(--ld-primary) !important;
        }

        .ld-table .ld-header-row :is(.text-green-400, .text-green-500, .text-green-600) {
            color: var(--ld-success) !important;
        }

        .ld-table .ld-header-row :is(.bg-blue-100, .bg-blue-200, .bg-blue-300) {
            background: rgb(253 151 31 / 18%) !important;
            color: var(--ld-paper) !important;
        }

        .ld-table .ld-header-cell:hover {
            background: #33342d !important;
        }

        .ld-table .ld-filter-row,
        .ld-table .ld-filter-cell {
            background: var(--ld-primary) !important;
            border-color: #d37b12 !important;
        }

        .ld-table .ld-data-row > .table-cell {
            border-bottom: 1px solid #f1f1ef;
            background: var(--ld-white);
        }

        .ld-table .ld-data-row:hover > .table-cell {
            background: rgb(253 151 31 / 6%);
        }

        .ld-table .ld-summary-row > .table-cell {
            border-top: 2px solid var(--ld-primary);
            background: var(--ld-paper);
            color: var(--ld-ink);
            font-weight: 600;
        }

        .ld-table .ld-pagination {
            border: 1px solid var(--ld-border) !important;
            border-top: 0 !important;
            border-radius: 0 0 1rem 1rem !important;
            background: var(--ld-white) !important;
        }

        .ld-table .ld-pagination button:hover:not(:disabled) {
            background: rgb(253 151 31 / 10%) !important;
            color: var(--ld-ink) !important;
        }

        .ld-table .ld-pagination-pages {
            border-color: var(--ld-border) !important;
            background: var(--ld-white);
            box-shadow: 0 1px 2px rgb(39 40 34 / 6%);
        }

        .ld-table .ld-pagination-button {
            min-width: 2.5rem;
            min-height: 2.5rem;
            margin: 0 !important;
            border: 0 !important;
            border-left: 1px solid var(--ld-border) !important;
            border-radius: 0 !important;
            line-height: 1;
        }

        .ld-table .ld-pagination-pages > .ld-pagination-button:first-child {
            border-left: 0 !important;
        }

        .ld-table .ld-pagination-numbers {
            margin: 0;
        }

        .ld-table .ld-pagination-mobile-button {
            min-height: 2.75rem;
        }

        .ld-table .ld-pagination-current {
            background: var(--ld-primary) !important;
            color: var(--ld-ink) !important;
            font-weight: 700 !important;
        }

        .ld-table .ld-empty {
            margin: 0;
            padding: 3rem 1rem;
            background: var(--ld-paper);
            color: var(--ld-muted);
        }

        .ld-table .ld-filter-active {
            color: var(--ld-primary) !important;
        }

        .ld-table .ld-button.bg-blue-500 {
            border-color: var(--ld-primary) !important;
            background: var(--ld-primary) !important;
            color: var(--ld-ink) !important;
        }

        .ld-table .ld-button.bg-blue-100 {
            border-color: rgb(253 151 31 / 35%) !important;
            background: rgb(253 151 31 / 10%) !important;
            color: #985b13 !important;
        }

        .ld-table .ld-highlight {
            border-radius: .25rem;
            background: rgb(253 151 31 / 24%) !important;
            color: var(--ld-ink);
        }

        .ld-table .ld-link {
            color: #985b13 !important;
            text-decoration-color: rgb(253 151 31 / 50%);
            text-underline-offset: .2em;
        }

        .ld-table .ld-link:hover {
            border-color: var(--ld-primary) !important;
            background: rgb(253 151 31 / 10%) !important;
            color: #7c4a0f !important;
        }

        .ld-table :is(.ld-query, .ld-delete, .ld-column-picker, .ld-filter-control, .ld-boolean) :is(.text-blue-400, .text-blue-500, .text-blue-600) { color: #985b13 !important; }
        .ld-table :is(.ld-query, .ld-delete, .ld-column-picker, .ld-filter-control) :is(.border-blue-300, .border-blue-400, .border-blue-500) { border-color: var(--ld-primary) !important; }
        .ld-table :is(.ld-query, .ld-delete, .ld-column-picker, .ld-filter-control) :is(.bg-blue-100, .bg-blue-200) { background-color: rgb(253 151 31 / 10%) !important; }
        .ld-table .ld-filter-control:is(.bg-blue-100, .bg-blue-200) { background-color: rgb(253 151 31 / 10%) !important; }
        .ld-table :is(.ld-query, .ld-delete, .ld-column-picker, .ld-filter-control) :is(.bg-blue-500, .bg-blue-600) { background-color: var(--ld-primary) !important; color: var(--ld-ink) !important; }

        .ld-table :is(.ld-query, .ld-delete, .ld-column-picker, .ld-filter-control, .ld-boolean) :is(.text-green-400, .text-green-500, .text-green-600) { color: #64881c !important; }
        .ld-table :is(.ld-query, .ld-delete, .ld-column-picker, .ld-filter-control) .border-green-400 { border-color: var(--ld-success) !important; }
        .ld-table :is(.ld-query, .ld-delete, .ld-column-picker, .ld-filter-control) :is(.bg-green-100, .bg-green-200) { background-color: rgb(166 226 46 / 14%) !important; }
        .ld-table :is(.ld-query, .ld-delete, .ld-column-picker, .ld-filter-control) :is(.bg-green-500, .bg-green-600) { background-color: var(--ld-success) !important; color: var(--ld-ink) !important; }

        .ld-table :is(.ld-query, .ld-delete, .ld-column-picker, .ld-filter-control, .ld-boolean) :is(.text-red-300, .text-red-400, .text-red-500, .text-red-600) { color: #bb1d56 !important; }
        .ld-table :is(.ld-query, .ld-delete, .ld-column-picker, .ld-filter-control) .border-red-400 { border-color: var(--ld-danger) !important; }
        .ld-table :is(.ld-query, .ld-delete, .ld-column-picker, .ld-filter-control) :is(.bg-red-100, .bg-red-200) { background-color: rgb(249 38 114 / 10%) !important; }
        .ld-table :is(.ld-query, .ld-delete, .ld-column-picker, .ld-filter-control) :is(.bg-red-500, .bg-red-600) { background-color: var(--ld-danger) !important; color: var(--ld-white) !important; }

        .ld-table .ld-selection-count:is(.bg-orange-400, .bg-orange-500) { background-color: var(--ld-primary) !important; color: var(--ld-ink) !important; }

        @media (max-width: 767px) {
            .ld-table .ld-toolbar {
                align-items: stretch;
                flex-direction: column;
            }

            .ld-table .ld-toolbar-actions {
                justify-content: flex-start;
            }

            .ld-table .ld-search,
            .ld-table .ld-search > div {
                width: 100% !important;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .ld-table :is(.ld-search, .ld-toolbar-actions, .ld-filter-control, .ld-query, .ld-delete, .ld-column-picker, .ld-pagination, .ld-row-checkbox) :is(button, input, select),
            .ld-table :is(.ld-button, .ld-header-cell, .ld-per-page) {
                transition: none;
            }
        }
    </style>
@endonce
