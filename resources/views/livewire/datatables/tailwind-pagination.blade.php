<nav class="ld-pagination-pages inline-flex overflow-hidden border border-gray-300 rounded pagination" aria-label="Pagination">
    <!-- Previous Page Link -->
    @if ($paginator->onFirstPage())
    <button class="ld-pagination-button relative inline-flex items-center justify-center px-3 py-2 text-sm font-medium leading-5 text-gray-500 bg-white"
        type="button" aria-label="{{ __('Previous page') }}" disabled>
        <span aria-hidden="true">&lsaquo;</span>
    </button>
    @else
    <button wire:click="previousPage"
        id="pagination-desktop-page-previous"
        type="button" aria-label="{{ __('Previous page') }}"
        class="ld-pagination-button relative inline-flex items-center justify-center px-3 py-2 text-sm font-medium leading-5 text-gray-500 transition duration-150 ease-in-out bg-white hover:text-gray-400 focus:z-10 focus:outline-none focus:border-blue-300 focus:shadow-outline-blue active:bg-gray-100 active:text-gray-500">
        <span aria-hidden="true">&lsaquo;</span>
    </button>
    @endif

    <div class="ld-pagination-numbers inline-flex">
        @foreach ($elements as $element)
        @if (is_string($element))
        <button class="ld-pagination-button relative inline-flex items-center justify-center px-3 py-2 text-sm font-medium leading-5 text-gray-700 bg-white" type="button" disabled>
            <span>{{ $element }}</span>
        </button>
        @endif

        <!-- Array Of Links -->

        @if (is_array($element))
        @foreach ($element as $page => $url)
        <button wire:click="gotoPage({{ $page }})"
                id="pagination-desktop-page-{{ $page }}"
                type="button"
                aria-label="{{ __('Go to page :page', ['page' => $page]) }}"
                @if($page === $paginator->currentPage()) aria-current="page" @endif
                class="ld-pagination-button relative inline-flex items-center justify-center px-3 py-2 text-sm leading-5 font-medium text-gray-700 hover:text-gray-500 focus:z-10 focus:outline-none focus:border-blue-300 focus:shadow-outline-blue active:bg-gray-100 active:text-gray-700 transition ease-in-out duration-150 {{ $page === $paginator->currentPage() ? 'ld-pagination-current bg-gray-200' : 'bg-white' }}">
            {{ $page }}
            </button>
        @endforeach
        @endif
        @endforeach
    </div>

    <!-- Next Page Link -->
    @if ($paginator->hasMorePages())
    <button wire:click="nextPage"
        id="pagination-desktop-page-next"
        type="button" aria-label="{{ __('Next page') }}"
        class="ld-pagination-button relative inline-flex items-center justify-center px-3 py-2 text-sm font-medium leading-5 text-gray-500 transition duration-150 ease-in-out bg-white hover:text-gray-400 focus:z-10 focus:outline-none focus:border-blue-300 focus:shadow-outline-blue active:bg-gray-100 active:text-gray-500">
        <span aria-hidden="true">&rsaquo;</span>
    </button>
    @else
    <button type="button" aria-label="{{ __('Next page') }}"
        class="ld-pagination-button relative inline-flex items-center justify-center px-3 py-2 text-sm font-medium leading-5 text-gray-500 bg-white"
        disabled><span aria-hidden="true">&rsaquo;</span></button>
    @endif
</nav>
