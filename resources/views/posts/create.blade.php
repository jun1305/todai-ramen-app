<x-app-layout title="記録する">
    @push('scripts')
    <script src="https://maps.googleapis.com/maps/api/js?key={{
            env('GOOGLE_MAPS_API_KEY')
        }}&libraries=places&language=ja"></script>
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
            <div class="space-y-2">
                <label class="block text-sm font-bold text-gray-700">
                    ラーメンの写真
                </label>
                <div class="relative">
                    <input
                        type="file"
                        name="image"
                        id="image"
                        accept="image/*"
                        class="block w-full text-sm text-gray-500 file:mr-4 file:py-3 file:px-6 file:rounded-full file:border-0 file:text-sm file:font-bold file:bg-orange-50 file:text-orange-700 hover:file:bg-orange-100 cursor-pointer"
                    />
                </div>
            </div>

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
                    placeholder="店名を入力（候補が出ます）"
                    class="w-full text-lg p-4 bg-gray-50 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-orange-500 focus:bg-white transition"
                    required
                    autocomplete="off"
                    @keydown.enter.prevent
                />
                <p class="text-xs text-gray-400">
                    ※Googleマップの候補から選んでください
                </p>
            </div>

            @push('scripts')
            <script src="https://maps.googleapis.com/maps/api/js?key={{
                    env('GOOGLE_MAPS_API_KEY')
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
                                        fields: ["name"],
                                    }
                                );

                            autocomplete.addListener("place_changed", () => {
                                const place = autocomplete.getPlace();
                                console.log("Googleから届いたデータ:", place); // 確認用

                                if (place.name) {
                                    // ★ここで「住所カット手術」を行う
                                    const simpleName = this.cleanName(
                                        place.name
                                    );

                                    console.log("整形後の名前:", simpleName); // 確認用

                                    setTimeout(() => {
                                        this.$refs.input.value = simpleName;
                                        this.$refs.input.dispatchEvent(
                                            new Event("input")
                                        );
                                    }, 100);
                                }
                            });
                        },

                        // ★名前をきれいにする関数（強力版）
                        cleanName(fullName) {
                            let name = fullName;

                            // 1. "日本、" を削除
                            name = name.replace(/^日本、\s*/, "");

                            // 2. "〒xxx-xxxx" を削除
                            name = name.replace(/〒\d{3}-\d{4}\s*/, "");

                            // 3. 【ここが修正ポイント】 住所部分をごっそり削除
                            // 「何か文字」+「全角半角の数字」+「その後のごちゃごちゃ(丁目とかハイフン)」+「スペース」
                            // というパターンを見つけて、そこまでを全部消します。
                            name = name.replace(/^.+?[0-9０-９]+.*?\s+/, "");

                            return name;
                        },
                    };
                }
            </script>

            <div x-data="{ 
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
                    if (this.step >= 1) {
                        this.score = Math.round(newVal);
                    } else {
                        this.score = parseFloat(newVal.toFixed(1)); 
                    }
                },

                // 手入力時のチェック
                validate() {
                    if (this.score === '') return;
                    let val = parseFloat(this.score);
                    if (val > 100) this.score = 100;
                    if (val < 0) this.score = 0;
                }
            }">
                <label class="block text-sm font-bold text-gray-700 mb-2">
                    点数 <span class="text-red-500">*</span>
                </label>

                <div class="bg-gray-50 p-4 rounded-xl border-2 border-gray-100">
                    
                    {{-- ① プリセットボタン --}}
                    <div class="flex justify-between gap-1 mb-6">
                        @foreach([80, 85, 90, 95, 100] as $preset)
                        <button type="button" 
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
                        <button type="button" @click="changeScore(-step)" class="w-12 h-12 rounded-full bg-white border-2 border-gray-200 text-gray-400 hover:text-orange-500 hover:border-orange-200 font-bold text-2xl shadow-sm active:scale-95 transition flex items-center justify-center">
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
                                {{-- ▼▼▼ 追加: e E + - を入力できないようにブロックする ▼▼▼ --}}
                                @keydown="['e', 'E', '+', '-'].includes($event.key) && $event.preventDefault()"
                                {{-- ▲▲▲ ここまで ▲▲▲ --}}
                                min="0"
                                max="100"
                                :step="step"
                                class="w-full text-center text-5xl font-black text-gray-800 bg-transparent focus:outline-none p-1"
                            />
                            <span class="absolute top-2 right-0 text-xs text-gray-400 font-bold pointer-events-none">点</span>
                        </div>

                        {{-- プラス --}}
                        <button type="button" @click="changeScore(step)" class="w-12 h-12 rounded-full bg-white border-2 border-gray-200 text-gray-400 hover:text-orange-500 hover:border-orange-200 font-bold text-2xl shadow-sm active:scale-95 transition flex items-center justify-center">
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
                        >
                    </div>

                    {{-- ④ 増減幅（Step）切り替えスイッチ --}}
                    <div class="flex justify-center items-center gap-2">
                        <span class="text-[10px] font-bold text-gray-400">増減:</span>
                        <div class="flex bg-white rounded-lg p-1 border border-gray-200 shadow-sm">
                            @foreach([0.1, 0.5, 1] as $s)
                            <button type="button" 
                                @click="step = {{ $s }}"
                                class="px-3 py-1 text-xs font-bold rounded transition"
                                :class="step === {{ $s }} ? 'bg-orange-100 text-orange-600' : 'text-gray-400 hover:bg-gray-50'"
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
