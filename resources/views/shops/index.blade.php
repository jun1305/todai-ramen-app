<x-app-layout title="お店図鑑">
    <div class="p-4 pb-20">
        <div class="sticky top-0 z-10 bg-gray-50 pb-4 pt-1">
            <form action="{{ route('shops.index') }}" method="GET">
                <div class="relative">
                    <input
                        type="text"
                        name="search"
                        value="{{ $search ?? '' }}"
                        placeholder="店名で検索..."
                        class="w-full pl-10 pr-4 py-3 rounded-full border-2 border-gray-200 focus:border-orange-400 focus:outline-none shadow-sm transition"
                    />
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5 text-gray-400 absolute left-3.5 top-3.5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                        />
                    </svg>
                </div>
            </form>
        </div>

        <div class="grid gap-3">
            @foreach($shops as $shop)
            <a
                href="{{ route('shops.show', $shop->id) }}"
                class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between active:scale-[0.98] transition"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="h-14 w-14 rounded-xl bg-gray-100 overflow-hidden shrink-0 border border-gray-200 shadow-sm"
                    >
                        @if($shop->latestPost && $shop->latestPost->image_path)
                        <img
                            src="{{ asset('storage/' . $post->image_path) }}"
                            class="w-full h-full object-cover"
                        />
                        @else
                        <div
                            class="w-full h-full flex items-center justify-center bg-orange-100 text-orange-600 font-bold text-xl"
                        >
                            {{ mb_substr($shop->name, 0, 1) }}
                        </div>
                        @endif
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-800">
                            {{ $shop->name }}
                        </h3>
                        <p class="text-xs text-gray-500">
                            {{ $shop->posts_count }}件の投稿
                        </p>
                    </div>
                </div>
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-5 w-5 text-gray-300"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M9 5l7 7-7 7"
                    />
                </svg>
            </a>
            @endforeach @if($shops->isEmpty())
            <div class="text-center py-10 text-gray-400">
                お店が見つかりません... 🍜
            </div>
            @endif
            <div class="mt-4">
                {{ $shops->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
