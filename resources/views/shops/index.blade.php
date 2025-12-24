<x-app-layout title="お店図鑑">
    {{-- Alpine.jsデータ定義 --}}
    {{-- genreOpen: モーダル開閉 --}}
    {{-- selectedGenres: PHPから受け取った選択済みID配列をJS配列として初期化 --}}
    <div class="pb-20 bg-gray-50 min-h-screen" 
         x-data="{ 
             genreOpen: false, 
             selectedGenres: {{ json_encode($genreIds) }}.map(String) // 文字列として扱う
         }">

        {{-- ★★★ 修正: 全体を囲むフォーム (GET送信) ★★★ --}}
        <form action="{{ route('shops.index') }}" method="GET">

            {{-- 固定ヘッダー --}}
            <div class="sticky top-0 z-5 bg-gray-50/95 backdrop-blur-md shadow-sm pt-3 pb-3">
                <div class="px-4">
                    <div class="flex gap-2">
                        {{-- 検索バー --}}
                        <div class="relative flex-1">
                            <input
                                type="text"
                                name="search"
                                value="{{ request('search') }}"
                                placeholder="店名やエリア..."
                                class="w-full pl-9 pr-2 py-2.5 rounded-xl bg-white border-0 shadow-sm ring-1 ring-gray-200 focus:ring-2 focus:ring-orange-400 focus:outline-none transition text-sm"
                            />
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 absolute left-2.5 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>

                        {{-- ジャンル選択トリガーボタン --}}
                        <button 
                            type="button" 
                            @click="genreOpen = true"
                            class="shrink-0 px-4 py-2.5 rounded-xl text-xs font-bold border shadow-sm transition flex items-center gap-1"
                            :class="selectedGenres.length > 0 ? 'bg-orange-500 text-white border-orange-500' : 'bg-white text-gray-600 border-gray-200'"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                            </svg>
                            {{-- 選択数に応じてテキスト変化 --}}
                            <span x-text="selectedGenres.length > 0 ? selectedGenres.length + '件選択中' : '絞り込み'"></span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- ▼▼▼ 複数選択モーダル ▼▼▼ --}}
            <div 
                x-show="genreOpen" 
                class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/60 backdrop-blur-sm p-4"
                x-transition.opacity
                style="display: none;"
            >
                <div 
                    @click.away="genreOpen = false"
                    class="bg-white w-full max-w-lg rounded-2xl overflow-hidden shadow-2xl flex flex-col max-h-[85vh]"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="translate-y-full sm:translate-y-10 opacity-0"
                    x-transition:enter-end="translate-y-0 opacity-100"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="translate-y-0 opacity-100"
                    x-transition:leave-end="translate-y-full sm:translate-y-10 opacity-0"
                >
                    {{-- ヘッダー --}}
                    <div class="p-4 border-b flex justify-between items-center bg-gray-50">
                        <h3 class="font-bold text-gray-800 flex items-center gap-2">
                            <span>🏷️</span> ジャンルを選択
                        </h3>
                        <button type="button" @click="genreOpen = false" class="p-1 text-gray-400 hover:text-gray-600 bg-gray-200 rounded-full">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>

                    {{-- 選択エリア（スクロール可） --}}
                    <div class="p-4 overflow-y-auto">
                        <div class="grid grid-cols-2 gap-2">
                            @foreach($genres as $genre)
                                <label class="cursor-pointer">
                                    {{-- 実際のチェックボックス（配列 genre_ids[] として送信） --}}
                                    <input 
                                        type="checkbox" 
                                        name="genre_ids[]" 
                                        value="{{ $genre->id }}" 
                                        class="peer sr-only" 
                                        x-model="selectedGenres"
                                    >
                                    {{-- 見た目のボタン --}}
                                    <div class="py-3 px-2 text-center text-sm font-bold rounded-lg border-2 transition select-none
                                                bg-white text-gray-500 border-gray-100
                                                peer-checked:bg-orange-50 peer-checked:text-orange-600 peer-checked:border-orange-500 peer-checked:shadow-inner
                                                hover:bg-gray-50">
                                        {{ $genre->name }}
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- フッター（決定ボタン） --}}
                    <div class="p-4 border-t bg-white safe-area-bottom">
                        <div class="flex gap-3">
                            {{-- クリアボタン --}}
                            <button 
                                type="button" 
                                @click="selectedGenres = []"
                                class="flex-1 py-3 font-bold text-gray-500 bg-gray-100 rounded-xl hover:bg-gray-200 transition"
                            >
                                クリア
                            </button>
                            {{-- 検索ボタン --}}
                            <button 
                                type="submit" 
                                class="flex-[2] py-3 font-bold text-white bg-orange-500 rounded-xl shadow-lg hover:bg-orange-600 active:scale-95 transition"
                            >
                                この条件で検索
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            {{-- ▲▲▲ モーダルここまで ▲▲▲ --}}

        </form>
        {{-- ★★★ フォーム終了 ★★★ --}}

        {{-- ① ピックアップ（ランダム・横スクロール） --}}
        @if($pickupShops->isNotEmpty() && !request('search'))
            <div class="mt-4 pl-4">
                <h2 class="font-black text-gray-800 text-lg mb-3 flex items-center gap-1">
                    {{-- ★修正: タイトル変更 --}}
                    <span class="text-orange-500">✨</span> 今日のピックアップ
                </h2>
                <div class="flex overflow-x-auto gap-4 pb-4 pr-4 no-scrollbar snap-x">
                    @foreach($pickupShops as $shop)
                        <a href="{{ route('shops.show', $shop->id) }}" class="snap-start shrink-0 w-64 h-40 relative rounded-2xl overflow-hidden shadow-md group">
                            {{-- 背景画像 --}}
                            @if($shop->latestPost && $shop->latestPost->image_path)
                                <img src="{{ asset($shop->latestPost->image_path) }}" class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
                            @else
                                <div class="w-full h-full bg-orange-100 flex items-center justify-center text-4xl font-bold text-orange-300">
                                    {{ mb_substr($shop->name, 0, 1) }}
                                </div>
                            @endif

                            {{-- グラデーション & 文字 --}}
                            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent flex flex-col justify-end p-3 text-white">
                                <div class="font-bold text-lg leading-tight mb-0.5 line-clamp-1 drop-shadow-md">{{ $shop->name }}</div>
                                <div class="flex items-center gap-2 text-xs font-bold text-orange-300">
                                    <span class="text-sm">★ {{ number_format($shop->posts_avg_score, 1) }}</span>
                                    <span class="text-gray-300 font-normal">({{ $shop->posts_count }}件)</span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- ② お店リスト（リッチ表示） --}}
        <div class="px-4 mt-2">
            <h2 class="font-black text-gray-800 text-lg mb-3">
                {{ request('search') ? '検索結果' : 'すべてのお店' }}
            </h2>

            <div class="space-y-3">
                @foreach($shops as $shop)
                <a href="{{ route('shops.show', $shop->id) }}" class="block bg-white rounded-2xl p-3 shadow-sm border border-gray-100 active:scale-[0.98] transition">
                    <div class="flex gap-3">
                        {{-- 左：サムネイル --}}
                        <div class="w-20 h-20 shrink-0 rounded-xl overflow-hidden bg-gray-100 relative">
                            @if($shop->latestPost && $shop->latestPost->image_path)
                                <img src="{{ asset($shop->latestPost->image_path) }}" class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex items-center justify-center bg-orange-50 text-orange-400 font-bold text-xl">
                                    {{ mb_substr($shop->name, 0, 1) }}
                                </div>
                            @endif
                            {{-- ★修正: 順位バッジのコードを削除しました --}}
                        </div>

                        {{-- 右：情報 --}}
                        <div class="flex-1 min-w-0 py-0.5">
                            {{-- 店名とスコア --}}
                            <div class="flex justify-between items-start mb-1">
                                <h3 class="font-bold text-gray-800 text-base truncate pr-2">{{ $shop->name }}</h3>
                                <div class="flex items-center gap-1 shrink-0 bg-orange-50 px-1.5 py-0.5 rounded-md border border-orange-100">
                                    <svg class="w-3 h-3 text-orange-500 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                    <span class="text-sm font-bold text-orange-600">{{ number_format($shop->posts_avg_score, 1) }}</span>
                                </div>
                            </div>

                            {{-- 投稿数・エリア（住所短縮） --}}
                            @if($shop->address)
                                <p class="text-xs text-gray-400 mb-2 flex items-center gap-2">
                                    {{-- ★修正: モデルの short_address を使用 --}}
                                    <span>📍 {{ $shop->short_address }}</span>
                                    <span>•</span>
                                    <span>{{ $shop->posts_count }}件の投稿</span>
                                </p>
                            @else
                            <p class="text-xs text-gray-400 mb-2 flex items-center gap-2">
                                    {{-- ★修正: モデルの short_address を使用 --}}
                                    <span>📍 住所未登録</span>
                                    <span>•</span>
                                    <span>{{ $shop->posts_count }}件の投稿</span>
                                </p>
                            @endif

                            {{-- 最新コメント（吹き出し風） --}}
                            @if($shop->latestPost && $shop->latestPost->comment)
                                <div class="bg-gray-50 rounded-lg px-2 py-1.5 text-xs text-gray-600 relative truncate">
                                    <span class="text-gray-400 mr-1">💬</span>
                                    {{ $shop->latestPost->comment }}
                                </div>
                            @endif
                        </div>
                    </div>
                </a>
                @endforeach
            </div>
            
            @if($shops->isEmpty())
                <div class="text-center py-20">
                    <div class="text-6xl mb-4">🍜</div>
                    <p class="text-gray-400 font-bold">まだお店がありません</p>
                    <p class="text-xs text-gray-400 mt-2">最初の投稿をしてみましょう！</p>
                </div>
            @endif

            <div class="mt-8 pb-10">
                {{ $shops->links('vendor.pagination.ramen') }}
            </div>
        </div>
    </div>
</x-app-layout>