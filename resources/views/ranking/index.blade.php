<x-app-layout title="ランキング">
    
    {{-- x-data: 現在のタブ状態をURLから取得 --}}
    <div x-data="{ activeTab: '{{ request('tab') }}' }" class="pb-20 bg-gray-50 min-h-screen" x-cloak>
        
        {{-- ========================================== --}}
        {{-- 固定ヘッダー（タブ ＆ 期間切り替え） --}}
        {{-- ========================================== --}}
        <div class="bg-white shadow-sm sticky top-0 z-5">
            <div class="p-4 space-y-4">
                
                {{-- ① タブ切り替えボタン --}}
                <div class="flex bg-gray-100 p-1 rounded-full">
                    {{-- 部員タブ --}}
                    <a href="{{ request()->fullUrlWithQuery(['tab' => 'users']) }}"
                       class="flex-1 py-2.5 rounded-full text-sm font-bold flex items-center justify-center gap-2 transition duration-300"
                       :class="activeTab === 'users' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-400 hover:text-gray-600'">
                        <span>🏆</span> 部員
                    </a>
                    
                    {{-- 人気店タブ --}}
                    <a href="{{ request()->fullUrlWithQuery(['tab' => 'shops']) }}"
                       class="flex-1 py-2.5 rounded-full text-sm font-bold flex items-center justify-center gap-2 transition duration-300"
                       :class="activeTab === 'shops' ? 'bg-white text-orange-600 shadow-sm' : 'text-gray-400 hover:text-gray-600'">
                        <span>🔥</span> 人気店
                    </a>
                </div>

                {{-- ② 期間切り替えボタン --}}
                <div class="flex justify-start gap-2 overflow-x-auto px-1 pb-1 no-scrollbar">
                    @foreach(['weekly' => '週間', 'monthly' => '月間', 'yearly' => '年間', 'total' => '累計'] as $key => $label)
                        <a href="{{ request()->fullUrlWithQuery(['period' => $key]) }}"
                           class="px-4 py-1.5 text-xs font-bold rounded-full border transition-colors whitespace-nowrap"
                           style="{{ request('period') === $key 
                               ? 'background-color: #1f2937; color: white; border-color: #1f2937;' 
                               : 'background-color: white; color: #6b7280; border-color: #e5e7eb;' }}">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>


        <div class="p-4 max-w-xl mx-auto">

            {{-- ========================================== --}}
            {{-- A. 部員ランキング --}}
            {{-- ========================================== --}}
            <section x-show="activeTab === 'users'">
                
                {{-- ソート切り替え --}}
                <div class="flex justify-end mb-4">
                    <div class="inline-flex bg-white rounded-lg p-1 border border-gray-200 shadow-sm">
                        <a href="{{ request()->fullUrlWithQuery(['user_sort' => 'point']) }}"
                           class="px-3 py-1.5 text-xs font-bold rounded-md transition {{ request('user_sort') === 'point' ? 'bg-blue-50 text-blue-600' : 'text-gray-400 hover:text-gray-600' }}">
                            ポイント順
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['user_sort' => 'count']) }}"
                           class="px-3 py-1.5 text-xs font-bold rounded-md transition {{ request('user_sort') === 'count' ? 'bg-blue-50 text-blue-600' : 'text-gray-400 hover:text-gray-600' }}">
                            杯数順
                        </a>
                    </div>
                </div>

                {{-- リスト表示 --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    @foreach($users as $index => $user)
                    @php 
                        $rank = $users->firstItem() + $index;
    
                        // ▼▼▼ ポイント表示ロジック（修正版） ▼▼▼
    
                        // 1. 内訳データの取得（コントローラーで取得済み）
                        $postPoints = $user->posts_sum_earned_points ?? 0;
                        $rallyPoints = ($user->completed_rallies_count ?? 0) * 5;
    
                        // 2. 合計点の決定
                        if (request('period') === 'total') {
                            // 累計：合計点はカラムから（高速）、内訳は計算結果から
                            $totalPoints = $user->total_score;
                        } else {
                            // 期間別：合計点も計算結果から
                            $totalPoints = $postPoints + $rallyPoints;
                        }

                        // 3. 内訳表示フラグ（常に表示したいなら true）
                        $showBreakdown = true; 
    
                        // ▲▲▲ ポイント表示ロジック終わり ▲▲▲
                    @endphp
                    
                        <div class="flex items-center p-4 border-b border-gray-50 last:border-none">
                            {{-- 順位 --}}
                            <div class="flex-none w-10 flex flex-col items-center justify-center mr-2">
                                @if($rank <= 3) <span class="text-2xl">{{ ['🥇','🥈','🥉'][$rank-1] }}</span>
                                @else <span class="font-black text-lg text-gray-400">{{ $rank }}</span> @endif
                            </div>

                            {{-- ユーザー --}}
                            <a href="{{ route('users.show', $user->id) }}" class="flex items-center flex-1 min-w-0 group">
                                <div class="h-10 w-10 rounded-full bg-gray-100 mr-3 shrink-0 overflow-hidden border border-gray-100">
                                    @if($user->icon_path)
                                        <img src="{{ asset($user->icon_path) }}" loading="lazy" class="w-full h-full object-cover" />
                                    @else
                                        <div class="w-full h-full flex items-center justify-center text-blue-500 font-bold bg-blue-50">
                                            {{ mb_substr($user->name, 0, 1) }}
                                        </div>
                                    @endif
                                </div>
                                <div class="truncate">
                                    <p class="font-bold text-gray-800 text-sm group-hover:text-blue-600 transition">{{ $user->name }}</p>
                                </div>
                            </a>

                            {{-- スコア表示 --}}
                            <div class="text-right ml-2 shrink-0">
                                @if(request('user_sort') === 'point')
                                    <div class="font-black text-lg text-blue-600 leading-none">
                                        {{ number_format($totalPoints) }}<span class="text-xs font-bold ml-0.5">Pt</span>
                                    </div>
                                    {{-- 内訳表示（期間別の時のみ） --}}
                                    @if($showBreakdown)
                                        <p class="text-[10px] text-gray-400 font-bold mt-1">
                                            <span class="font-normal text-[9px] ml-0.5">(投{{$postPoints}}+ラ{{$rallyPoints}})</span>
                                        </p>
                                    @else
                                        {{-- 累計の時は杯数だけ表示しておく --}}
                                        <p class="text-[10px] text-gray-400 font-bold mt-1">
                                            {{ number_format($user->posts_count) }}杯
                                        </p>
                                    @endif
                                @else
                                    <div class="font-black text-lg text-blue-600 leading-none">
                                        {{ number_format($user->posts_count) }}<span class="text-xs font-bold ml-0.5">杯</span>
                                    </div>
                                    <p class="text-[10px] text-gray-400 font-bold mt-1">
                                        {{ number_format($totalPoints) }}Pt
                                    </p>
                                @endif
                            </div>
                        </div>
                    @endforeach

                    @if($users->isEmpty())
                        <div class="py-12 text-center text-gray-400 text-sm">データがありません</div>
                    @endif
                </div>

                {{-- ページネーション --}}
                <div class="mt-6">
                    {{ $users->links('vendor.pagination.ramen') }}
                </div>
            </section>


            {{-- ========================================== --}}
            {{-- B. 人気店ランキング --}}
            {{-- ========================================== --}}
            <section x-show="activeTab === 'shops'" style="display: none;">
                
                {{-- ソート切り替え --}}
                <div class="flex justify-end mb-4">
                    <div class="inline-flex bg-white rounded-lg p-1 border border-gray-200 shadow-sm">
                        <a href="{{ request()->fullUrlWithQuery(['shop_sort' => 'count']) }}"
                           class="px-3 py-1.5 text-xs font-bold rounded-md transition {{ request('shop_sort') === 'count' ? 'bg-orange-50 text-orange-600' : 'text-gray-400 hover:text-gray-600' }}">
                            投稿数順
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['shop_sort' => 'score']) }}"
                           class="px-3 py-1.5 text-xs font-bold rounded-md transition {{ request('shop_sort') === 'score' ? 'bg-orange-50 text-orange-600' : 'text-gray-400 hover:text-gray-600' }}">
                            平均点順
                        </a>
                    </div>
                </div>

                {{-- リスト表示 --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    @foreach($shops as $index => $shop)
                        @php 
                            $rank = $shops->firstItem() + $index; 
                            
                            // ▼▼▼ 店のスコア表示用ロジック（ここを追加・修正） ▼▼▼
                            if (request('period') === 'total') {
                                // 累計：カラムから直接取得
                                $shopCount = $shop->posts_count;
                                $shopScore = $shop->posts_avg_score;
                            } else {
                                // 期間別：計算結果のリレーションから取得
                                // withCountは {リレーション名}_count、withAvgは {リレーション名}_avg_{カラム名} という名前になる
                                $shopCount = $shop->posts_count; 
                                $shopScore = $shop->posts_avg_score; 
                            }
                            // ▲▲▲ ロジック終わり ▲▲▲
                        @endphp

                        <div class="flex items-center p-4 border-b border-gray-50 last:border-none">
                            {{-- 順位 --}}
                            <div class="flex-none w-10 flex flex-col items-center justify-center mr-2">
                                @if($rank <= 3) <span class="text-2xl">{{ ['🥇','🥈','🥉'][$rank-1] }}</span>
                                @else <span class="font-black text-lg text-gray-400">{{ $rank }}</span> @endif
                            </div>

                            {{-- 店舗情報 --}}
                            <a href="{{ route('shops.show', $shop->id) }}" class="flex items-center flex-1 min-w-0 group">
                                <div class="h-10 w-10 rounded-lg bg-gray-100 mr-3 shrink-0 overflow-hidden border border-gray-100 relative">
                                    @if($shop->latestPost && $shop->latestPost->image_path)
                                        <img src="{{ asset($shop->latestPost->image_path) }}" loading="lazy" class="w-full h-full object-cover" />
                                    @else
                                        <div class="w-full h-full flex items-center justify-center text-orange-500 font-bold bg-orange-50">
                                            {{ mb_substr($shop->name, 0, 1) }}
                                        </div>
                                    @endif
                                </div>
                                <div class="truncate pr-2">
                                    <p class="font-bold text-gray-800 text-sm group-hover:text-orange-600 transition truncate">{{ $shop->name }}</p>
                                    @if(request('shop_sort') === 'score')
                                        {{-- 修正: 変数を使用 --}}
                                        <p class="text-[10px] text-gray-400 mt-0.5">{{ number_format($shopCount) }}件の投稿</p>
                                    @endif
                                </div>
                            </a>

                            {{-- スコア表示 --}}
                            <div class="text-right ml-2 shrink-0">
                                @if(request('shop_sort') === 'score')
                                    <div class="font-black text-lg text-orange-600 leading-none flex items-baseline justify-end gap-0.5">
                                        {{-- 修正: 変数を使用 --}}
                                        {{ number_format($shopScore ?? 0, 1) }}<span class="text-xs font-bold">点</span>
                                    </div>
                                @else
                                    <div class="font-black text-lg text-orange-600 leading-none">
                                        {{-- 修正: 変数を使用 --}}
                                        {{ number_format($shopCount) }}<span class="text-xs font-bold ml-0.5">件</span>
                                    </div>
                                    <div class="flex justify-end text-orange-300 text-[8px] mt-1">
                                        {{-- 修正: 変数を使用 --}}
                                        {{ number_format($shopScore ?? 0, 1) }}点
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach

                    @if($shops->isEmpty())
                        <div class="py-12 text-center text-gray-400 text-sm">データがありません</div>
                    @endif
                </div>

                {{-- ページネーション --}}
                <div class="mt-6">
                    {{ $shops->links('vendor.pagination.ramen') }}
                </div>
            </section>

        </div>
    </div>
</x-app-layout>