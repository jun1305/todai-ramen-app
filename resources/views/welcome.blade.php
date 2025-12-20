<x-app-layout title="ホーム">
    <div class="bg-gray-50 min-h-screen pb-7">
        
        {{-- メインカラム: 上下の余白(pt)と左右の余白(px)を少し詰めました --}}
        <div class="max-w-md mx-auto pt-4 px-2">

        {{-- ▼▼▼ キャンペーン ▼▼▼ --}}
            @if($campaign)
            <div class="mb-4 px-1">
                <div class="relative bg-gradient-to-br from-red-600 to-red-700 rounded-2xl p-4 text-white shadow-md shadow-red-200 overflow-hidden group">
                    {{-- 背景の装飾文字 --}}
                    <div class="absolute -right-4 -bottom-8 text-white opacity-10 font-black text-8xl italic select-none pointer-events-none transform group-hover:scale-110 transition duration-700">
                        x{{ $campaign->multiplier }}
                    </div>

                    <div class="relative z-10 flex justify-between items-center">
                        <div class="flex-1 pr-2">
                            <div class="flex items-baseline gap-2 mb-2">
                                <span class="bg-white/20 backdrop-blur-sm text-[10px] font-bold px-2 py-0.5 rounded-full text-white border border-white/10 shrink-0">
                                    PICKUP
                                </span>
                                <h2 class="text-lg font-black leading-tight tracking-tight line-clamp-1">
                                    {{-- ▼▼▼ 修正: リンクを追加（デザインは維持しつつ、ホバー時に下線を表示） ▼▼▼ --}}
                                    <a href="{{ route('shops.show', $campaign->shop_id) }}" class="hover:underline hover:text-red-50 transition-colors">
                                        {{ $campaign->title }}
                                    </a>
                                </h2>
                            </div>

                            <div class="flex items-center gap-1 text-xs font-bold text-red-100">
                                <span class="bg-yellow-400 w-1.5 h-1.5 rounded-full animate-pulse"></span>
                                今ならポイント{{ $campaign->multiplier }}倍！
                            </div>
                        </div>

                        {{-- マップボタン（変更なし） --}}
                        <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($campaign->shop->name ?? '') }}+ラーメン"
                           target="_blank"
                           class="shrink-0 inline-flex items-center justify-center bg-white text-red-700 text-xs font-bold w-10 h-10 rounded-full hover:bg-red-50 transition shadow-sm group-hover:shadow-md">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
            @endif
            {{-- ▲▲▲ キャンペーンここまで ▲▲▲ --}}

            {{-- ▼▼▼ 投稿フィード ▼▼▼ --}}
            {{-- space-y-8 だと広すぎるので space-y-4 (16px) に詰めました --}}
            <div class="space-y-4">
                @foreach($posts as $post)
                {{-- ▼▼▼ 修正1: relative を追加（中の絶対配置リンクの基準にするため） ▼▼▼ --}}
                <div class="relative bg-white rounded-3xl shadow-[0_2px_15px_-3px_rgba(0,0,0,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] overflow-hidden border border-gray-100/50 transition hover:shadow-lg">
                    
                    {{-- ▼▼▼ 修正2: カード全体を覆う透明なリンク（投稿詳細へ） ▼▼▼ --}}
                    <a href="{{ route('posts.show', $post) }}" class="absolute inset-0 z-0"></a>

                    {{-- 1. ヘッダー --}}
                    <div class="px-4 py-3 flex items-center justify-between">
                        {{-- ▼▼▼ 修正3: 既存リンクには relative z-10 をつけて手前に持ってくる ▼▼▼ --}}
                        <a href="{{ route('users.show', $post->user->id) }}" class="relative z-10 flex items-center gap-3 group">
                            <div class="h-9 w-9 rounded-full overflow-hidden border-2 border-white shadow-sm ring-1 ring-gray-100">
                                @if($post->user->icon_path)
                                    <img src="{{ asset($post->user->icon_path) }}" class="w-full h-full object-cover" alt="{{ $post->user->name }}" />
                                @else
                                    <div class="w-full h-full bg-gradient-to-br from-orange-100 to-orange-200 flex items-center justify-center text-orange-500 font-black text-xs">
                                        {{ mb_substr($post->user->name, 0, 1) }}
                                    </div>
                                @endif
                            </div>
                            <div>
                                <p class="text-sm font-bold text-gray-900 leading-none group-hover:text-orange-600 transition">{{ $post->user->name }}</p>
                                <p class="text-[10px] text-gray-400 mt-0.5 font-medium flex items-center gap-1">
                                    <span>{{ $post->eaten_at->format('Y.m.d') }}</span>
                                    <span class="text-gray-300">•</span>
                                    <span>{{ $post->eaten_at->diffForHumans() }}</span>
                                </p>
                            </div>
                        </a>
                    </div>

                    {{-- 2. 画像 --}}
                    <div class="relative w-full aspect-square bg-gray-100 pointer-events-none"> {{-- 画像自体はクリックイベントを通す必要がないので pointer-events-none でもOK --}}
                        @if($post->image_path)
                            <img src="{{ asset($post->image_path) }}" loading="lazy" class="w-full h-full object-cover" alt="ラーメン画像" />
                        @else
                            <div class="w-full h-full flex flex-col items-center justify-center text-gray-300 bg-gray-50">
                                <span class="text-xs font-bold tracking-widest uppercase">No Image</span>
                            </div>
                        @endif

                        {{-- 点数バッジ --}}
                        <div class="absolute bottom-3 right-3 bg-white/90 backdrop-blur-md px-2.5 py-1 rounded-xl shadow-lg border border-white/50 flex items-baseline gap-1">
                            <span class="text-xl font-black text-orange-500 tracking-tighter">{{ $post->score }}</span>
                            <span class="text-[9px] font-bold text-gray-500">点</span>
                        </div>
                    </div>

                    {{-- 3. アクション & コンテンツ --}}
                    <div class="px-4 pt-3 pb-5">
                        <div class="flex items-center justify-between mb-2">
                            {{-- いいねボタン --}}
                            {{-- ▼▼▼ 修正4: ここも relative z-10 を追加 ▼▼▼ --}}
                            <div class="relative z-10" x-data="{ liked: {{ $post->isLikedBy(Auth::user()) ? 'true' : 'false' }}, count: {{ $post->likes->count() }} }">
                                <button @click="fetch('/posts/{{ $post->id }}/like', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } }).then(res => res.json()).then(data => { liked = (data.status === 'added'); count = data.count; })"
                                    class="flex items-center gap-1.5 group -ml-1 p-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 transition-colors duration-300" :class="liked ? 'text-red-500 fill-current' : 'text-gray-800'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                    </svg>
                                    <span x-show="count > 0" x-text="count" class="text-xs font-bold text-gray-700"></span>
                                </button>
                            </div>

                            {{-- マップ --}}
                            {{-- ▼▼▼ 修正5: relative z-10 を追加 ▼▼▼ --}}
                            <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($post->shop->name) }}+ラーメン" 
                               target="_blank" 
                               class="relative z-10 flex items-center gap-1 text-gray-400 hover:text-green-600 transition p-1">

                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>

                                @if($post->shop->short_address)
                                    <span class="text-xs font-bold">{{ $post->shop->short_address }}</span>
                                @endif
                            </a>
                        </div>

                        <div class="mb-1">
                            {{-- 店名リンク --}}
                            {{-- ▼▼▼ 修正6: relative z-10 を追加 ▼▼▼ --}}
                            <a href="{{ route('shops.show', $post->shop->id) }}" class="relative z-10 text-base font-black text-gray-900 hover:text-orange-600 transition line-clamp-1">
                                {{ $post->shop->name }}
                            </a>
                        </div>
                        
                        {{-- コメント（ここをクリックしても詳細へ飛ぶ） --}}
                        <p class="text-xs text-gray-700 leading-relaxed font-medium line-clamp-3">
                            {{ $post->comment }}
                        </p>
                    </div>

                </div>
                @endforeach
            </div>

            {{-- ページネーション --}}
            <div class="mt-6 mb-5">
                {{ $posts->links('vendor.pagination.ramen') }}
            </div>
            
            {{-- 空の状態 --}}
            @if($posts->isEmpty())
            <div class="text-center py-20 text-gray-400">
                <p class="font-bold">まだ投稿がありません</p>
            </div>
            @endif

        </div>
    </div>
</x-app-layout>