<x-app-layout title="ランキング">
    <div x-data="{ activeTab: '{{ request('tab', 'users') }}' }" class="pb-20 bg-gray-50 min-h-screen" x-cloak>
        
        {{-- ========================================== --}}
        {{-- 固定ヘッダー（タブ ＆ 期間切り替え） --}}
        {{-- ========================================== --}}
        <div class="bg-white shadow-md sticky top-0 z-10">
            <div class="px-4 py-3 space-y-3">
                {{-- ① タブ切り替えボタン --}}
                <div class="flex bg-gray-100 p-1 rounded-full">
                    <a href="{{ request()->fullUrlWithQuery(['tab' => 'users']) }}"
                       class="flex-1 py-2 rounded-full text-sm font-bold flex items-center justify-center gap-2 transition duration-300"
                       :class="activeTab === 'users' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-400 hover:text-gray-600'">
                        <span>🏆</span> 部員
                    </a>
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
                           style="{{ request('period', 'weekly') === $key 
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
            <section x-show="activeTab === 'users'">
                {{-- ソート切り替え --}}
                <div class="flex justify-end px-4 py-3">
                    <div class="inline-flex bg-white rounded-lg p-1 border border-gray-200 shadow-sm">
                        <a href="{{ request()->fullUrlWithQuery(['user_sort' => 'point']) }}"
                           class="px-3 py-1.5 text-xs font-bold rounded-md transition {{ request('user_sort', 'point') === 'point' ? 'bg-blue-50 text-blue-600' : 'text-gray-400 hover:text-gray-600' }}">
                            ポイント順
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['user_sort' => 'count']) }}"
                           class="px-3 py-1.5 text-xs font-bold rounded-md transition {{ request('user_sort') === 'count' ? 'bg-blue-50 text-blue-600' : 'text-gray-400 hover:text-gray-600' }}">
                            杯数順
                        </a>
                    </div>
                </div>

                <div class="space-y-4 px-4">
                    @foreach($users as $index => $user)
                    @php 
                        $rank = $users->firstItem() + $index;
                        
                        // ポイント計算
                        $postPoints = $user->posts_sum_earned_points ?? 0;
                        $rallyPoints = ($user->completed_rallies_count ?? 0) * 5;
                        $totalPoints = (request('period') === 'total') ? $user->total_score : ($postPoints + $rallyPoints);
                        
                        // トップ3かどうか
                        $isTop3 = $rank <= 3;
                    @endphp

                    @if($isTop3)
                        {{-- ★★★ トップ3用の豪華デザイン（参考画像風） ★★★ --}}
                        <div class="bg-gradient-to-br from-amber-50 to-orange-100 rounded-2xl shadow-md overflow-hidden border border-orange-200 relative">
                            {{-- 背景の装飾（月桂樹っぽい雰囲気） --}}
                            <div class="absolute inset-0 opacity-10 pointer-events-none" 
                                 style="background-image: radial-gradient(circle at center, orange 1px, transparent 1px); background-size: 20px 20px;"></div>

                            {{-- 赤い順位バー --}}
                            <div class="bg-red-600 text-white text-center font-black py-1 text-sm shadow-sm relative z-10">
                                {{ $rank }}位
                            </div>

                            <div class="p-6 flex flex-col items-center relative z-10">
                                {{-- アイコン（大・丸） --}}
                                <a href="{{ route('users.show', $user->id) }}" class="relative group">
                                    <div class="w-28 h-28 rounded-full border-4 border-white shadow-lg overflow-hidden bg-gray-100">
                                        @if($user->icon_path)
                                            <img src="{{ asset($user->icon_path) }}" class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
                                        @else
                                            <div class="w-full h-full flex items-center justify-center bg-blue-50 text-blue-400 text-4xl font-black">
                                                {{ mb_substr($user->name, 0, 1) }}
                                            </div>
                                        @endif
                                    </div>
                                    {{-- メダルバッジ --}}
                                    <div class="absolute -bottom-2 -right-2 text-4xl drop-shadow-md">
                                        {{ ['🥇','🥈','🥉'][$rank-1] }}
                                    </div>
                                </a>

                                {{-- 名前 --}}
                                <a href="{{ route('users.show', $user->id) }}" class="mt-4 text-center group">
                                    <h3 class="text-xl font-black text-gray-800 leading-tight group-hover:text-blue-600 transition">{{ $user->name }}</h3>
                                </a>

                                {{-- スコア --}}
                                <div class="mt-2 inline-block bg-white/80 px-4 py-1 rounded-full border border-orange-200 shadow-sm">
                                    @if(request('user_sort') === 'point')
                                        <span class="font-black text-xl text-red-500">{{ number_format($totalPoints) }}</span>
                                        <span class="text-xs font-bold text-gray-500">Pt</span>
                                    @else
                                        <span class="font-black text-xl text-red-500">{{ number_format($user->posts_count) }}</span>
                                        <span class="text-xs font-bold text-gray-500">杯</span>
                                    @endif
                                </div>

                                {{-- 吹き出しコメント（最新の投稿があれば） --}}
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
                        {{-- ★★★ 4位以下の通常リスト ★★★ --}}
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
                                @if(request('user_sort') === 'point')
                                    {{ number_format($totalPoints) }}<span class="text-xs ml-0.5">Pt</span>
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


            {{-- ========================================== --}}
            {{-- B. 人気店ランキング --}}
            {{-- ========================================== --}}
            <section x-show="activeTab === 'shops'" style="display: none;">
                {{-- ソート切り替え --}}
                <div class="flex justify-end px-4 py-3">
                    <div class="inline-flex bg-white rounded-lg p-1 border border-gray-200 shadow-sm">
                        <a href="{{ request()->fullUrlWithQuery(['shop_sort' => 'count']) }}"
                           class="px-3 py-1.5 text-xs font-bold rounded-md transition {{ request('shop_sort', 'count') === 'count' ? 'bg-orange-50 text-orange-600' : 'text-gray-400 hover:text-gray-600' }}">
                            投稿数順
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['shop_sort' => 'score']) }}"
                           class="px-3 py-1.5 text-xs font-bold rounded-md transition {{ request('shop_sort') === 'score' ? 'bg-orange-50 text-orange-600' : 'text-gray-400 hover:text-gray-600' }}">
                            平均点順
                        </a>
                    </div>
                </div>

                <div class="space-y-4 px-4">
                    @foreach($shops as $index => $shop)
                        @php 
                            $rank = $shops->firstItem() + $index; 
                            $shopCount = $shop->posts_count;
                            $shopScore = $shop->posts_avg_score;
                            $isTop3 = $rank <= 3;
                        @endphp

                        @if($isTop3)
                            {{-- ★★★ トップ3用の豪華デザイン（店舗版） ★★★ --}}
                            <div class="bg-gradient-to-br from-amber-50 to-orange-100 rounded-2xl shadow-md overflow-hidden border border-orange-200 relative">
                                <div class="absolute inset-0 opacity-10 pointer-events-none" 
                                     style="background-image: radial-gradient(circle at center, orange 1px, transparent 1px); background-size: 20px 20px;"></div>

                                {{-- 赤い順位バー --}}
                                <div class="bg-red-600 text-white text-center font-black py-1 text-sm shadow-sm relative z-10">
                                    {{ $rank }}位
                                </div>

                                <div class="p-6 flex flex-col items-center relative z-10">
                                    {{-- 画像（大・丸） --}}
                                    <a href="{{ route('shops.show', $shop->id) }}" class="relative group">
                                        <div class="w-32 h-32 rounded-full border-4 border-white shadow-lg overflow-hidden bg-gray-100">
                                            @if($shop->latestPost && $shop->latestPost->image_path)
                                                <img src="{{ asset($shop->latestPost->image_path) }}" class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
                                            @else
                                                <div class="w-full h-full flex items-center justify-center bg-orange-50 text-orange-400 text-4xl font-black">
                                                    {{ mb_substr($shop->name, 0, 1) }}
                                                </div>
                                            @endif
                                        </div>
                                        <div class="absolute -bottom-2 -right-2 text-4xl drop-shadow-md">
                                            {{ ['🥇','🥈','🥉'][$rank-1] }}
                                        </div>
                                    </a>

                                    {{-- 店名 --}}
                                    <a href="{{ route('shops.show', $shop->id) }}" class="mt-4 text-center group px-4">
                                        <h3 class="text-xl font-black text-gray-800 leading-tight group-hover:text-orange-600 transition">{{ $shop->name }}</h3>
                                        @if($shop->address)
                                            <p class="text-xs text-gray-500 mt-1 font-bold">{{ Str::limit($shop->address, 10) }}</p>
                                        @endif
                                    </a>

                                    {{-- スコア --}}
                                    <div class="mt-2 inline-block bg-white/80 px-4 py-1 rounded-full border border-orange-200 shadow-sm">
                                        @if(request('shop_sort') === 'score')
                                            <span class="font-black text-xl text-orange-600">{{ number_format($shopScore, 1) }}</span>
                                            <span class="text-xs font-bold text-gray-500">点</span>
                                        @else
                                            <span class="font-black text-xl text-orange-600">{{ number_format($shopCount) }}</span>
                                            <span class="text-xs font-bold text-gray-500">件</span>
                                        @endif
                                    </div>

                                    {{-- 吹き出し（最新投稿のコメント） --}}
                                    @if($shop->latestPost && $shop->latestPost->comment)
                                        <div class="mt-4 relative w-full">
                                            <div class="bg-white p-3 rounded-xl text-xs text-gray-600 shadow-sm border border-gray-100 relative text-center leading-relaxed">
                                                <div class="flex items-center justify-center gap-2 mb-1">
                                                    {{-- ユーザーアイコン小 --}}
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
                            {{-- ★★★ 4位以下の通常リスト ★★★ --}}
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
                                    @if(request('shop_sort') === 'score')
                                        {{ number_format($shopScore, 1) }}<span class="text-xs ml-0.5">点</span>
                                    @else
                                        {{ number_format($shopCount) }}<span class="text-xs ml-0.5">件</span>
                                    @endif
                                </div>
                            </div>
                        @endif
                    @endforeach

                    @if($shops->isEmpty())
                        <div class="py-12 text-center text-gray-400 text-sm">データがありません</div>
                    @endif
                </div>

                <div class="mt-6 px-4">
                    {{ $shops->links('vendor.pagination.ramen') }}
                </div>
            </section>

        </div>
    </div>
</x-app-layout>