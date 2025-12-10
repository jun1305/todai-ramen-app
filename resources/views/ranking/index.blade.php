<x-app-layout title="ランキング">
    <div
        x-data="{ activeTab: '{{ request('tab', 'users') }}' }"
        class="p-4 space-y-6 pb-20"
        x-cloak {{-- Alpine読み込み前のチラつき防止 --}}
    >
        {{-- タブ切り替え（部員 / 人気店） --}}
        <div class="flex bg-gray-200 p-1 rounded-full relative">
            <button
                @click="activeTab = 'users'"
                class="flex-1 py-2 rounded-full text-sm font-bold transition duration-300 z-10 focus:outline-none"
                style="-webkit-tap-highlight-color: transparent;"
                :class="activeTab === 'users' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
            >
                🏆 部員
            </button>
            <button
                @click="activeTab = 'shops'"
                class="flex-1 py-2 rounded-full text-sm font-bold transition duration-300 z-10 focus:outline-none"
                style="-webkit-tap-highlight-color: transparent;"
                :class="activeTab === 'shops' ? 'bg-white text-orange-600 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
            >
                🔥 人気店
            </button>
        </div>

        {{-- 期間切り替え（週間/月間...） --}}
        <div class="flex justify-start gap-2 overflow-x-auto px-1 py-1 no-scrollbar">
            @php
                $periods = ['weekly' => '週間', 'monthly' => '月間', 'yearly' => '年間', 'total' => '累計'];
                $currentPeriod = request('period', 'total');
            @endphp
            @foreach($periods as $key => $label)
            <button
                {{-- ページ遷移時に現在のタブ(activeTab)を維持するようにパラメータを渡す --}}
                @click="window.location.href = '{{ route('ranking.index') }}?period={{ $key }}&tab=' + activeTab"
                class="px-4 py-1.5 text-xs font-bold rounded-full border transition-colors whitespace-nowrap focus:outline-none"
                style="-webkit-tap-highlight-color: transparent;"
                :class="'{{ $currentPeriod }}' === '{{ $key }}'
                    ? 'bg-gray-800 text-white border-gray-800'
                    : 'bg-white text-gray-500 border-gray-200 hover:bg-gray-50'"
            >
                {{ $label }}
            </button>
            @endforeach
        </div>

        {{-- 部員ランキング --}}
        <section
            x-show="activeTab === 'users'"
            x-transition.opacity.duration.300ms
            style="display: none;" 
            :style="activeTab === 'users' ? 'display: block' : 'display: none'"
        >
            <div class="flex items-center justify-between mb-2 px-2">
                <h2 class="text-lg font-bold text-gray-800">部員ランキング</h2>
                <span class="text-xs text-gray-500 font-bold bg-blue-50 text-blue-600 px-2 py-1 rounded">
                    {{ $periods[$currentPeriod] }} / ポイント順
                </span>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                @foreach($users as $index => $user)
                <div class="flex items-center p-4 border-b border-gray-100 last:border-none relative">
                    {{-- 順位（左側の幅を少し狭くして間延びを防ぐ w-12 -> w-8 or w-10） --}}
                    <div class="flex-none w-8 flex flex-col items-center justify-center mr-1">
                        @if($index === 0)
                            <div class="w-6 h-6 flex items-center justify-center mb-1">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-full h-full text-yellow-500">
                                    <path fill-rule="evenodd" d="M12.003 4.978a.75.75 0 01.996.14l2.25 2.75 4.346-3.877a.75.75 0 011.196.852l-2.43 8.163a2.25 2.25 0 01-2.152 1.61H7.794a2.25 2.25 0 01-2.152-1.61L3.212 4.843a.75.75 0 011.196-.852l4.346 3.877 2.25-2.75a.75.75 0 01.999-.14z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <span class="font-black text-xl text-yellow-500 leading-none">{{ $index + 1 }}</span>
                        @elseif($index === 1)
                            <div class="w-5 h-5 flex items-center justify-center mb-1">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-full h-full text-gray-400">
                                    <path fill-rule="evenodd" d="M12.003 4.978a.75.75 0 01.996.14l2.25 2.75 4.346-3.877a.75.75 0 011.196.852l-2.43 8.163a2.25 2.25 0 01-2.152 1.61H7.794a2.25 2.25 0 01-2.152-1.61L3.212 4.843a.75.75 0 011.196-.852l4.346 3.877 2.25-2.75a.75.75 0 01.999-.14z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <span class="font-bold text-lg text-gray-500 leading-none">{{ $index + 1 }}</span>
                        @elseif($index === 2)
                            <div class="w-5 h-5 flex items-center justify-center mb-1">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-full h-full text-orange-400">
                                    <path fill-rule="evenodd" d="M12.003 4.978a.75.75 0 01.996.14l2.25 2.75 4.346-3.877a.75.75 0 011.196.852l-2.43 8.163a2.25 2.25 0 01-2.152 1.61H7.794a2.25 2.25 0 01-2.152-1.61L3.212 4.843a.75.75 0 011.196-.852l4.346 3.877 2.25-2.75a.75.75 0 01.999-.14z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <span class="font-bold text-lg text-orange-500 leading-none">{{ $index + 1 }}</span>
                        @else
                            <span class="font-bold text-lg text-gray-400 leading-none text-center w-full">{{ $index + 1 }}</span>
                        @endif
                    </div>

                    {{-- ユーザー情報へのリンク --}}
                    <a href="{{ route('users.show', $user->id) }}" class="flex items-center flex-1 min-w-0 group focus:outline-none" style="-webkit-tap-highlight-color: transparent;">
                        
                        {{-- 画像周り: mx-3をやめて ml-1 mr-3 に変更し、距離を詰める --}}
                        <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold ml-1 mr-3 shrink-0 overflow-hidden border border-blue-50">
                            @if($user->icon_path)
                                <img src="{{ asset($user->icon_path) }}" loading="lazy" class="w-full h-full object-cover" />
                            @else
                                {{ mb_substr($user->name, 0, 1) }}
                            @endif
                        </div>
                        
                        <div class="min-w-0">
                            <p class="font-bold text-gray-800 truncate group-hover:text-blue-600 transition text-base">
                                {{ $user->name }}
                            </p>
                        </div>
                    </a>

                    {{-- ポイント表示 --}}
                    <div class="text-right ml-2 shrink-0">
                        <div class="font-black text-lg text-orange-600 leading-none">
                            {{ $user->period_points }}<span class="text-xs font-bold ml-0.5">Pt</span>
                        </div>
                        <p class="text-[10px] text-gray-400 font-bold mt-0.5">
                            {{ $user->period_count }}杯
                        </p>
                    </div>
                </div>
                @endforeach
                
                @if($users->isEmpty())
                <div class="py-12 text-center text-gray-400 text-sm">
                    <p class="mb-2 text-2xl">🍃</p>
                    この期間の記録はありません
                </div>
                @endif
            </div>
        </section>

        {{-- 人気店ランキング --}}
        <section
            x-show="activeTab === 'shops'"
            x-transition.opacity.duration.300ms
            style="display: none;"
            :style="activeTab === 'shops' ? 'display: block' : 'display: none'"
        >
            <div class="flex items-center justify-between mb-2 px-2">
                <h2 class="text-lg font-bold text-gray-800">人気店ランキング</h2>
                <span class="text-xs text-gray-500 font-bold bg-orange-50 text-orange-600 px-2 py-1 rounded">
                    {{ $periods[$currentPeriod] }} / 投稿数順
                </span>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                @foreach($shops as $index => $shop)
                <div class="flex items-center p-4 border-b border-gray-100 last:border-none">
                    {{-- 順位 --}}
                    <div class="flex-none w-8 flex flex-col items-center justify-center mr-1">
                        @if($index === 0)
                            <div class="w-6 h-6 flex items-center justify-center mb-1">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-full h-full text-yellow-500">
                                    <path fill-rule="evenodd" d="M12.003 4.978a.75.75 0 01.996.14l2.25 2.75 4.346-3.877a.75.75 0 011.196.852l-2.43 8.163a2.25 2.25 0 01-2.152 1.61H7.794a2.25 2.25 0 01-2.152-1.61L3.212 4.843a.75.75 0 011.196-.852l4.346 3.877 2.25-2.75a.75.75 0 01.999-.14z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <span class="font-black text-xl text-yellow-500 leading-none">{{ $index + 1 }}</span>
                        @elseif($index === 1)
                            <div class="w-5 h-5 flex items-center justify-center mb-1">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-full h-full text-gray-400">
                                    <path fill-rule="evenodd" d="M12.003 4.978a.75.75 0 01.996.14l2.25 2.75 4.346-3.877a.75.75 0 011.196.852l-2.43 8.163a2.25 2.25 0 01-2.152 1.61H7.794a2.25 2.25 0 01-2.152-1.61L3.212 4.843a.75.75 0 011.196-.852l4.346 3.877 2.25-2.75a.75.75 0 01.999-.14z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <span class="font-bold text-lg text-gray-500 leading-none">{{ $index + 1 }}</span>
                        @elseif($index === 2)
                            <div class="w-5 h-5 flex items-center justify-center mb-1">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-full h-full text-orange-400">
                                    <path fill-rule="evenodd" d="M12.003 4.978a.75.75 0 01.996.14l2.25 2.75 4.346-3.877a.75.75 0 011.196.852l-2.43 8.163a2.25 2.25 0 01-2.152 1.61H7.794a2.25 2.25 0 01-2.152-1.61L3.212 4.843a.75.75 0 011.196-.852l4.346 3.877 2.25-2.75a.75.75 0 01.999-.14z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <span class="font-bold text-lg text-orange-500 leading-none">{{ $index + 1 }}</span>
                        @else
                            <span class="font-bold text-lg text-gray-400 leading-none text-center w-full">{{ $index + 1 }}</span>
                        @endif
                    </div>

                    {{-- 店舗情報 --}}
                    <a href="{{ route('shops.show', $shop->id) }}" class="h-10 w-10 rounded-lg bg-gray-100 overflow-hidden shrink-0 ml-1 mr-3 hover:opacity-80 transition block border border-gray-100 focus:outline-none" style="-webkit-tap-highlight-color: transparent;">
                        @if($shop->latestPost && $shop->latestPost->image_path)
                            <img src="{{ asset($shop->latestPost->image_path) }}" loading="lazy" class="w-full h-full object-cover" />
                        @else
                            <div class="w-full h-full flex items-center justify-center bg-orange-100 text-orange-600 font-bold">
                                {{ mb_substr($shop->name, 0, 1) }}
                            </div>
                        @endif
                    </a>

                    <div class="flex-1 min-w-0">
                        <a href="{{ route('shops.show', $shop->id) }}" class="font-bold text-gray-800 hover:text-orange-600 hover:underline transition block truncate text-base focus:outline-none">
                            {{ $shop->name }}
                        </a>
                        <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($shop->name) }}+ラーメン" target="_blank" class="text-xs text-blue-500 hover:underline flex items-center gap-1 mt-0.5 focus:outline-none">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            地図
                        </a>
                    </div>

                    <div class="font-bold text-gray-700 ml-2 shrink-0">
                        {{ $shop->posts_count }}<span class="text-xs text-gray-400 font-normal ml-0.5">件</span>
                    </div>
                </div>
                @endforeach
                
                @if($shops->isEmpty())
                <div class="py-12 text-center text-gray-400 text-sm">
                    <p class="mb-2 text-2xl">🍜</p>
                    この期間の記録はありません
                </div>
                @endif
            </div>
        </section>
    </div>
</x-app-layout> 