<x-app-layout title="ホーム">
    <div class="p- text-center">
        @if($campaign)
        <div class="mb-6 px-1">
            <div
                class="relative bg-gradient-to-br from-red-600 to-red-700 rounded-2xl p-4 text-white shadow-md shadow-red-200 overflow-hidden group"
            >
                <div
                    class="absolute -right-4 -bottom-8 text-white opacity-10 font-black text-8xl italic select-none pointer-events-none transform group-hover:scale-110 transition duration-700"
                >
                    x{{ $campaign->multiplier }}
                </div>

                <div class="relative z-10 flex justify-between items-center">
                    <div class="flex-1 pr-2">
                        <div class="flex items-baseline gap-2 mb-2">
                            <span
                                class="bg-white/20 backdrop-blur-sm text-[10px] font-bold px-2 py-0.5 rounded-full text-white border border-white/10 shrink-0"
                            >
                                PICKUP
                            </span>
                            <h2
                                class="text-lg font-black leading-tight tracking-tight line-clamp-1"
                            >
                                {{ $campaign->title }}
                            </h2>
                        </div>

                        <div
                            class="flex items-center gap-1 text-xs font-bold text-red-100"
                        >
                            <span
                                class="bg-yellow-400 w-1.5 h-1.5 rounded-full animate-pulse"
                            ></span>
                            今ならポイント{{ $campaign->multiplier }}倍！
                        </div>
                    </div>

                    <a
                        href="https://www.google.com/maps/search/?api=1&query={{ urlencode($campaign->shop->name ?? '') }}+ラーメン"
                        target="_blank"
                        class="shrink-0 inline-flex items-center justify-center bg-white text-red-700 text-xs font-bold w-10 h-10 rounded-full hover:bg-red-50 transition shadow-sm group-hover:shadow-md"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </a>
                </div>
            </div>
        </div>
        @endif
        <div class="mt-4 space-y-4">
            @foreach($posts as $post)
            <div
                class="bg-white rounded-lg border border-gray-100 shadow-sm overflow-hidden mb-4"
            >
                <div class="p-3 flex items-center space-x-3">
                    <a
                        href="{{ route('users.show', $post->user->id) }}"
                        class="flex items-center space-x-3 group"
                    >
                        {{-- ▼▼▼ 修正箇所 ▼▼▼ --}}
                        <div
                            class="h-8 w-8 rounded-full overflow-hidden shrink-0 border border-gray-100"
                        >
                            @if($post->user->icon_path)
                            {{-- 画像がある場合 --}}
                            <img
                                src="{{ asset($post->user->icon_path) }}"
                                class="w-full h-full object-cover"
                                alt="{{ $post->user->name }}"
                            />
                            @else
                            {{-- 画像がない場合（デフォルト） --}}
                            {{-- 元のデザインクラス(bg-blue-100等)をここに適用 --}}
                            <div
                                class="w-full h-full bg-blue-100 flex items-center justify-center text-xs font-bold text-blue-600"
                            >
                                {{ mb_substr($post->user->name, 0, 1) }}
                            </div>
                            @endif
                        </div>
                        {{-- ▲▲▲ 修正箇所 ▲▲▲ --}}

                        <div>
                            <p class="text-sm font-bold">
                                {{ $post->user->name }}
                            </p>
                            <p class="text-xs text-gray-400">
                                {{ $post->eaten_at->diffForHumans() }}
                            </p>
                        </div>
                    </a>
                </div>

                @if($post->image_path)
                <div
                    class="h-64 bg-gray-100 overflow-hidden flex items-center justify-center"
                >
                    <img
                        src="{{ asset($post->image_path) }}"
                        alt="ラーメン画像"
                        class="w-full h-full object-cover"
                    />
                </div>
                @else
                <div
                    class="h-48 bg-gray-50 flex items-center justify-center text-gray-300"
                >
                    <div class="text-center">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-12 w-12 mx-auto mb-2"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                            />
                        </svg>
                        <span class="text-sm">No Image</span>
                    </div>
                </div>
                @endif

                <div class="p-4">
                    <div class="flex items-center justify-between mb-2">
                        <h4
                            class="font-bold text-lg text-gray-800 flex items-center gap-2"
                        >
                            <a
                                href="{{ route('shops.show', $post->shop->id) }}"
                                class="hover:text-orange-600 hover:underline transition"
                            >
                                {{ $post->shop->name }}
                            </a>

                            <a
                                href="https://www.google.com/maps/search/?api=1&query={{ urlencode($post->shop->name) }}+ラーメン"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-gray-400 hover:text-red-500 transition"
                                title="Googleマップで見る"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5"
                                    viewBox="0 0 20 20"
                                    fill="currentColor"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            </a>
                        </h4>
                        <div class="flex text-orange-400 text-sm">
                            @for($i=0; $i<$post->score; $i++) ★ @endfor
                        </div>
                    </div>
                    <p class="text-gray-600 leading-relaxed">
                        {{ $post->comment }}
                    </p>

                    <div
                        class="flex items-center justify-end border-t border-gray-50 pt-2"
                        x-data="{ 
                        liked: {{ $post->isLikedBy(Auth::user()) ? 'true' : 'false' }}, 
                        count: {{ $post->likes->count() }} 
                     }"
                    >
                        <button
                            @click="
                        fetch('/posts/{{ $post->id }}/like', {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                        })
                        .then(res => res.json())
                        .then(data => {
                            liked = (data.status === 'added');
                            count = data.count;
                        })"
                            class="flex items-center gap-1 transition"
                            :class="liked ? 'text-red-500' : 'text-gray-400 hover:text-red-400'"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-6 w-6 transition transform active:scale-125"
                                :fill="liked ? 'currentColor' : 'none'"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"
                                />
                            </svg>

                            <span
                                class="text-sm font-bold"
                                x-text="count"
                            ></span>
                        </button>
                    </div>
                </div>
            </div>
            @endforeach @if($posts->isEmpty())
            <div class="text-center py-10 text-gray-400">
                <p>まだ投稿がありません。<br />あなたが最初の発見者に！</p>
            </div>
            @endif
            <div class="mt-8 pb-10">
                {{ $posts->links('vendor.pagination.ramen') }}
            </div>
        </div>
    </div>
</x-app-layout>
