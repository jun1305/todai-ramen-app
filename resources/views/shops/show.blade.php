<x-app-layout title="{{ $shop->name }}">
    <div class="bg-white p-6 shadow-sm border-b border-gray-100 mb-4 -mx-4 -mt-4 pt-8 relative">
        
        {{-- 戻るボタン --}}
        <a 
            href="{{ route('shops.index') }}" 
            onclick="event.preventDefault(); history.back();"
            class="absolute left-1 top-4 text-gray-400 hover:text-gray-600 transition p-4 rounded-full active:bg-gray-50 z-10"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </a>

        {{-- 店名エリア --}}
        <div class="flex items-center mb-2 px-2 pl-10 relative z-0"> {{-- mb-4 を mb-2 に詰めて平均点との距離を調整 --}}
            <div class="h-16 w-16 rounded-full bg-gray-100 overflow-hidden shadow-md border-2 border-white mr-4 shrink-0">
                @if($shop->latestPost && $shop->latestPost->image_path)
                <img src="{{ asset($shop->latestPost->image_path) }}" loading="lazy" class="w-full h-full object-cover" />
                @else
                <div class="w-full h-full flex items-center justify-center bg-orange-100 text-orange-600 font-black text-2xl">
                    {{ mb_substr($shop->name, 0, 1) }}
                </div>
                @endif
            </div>
            <h1 class="text-2xl font-black text-gray-800 leading-tight line-clamp-2">
                {{ $shop->name }}
            </h1>
        </div>

        {{-- ▼▼▼ 追加: 平均点表示エリア (5件以上の場合のみ表示) ▼▼▼ --}}
        @if($shop->posts_count >= 5)
        <div class="px-2 pl-10 mb-4">
            <div class="inline-flex items-center gap-3 bg-orange-50 border border-orange-100 px-4 py-4 mt-1 rounded-xl shadow-sm">
                <div class="flex flex-col leading-none">
                    <span class="text-[10px] font-bold text-orange-400">みんなの平均</span>
                    <span class="text-[10px] font-bold text-gray-400">({{ $shop->posts_count }}件の口コミ)</span>
                </div>
                <div class="flex items-baseline gap-1 text-orange-600 leading-none border-l border-orange-200 pl-3">
                    <span class="text-2xl font-black tracking-tighter">{{ number_format($avgScore, 1) }}</span>
                    <span class="text-xs font-bold text-orange-400">点</span>
                </div>
            </div>
        </div>
        @endif
        {{-- ▲▲▲ ここまで ▲▲▲ --}}

        {{-- 外部リンクボタン群 --}}
        <div class="flex justify-start gap-2 flex-wrap px-2 pl-10 mt-4">
            <a href="https://www.google.com/search?q={{ urlencode($shop->name) }}+X" target="_blank" class="flex items-center gap-1 bg-black hover:bg-gray-800 text-white text-xs font-bold px-3 py-2 rounded-full transition shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 fill-current" viewBox="0 0 24 24">
                    <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" />
                </svg>
                Post
            </a>

            <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($shop->name) }}+ラーメン" target="_blank" class="flex items-center gap-1 bg-gray-100 hover:bg-gray-200 text-gray-600 text-xs font-bold px-3 py-2 rounded-full transition border border-gray-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                </svg>
                マップ
            </a>

            <a href="https://www.google.com/search?q={{ urlencode($shop->name) }}+食べログ" target="_blank" class="flex items-center gap-1 bg-yellow-50 hover:bg-yellow-100 text-yellow-700 text-xs font-bold px-3 py-2 rounded-full transition border border-yellow-200">
                <span class="text-sm leading-none">🥢</span> 食べログ
            </a>

            <a href="https://www.google.com/search?q={{ urlencode($shop->name) }}+ラーメンDB" target="_blank" class="flex items-center gap-1 bg-red-50 hover:bg-red-100 text-red-700 text-xs font-bold px-3 py-2 rounded-full transition border border-red-200">
                <span class="text-sm leading-none">🍜</span> RDB
            </a>
        </div>
    </div>

    <div class="px-2 pb-20">
        <h2 class="text-sm font-bold text-gray-500 mb-3 px-2">
            みんなの記録 ({{ $shop->posts_count }}件)
        </h2>

        @foreach($posts as $post)
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden mb-4">
            
            {{-- ヘッダー部分 --}}
            <div class="p-3 flex items-center justify-between border-b border-gray-50">
                
                <a href="{{ route('users.show', $post->user->id) }}" class="flex items-center gap-2 group">
                    <div class="h-6 w-6 rounded-full overflow-hidden shrink-0 border border-gray-100 group-hover:border-orange-300 transition">
                        @if($post->user->icon_path)
                        <img src="{{ asset($post->user->icon_path) }}" loading="lazy" class="w-full h-full object-cover" alt="{{ $post->user->name }}" />
                        @else
                        <div class="w-full h-full bg-blue-100 flex items-center justify-center text-[10px] font-bold text-blue-600">
                            {{ mb_substr($post->user->name, 0, 1) }}
                        </div>
                        @endif
                    </div>
                    <span class="text-xs font-bold text-gray-700 group-hover:text-orange-600 transition">
                        {{ $post->user->name }}
                    </span>
                </a>

                <div class="text-[10px] text-gray-400 flex items-center gap-1">
                    <span>{{ $post->eaten_at->format('Y/m/d') }}</span>
                    <span class="text-gray-300">•</span>
                    <span>{{ $post->eaten_at->diffForHumans() }}</span>
                </div>
            </div>

            {{-- コンテンツ部分 --}}
            <div class="flex">
                <div class="flex-1 p-3 min-w-0">
                    <div class="flex items-baseline gap-0.5 text-orange-600 leading-none mb-1">
                        <span class="text-lg font-black tracking-tighter">{{ $post->score }}</span>
                        <span class="text-[9px] font-bold text-orange-400">点</span>
                    </div>

                    <p class="text-sm text-gray-700 leading-snug line-clamp-3">
                        {{ $post->comment }}
                    </p>
                </div>

                @if($post->image_path)
                <div class="w-28 bg-gray-100 relative shrink-0">
                    <img src="{{ asset($post->image_path) }}" loading="lazy" class="absolute inset-0 w-full h-full object-cover" />
                </div>
                @endif
            </div>
        </div>
        @endforeach 
        
        @if($posts->isEmpty())
        <div class="text-center py-10 text-gray-400 text-sm">
            まだ投稿がありません
        </div>
        @endif
        
        <div class="mt-8 pb-10">
            {{ $posts->links('vendor.pagination.ramen') }}
        </div>
    </div>
</x-app-layout>