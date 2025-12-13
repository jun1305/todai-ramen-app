<x-app-layout title="{{ $rally->title }}">
    
    {{-- ▼▼▼ x-data でモーダルの開閉を管理 ▼▼▼ --}}
    <div class="bg-gray-50 min-h-screen pb-24" x-data="{ openChallengers: false }">
        
        {{-- ヘッダー（ラリー情報） --}}
        <div class="bg-white p-6 shadow-sm border-b border-gray-100 relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-orange-400 to-red-500"></div>
            
            <div class="flex justify-between items-start mb-4">
                <a href="{{ route('rallies.index') }}" class="text-gray-400 hover:text-gray-600 transition flex items-center gap-1 text-xs font-bold">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                    </svg>
                    一覧へ
                </a>
                
                {{-- 作成者プロフィールへのリンク --}}
                <a href="{{ route('users.show', $rally->creator->id) }}" class="flex items-center gap-2 bg-gray-50 px-3 py-1 rounded-full border border-gray-100 hover:bg-gray-100 hover:border-gray-200 transition">
                    <div class="h-5 w-5 rounded-full bg-gray-200 overflow-hidden">
                        @if($rally->creator->icon_path)
                            <img src="{{ asset($rally->creator->icon_path) }}" class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-[8px] font-bold text-gray-400">
                                {{ mb_substr($rally->creator->name, 0, 1) }}
                            </div>
                        @endif
                    </div>
                    <span class="text-xs text-gray-500 font-bold">{{ $rally->creator->name }}</span>
                </a>
            </div>

            <h1 class="text-2xl font-black text-gray-800 mb-2 leading-tight">
                {{ $rally->title }}
            </h1>
            <p class="text-sm text-gray-500 leading-relaxed mb-4">
                {{ $rally->description }}
            </p>

            {{-- 挑戦者数ボタン（ここを押すとモーダルが開く） --}}
            <button @click="openChallengers = true" class="flex items-center gap-1 text-xs font-bold text-orange-600 bg-orange-50 px-3 py-1.5 rounded-lg border border-orange-100 hover:bg-orange-100 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z" />
                </svg>
                <span>{{ $rally->challengers->count() }}人が挑戦中</span>
                <span class="text-orange-400 ml-1">›</span>
            </button>
        </div>

        {{-- コンテンツエリア --}}
        <div class="max-w-md mx-auto p-4">

            {{-- 参加ボタン / 進捗バー --}}
            @if(!$isJoined)
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 text-center mb-6">
                    <p class="font-bold text-gray-800 mb-4">このラリーに挑戦しますか？</p>
                    <form action="{{ route('rallies.join', $rally) }}" method="POST">
                        @csrf
                        <button class="w-full bg-gradient-to-r from-orange-500 to-red-600 text-white font-black py-4 rounded-xl shadow-lg shadow-orange-200 hover:scale-[1.02] active:scale-95 transition flex items-center justify-center gap-2">
                            <span>🔥</span> 挑戦する！
                        </button>
                    </form>
                </div>
            @else
                {{-- 進捗状況 --}}
                @php
                    $total = $rally->shops->count();
                    $done = count($conqueredShopIds);
                    $percent = $total > 0 ? ($done / $total) * 100 : 0;
                @endphp
                <div class="mb-6">
                    <div class="flex justify-between items-end mb-2 px-1">
                        <span class="text-xs font-bold text-gray-500">進捗状況</span>
                        <span class="text-xl font-black text-orange-600 leading-none">
                            {{ $done }} <span class="text-xs text-gray-400">/ {{ $total }}</span>
                        </span>
                    </div>
                    <div class="h-3 bg-gray-200 rounded-full overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-orange-400 to-red-500 transition-all duration-1000 ease-out" style="width: {{ $percent }}%"></div>
                    </div>
                    @if($done === $total)
                        <div class="mt-4 bg-yellow-50 border border-yellow-200 text-yellow-700 p-3 rounded-lg text-center font-bold text-sm">
                            🎉 コンプリートおめでとうございます！
                        </div>
                    @endif
                </div>
            @endif

            {{-- スタンプカード（お店リスト） --}}
            <div class="space-y-4">
                @foreach($rally->shops as $index => $shop)
                @php
                    $isConquered = in_array($shop->id, $conqueredShopIds);
                    // 写真があるかどうかのチェック
                    $hasPhoto = $isConquered && isset($myShopImages[$shop->id]);
                @endphp

                <div class="relative group">
                    {{-- お店カード --}}
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden flex {{ $isConquered ? 'opacity-100' : 'opacity-60 grayscale' }} transition duration-500">

                        {{-- 左側の番号/スタンプ（元のシンプルな形に戻しました） --}}
                        <div class="w-16 bg-gray-50 flex items-center justify-center border-r border-gray-100 shrink-0 relative">
                            @if($isConquered)
                                {{-- 制覇スタンプ --}}
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <div class="w-12 h-12 rounded-full border-4 border-red-500 text-red-500 flex items-center justify-center font-black transform -rotate-12 opacity-80" style="mask-image: url('https://raw.githubusercontent.com/googlefonts/noto-emoji/main/png/512/emoji_u1f4ae.png'); -webkit-mask-image: none;">
                                        <span class="text-xs">済</span>
                                    </div>
                                </div>
                                <span class="text-2xl">🍜</span>
                            @else
                                <span class="text-xl font-black text-gray-300">{{ $index + 1 }}</span>
                            @endif
                        </div>

                        {{-- お店情報エリア（左右に分割） --}}
                        <div class="flex-1 flex overflow-hidden">
                            {{-- 左側：店名とボタン --}}
                            <div class="p-4 flex-1 min-w-0 flex flex-col justify-center">
                                <h3 class="font-bold text-gray-800 truncate mb-1">
                                    <a href="{{ route('shops.show', $shop->id) }}" class="hover:underline hover:text-orange-600 transition">
                                        {{ $shop->name }}
                                    </a>
                                </h3>
                                <div class="flex gap-2">
                                    <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($shop->name) }}+ラーメン" target="_blank" class="text-[10px] bg-gray-100 text-gray-500 px-2 py-1 rounded hover:bg-gray-200 transition flex-shrink-0">
                                        📍 マップ
                                    </a>
                                    <a href="{{ route('posts.create', ['shop_name' => $shop->name]) }}" class="text-[10px] bg-orange-50 text-orange-600 px-2 py-1 rounded hover:bg-orange-100 transition flex-shrink-0">
                                        🖊️ 記録する
                                    </a>
                                </div>
                            </div>

                            {{-- ▼▼▼ 追加: 右側：写真（ある場合のみ表示） ▼▼▼ --}}
                            @if($hasPhoto)
                            <div class="w-24 relative shrink-0 border-l border-gray-50">
                                <img src="{{ asset($myShopImages[$shop->id]) }}" class="w-full h-full object-cover absolute inset-0">
                                <div class="absolute inset-0 bg-black/5 pointer-events-none"></div> {{-- うっすら影 --}}
                            </div>
                            @endif
                        </div>
                    </div>

                    {{-- 未達成時の「ここに行く」ガイド（参加中のみ） --}}
                    @if($isJoined && !$isConquered)
                    <div class="absolute -right-1 -top-1 w-3 h-3 bg-red-500 rounded-full animate-ping"></div>
                    @endif
                </div>
                @endforeach
            </div>

        </div>

        {{-- ▼▼▼ 参加者リストのモーダル（シンプル・フェードイン形式） ▼▼▼ --}}
        <div x-show="openChallengers" 
             style="display: none;" 
             class="fixed inset-0 z-50 flex items-center justify-center"> {{-- 中央寄せ --}}
            
            {{-- 背景（ぼかし） --}}
            <div x-show="openChallengers"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="openChallengers = false"
                 class="fixed inset-0 bg-black/40 backdrop-blur-sm"></div>

                 {{-- ▼▼▼ モーダル（ランキング仕様に修正） ▼▼▼ --}}
        <div x-show="openChallengers" 
             style="display: none;" 
             class="fixed inset-0 z-50 flex items-center justify-center">
            
            <div x-show="openChallengers" @click="openChallengers = false" class="fixed inset-0 bg-black/40 backdrop-blur-sm"></div>

            <div x-show="openChallengers" class="relative bg-white w-full max-w-sm mx-4 rounded-3xl shadow-2xl overflow-hidden max-h-[80vh] flex flex-col">
                <div class="p-4 border-b border-gray-100 flex justify-between items-center bg-white">
                    <h3 class="font-bold text-gray-800 flex items-center gap-2">
                        <span class="text-xl">🏆</span> 挑戦者ランキング
                    </h3>
                    <button @click="openChallengers = false" class="p-2 bg-gray-100 rounded-full text-gray-500 hover:bg-gray-200 transition">✕</button>
                </div>

                <div class="overflow-y-auto p-4 space-y-3">
                    @php
                        $totalShops = $rally->shops->count();
                    @endphp

                    @forelse($rally->challengers as $index => $challenger)
                        @php
                            // ① このラリーに含まれるお店のIDリストを取得
                            $rallyShopIds = $rally->shops->pluck('id');

                            // ② ユーザーの投稿のうち「このラリーのお店」かつ「重複なし」でカウント
                            $progressCount = $challenger->posts
                                ->whereIn('shop_id', $rallyShopIds)
                                ->unique('shop_id')
                                ->count();
                        @endphp

                    <a href="{{ route('users.show', $challenger->id) }}" class="flex items-center gap-3 p-3 rounded-xl hover:bg-gray-50 transition border border-transparent hover:border-gray-100 relative">
                        
                        {{-- 順位メダル --}}
                        @if($challenger->pivot->is_completed)
                            <div class="absolute -top-1 -left-1 w-6 h-6 flex items-center justify-center rounded-full text-[10px] font-black border-2 border-white shadow-sm
                                {{ $index === 0 ? 'bg-yellow-400 text-yellow-900' : 
                                  ($index === 1 ? 'bg-gray-300 text-gray-800' : 
                                  ($index === 2 ? 'bg-orange-300 text-orange-900' : 'bg-blue-100 text-blue-800')) }}">
                                {{ $index + 1 }}
                            </div>
                        @endif

                        {{-- アイコン --}}
                        <div class="h-10 w-10 rounded-full bg-gray-100 overflow-hidden border border-gray-200 shrink-0">
                            @if($challenger->icon_path)
                                <img src="{{ asset($challenger->icon_path) }}" class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-sm font-bold text-gray-400">
                                    {{ mb_substr($challenger->name, 0, 1) }}
                                </div>
                            @endif
                        </div>

                        {{-- 名前と進捗 --}}
                        <div class="flex-1">
                            <div class="flex justify-between items-center">
                                <div class="font-bold text-gray-800 text-sm">{{ $challenger->name }}</div>
                                
                                {{-- ▼▼▼ 進捗カウント表示 ▼▼▼ --}}
                                <div class="text-xs font-black {{ $progressCount == $totalShops ? 'text-yellow-600' : 'text-orange-500' }}">
                                    {{ $progressCount }} <span class="text-[10px] text-gray-400 font-bold">/ {{ $totalShops }}</span>
                                </div>
                            </div>

                            <div class="text-[10px] text-gray-400 mt-0.5">
                                @if($challenger->pivot->is_completed)
                                    <span class="text-yellow-600 font-bold">
                                        👑 {{ \Carbon\Carbon::parse($challenger->pivot->completed_at)->format('Y/m/d') }} 制覇
                                    </span>
                                @else
                                    <span class="bg-gray-100 px-1.5 py-0.5 rounded text-gray-500">挑戦中</span>
                                @endif
                            </div>
                        </div>
                    </a>
                    @empty
                    <div class="text-center py-8 text-gray-400 text-sm">まだ挑戦者はいません</div>
                    @endforelse
                </div>
            </div>
        </div>

    </div>
</x-app-layout>