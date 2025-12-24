<x-app-layout title="{{ $shop->name }}">
    <div class="bg-white shadow-sm border-b border-gray-100 mb-4 -mx-4 -mt-4 pb-6 pt-safe relative">
        
        {{-- 戻るボタン --}}
        <div class="px-4 pt-4 mb-2">
            <a href="{{ route('shops.index') }}" 
               onclick="event.preventDefault(); history.back();"
               class="inline-flex items-center text-gray-400 hover:text-gray-600 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                <span class="text-sm font-bold ml-1">戻る</span>
            </a>
        </div>

        {{-- ★★★ 修正: 右上のボタンエリア（保存 ＆ 編集を縦並びに） ★★★ --}}
        {{-- ★★★ 修正: 右上のボタンエリア（縦並び・デザイン調整） ★★★ --}}
        <div class="absolute top-4 right-4 z-10 flex flex-col items-center gap-3">
            
            {{-- ① 保存ボタン（丸型・変更なし） --}}
            @auth
            <div x-data="{ bookmarked: {{ $shop->isBookmarkedBy(Auth::user()) ? 'true' : 'false' }} }">
                <button 
                    @click="fetch('{{ route('shops.bookmark', $shop) }}', { 
                        method: 'POST', 
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } 
                    })
                    .then(res => res.json())
                    .then(data => { bookmarked = data.bookmarked; })"
                    class="flex flex-col items-center justify-center h-12 w-12 rounded-full shadow-md border transition duration-300"
                    :class="bookmarked ? 'bg-yellow-50 border-yellow-200 text-yellow-500' : 'bg-white border-gray-100 text-gray-300 hover:text-yellow-400'"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 transition-transform active:scale-125" :class="bookmarked ? 'fill-current' : 'fill-none'" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                    </svg>
                    <span class="text-[8px] font-bold mt-0.5" x-text="bookmarked ? '登録中' : '保存'"></span>
                </button>
            </div>
            @endauth

            {{-- ② 編集ボタン（管理者のみ・横長のデザインに戻す） --}}
            @if(auth()->id() === 1)
                <a href="{{ route('shops.edit', $shop) }}" 
                   class="bg-white/90 backdrop-blur-sm border border-gray-200 shadow-sm text-gray-500 hover:text-orange-600 hover:border-orange-200 px-3 py-1.5 rounded-full text-xs font-bold flex items-center gap-1 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                    編集
                </a>
            @endif

        </div>
        {{-- ★★★ 修正ここまで ★★★ --}}

        <div class="px-6">
            <div class="flex items-start gap-4">
                {{-- 店舗アイコン --}}
                <div class="h-20 w-20 rounded-full bg-gray-100 overflow-hidden shadow-sm border border-gray-100 shrink-0 relative group">
                    @if($shop->latestPost && $shop->latestPost->image_path)
                        <img src="{{ asset($shop->latestPost->image_path) }}" class="w-full h-full object-cover group-hover:scale-110 transition duration-500" />
                    @else
                        <div class="w-full h-full flex items-center justify-center bg-orange-50 text-orange-400 font-black text-3xl">
                            {{ mb_substr($shop->name, 0, 1) }}
                        </div>
                    @endif
                </div>

                {{-- 店名 & スコア --}}
                <div class="flex-1 min-w-0 pt-1">
                    {{-- ▼▼▼ 修正: flex justify-between で左右配置 ▼▼▼ --}}
                    <div class="flex justify-between items-start mb-2">
                        {{-- 店名 --}}
                        <h1 class="text-xl font-black text-gray-800 leading-tight">
                            {{ $shop->name }}
                        </h1>
                    </div>
                    {{-- ▲▲▲ 修正ここまで ▲▲▲ --}}

                    {{-- ▼▼▼ 追加: ジャンルタグ表示 ▼▼▼ --}}
                    @if($shop->genres->isNotEmpty())
                        <div class="flex flex-wrap gap-1 mb-2">
                            @foreach($shop->genres as $genre)
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-md border bg-orange-50 text-orange-600 border-orange-100">
                                    {{ $genre->name }}
                                </span>
                            @endforeach
                        </div>
                    @endif
                    {{-- ▲▲▲ 追加ここまで ▲▲▲ --}}
                    
                    {{-- 住所（モデルの short_address を使用） --}}
                    @if($shop->address)
                        <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($shop->name) }}" target="_blank" class="inline-flex items-center gap-1 text-xs text-gray-400 hover:text-blue-500 hover:underline mb-2 transition">
                            <svg class="w-3 h-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span class="truncate font-bold">{{ $shop->short_address }}</span>
                        </a>
                    @else
                        {{-- 住所がない場合は「店名」でマップ検索 --}}
                        <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($shop->name . ' ラーメン') }}" target="_blank" class="inline-flex items-center gap-1 text-xs text-gray-400 hover:text-blue-500 hover:underline mb-2 transition">
                            {{-- 検索アイコン（虫眼鏡）に変更 --}}
                            <svg class="w-3 h-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <span class="truncate font-bold">住所未登録（マップで検索）</span>
                        </a>
                    @endif

                    {{-- 平均スコア --}}
                    @if($avgScore)
                        <div class="flex items-center gap-2">
                            <div class="flex items-baseline text-orange-500 leading-none">
                                <span class="text-2xl font-black tracking-tight">{{ number_format($avgScore, 1) }}</span>
                                <span class="text-xs font-bold ml-0.5">点</span>
                            </div>
                            <span class="text-xs text-gray-400">({{ $shop->posts_count }}件の記録)</span>
                        </div>
                    @else
                        <span class="text-xs text-gray-400 bg-gray-100 px-2 py-1 rounded-md">まだスコアがありません</span>
                    @endif
                </div>
            </div>

            {{-- アクションボタン（サイズ統一・整理） --}}
            <div class="flex flex-wrap items-center gap-2 mt-6">
                {{-- ① 記録する (メイン：オレンジに変更＆パラメータ修正) --}}
                {{-- 参考コードに合わせてパラメータを ['shop_name' => $shop->name] に変更 --}}
                <a href="{{ route('posts.create', ['shop_name' => $shop->name]) }}" 
                   class="h-9 bg-orange-600 hover:bg-orange-700 text-white text-xs font-bold px-4 rounded-full shadow-md flex items-center gap-2 active:scale-95 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                    記録する
                </a>

                {{-- ② Xで検索 --}}
                <a href="https://www.google.com/search?q={{ urlencode($shop->name) }}+X" target="_blank" class="h-9 bg-black hover:bg-gray-800 text-white text-xs font-bold px-4 rounded-full flex items-center gap-1.5 transition shadow-sm border border-black">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 fill-current" viewBox="0 0 24 24">
                        <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" />
                    </svg>
                    Post
                </a>

                {{-- ③ 食べログ --}}
                <a href="https://www.google.com/search?q={{ urlencode($shop->name) }}+食べログ" target="_blank" class="h-9 bg-white border border-gray-200 text-gray-600 hover:bg-orange-50 hover:text-orange-600 hover:border-orange-200 text-xs font-bold px-4 rounded-full flex items-center gap-1.5 transition">
                    🥢 食べログ
                </a>

                {{-- ④ RDB --}}
                <a href="https://www.google.com/search?q={{ urlencode($shop->name) }}+ラーメンDB" target="_blank" class="h-9 bg-white border border-gray-200 text-gray-600 hover:bg-red-50 hover:text-red-600 hover:border-red-200 text-xs font-bold px-4 rounded-full flex items-center gap-1.5 transition">
                    🍜 RDB
                </a>
            </div>
        </div>
    </div>

    {{-- 以下、みんなの記録リスト（変更なし） --}}
    <div class="px-4 pb-20 max-w-xl mx-auto">
        <h2 class="text-sm font-bold text-gray-500 mb-4 ml-1">
            みんなの記録 ({{ $shop->posts_count }}件)
        </h2>

        <div class="space-y-4">
            @foreach($posts as $post)
            {{-- ▼▼▼ 修正1: relative を追加（全体リンクの基準） & transition hoverを追加 ▼▼▼ --}}
            <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 relative transition hover:shadow-md">
                
                {{-- ▼▼▼ 修正2: カード全体を覆う透明なリンク（詳細ページへ） ▼▼▼ --}}
                <a href="{{ route('posts.show', $post) }}" class="absolute inset-0 z-0"></a>

                {{-- ユーザー情報＆日付 --}}
                <div class="flex justify-between items-center mb-3">
                    {{-- ▼▼▼ 修正3: relative z-10 を追加（ユーザーリンクを手前に） ▼▼▼ --}}
                    <a href="{{ route('users.show', $post->user->id) }}" class="relative z-10 flex items-center gap-2 group">
                        <div class="h-8 w-8 rounded-full bg-gray-100 overflow-hidden border border-gray-100">
                            @if($post->user->icon_path)
                                <img src="{{ asset($post->user->icon_path) }}" class="w-full h-full object-cover" />
                            @else
                                <div class="w-full h-full flex items-center justify-center text-xs font-bold text-gray-400">
                                    {{ mb_substr($post->user->name, 0, 1) }}
                                </div>
                            @endif
                        </div>
                        <span class="text-sm font-bold text-gray-800 group-hover:text-orange-600 transition">{{ $post->user->name }}</span>
                    </a>
                    
                    {{-- 相対時間表示 --}}
                    <div class="text-right">
                        <span class="text-xs text-gray-400 block leading-none">{{ $post->eaten_at->format('Y/m/d') }}</span>
                        <span class="text-[10px] text-gray-300 block mt-0.5">{{ $post->eaten_at->diffForHumans() }}</span>
                    </div>
                </div>

                <div class="flex gap-4">
                    {{-- 左側：スコアとコメント --}}
                    {{-- ▼▼▼ 修正4: pointer-events-none を追加（クリック貫通させるため） ▼▼▼ --}}
                    <div class="flex-1 min-w-0 pointer-events-none">
                        {{-- スコア --}}
                        <div class="flex items-baseline gap-1 text-orange-600 leading-none mb-2">
                            <span class="text-2xl font-black">{{ $post->score }}</span>
                            <span class="text-xs font-bold">点</span>
                        </div>

                        {{-- コメント --}}
                        @if($post->comment)
                            <p class="text-sm text-gray-600 leading-relaxed line-clamp-3 mb-2">
                                {{ $post->comment }}
                            </p>
                        @else
                            <p class="text-xs text-gray-300 italic mb-2">コメントなし</p>
                        @endif

                        {{-- いいねボタン --}}
                        {{-- ▼▼▼ 修正5: pointer-events-auto と relative z-10 を追加（クリック有効化） ▼▼▼ --}}
                        <div class="pointer-events-auto relative z-10 inline-block" x-data="{ liked: {{ $post->isLikedBy(Auth::user()) ? 'true' : 'false' }}, count: {{ $post->likes->count() }} }">
                            <button type="button"
                                @click="fetch('{{ route('posts.like', $post) }}', { 
                                    method: 'POST', 
                                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } 
                                })
                                .then(res => res.json())
                                .then(data => { 
                                    liked = (data.status === 'added'); 
                                    count = data.count; 
                                })"
                                class="flex items-center gap-1 text-xs font-bold transition group p-1 -ml-1"
                                :class="liked ? 'text-pink-500' : 'text-gray-400 hover:text-gray-600'"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" 
                                     class="h-4 w-4 transition-transform active:scale-125 duration-200" 
                                     :class="liked ? 'fill-current' : 'fill-none'" 
                                     viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                </svg>
                                <span x-text="count"></span>
                            </button>
                        </div>
                    </div>

                    {{-- 右側：写真（あれば） --}}
                    @if($post->image_path)
                        {{-- 写真エリアは pointer-events-none がなくても画像自体はクリックイベントを持たないので貫通するが、念のため指定しても良い --}}
                        <div class="w-24 h-24 shrink-0 rounded-xl bg-gray-100 overflow-hidden border border-gray-50 pointer-events-none">
                            <img src="{{ asset($post->image_path) }}" loading="lazy" class="w-full h-full object-cover" />
                        </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>

        @if($posts->isEmpty())
            <div class="py-12 text-center">
                <div class="inline-block p-4 rounded-full bg-gray-50 text-gray-300 mb-2">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                </div>
                <p class="text-gray-400 font-bold text-sm">まだ投稿がありません</p>
                <p class="text-xs text-gray-400 mt-1">一番乗りで記録しましょう！</p>
            </div>
        @endif

        <div class="mt-8">
            {{ $posts->links('vendor.pagination.ramen') }}
        </div>
    </div>
</x-app-layout>