<x-app-layout title="記録する">
    @push('scripts')
    <script src="https://maps.googleapis.com/maps/api/js?key={{config('services.google_maps.key') }}&libraries=places&language=ja"></script>
    @endpush

    <div class="p-4">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">
            🍜 ラーメン記録
        </h2>

        @if ($errors->any())
        <div
            class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 text-sm"
        >
            <ul>
                @foreach ($errors->all() as $error)
                <li>・{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form
            action="{{ route('posts.store') }}"
            method="POST"
            class="space-y-8"
            enctype="multipart/form-data"
        >
            @csrf
                
            {{-- ▼▼▼ 修正: 画像プレビュー機能 ▼▼▼ --}}
            <div class="space-y-2" x-data="{ imagePreview: null }">
                <label class="block text-sm font-bold text-gray-700">
                    ラーメンの写真 <span class="text-red-500">*</span>
                </label>
                
                {{-- クリック領域全体をラベルにしてしまうのが一番確実 --}}
                <label for="image" 
                       class="relative block w-full bg-gray-100 rounded-xl border-2 border-dashed border-gray-300 overflow-hidden hover:bg-gray-50 transition cursor-pointer group"
                       :class="!imagePreview ? 'aspect-video' : ''">
                    
                    {{-- 画像がある場合：プレビュー表示 --}}
                    <template x-if="imagePreview">
                        <img :src="imagePreview" class="w-full h-auto max-h-[500px] object-contain mx-auto">
                    </template>

                    {{-- 画像がない場合：アップロード案内 --}}
                    <template x-if="!imagePreview">
                        <div class="absolute inset-0 flex flex-col items-center justify-center text-gray-400 group-hover:text-orange-500 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span class="text-xs font-bold">写真をタップして選択</span>
                        </div>
                    </template>
                    
                    {{-- 画像選択後の変更案内 --}}
                    <template x-if="imagePreview">
                        <div class="absolute bottom-2 right-2 bg-black/50 text-white text-[10px] px-2 py-1 rounded-full pointer-events-none">
                            タップして変更
                        </div>
                    </template>

                    {{-- 実際のファイル入力（ラベルの中に入れて隠す） --}}
                    <input
                        type="file"
                        name="image"
                        id="image"
                        accept="image/*"
                        required
                        class="sr-only" {{-- hidden ではなく sr-only 推奨 --}}
                        @change="
                            const file = $event.target.files[0];
                            if (file) {
                                const reader = new FileReader();
                                reader.onload = (e) => { imagePreview = e.target.result };
                                reader.readAsDataURL(file);
                            }
                        "
                    />
                </label>
            </div>
            {{-- ▲▲▲ 修正ここまで ▲▲▲ --}}

            <div class="space-y-2" x-data="googleAutocomplete()">
                <label
                    for="shop_name"
                    class="block text-sm font-bold text-gray-700"
                >
                    店名 <span class="text-red-500">*</span>
                </label>

                <input
                    type="text"
                    name="shop_name"
                    id="shop_name"
                    x-ref="input"
                    value="{{ old('shop_name', request('shop_name')) }}"
                    placeholder="店名を入力（候補が出ます）"
                    class="w-full text-lg p-4 bg-gray-50 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-orange-500 focus:bg-white transition"
                    required
                    autocomplete="off"
                    @keydown.enter.prevent
                />
                <input type="hidden" name="google_place_id" x-ref="placeId" />
                <input type="hidden" name="address" x-ref="address" />
                <p class="text-xs text-gray-400">
                    ※Googleマップの候補から選んでください
                </p>
            </div>

            @push('scripts')
            <script src="https://maps.googleapis.com/maps/api/js?key={{
                    config('services.google_maps.key')
                }}&libraries=places&language=ja&callback=Function.prototype"></script>
            @endpush

            <script>
                function googleAutocomplete() {
                    return {
                        init() {
                            if (typeof google === "undefined") return;

                            const autocomplete =
                                new google.maps.places.Autocomplete(
                                    this.$refs.input,
                                    {
                                        types: ["establishment"],
                                        componentRestrictions: {
                                            country: "jp",
                                        },
                                        // ★ fields に formatted_address と place_id を追加！
                                        fields: [
                                            "name",
                                            "formatted_address",
                                            "place_id",
                                        ],
                                    }
                                );

                            autocomplete.addListener("place_changed", () => {
                                const place = autocomplete.getPlace();

                                if (place.name) {
                                    // 1. 隠し項目にIDと住所をセット（追加部分）
                                    if (this.$refs.placeId) {
                                        this.$refs.placeId.value =
                                            place.place_id || "";
                                    }
                                    if (
                                        this.$refs.address &&
                                        place.formatted_address
                                    ) {
                                        // 住所のクリーニングをしてセット
                                        this.$refs.address.value =
                                            this.cleanAddress(
                                                place.formatted_address
                                            );
                                    }

                                    // 2. 店名入力欄の見た目を整える（既存の処理）
                                    const simpleName = this.cleanName(
                                        place.name
                                    );
                                    setTimeout(() => {
                                        this.$refs.input.value = simpleName;
                                        this.$refs.input.dispatchEvent(
                                            new Event("input")
                                        );
                                    }, 100);
                                }
                            });
                        },

                        // 店名整形（既存）
                        cleanName(fullName) {
                            // ... (中身は今のままでOK) ...
                            return fullName
                                .replace(/^日本、\s*/, "")
                                .replace(/〒\d{3}-\d{4}\s*/, "")
                                .replace(/^.+?[0-9０-９]+.*?\s+/, "");
                        },

                        // ★追加：住所整形関数（Dailyと同じロジック）
                        cleanAddress(address) {
                            let clean = address;
                            clean = clean.replace(/^日本、\s*/, "");
                            clean = clean.replace(/〒\d{3}-\d{4}\s*/, "");
                            return clean.trim();
                        },
                    };
                }
            </script>

            {{-- ▼▼▼ 追加: 3. ジャンル選択 ▼▼▼ --}}
            <div class="space-y-2">
                <label class="block text-sm font-bold text-gray-700">
                    ジャンル <span class="text-gray-400 font-normal text-xs">(複数選択可)</span>
                </label>
                
                <div class="flex flex-wrap gap-2">
                    @foreach($genres as $genre)
                        <label class="cursor-pointer">
                            <input type="checkbox" name="genres[]" value="{{ $genre->id }}" class="peer sr-only"
                                   @if(in_array($genre->id, old('genres', []))) checked @endif>
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
            {{-- ▲▲▲ 追加ここまで ▲▲▲ --}}

            <div
                x-data="{ 
                score: {{ old('score', $post->score ?? 85.0) }},
                step: 0.1, // 初期値
                
                // 数値を変更するロジック
                changeScore(amount) {
                    let current = parseFloat(this.score);
                    if (isNaN(current)) current = 0;
                    let newVal = current + parseFloat(amount);
                    if (newVal > 100) newVal = 100;
                    if (newVal < 0) newVal = 0;
                    // 桁数調整
                    this.score = parseFloat(newVal.toFixed(1)); 
                    
                },

                // 手入力時のチェック
                validate() {
                    if (this.score === '') return;
                    let val = parseFloat(this.score);
                    if (val > 100) this.score = 100;
                    if (val < 0) this.score = 0;
                }
            }"
            >
                <label class="block text-sm font-bold text-gray-700 mb-2">
                    点数 <span class="text-red-500">*</span>
                </label>

                <div class="bg-gray-50 p-4 rounded-xl border-2 border-gray-100">
                    {{-- ① プリセットボタン --}}
                    <div class="flex justify-between gap-1 mb-6">
                        @foreach([80, 85, 90, 95, 100] as $preset)
                        <button
                            type="button"
                            @click="score = {{ $preset }}"
                            class="flex-1 py-2 text-sm font-bold rounded-lg border transition shadow-sm"
                            :class="parseFloat(score) === {{ $preset }} 
                                ? 'bg-orange-500 text-white border-orange-500 shadow-orange-200' 
                                : 'bg-white text-gray-500 border-gray-200 hover:bg-orange-50 hover:text-orange-600'"
                        >
                            {{ $preset }}
                        </button>
                        @endforeach
                    </div>

                    {{-- ② メイン入力エリア（数値 ＆ ±ボタン） --}}
                    <div class="flex items-center justify-center gap-4 mb-2">
                        {{-- マイナス --}}
                        <button
                            type="button"
                            @click="changeScore(-step)"
                            class="w-12 h-12 rounded-full bg-white border-2 border-gray-200 text-gray-400 hover:text-orange-500 hover:border-orange-200 font-bold text-2xl shadow-sm active:scale-95 transition flex items-center justify-center"
                        >
                            -
                        </button>

                        {{-- 数値表示 --}}
                        <div class="relative w-32">
                            <input
                                type="number"
                                name="score"
                                x-model="score"
                                @input="validate()"
                                @blur="if(score === '') score = 0"
                                {{--
                                ▼▼▼
                                追加:
                                e
                                E
                                +
                                -
                                を入力できないようにブロックする
                                ▼▼▼
                                --}}
                                @keydown="['e', 'E', '+', '-'].includes($event.key) && $event.preventDefault()"
                                {{--
                                ▲▲▲
                                ここまで
                                ▲▲▲
                                --}}
                                min="0"
                                max="100"
                                :step="step"
                                class="w-full text-center text-5xl font-black text-gray-800 bg-transparent focus:outline-none p-1"
                            />
                            <span
                                class="absolute top-2 right-0 text-xs text-gray-400 font-bold pointer-events-none"
                                >点</span
                            >
                        </div>

                        {{-- プラス --}}
                        <button
                            type="button"
                            @click="changeScore(step)"
                            class="w-12 h-12 rounded-full bg-white border-2 border-gray-200 text-gray-400 hover:text-orange-500 hover:border-orange-200 font-bold text-2xl shadow-sm active:scale-95 transition flex items-center justify-center"
                        >
                            +
                        </button>
                    </div>

                    {{-- ③ スライダー（復活！） --}}
                    <div class="px-2 mb-4">
                        <input
                            type="range"
                            x-model="score"
                            min="0"
                            max="100"
                            :step="step"
                            class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-orange-500"
                        />
                    </div>

                    {{-- ④ 増減幅（Step）切り替えスイッチ --}}
                    <div class="flex justify-center items-center gap-2">
                        <span class="text-[10px] font-bold text-gray-400"
                            >増減:</span
                        >
                        <div
                            class="flex bg-white rounded-lg p-1 border border-gray-200 shadow-sm"
                        >
                            @foreach([0.1, 0.5, 1] as $s)
                            <button
                                type="button"
                                @click="step = {{ $s }}"
                                class="px-3 py-1 text-xs font-bold rounded transition"
                                :class="step === {{
                                    $s
                                }} ? 'bg-orange-100 text-orange-600' : 'text-gray-400 hover:bg-gray-50'"
                            >
                                {{ $s }}
                            </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <label
                    for="comment"
                    class="block text-sm font-bold text-gray-700 mb-1"
                >
                    感想・コール
                </label>
                <textarea
                    name="comment"
                    id="comment"
                    rows="3"
                    placeholder="ニンニクアブラマシマシ。神豚だった。"
                    class="w-full p-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-200 transition"
                ></textarea>
            </div>

            <div class="h-8"></div>

            <button
                type="submit"
                class="w-full bg-orange-600 text-white font-bold text-lg py-4 rounded-full shadow-lg hover:bg-orange-700 transform active:scale-95 transition"
            >
                記録をつける！ 🔥
            </button>
        </form>
    </div>
</x-app-layout>
