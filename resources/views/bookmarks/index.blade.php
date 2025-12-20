<x-app-layout title="行きたいお店">
    <div class="px-4 py-6">
        <div class="flex items-center gap-3 mb-6">
            <span class="text-3xl">🔖</span>
            <h1 class="font-black text-2xl text-gray-800">行きたいお店リスト</h1>
        </div>

        @if($shops->isEmpty())
            <div class="text-center py-20 bg-white rounded-3xl border border-gray-100 shadow-sm">
                <div class="text-6xl mb-4">🍜</div>
                <p class="text-gray-500 font-bold mb-2">まだ登録がありません</p>
                <p class="text-xs text-gray-400 mb-6">気になるお店を見つけて<br>ブックマークしてみましょう！</p>
                <a href="{{ route('shops.index') }}" class="inline-block bg-orange-500 text-white font-bold py-3 px-6 rounded-full shadow-md hover:bg-orange-600 transition">
                    お店を探す
                </a>
            </div>
        @else
            <div class="grid grid-cols-1 gap-4">
                @foreach($shops as $shop)
                    <a href="{{ route('shops.show', $shop) }}" class="flex items-center gap-4 p-4 bg-white rounded-2xl shadow-sm border border-gray-100 active:scale-[0.98] transition">
                        {{-- アイコン --}}
                        <div class="h-16 w-16 rounded-xl bg-gray-100 overflow-hidden shrink-0 border border-gray-50">
                            @if($shop->latestPost && $shop->latestPost->image_path)
                                <img src="{{ asset($shop->latestPost->image_path) }}" class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex items-center justify-center bg-orange-50 text-orange-400 font-bold text-xl">
                                    {{ mb_substr($shop->name, 0, 1) }}
                                </div>
                            @endif
                        </div>

                        <div class="flex-1 min-w-0">
                            <h3 class="font-bold text-gray-800 truncate mb-1">{{ $shop->name }}</h3>
                            <p class="text-xs text-gray-400 truncate mb-2">📍 {{ $shop->short_address ?? '住所未登録' }}</p>
                            
                            @if($shop->posts_avg_score)
                                <div class="flex items-baseline gap-1 text-orange-500 leading-none">
                                    <span class="text-lg font-black">{{ number_format($shop->posts_avg_score, 1) }}</span>
                                    <span class="text-[10px] font-bold">点</span>
                                </div>
                            @else
                                <span class="text-[10px] text-gray-300 bg-gray-100 px-1.5 py-0.5 rounded">まだ記録なし</span>
                            @endif
                        </div>

                        {{-- 矢印 --}}
                        <div class="text-gray-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="mt-8">
                {{ $shops->links('vendor.pagination.ramen') }}
            </div>
        @endif
    </div>
</x-app-layout>