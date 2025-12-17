<x-app-layout title="一杯を記録する">
    <div class="max-w-lg mx-auto px-4 py-8">
        
        <div class="bg-white rounded-3xl shadow-xl overflow-hidden">
            {{-- ▼▼▼ relative を追加 ▼▼▼ --}}
            <div class="bg-slate-900 p-6 text-center relative">
                
                {{-- ▼▼▼ 戻るボタンを追加 ▼▼▼ --}}
                <a href="{{ route('daily.index') }}" class="absolute left-6 top-1/2 -translate-y-1/2 text-slate-500 hover:text-white transition p-2 hover:bg-slate-800 rounded-full">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                {{-- ▲▲▲ 追加ここまで ▲▲▲ --}}

                <h1 class="text-white font-black text-xl tracking-wider">NEW RAMEN LOG</h1>
                <p class="text-orange-400 text-xs font-bold mt-1">今日の一杯を記録</p>
            </div>

            <form action="{{ route('daily.store') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
                @csrf

                {{-- ▼▼▼ 写真アップロード（プレビュー付き） ▼▼▼ --}}
                <div x-data="{ imagePreview: null }" class="text-center">
                    <div class="relative w-full aspect-square bg-gray-100 rounded-2xl border-2 border-dashed border-gray-300 overflow-hidden hover:bg-gray-50 transition cursor-pointer"
                         @click="document.getElementById('imageInput').click()">
                        
                        {{-- プレビュー表示 --}}
                        <img x-show="imagePreview" :src="imagePreview" class="w-full h-full object-cover absolute inset-0 z-10">
                        
                        {{-- 未選択時の表示 --}}
                        <div x-show="!imagePreview" class="absolute inset-0 flex flex-col items-center justify-center text-gray-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span class="text-xs font-bold">写真をタップして選択</span>
                        </div>
                    </div>
                    <input type="file" id="imageInput" name="image" class="hidden" accept="image/*"
                           @change="const file = $event.target.files[0]; 
                                    if(file){ 
                                        const reader = new FileReader(); 
                                        reader.onload = (e) => imagePreview = e.target.result; 
                                        reader.readAsDataURL(file); 
                                    }">
                    @error('image') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- ▼▼▼ Googleマップ店名検索 ▼▼▼ --}}
                <div class="relative">
                    <label class="block text-xs font-bold text-gray-500 mb-1">お店の名前 (Google検索)</label>
                    <div class="relative">
                        <span class="absolute left-3 top-3 text-gray-400">📍</span>
                        <input type="text" id="shop_search" name="shop_name" 
                               class="w-full pl-9 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl font-bold focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition" 
                               placeholder="店名を入力して検索..." autocomplete="off">
                        
                        {{-- 隠しフィールド：Google Place IDが入る --}}
                        <input type="hidden" id="google_place_id" name="google_place_id">
                        <input type="hidden" id="address" name="address">
                    </div>
                    @error('shop_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- メニュー名 --}}
                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-1">食べたメニュー</label>
                    <input type="text" name="menu_name" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl font-bold focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition" placeholder="例：特製中華そば">
                </div>

                {{-- 食べた日 --}}
                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-1">食べた日</label>
                    <input type="date" name="eaten_at" value="{{ date('Y-m-d') }}" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl font-bold focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition">
                </div>

                {{-- コメント --}}
                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-1">会長の一言メモ</label>
                    <textarea name="comment" rows="3" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl font-bold focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition" placeholder="スープの深みが..."></textarea>
                </div>

                <button type="submit" class="w-full bg-gradient-to-r from-slate-800 to-slate-900 text-white font-black py-4 rounded-xl shadow-lg hover:scale-[1.02] active:scale-95 transition flex items-center justify-center gap-2">
                    <span>💾</span> 記録に残す
                </button>
            </form>
        </div>
    </div>

    {{-- Google Maps API --}}
    {{-- ※ APIキーは環境変数等で管理することを推奨します --}}
    <script src="https://maps.googleapis.com/maps/api/js?key={{
            env('GOOGLE_MAPS_API_KEY')
        }}&libraries=places&language=ja"></script>

    <script>
        function initAutocomplete() {
            // 入力欄の取得
            const input = document.getElementById('shop_search');       // 店名入力欄（表示用）
            const placeIdInput = document.getElementById('google_place_id'); // Place ID（隠し）
            const addressInput = document.getElementById('address');    // ★追加：住所（隠し）

            if (!input || typeof google === 'undefined') return;

            // オートコンプリート設定
            const autocomplete = new google.maps.places.Autocomplete(input, {
                types: ['establishment'],
                componentRestrictions: { country: 'jp' },
                // ★ 'formatted_address' を追加しないと住所が取れません！
                fields: ['place_id', 'name', 'formatted_address'] 
            });

            // ★ 候補選択時の処理
            autocomplete.addListener('place_changed', function() {
                const place = autocomplete.getPlace();
                
                if (!place.place_id || !place.name) {
                    return;
                }

                // 1. Place ID セット
                placeIdInput.value = place.place_id;

                // 2. 店名をセット（Googleが勝手に書き換えるのを防ぐため遅延セット）
                // 店名はそのまま使うのが一番安全です
                setTimeout(() => {
                    input.value = place.name; 
                }, 50);

                // 3. ★ 住所を取得して整形し、隠し項目にセット
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

        // ★ 住所整形関数（「日本、」や郵便番号をカット）
        function cleanAddress(address) {
            let clean = address;
            // 1. "日本、" 削除
            clean = clean.replace(/^日本、\s*/, "");
            // 2. "〒xxx-xxxx" 削除
            clean = clean.replace(/〒\d{3}-\d{4}\s*/, "");
            
            return clean.trim();
        }

        window.onload = initAutocomplete;
    </script>
</x-app-layout>