<x-app-layout title="ランキング">
    <div
        {{-- request('tab') でサーバーから受け取ったタブを初期値にする --}}
        x-data="{ activeTab: '{{ request('tab', 'users') }}' }"
        class="pb-20 bg-gray-50 min-h-screen"
        x-cloak
    >
        {{-- ヘッダー部分 --}}
        <div class="bg-white shadow-sm sticky top-0 z-30">
            <div class="p-4 space-y-4">
                
                {{-- ① タブ切り替え --}}
                <div class="flex bg-gray-100 p-1 rounded-full relative">
                    {{-- 
                        タブ切り替え時にURLのパラメータも書き換える（JSのみ）。
                        ただし、ページネーション側で 'tab' を固定しているので、
                        ここの replaceState はあくまで「リロードしたときに同じタブを開く」用。
                    --}}
                    <button
                        @click="activeTab = 'users'; window.history.replaceState(null, '', '{{ request()->fullUrlWithQuery(['tab' => 'users']) }}');"
                        class="flex-1 py-2.5 rounded-full text-sm font-bold transition duration-300 z-10 focus:outline-none flex items-center justify-center gap-2"
                        :class="activeTab === 'users' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-400 hover:text-gray-600'"
                    >
                        <span>🏆</span> 部員
                    </button>
                    <button
                        @click="activeTab = 'shops'; window.history.replaceState(null, '', '{{ request()->fullUrlWithQuery(['tab' => 'shops']) }}');"
                        class="flex-1 py-2.5 rounded-full text-sm font-bold transition duration-300 z-10 focus:outline-none flex items-center justify-center gap-2"
                        :class="activeTab === 'shops' ? 'bg-white text-orange-600 shadow-sm' : 'text-gray-400 hover:text-gray-600'"
                    >
                        <span>🔥</span> 人気店
                    </button>
                </div>

                {{-- ② 期間切り替え --}}
                <div class="flex justify-start gap-2 overflow-x-auto px-1 pb-1 no-scrollbar">
                    @foreach(['weekly' => '週間', 'monthly' => '月間', 'yearly' => '年間', 'total' => '累計'] as $key => $label)
                    {{-- ヘルパー関数で現在のURLの 'period' だけを書き換える --}}
                    <a
                        href="{{ request()->fullUrlWithQuery(['period' => $key]) }}"
                        class="px-4 py-1.5 text-xs font-bold rounded-full border transition-colors whitespace-nowrap"
                        style="{{ $period === $key ? 'background-color: #1f2937; color: white; border-color: #1f2937;' : 'background-color: white; color: #6b7280; border-color: #e5e7eb;' }}"
                    >
                        {{ $label }}
                    </a>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="p-4 max-w-xl mx-auto">

            {{-- ========================================== --}}
            {{-- 部員ランキングエリア --}}
            {{-- ========================================== --}}
            <section x-show="activeTab === 'users'" style="display: none;" :style="activeTab === 'users' ? 'display: block' : 'display: none'">
                
                {{-- 部員ソート --}}
                <div class="flex justify-end mb-4">
                    <div class="inline-flex bg-white rounded-lg p-1 border border-gray-200 shadow-sm">
                        {{-- 現在のURLパラメータを維持しつつ、tabとuser_sortを更新 --}}
                        <a href="{{ request()->fullUrlWithQuery(['tab' => 'users', 'user_sort' => 'point']) }}"
                           class="px-3 py-1.5 text-xs font-bold rounded-md transition {{ $userSort === 'point' ? 'bg-blue-50 text-blue-600' : 'text-gray-400 hover:text-gray-600' }}">
                            ポイント順
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['tab' => 'users', 'user_sort' => 'count']) }}"
                           class="px-3 py-1.5 text-xs font-bold rounded-md transition {{ $userSort === 'count' ? 'bg-blue-50 text-blue-600' : 'text-gray-400 hover:text-gray-600' }}">
                            杯数順
                        </a>
                    </div>
                </div>

                {{-- 部員リスト --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    @foreach($users as $index => $user)
                    @php $rank = $users->firstItem() + $index; @endphp
                    
                    <div class="flex items-center p-4 border-b border-gray-50 last:border-none relative">
                        <div class="flex-none w-10 flex flex-col items-center justify-center mr-2">
                            @if($rank <= 3) <span class="text-2xl">{{ ['🥇','🥈','🥉'][$rank-1] }}</span>
                            @else <span class="font-black text-lg text-gray-400">{{ $rank }}</span> @endif
                        </div>

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

                        <div class="text-right ml-2 shrink-0">
                            @if($userSort === 'point')
                                <div class="font-black text-lg text-blue-600 leading-none">
                                    {{ number_format($user->posts_sum_earned_points ?? 0) }}<span class="text-xs font-bold ml-0.5">Pt</span>
                                </div>
                                <p class="text-[10px] text-gray-400 font-bold mt-1">{{ number_format($user->posts_count) }}杯</p>
                            @else
                                <div class="font-black text-lg text-blue-600 leading-none">
                                    {{ number_format($user->posts_count) }}<span class="text-xs font-bold ml-0.5">杯</span>
                                </div>
                                <p class="text-[10px] text-gray-400 font-bold mt-1">{{ number_format($user->posts_sum_earned_points ?? 0) }}Pt</p>
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
            {{-- 人気店ランキングエリア --}}
            {{-- ========================================== --}}
            <section x-show="activeTab === 'shops'" style="display: none;" :style="activeTab === 'shops' ? 'display: block' : 'display: none'">
                
                {{-- 店舗ソート --}}
                <div class="flex justify-end mb-4">
                    <div class="inline-flex bg-white rounded-lg p-1 border border-gray-200 shadow-sm">
                        <a href="{{ request()->fullUrlWithQuery(['tab' => 'shops', 'shop_sort' => 'count']) }}"
                           class="px-3 py-1.5 text-xs font-bold rounded-md transition {{ $shopSort === 'count' ? 'bg-orange-50 text-orange-600' : 'text-gray-400 hover:text-gray-600' }}">
                            投稿数順
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['tab' => 'shops', 'shop_sort' => 'score']) }}"
                           class="px-3 py-1.5 text-xs font-bold rounded-md transition {{ $shopSort === 'score' ? 'bg-orange-50 text-orange-600' : 'text-gray-400 hover:text-gray-600' }}">
                            平均点順
                        </a>
                    </div>
                </div>

                {{-- 店舗リスト --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    @foreach($shops as $index => $shop)
                    @php $rank = $shops->firstItem() + $index; @endphp

                    <div class="flex items-center p-4 border-b border-gray-50 last:border-none">
                        <div class="flex-none w-10 flex flex-col items-center justify-center mr-2">
                            @if($rank <= 3) <span class="text-2xl">{{ ['🥇','🥈','🥉'][$rank-1] }}</span>
                            @else <span class="font-black text-lg text-gray-400">{{ $rank }}</span> @endif
                        </div>

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
                                @if($shopSort === 'score')
                                    <p class="text-[10px] text-gray-400 mt-0.5">{{ number_format($shop->posts_count) }}件の投稿</p>
                                @endif
                            </div>
                        </a>

                        <div class="text-right ml-2 shrink-0">
                            @if($shopSort === 'score')
                                <div class="font-black text-lg text-orange-600 leading-none flex items-baseline justify-end gap-0.5">
                                    {{ number_format($shop->posts_avg_score ?? 0, 1) }}<span class="text-xs font-bold">点</span>
                                </div>
                            @else
                                <div class="font-black text-lg text-orange-600 leading-none">
                                    {{ number_format($shop->posts_count) }}<span class="text-xs font-bold ml-0.5">件</span>
                                </div>
                                <div class="flex justify-end text-orange-300 text-[8px] mt-1">
                                    {{ number_format($shop->posts_avg_score ?? 0, 1) }}点
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