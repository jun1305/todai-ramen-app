@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex items-center justify-center my-8">
        <div class="flex items-center gap-1 bg-white p-1 rounded-full shadow-sm border border-gray-100">
            {{-- 前のページへ --}}
            @if ($paginator->onFirstPage())
                <span aria-disabled="true" aria-label="{{ __('pagination.previous') }}">
                    <span class="flex items-center justify-center w-8 h-8 rounded-full bg-gray-50 text-gray-300 cursor-not-allowed" aria-hidden="true">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                    </span>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="flex items-center justify-center w-8 h-8 rounded-full bg-white text-gray-500 hover:bg-orange-50 hover:text-orange-500 transition duration-150" aria-label="{{ __('pagination.previous') }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                </a>
            @endif

            {{-- ページ番号の要素 --}}
            @foreach ($elements as $element)
                {{-- "..." の表示 --}}
                @if (is_string($element))
                    <span aria-disabled="true">
                        <span class="flex items-center justify-center w-8 h-8 text-gray-400 text-xs">{{ $element }}</span>
                    </span>
                @endif

                {{-- 数字の配列 --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page">
                                <span class="flex items-center justify-center w-8 h-8 rounded-full bg-orange-500 text-white font-bold text-sm shadow-md transform scale-105">
                                    {{ $page }}
                                </span>
                            </span>
                        @else
                            <a href="{{ $url }}" class="flex items-center justify-center w-8 h-8 rounded-full bg-white text-gray-500 hover:bg-orange-50 hover:text-orange-600 transition duration-150 text-sm" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- 次のページへ --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="flex items-center justify-center w-8 h-8 rounded-full bg-white text-gray-500 hover:bg-orange-50 hover:text-orange-500 transition duration-150" aria-label="{{ __('pagination.next') }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </a>
            @else
                <span aria-disabled="true" aria-label="{{ __('pagination.next') }}">
                    <span class="flex items-center justify-center w-8 h-8 rounded-full bg-gray-50 text-gray-300 cursor-not-allowed" aria-hidden="true">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </span>
                </span>
            @endif
        </div>
    </nav>
@endif