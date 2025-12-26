<x-app-layout title="記録を編集">
    @push('scripts')
    {{-- Google Maps API スクリプト --}}
    <script src="https://maps.googleapis.com/maps/api/js?key={{
            config('services.google_maps.key') 
        }}&libraries=places&language=ja&callback=Function.prototype"></script>
    @endpush

    <div class="p-4">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">
            ✏️ 記録を編集
        </h2>

        <form action="{{ route('daily.update', $daily->id) }}" method="POST" enctype="multipart/form-data" class="space-y-8">
            @csrf
            @method('PUT') {{-- PUTメソッドを指定 --}}

            {{-- 画像プレビュー（初期値に現在の画像を入れる） --}}
            <div class="space-y-2" x-data="{ imagePreview: '{{ asset($daily->image_path) }}' }">
                <label class="block text-sm font-bold text-gray-700">
                    ラーメンの写真 <span class="text-gray-400 text-xs">（変更する場合のみ選択）</span>
                </label>
                
                <div class="relative w-full bg-gray-100 rounded-xl border-2 border-dashed border-gray-300 overflow-hidden hover:bg-gray-50 transition cursor-pointer group"
                     @click="document.getElementById('image').click()">
                    
                    {{-- プレビュー表示 --}}
                    <template x-if="imagePreview">
                        <img :src="imagePreview" class="w-full h-auto max-h-[500px] object-contain mx-auto">
                    </template>
                    
                    <div class="absolute bottom-2 right-2 bg-black/50 text-white text-[10px] px-2 py-1 rounded-full pointer-events-none">
                        タップして変更
                    </div>
                </div>

                <input type="file" name="image" id="image" accept="image/*" class="hidden"
                    @change="
                        const file = $event.target.files[0];
                        if (file) {
                            const reader = new FileReader();
                            reader.onload = (e) => { imagePreview = e.target.result };
                            reader.readAsDataURL(file);
                        }
                    "
                />
            </div>

            {{-- 店名（オートコンプリート付き） --}}
            <div class="space-y-2" x-data="googleAutocomplete()">
                <label class="block text-sm font-bold text-gray-700">店名 *</label>
                <input type="text" name="shop_name" x-ref="input"
                    value="{{ old('shop_name', $daily->shop_name) }}" {{-- 初期値セット --}}
                    class="w-full text-lg p-4 bg-gray-50 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-orange-500 focus:bg-white transition"
                    required>
                <input type="hidden" name="google_place_id" x-ref="placeId" value="{{ $daily->shop->google_place_id ?? '' }}">
                <input type="hidden" name="address" x-ref="address" value="{{ $daily->shop->address ?? '' }}">
            </div>

            {{-- メニュー名 --}}
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">メニュー名</label>
                <input type="text" name="menu_name" 
                       value="{{ old('menu_name', $daily->menu_name) }}"
                       class="w-full p-3 border rounded-lg bg-gray-50" placeholder="例：特製醤油ラーメン">
            </div>

            {{-- 日付 --}}
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">食べた日 *</label>
                <input type="date" name="eaten_at" 
                       value="{{ old('eaten_at', $daily->eaten_at->format('Y-m-d')) }}"
                       class="w-full p-3 border rounded-lg bg-gray-50 font-bold" required>
            </div>

            {{-- コメント --}}
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">会長のレビュー</label>
                <textarea name="comment" rows="5" class="w-full p-3 border rounded-lg bg-gray-50">{{ old('comment', $daily->comment) }}</textarea>
            </div>

            <button type="submit" class="w-full bg-orange-600 text-white font-bold text-lg py-4 rounded-full shadow-lg hover:bg-orange-700 transition">
                更新する
            </button>
        </form>
    </div>
    <script>
        function googleAutocomplete() {
            return {
                init() {
                    // Google APIがロードされるのを待つ（簡易的なリトライ）
                    const checkGoogle = setInterval(() => {
                        if (typeof google !== "undefined" && google.maps && google.maps.places) {
                            clearInterval(checkGoogle);
                            this.setupAutocomplete();
                        }
                    }, 100);
                },
                setupAutocomplete() {
                    const autocomplete = new google.maps.places.Autocomplete(
                        this.$refs.input,
                        {
                            types: ["establishment"],
                            componentRestrictions: { country: "jp" },
                            fields: ["name", "formatted_address", "place_id"],
                        }
                    );

                    autocomplete.addListener("place_changed", () => {
                        const place = autocomplete.getPlace();

                        if (place.name) {
                            // 隠しフィールドに値をセット
                            if (this.$refs.placeId) this.$refs.placeId.value = place.place_id || "";
                            
                            if (this.$refs.address) {
                                let clean = place.formatted_address || "";
                                clean = clean.replace(/^日本、\s*/, "").replace(/〒\d{3}-\d{4}\s*/, "").trim();
                                this.$refs.address.value = clean;
                            }

                            // 店名の見た目を整える
                            const simpleName = place.name
                                .replace(/^日本、\s*/, "")
                                .replace(/〒\d{3}-\d{4}\s*/, "")
                                .replace(/^.+?[0-9０-９]+.*?\s+/, ""); // 郵便番号などを消す

                            // 入力欄に反映させる（少し待ってから）
                            setTimeout(() => {
                                this.$refs.input.value = simpleName;
                                this.$refs.input.dispatchEvent(new Event("input"));
                            }, 100);
                        }
                    });
                }
            };
        }
    </script>
</x-app-layout>