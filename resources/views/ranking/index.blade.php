<x-app-layout title="ランキング">
    <div x-data="{ activeTab: 'users' }" class="p-4 space-y-6 pb-20">
        <div class="flex bg-gray-200 p-1 rounded-full relative">
            <button
                @click="activeTab = 'users'"
                class="flex-1 py-2 rounded-full text-sm font-bold transition duration-300 z-10"
                :class="activeTab === 'users' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
            >
                🏆 部員
            </button>

            <button
                @click="activeTab = 'shops'"
                class="flex-1 py-2 rounded-full text-sm font-bold transition duration-300 z-10"
                :class="activeTab === 'shops' ? 'bg-white text-orange-600 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
            >
                🔥 人気店
            </button>
        </div>

        <section
            x-show="activeTab === 'users'"
            x-transition.opacity.duration.300ms
        >
            <div class="flex items-center justify-between mb-4 px-2">
                <h2 class="text-lg font-bold text-gray-800">部員ランキング</h2>
                <span class="text-xs text-gray-500">ポイント順</span>
            </div>

            <div
                class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden"
            >
                @foreach($users as $index => $user)
                <div
                    class="flex items-center p-4 border-b border-gray-100 last:border-none"
                >
                    <div
                        class="w-8 font-bold text-center text-lg {{
                            $index < 3 ? 'text-yellow-500' : 'text-gray-400'
                        }}"
                    >
                        {{ $index + 1 }}
                    </div>
                    <div
                        class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold mx-4 shrink-0"
                    >
                        {{ mb_substr($user->name, 0, 1) }}
                    </div>
                    <div class="flex-1">
                        <p class="font-bold text-gray-800">{{ $user->name }}</p>
                    </div>
                    <div class="text-right">
                        <div
                            class="font-black text-xl text-orange-600 leading-none"
                        >
                            {{ $user->points }}
                            <span class="text-xs font-bold">Pt</span>
                        </div>
                        <p class="text-[10px] text-gray-400 font-bold">
                            ({{ $user->posts_count }}杯)
                        </p>
                    </div>
                </div>
                @endforeach
            </div>
        </section>

        <section
            x-show="activeTab === 'shops'"
            x-transition.opacity.duration.300ms
            style="display: none"
        >
            <div class="flex items-center justify-between mb-4 px-2">
                <h2 class="text-lg font-bold text-gray-800">
                    人気店ランキング
                </h2>
                <span class="text-xs text-gray-500">投稿数順</span>
            </div>

            <div
                class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden"
            >
                @foreach($shops as $index => $shop)
                <div
                    class="flex items-center p-4 border-b border-gray-100 last:border-none"
                >
                    <div
                        class="w-8 font-bold text-center text-lg {{
                            $index < 3 ? 'text-red-500' : 'text-gray-400'
                        }}"
                    >
                        {{ $index + 1 }}
                    </div>

                    <div class="h-12 w-12 rounded-lg bg-gray-100 overflow-hidden shrink-0 mr-3">
                            @if($shop->latestPost && $shop->latestPost->image_path)
                                <img src="{{ asset( $shop->latestPost->image_path) }}" class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex items-center justify-center bg-orange-100 text-orange-600 font-bold">
                                    {{ mb_substr($shop->name, 0, 1) }}
                                </div>
                            @endif
                        </div>
                    <div class="flex-1 pl-2">
                        <a
                            href="{{ route('shops.show', $shop->id) }}"
                            class="font-bold text-gray-800 hover:text-orange-600 hover:underline transition block"
                        >
                            {{ $shop->name }}
                        </a>

                        <a
                            href="https://www.google.com/maps/search/?api=1&query={{ urlencode($shop->name) }}+ラーメン"
                            target="_blank"
                            class="text-xs text-blue-500 hover:underline flex items-center gap-1 mt-1"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-3 w-3"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                                />
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                                />
                            </svg>
                            地図
                        </a>
                    </div>
                    <div class="font-bold text-gray-700">
                        {{ $shop->posts_count }}
                        <span class="text-xs text-gray-400 font-normal"
                            >件</span
                        >
                    </div>
                </div>
                @endforeach
            </div>
        </section>
    </div>
</x-app-layout>
