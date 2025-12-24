<x-app-layout title="ランキング">
    {{-- ★変更: genreModalOpen を追加してモーダルの開閉を管理 --}}
    <div x-data="{ activeTab: '{{ $tab }}', genreModalOpen: false }" class="pb-20 bg-gray-50 min-h-screen" x-cloak>
        
        {{-- ========================================== --}}
        {{-- 固定ヘッダー（タブ ＆ 期間切り替え） --}}
        {{-- ========================================== --}}
        <div class="bg-white shadow-md sticky top-0 z-11">
            <div class="px-4 py-3 space-y-3">
                {{-- ① タブ切り替えボタン --}}
                <div class="flex bg-gray-100 p-1 rounded-full">
                    {{-- 部員タブ --}}
                    <a href="{{ request()->fullUrlWithQuery(['tab' => 'users']) }}"
                       class="flex-1 py-2 rounded-full text-sm font-bold flex items-center justify-center gap-2 transition duration-300"
                       :class="activeTab === 'users' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-400 hover:text-gray-600'">
                        <span>🏆</span> 部員
                    </a>
                    {{-- 人気店タブ --}}
                    <a href="{{ request()->fullUrlWithQuery(['tab' => 'shops']) }}"
                       class="flex-1 py-2 rounded-full text-sm font-bold flex items-center justify-center gap-2 transition duration-300"
                       :class="activeTab === 'shops' ? 'bg-white text-orange-600 shadow-sm' : 'text-gray-400 hover:text-gray-600'">
                        <span>🔥</span> 人気店
                    </a>
                </div>

                {{-- ② 期間切り替えボタン --}}
                <div class="flex justify-start gap-2 overflow-x-auto px-1 pb-1 no-scrollbar">
                    @foreach(['weekly' => '週間', 'monthly' => '月間', 'yearly' => '年間', 'total' => '累計'] as $key => $label)
                        <a href="{{ request()->fullUrlWithQuery(['period' => $key]) }}"
                           class="px-3 py-1 text-xs font-bold rounded-full border transition-colors whitespace-nowrap"
                           style="{{ request('period', 'total') === $key 
                               ? 'background-color: #1f2937; color: white; border-color: #1f2937;' 
                               : 'background-color: white; color: #6b7280; border-color: #e5e7eb;' }}">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="max-w-xl mx-auto w-full">

            {{-- ========================================== --}}
            {{-- A. 部員ランキング --}}
            {{-- ========================================== --}}
            @if($tab === 'users')
            <section>
                {{-- ソート切り替え --}}
                <div class="flex justify-end px-4 py-3">
                    <div class="inline-flex bg-white rounded-lg p-1 border border-gray-200 shadow-sm">
                        <a href="{{ request()->fullUrlWithQuery(['user_sort' => 'point']) }}"
                           class="px-3 py-1.5 text-xs font-bold rounded-md transition {{ $userSort === 'point' ? 'bg-blue-50 text-blue-600' : 'text-gray-400 hover:text-gray-600' }}">ポイント順</a>
                        <a href="{{ request()->fullUrlWithQuery(['user_sort' => 'count']) }}"
                           class="px-3 py-1.5 text-xs font-bold rounded-md transition {{ $userSort === 'count' ? 'bg-blue-50 text-blue-600' : 'text-gray-400 hover:text-gray-600' }}">杯数順</a>
                    </div>
                </div>

                <div class="space-y-4 px-4">
                    @foreach($users as $index => $user)
                        @php 
                            $rank = $users->firstItem() + $index;
                            $postPoints = $user->posts_sum_earned_points ?? 0;
                            $rallyPoints = ($user->completed_rallies_count ?? 0) * 5;
                            $displayPoints = ($period === 'total') ? $user->total_score : ($postPoints + $rallyPoints);
                            $isTop3 = $rank <= 3;
                        @endphp
                        
                        @if($isTop3)
                            {{-- トップ3デザイン --}}
                            <div class="bg-gradient-to-br from-amber-50 to-orange-100 rounded-2xl shadow-md overflow-hidden border border-orange-200 relative">
                                <div class="absolute inset-0 opacity-10 pointer-events-none" style="background-image: radial-gradient(circle at center, orange 1px, transparent 1px); background-size: 20px 20px;"></div>
                                <div class="bg-red-600 text-white text-center font-black py-1 text-sm shadow-sm relative z-10">{{ $rank }}位</div>
                                <div class="p-6 flex flex-col items-center relative z-10">
                                    <a href="{{ route('users.show', $user->id) }}" class="relative group">
                                        <div class="w-28 h-28 rounded-full border-4 border-white shadow-lg overflow-hidden bg-gray-100">
                                            @if($user->icon_path)
                                                <img src="{{ asset($user->icon_path) }}" class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
                                            @else
                                                <div class="w-full h-full flex items-center justify-center bg-blue-50 text-blue-400 text-4xl font-black">{{ mb_substr($user->name, 0, 1) }}</div>
                                            @endif
                                        </div>
                                        <div class="absolute -bottom-2 -right-2 text-4xl drop-shadow-md">{{ ['🥇','🥈','🥉'][$rank-1] }}</div>
                                    </a>
                                    <a href="{{ route('users.show', $user->id) }}" class="mt-4 text-center group">
                                        <h3 class="text-xl font-black text-gray-800 leading-tight group-hover:text-blue-600 transition">{{ $user->name }}</h3>
                                    </a>
                                    <div class="mt-2 inline-block bg-white/80 px-4 py-1 rounded-full border border-orange-200 shadow-sm">
                                        @if($userSort === 'point')
                                            <span class="font-black text-xl text-red-500">{{ number_format($displayPoints) }}</span><span class="text-xs font-bold text-gray-500">Pt</span>
                                        @else
                                            <span class="font-black text-xl text-red-500">{{ number_format($user->posts_count) }}</span><span class="text-xs font-bold text-gray-500">杯</span>
                                        @endif
                                    </div>
                                    @if($user->posts->first() && $user->posts->first()->comment)
                                        <div class="mt-4 relative w-full">
                                            <div class="bg-white p-3 rounded-xl text-xs text-gray-600 shadow-sm border border-gray-100 relative text-center leading-relaxed">
                                                <span class="text-orange-400 text-lg absolute -top-3 left-1/2 -translate-x-1/2">💬</span>
                                                "{{ Str::limit($user->posts->first()->comment, 60) }}"
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @else
                            {{-- 4位以下デザイン --}}
                            <div class="bg-white rounded-xl p-3 shadow-sm border border-gray-100 flex items-center">
                                <div class="w-8 text-center font-bold text-gray-400 mr-2">{{ $rank }}</div>
                                <a href="{{ route('users.show', $user->id) }}" class="flex items-center flex-1 min-w-0">
                                    <div class="w-10 h-10 rounded-full bg-gray-100 mr-3 overflow-hidden border border-gray-100">
                                        @if($user->icon_path)
                                            <img src="{{ asset($user->icon_path) }}" class="w-full h-full object-cover">
                                        @else
                                            <div class="w-full h-full flex items-center justify-center bg-blue-50 text-blue-500 font-bold">{{ mb_substr($user->name, 0, 1) }}</div>
                                        @endif
                                    </div>
                                    <div class="truncate">
                                        <div class="font-bold text-gray-800 text-sm truncate">{{ $user->name }}</div>
                                    </div>
                                </a>
                                <div class="text-right font-bold text-gray-600 text-sm">
                                    @if($userSort === 'point')
                                        {{ number_format($displayPoints) }}<span class="text-xs ml-0.5">Pt</span>
                                    @else
                                        {{ number_format($user->posts_count) }}<span class="text-xs ml-0.5">杯</span>
                                    @endif
                                </div>
                            </div>
                        @endif
                    @endforeach

                    @if($users->isEmpty())
                        <div class="py-12 text-center text-gray-400 text-sm">データがありません</div>
                    @endif
                </div>

                <div class="mt-6 px-4">
                    {{ $users->links('vendor.pagination.ramen') }}
                </div>
            </section>
            @endif

            {{-- ========================================== --}}
            {{-- B. 人気店ランキング --}}
            {{-- ========================================== --}}
            @if($tab === 'shops')
            <section>
                
                {{-- ▼▼▼ 修正: フィルタ＆ソートエリア ▼▼▼ --}}
                <div class="px-4 pt-3 flex justify-between items-center">
                    {{-- ジャンル選択ボタン（モーダルを開く） --}}
                    <button 
                        @click="genreModalOpen = true"
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border transition shadow-sm
                               {{ $genreId ? 'bg-orange-500 text-white border-orange-500' : 'bg-white text-gray-600 border-gray-200' }}"
                    >
                        <span class="text-xs font-bold">
                            @if($genreId && $selectedGenre = $allGenres->find($genreId))
                                {{ $selectedGenre->name }}
                            @else
                                🏷️ ジャンル絞り込み
                            @endif
                        </span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    {{-- ソート切り替え --}}
                    <div class="inline-flex bg-white rounded-lg p-1 border border-gray-200 shadow-sm">
                        <a href="{{ request()->fullUrlWithQuery(['shop_sort' => 'count']) }}"
                           class="px-3 py-1.5 text-xs font-bold rounded-md transition {{ $shopSort === 'count' ? 'bg-orange-50 text-orange-600' : 'text-gray-400 hover:text-gray-600' }}">投稿数</a>
                        <a href="{{ request()->fullUrlWithQuery(['shop_sort' => 'score']) }}"
                           class="px-3 py-1.5 text-xs font-bold rounded-md transition {{ $shopSort === 'score' ? 'bg-orange-50 text-orange-600' : 'text-gray-400 hover:text-gray-600' }}">平均点</a>
                    </div>
                </div>
                {{-- ▲▲▲ 修正ここまで ▲▲▲ --}}

                <div class="space-y-4 px-4 mt-3">
                    @foreach($shops as $index => $shop)
                        @php 
                            $rank = $shops->firstItem() + $index; 
                            $shopCount = $shop->posts_count; 
                            $shopScore = $shop->posts_avg_score ?? 0;
                            $isTop3 = $rank <= 3;
                        @endphp
                        
                        @if($isTop3)
                            {{-- トップ3デザイン --}}
                            <div class="bg-gradient-to-br from-amber-50 to-orange-100 rounded-2xl shadow-md overflow-hidden border border-orange-200 relative">
                                <div class="absolute inset-0 opacity-10 pointer-events-none" style="background-image: radial-gradient(circle at center, orange 1px, transparent 1px); background-size: 20px 20px;"></div>
                                <div class="bg-red-600 text-white text-center font-black py-1 text-sm shadow-sm relative z-10">{{ $rank }}位</div>
                                <div class="p-6 flex flex-col items-center relative z-10">
                                    <a href="{{ route('shops.show', $shop->id) }}" class="relative group">
                                        <div class="w-32 h-32 rounded-full border-4 border-white shadow-lg overflow-hidden bg-gray-100">
                                            @if($shop->latestPost && $shop->latestPost->image_path)
                                                <img src="{{ asset($shop->latestPost->image_path) }}" class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
                                            @else
                                                <div class="w-full h-full flex items-center justify-center bg-orange-50 text-orange-400 text-4xl font-black">{{ mb_substr($shop->name, 0, 1) }}</div>
                                            @endif
                                        </div>
                                        <div class="absolute -bottom-2 -right-2 text-4xl drop-shadow-md">{{ ['🥇','🥈','🥉'][$rank-1] }}</div>
                                    </a>
                                    <a href="{{ route('shops.show', $shop->id) }}" class="mt-4 text-center group px-4">
                                        <h3 class="text-xl font-black text-gray-800 leading-tight group-hover:text-orange-600 transition">{{ $shop->name }}</h3>
                                        @if($shop->address)
                                            <p class="text-xs text-gray-500 mt-1 font-bold">{{ Str::limit($shop->address, 10) }}</p>
                                        @endif
                                    </a>
                                    <div class="mt-2 inline-block bg-white/80 px-4 py-1 rounded-full border border-orange-200 shadow-sm">
                                        @if($shopSort === 'score')
                                            <span class="font-black text-xl text-orange-600">{{ number_format($shopScore, 1) }}</span><span class="text-xs font-bold text-gray-500">点</span>
                                        @else
                                            <span class="font-black text-xl text-orange-600">{{ number_format($shopCount) }}</span><span class="text-xs font-bold text-gray-500">件</span>
                                        @endif
                                    </div>
                                    @if($shop->latestPost && $shop->latestPost->comment)
                                        <div class="mt-4 relative w-full">
                                            <div class="bg-white p-3 rounded-xl text-xs text-gray-600 shadow-sm border border-gray-100 relative text-center leading-relaxed">
                                                <div class="flex items-center justify-center gap-2 mb-1">
                                                    <div class="w-5 h-5 rounded-full overflow-hidden bg-gray-100">
                                                        @if($shop->latestPost->user && $shop->latestPost->user->icon_path)
                                                            <img src="{{ asset($shop->latestPost->user->icon_path) }}" class="w-full h-full object-cover">
                                                        @else
                                                            <div class="w-full h-full bg-blue-100"></div>
                                                        @endif
                                                    </div>
                                                    <span class="font-bold text-gray-400 text-[10px]">{{ $shop->latestPost->user->name ?? '名無し' }}</span>
                                                </div>
                                                "{{ Str::limit($shop->latestPost->comment, 60) }}"
                                                <div class="absolute -top-1.5 left-1/2 -translate-x-1/2 w-3 h-3 bg-white rotate-45 border-t border-l border-gray-100"></div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @else
                            {{-- 4位以下デザイン --}}
                            <div class="bg-white rounded-xl p-3 shadow-sm border border-gray-100 flex items-center">
                                <div class="w-8 text-center font-bold text-gray-400 mr-2">{{ $rank }}</div>
                                <a href="{{ route('shops.show', $shop->id) }}" class="flex items-center flex-1 min-w-0">
                                    <div class="w-10 h-10 rounded-lg bg-gray-100 mr-3 overflow-hidden border border-gray-100">
                                        @if($shop->latestPost && $shop->latestPost->image_path)
                                            <img src="{{ asset($shop->latestPost->image_path) }}" class="w-full h-full object-cover">
                                        @else
                                            <div class="w-full h-full flex items-center justify-center bg-orange-50 text-orange-400 font-bold">{{ mb_substr($shop->name, 0, 1) }}</div>
                                        @endif
                                    </div>
                                    <div class="truncate">
                                        <div class="font-bold text-gray-800 text-sm truncate">{{ $shop->name }}</div>
                                    </div>
                                </a>
                                <div class="text-right font-bold text-gray-600 text-sm">
                                    @if($shopSort === 'score')
                                        {{ number_format($shopScore, 1) }}<span class="text-xs ml-0.5">点</span>
                                    @else
                                        {{ number_format($shopCount) }}<span class="text-xs ml-0.5">件</span>
                                    @endif
                                </div>
                            </div>
                        @endif
                    @endforeach

                    @if($shops->isEmpty())
                        <div class="py-12 text-center text-gray-400 text-sm">
                            データがありません
                            @if($genreId)
                                <br><span class="text-xs">（このジャンルの投稿はまだありません）</span>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="mt-6 px-4">
                    {{ $shops->links('vendor.pagination.ramen') }}
                </div>

                {{-- ▼▼▼ ジャンル選択モーダル ▼▼▼ --}}
                <div 
                    x-show="genreModalOpen" 
                    class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/60 backdrop-blur-sm p-4"
                    x-transition.opacity
                    style="display: none;"
                >
                    <div 
                        @click.away="genreModalOpen = false"
                        class="bg-white w-full max-w-lg rounded-2xl overflow-hidden shadow-2xl flex flex-col max-h-[80vh]"
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="translate-y-full sm:translate-y-10 opacity-0"
                        x-transition:enter-end="translate-y-0 opacity-100"
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="translate-y-0 opacity-100"
                        x-transition:leave-end="translate-y-full sm:translate-y-10 opacity-0"
                    >
                        <div class="p-4 border-b flex justify-between items-center bg-gray-50">
                            <h3 class="font-bold text-gray-800">ジャンルで絞り込み</h3>
                            <button @click="genreModalOpen = false" class="p-1 text-gray-400 hover:text-gray-600 bg-gray-200 rounded-full">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>

                        <div class="p-4 overflow-y-auto">
                            {{-- 「すべて」ボタン --}}
                            <a href="{{ request()->fullUrlWithQuery(['genre_id' => null]) }}" 
                               class="block w-full text-center py-3 mb-4 rounded-xl font-bold transition border-2
                                      {{ is_null($genreId) ? 'bg-orange-500 text-white border-orange-500' : 'bg-gray-50 text-gray-600 border-gray-100 hover:bg-gray-100' }}">
                                すべてのお店
                            </a>

                            {{-- ジャンル一覧 --}}
                            <div class="grid grid-cols-2 gap-2">
                                @foreach($allGenres as $g)
                                    <a href="{{ request()->fullUrlWithQuery(['genre_id' => $g->id]) }}" 
                                       class="py-2.5 px-2 text-center text-sm font-bold rounded-lg border transition
                                              {{ $genreId == $g->id 
                                                  ? 'bg-orange-50 text-orange-600 border-orange-200 shadow-inner' 
                                                  : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50 hover:border-gray-300' }}">
                                        {{ $g->name }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                {{-- ▲▲▲ モーダルここまで ▲▲▲ --}}

            </section>
            @endif

        </div>
    </div>
</x-app-layout>