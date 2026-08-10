<div class="ld-pagination-mobile flex justify-between">
<!-- Previous Page Link -->
@if ($paginator->onFirstPage())
<div class="ld-pagination-mobile-button w-32 flex justify-between items-center relative px-4 py-2 border border-gray-300 text-sm leading-5 font-medium rounded-md text-gray-400 bg-gray-50" aria-disabled="true">
    <x-icons.arrow-left />
    {{ __('Previous')}}
</div>
@else
<button wire:click="previousPage" id="pagination-mobile-page-previous" type="button" class="ld-pagination-mobile-button w-32 flex justify-between items-center relative px-4 py-2 border border-gray-300 text-sm leading-5 font-medium rounded-md text-gray-700 bg-white hover:text-gray-500 focus:outline-none focus:shadow-outline-blue focus:border-blue-300 active:bg-gray-100 active:text-gray-700 transition ease-in-out duration-150">
    <x-icons.arrow-left />
    {{ __('Previous')}}
</button>
@endif


<!-- Next Page Link -->
@if ($paginator->hasMorePages())
<button wire:click="nextPage" id="pagination-mobile-page-next" type="button" class="ld-pagination-mobile-button w-32 flex justify-between items-center relative px-4 py-2 border border-gray-300 text-sm leading-5 font-medium rounded-md text-gray-700 bg-white hover:text-gray-500 focus:outline-none focus:shadow-outline-blue focus:border-blue-300 active:bg-gray-100 active:text-gray-700 transition ease-in-out duration-150">
    {{ __('Next')}}
    <x-icons.arrow-right />
</button>
@else
<div class="ld-pagination-mobile-button w-32 flex justify-between items-center relative px-4 py-2 border border-gray-300 text-sm leading-5 font-medium rounded-md text-gray-400 bg-gray-50" aria-disabled="true">
    {{ __('Next')}}
    <x-icons.arrow-right class="inline" />
</div>
@endif
</div>
