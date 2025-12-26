<x-app-layout title="お店情報を編集">
    <div class="max-w-xl mx-auto p-6 pb-20">
        
        <div class="flex items-center gap-2 mb-6">
            <div class="bg-orange-100 p-2 rounded-lg text-orange-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
            </div>
            <h2 class="text-xl font-black text-gray-800">お店情報を編集</h2>
        </div>

        <form action="{{ route('shops.update', $shop) }}" method="POST" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-6">
            @csrf
            @method('PUT')

            {{-- 店名 (ID: shop_search を追加してオートコンプリート対象に) --}}
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">店名</label>
                <input type="text" id="shop_search" name="name" value="{{ old('name', $shop->name) }}" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl font-bold focus:outline-none focus:border-orange-500 transition" required placeholder="お店を検索して入力...">
            </div>

            {{-- 住所 (ID: address を追加) --}}
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">住所</label>
                <input type="text" id="address" name="address" value="{{ old('address', $shop->address) }}" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-orange-500 transition">
            </div>

            {{-- Google Place ID (ID: google_place_id を追加) --}}
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Google Place ID</label>
                <input type="text" id="google_place_id" name="google_place_id" value="{{ old('google_place_id', $shop->google_place_id) }}" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl text-sm font-mono text-gray-500 focus:outline-none focus:border-orange-500 transition">
            </div>

            {{-- ジャンル選択 --}}
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">ジャンル（複数選択可）</label>
                <div class="flex flex-wrap gap-2">
                    @foreach($genres as $genre)
                        <label class="cursor-pointer">
                            <input type="checkbox" name="genres[]" value="{{ $genre->id }}" class="peer sr-only"
                                   @if($shop->genres->contains($genre->id)) checked @endif
                            >
                            <div class="px-3 py-1.5 rounded-full text-xs font-bold border transition-all duration-200 select-none
                                        bg-white text-gray-500 border-gray-200
                                        peer-checked:bg-orange-500 peer-checked:text-white peer-checked:border-orange-500 peer-checked:shadow-md
                                        hover:bg-gray-50">
                                {{ $genre->name }}
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- ボタン --}}
            <div class="flex gap-3 pt-4">
                <a href="{{ route('shops.show', $shop) }}" class="flex-1 bg-gray-100 text-gray-600 py-3 rounded-xl font-bold text-center hover:bg-gray-200 transition">キャンセル</a>
                <button type="submit" class="flex-[2] bg-orange-500 text-white py-3 rounded-xl font-bold hover:bg-orange-600 transition shadow-lg shadow-orange-200">
                    更新する
                </button>
            </div>
        </form>
    </div>

    {{-- ▼▼▼ Google Maps API & Autocomplete Script ▼▼▼ --}}
    @push('scripts')
        <script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.key') }}&libraries=places&language=ja"></script>

        <script>
            function initAutocomplete() {
                // 入力欄の取得
                const input = document.getElementById('shop_search');       // 店名入力欄
                const placeIdInput = document.getElementById('google_place_id'); // Place ID
                const addressInput = document.getElementById('address');    // 住所

                if (!input || typeof google === 'undefined') return;

                // オートコンプリート設定
                const autocomplete = new google.maps.places.Autocomplete(input, {
                    types: ['establishment'], // 施設のみ検索
                    componentRestrictions: { country: 'jp' }, // 日本国内限定
                    fields: ['place_id', 'name', 'formatted_address'] // 必要な情報を指定
                });

                // 候補選択時の処理
                autocomplete.addListener('place_changed', function() {
                    const place = autocomplete.getPlace();
                    
                    if (!place.place_id || !place.name) {
                        return;
                    }

                    // 1. Place ID セット
                    if (placeIdInput) placeIdInput.value = place.place_id;

                    // 2. 店名をセット
                    setTimeout(() => {
                        input.value = place.name; 
                    }, 50);

                    // 3. 住所を取得して整形しセット
                    if (place.formatted_address && addressInput) {
                        const cleanAddr = cleanAddress(place.formatted_address);
                        addressInput.value = cleanAddr;
                    }
                });

                // Enterキー誤送信防止
                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') e.preventDefault();
                });
            }

            // 住所整形関数
            function cleanAddress(address) {
                let clean = address;
                clean = clean.replace(/^日本、\s*/, "");
                clean = clean.replace(/〒\d{3}-\d{4}\s*/, "");
                return clean.trim();
            }

            // ページ読み込み完了時に実行
            // (defer属性がついている場合やSPA遷移を考慮して addEventListener を使用)
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initAutocomplete);
            } else {
                initAutocomplete();
            }
        </script>
    @endpush
</x-app-layout>