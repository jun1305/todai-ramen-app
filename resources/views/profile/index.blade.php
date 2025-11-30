<x-app-layout title="マイページ">
    <div
        class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 mb-6 text-center relative overflow-hidden"
    >
        @if(Auth::id() === $user->id)
        <form
            action="{{ route('logout') }}"
            method="POST"
            class="absolute top-3 right-3 z-20"
            onsubmit="return confirm('ログアウトしますか？');"
        >
            @csrf
            <button
                class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-full transition"
                title="ログアウト"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-6 w-6"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
                    />
                </svg>
            </button>
        </form>
        @endif
        <div
            class="absolute top-0 left-0 w-full h-24 bg-gradient-to-r from-blue-500 to-cyan-400 opacity-10"
        ></div>

        <div
            class="relative z-10 mx-auto h-24 w-24 rounded-full bg-white p-1 shadow-md mb-3"
        >
            <div
                class="h-full w-full rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-black text-3xl"
            >
                {{ mb_substr($user->name, 0, 1) }}
            </div>
        </div>

        <h2 class="text-xl font-black text-gray-800 mb-6 relative z-10">
            {{ $user->name }}
        </h2>

        <div class="flex justify-center gap-4 relative z-10">
            <div
                class="flex-1 bg-gray-50 rounded-xl p-3 border border-gray-100"
            >
                <p class="text-xs text-gray-400 font-bold mb-1">食べた杯数</p>
                <p class="text-2xl font-black text-gray-800">
                    {{ $user->posts_count


                    }}<span class="text-xs font-normal ml-1">杯</span>
                </p>
            </div>
            <div
                class="flex-1 bg-orange-50 rounded-xl p-3 border border-orange-100"
            >
                <p class="text-xs text-orange-400 font-bold mb-1">
                    獲得ポイント
                </p>
                <p class="text-2xl font-black text-orange-600">
                    {{ $user->points


                    }}<span class="text-xs font-normal ml-1">Pt</span>
                </p>
            </div>
        </div>
    </div>

    <div class="pb-20">
        <h3
            class="font-bold text-gray-500 text-sm mb-4 px-2 flex items-center gap-2"
        >
            <span>📅</span> 麺活ログ
        </h3>

        @foreach($posts as $post)
        <div
            class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden mb-4 flex"
        >
            <div class="flex-1 p-4">
                <p class="text-[10px] text-gray-400 font-bold mb-1">
                    {{ $post->eaten_at->format('Y/m/d') }}
                </p>
                <h4 class="font-bold text-gray-800 mb-2 line-clamp-1">
                    <a
                        href="{{ route('shops.show', $post->shop->id) }}"
                        class="hover:text-orange-600 hover:underline"
                    >
                        {{ $post->shop->name }}
                    </a>
                </h4>

                <div class="flex text-orange-400 text-xs mb-2">
                    @for($i=0; $i<$post->score; $i++) ★ @endfor
                </div>

                <p class="text-xs text-gray-500 line-clamp-2">
                    {{ $post->comment }}
                </p>
            </div>

            @if($post->image_path)
            <div class="w-24 bg-gray-100 shrink-0">
                <img
                    src="{{ asset('storage/' . $post->image_path) }}"
                    class="w-full h-full object-cover"
                />
            </div>
            @endif
        </div>
        @endforeach @if($posts->isEmpty())
        <div class="text-center py-10 text-gray-400">
            まだ記録がありません。<br />今日の一杯を食べに行こう！
        </div>
        @endif
        <div class="mt-4 px-2">
            {{ $posts->links() }}
        </div>
    </div>
</x-app-layout>
