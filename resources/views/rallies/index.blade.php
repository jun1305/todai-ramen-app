<x-app-layout title="ラーメンラリー">
    
<div class="bg-gray-50 min-h-screen pb-32" 
         {{-- ▼▼▼ 修正箇所: ブラウザの記憶(sessionStorage)を使って状態を維持 ▼▼▼ --}}
         x-data="{ 
             searchOpen: sessionStorage.getItem('ramen_search_open') === 'true' || {{ request('search') ? 'true' : 'false' }}, 
             searchType: '{{ request('type', 'title') }}' 
         }"
         {{-- 開閉するたびに状態を保存する --}}
         x-init="$watch('searchOpen', val => sessionStorage.setItem('ramen_search_open', val))">
        
        {{-- ヘッダー --}}
        {{-- ▼▼▼ 修正: pb-6 → pb-10 に変更して、ボタン下の余白を確保 ▼▼▼ --}}
        <div class="bg-slate-900 text-white pt-6 pb-12 px-4 rounded-b-[2rem] shadow-md relative overflow-hidden z-10 transition-all duration-300"
             :class="searchOpen ? 'pb-10' : 'pb-12'"> 
            
            <div class="absolute top-0 left-0 w-full h-full bg-gradient-to-br from-orange-500/20 to-transparent pointer-events-none"></div>
            
            {{-- 検索アイコン --}}
            <button @click="searchOpen = !searchOpen" 
                    class="absolute top-6 right-6 text-gray-300 hover:text-white transition p-2 rounded-full hover:bg-white/10 z-20">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </button>

            <div class="relative z-10 text-center">
                <h1 class="text-2xl font-black mb-2 flex items-center justify-center gap-2">
                    <span class="text-3xl">🚩</span> ラーメンラリー
                </h1>
                
                <p x-show="!searchOpen" class="text-sm text-gray-300 font-bold">
                    テーマを決めて巡ろう！<br>あなただけのラーメンクエスト。
                </p>

                {{-- 検索フォーム --}}
                <div x-show="searchOpen" x-cloak class="mt-6 max-w-xs mx-auto"> {{-- mt-4 -> mt-6 に変更 --}}
                    <form action="{{ route('rallies.index') }}" method="GET">
                        {{-- 既存パラメータ --}}
                        @if(request('filter')) <input type="hidden" name="filter" value="{{ request('filter') }}"> @endif
                        @if(request('sort')) <input type="hidden" name="sort" value="{{ request('sort') }}"> @endif

                        <div class="flex justify-center gap-2 mb-4"> {{-- mb-3 -> mb-4 に変更 --}}
                            <button type="button" @click="searchType = 'title'" 
                                class="text-xs font-bold px-4 py-1.5 rounded-full transition border"
                                :class="searchType === 'title' ? 'bg-orange-500 text-white border-orange-500' : 'bg-transparent text-gray-400 border-gray-600 hover:text-white'">
                                ラリー名
                            </button>
                            <button type="button" @click="searchType = 'creator'" 
                                class="text-xs font-bold px-4 py-1.5 rounded-full transition border"
                                :class="searchType === 'creator' ? 'bg-orange-500 text-white border-orange-500' : 'bg-transparent text-gray-400 border-gray-600 hover:text-white'">
                                作成者
                            </button>
                            <input type="hidden" name="type" x-model="searchType">
                        </div>
                        
                        <div class="mb-4"> {{-- mb-3 -> mb-4 に変更 --}}
                            <input type="text" name="search" value="{{ request('search') }}" 
                                class="w-full bg-white/10 border border-white/20 rounded-xl py-3 px-4 text-white placeholder-gray-400 focus:outline-none focus:bg-white/20 focus:border-orange-500 transition text-sm text-center"
                                :placeholder="searchType === 'title' ? 'ラリー名を入力...' : '作成者名を入力...'">
                        </div>
                        
                        <button type="submit" class="w-full bg-white text-slate-900 font-black py-3 rounded-xl shadow-lg hover:bg-gray-100 transition flex items-center justify-center gap-2 text-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            検索する
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- コンテンツエリア --}}
        <div class="max-w-md mx-auto px-4 -mt-6 relative z-10">
            
            {{-- ▼▼▼ フィルター＆ソートエリア（ここを追加！） ▼▼▼ --}}
            <div class="mb-6 space-y-3">
                {{-- フィルターチップ（横スクロール） --}}
                <div class="flex overflow-x-auto gap-2 pb-1 no-scrollbar -mx-4 px-4">
                    @php
                        $filters = [
                            'all' => 'すべて',
                            'not_joined' => '未参加',
                            'active' => '挑戦中',
                            'completed' => '制覇済'
                        ];
                        $currentFilter = request('filter', 'all');
                    @endphp
                    
                    @foreach($filters as $key => $label)
                    <a href="{{ request()->fullUrlWithQuery(['filter' => $key == 'all' ? null : $key, 'page' => null]) }}" 
                       class="whitespace-nowrap px-4 py-2 rounded-full text-xs font-bold border shadow-sm transition
                       {{ $currentFilter == ($key == 'all' ? 'all' : $key) 
                          ? 'bg-slate-900 text-white border-slate-900' 
                          : 'bg-white text-gray-500 border-gray-100 hover:border-gray-300' }}">
                        {{ $label }}
                    </a>
                    @endforeach
                </div>

                {{-- 並び替えドロップダウン --}}
                <div class="flex justify-end items-center gap-2">
                    <span class="text-[10px] font-bold text-gray-400">並び替え:</span>
                    <form method="GET" action="{{ route('rallies.index') }}">
                        {{-- 現在の検索・フィルター条件を維持 --}}
                        @if(request('search')) <input type="hidden" name="search" value="{{ request('search') }}"> @endif
                        @if(request('type')) <input type="hidden" name="type" value="{{ request('type') }}"> @endif
                        @if(request('filter')) <input type="hidden" name="filter" value="{{ request('filter') }}"> @endif

                        <select name="sort" onchange="this.form.submit()" class="bg-white border border-gray-200 text-gray-700 text-xs font-bold rounded-lg px-3 py-1.5 focus:outline-none focus:border-orange-500 shadow-sm appearance-none">
                            <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>新着順</option>
                            <option value="popular" {{ request('sort') == 'popular' ? 'selected' : '' }}>人気順（参加者多）</option>
                            <option value="shops_desc" {{ request('sort') == 'shops_desc' ? 'selected' : '' }}>店数が多い順</option>
                            <option value="shops_asc" {{ request('sort') == 'shops_asc' ? 'selected' : '' }}>店数が少ない順</option>
                        </select>
                    </form>
                </div>
            </div>

            {{-- 作成ボタン --}}
            <div class="mb-6 text-center">
                <a href="{{ route('rallies.create') }}" class="block w-full bg-orange-500 text-white font-black py-4 rounded-xl shadow-lg shadow-orange-200 hover:bg-orange-600 hover:scale-[1.02] transition flex items-center justify-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>新しいラリーを作る</span>
                </a>
            </div>

            {{-- 検索結果表示 --}}
            @if(request('search'))
            <div class="mb-4 flex items-center justify-between">
                <p class="text-sm font-bold text-gray-500">
                    "{{ request('search') }}" の検索結果
                </p>
                <a href="{{ route('rallies.index') }}" class="text-xs text-orange-500 hover:underline">クリア</a>
            </div>
            @endif

            {{-- ラリーリスト --}}
            <div class="space-y-4">
                @foreach($rallies as $rally)
                @php
                    $isJoined = in_array($rally->id, $myJoinedRallyIds);
                    $total = $rally->shops_count; // withCountで取得済み
                    $conqueredCount = $rally->shops->whereIn('id', $myConqueredShopIds)->count();
                    $isCompleted = ($total > 0 && $conqueredCount >= $total);
                @endphp

                <a href="{{ route('rallies.show', $rally) }}" class="block bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:shadow-md transition relative overflow-hidden group">
                    
                    {{-- バッジ --}}
                    @if($isJoined)
                        @if($isCompleted)
                            <div class="absolute top-0 right-0 bg-yellow-400 text-yellow-900 text-[10px] font-black px-3 py-1 rounded-bl-xl shadow-sm z-10 flex items-center gap-1">
                                <span>👑</span> COMPLETE!
                            </div>
                        @else
                            <div class="absolute top-0 right-0 bg-orange-500 text-white text-[10px] font-bold px-3 py-1 rounded-bl-xl shadow-sm z-10">
                                挑戦中: {{ $conqueredCount }}/{{ $total }}
                            </div>
                        @endif
                    @endif
                    
                    <div class="flex justify-between items-start mb-2 pr-16">
                        <h3 class="text-lg font-black text-gray-800 line-clamp-2 group-hover:text-orange-600 transition">
                            {{ $rally->title }}
                        </h3>
                    </div>
                    
                    <p class="text-xs text-gray-500 line-clamp-2 mb-4">
                        {{ $rally->description }}
                    </p>

                    {{-- カード下部（作成者・店数・人数） --}}
                    <div class="flex items-center justify-between border-t border-gray-50 pt-3">
                        
                        <div class="flex items-center gap-3">
                            {{-- 作成者 --}}
                            <div class="flex items-center gap-1.5">
                                <div class="h-5 w-5 rounded-full bg-gray-100 overflow-hidden border border-gray-100">
                                    @if($rally->creator->icon_path)
                                        <img src="{{ asset($rally->creator->icon_path) }}" class="w-full h-full object-cover">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center text-[8px] font-bold text-gray-400">
                                            {{ mb_substr($rally->creator->name, 0, 1) }}
                                        </div>
                                    @endif
                                </div>
                                <span class="text-xs text-gray-400 truncate max-w-[80px]">{{ $rally->creator->name }}</span>
                            </div>

                            {{-- ▼▼▼ 修正: 店数表示に変更 ▼▼▼ --}}
                            <div class="flex items-center gap-1 text-[10px] font-bold text-gray-400 bg-gray-50 px-2 py-1 rounded">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10.496 2.132a1 1 0 00-.992 0l-7 4A1 1 0 003 8v7a1 1 0 100 2h14a1 1 0 100-2V8a1 1 0 00.496-1.868l-7-4zM6 9a1 1 0 00-1 1v3a1 1 0 102 0v-3a1 1 0 00-1-1zm3 1a1 1 0 012 0v3a1 1 0 11-2 0v-3zm5-1a1 1 0 00-1 1v3a1 1 0 102 0v-3a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                                全{{ $total }}軒
                            </div>
                        </div>

                        {{-- 参加人数 --}}
                        <div class="flex items-center gap-1 text-xs font-bold text-gray-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z" />
                            </svg>
                            {{ $rally->challengers_count }}人
                        </div>
                    </div>
                </a>
                @endforeach
            </div>

            {{-- 空の時 --}}
            @if($rallies->isEmpty())
            <div class="text-center py-16 px-6 text-gray-400 bg-white rounded-2xl border border-dashed border-gray-300 mt-4">
                <div class="text-4xl mb-4">😢</div>
                <p class="font-bold text-gray-600 mb-2">条件に合うラリーがありません</p>
                @if(request('filter') || request('search'))
                    <p class="text-xs mb-6">条件を変更してみてください</p>
                    <a href="{{ route('rallies.index') }}" class="text-orange-500 font-bold underline">リセットする</a>
                @else
                    <p class="text-xs">上のボタンから最初のラリーを作ってみよう！</p>
                @endif
            </div>
            @endif

            {{-- ページネーション --}}
            <div class="mt-8">
                {{ $rallies->links('vendor.pagination.ramen') }}
            </div>
        </div>
    </div>
</x-app-layout>