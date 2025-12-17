<x-app-layout title="ラリー作成">
    {{-- Google Maps API --}}
    @push('scripts')
    <script src="https://maps.googleapis.com/maps/api/js?key={{
            env('GOOGLE_MAPS_API_KEY')
        }}&libraries=places&language=ja&callback=Function.prototype"></script>
    @endpush

    <div class="max-w-xl mx-auto p-6 pb-24">
        {{-- ヘッダー --}}
        <div class="text-center mb-8 relative">
            {{-- relativeを追加 --}}

            {{-- ▼▼▼ 追加: 戻るボタン（絶対配置で左上に） ▼▼▼ --}}
            <a
                href="{{ route('rallies.index') }}"
                class="absolute left-0 top-0 text-gray-400 hover:text-gray-600 bg-gray-50 hover:bg-gray-100 p-2 rounded-full transition"
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
                        d="M15 19l-7-7 7-7"
                    />
                </svg>
            </a>

            <span class="text-4xl block mb-2">🚩</span>
            <h1 class="text-2xl font-black text-gray-800">ラリーを作る</h1>
            <p class="text-xs text-gray-500 font-bold mt-2">
                あなたのオススメやテーマに沿った<br />
                最強の5店舗を選んでください！
            </p>
        </div>

        <form
            action="{{ route('rallies.store') }}"
            method="POST"
            class="space-y-8"
        >
            @csrf

            {{-- 1. ラリーの設定 --}}
            <div
                class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100"
            >
                <h2
                    class="font-bold text-gray-800 mb-4 flex items-center gap-2"
                >
                    <span
                        class="bg-slate-900 text-white text-[10px] px-2 py-1 rounded"
                        >STEP 1</span
                    >
                    基本情報
                </h2>

                <div class="space-y-4">
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-500 mb-1"
                            >ラリーのタイトル</label
                        >
                        <input
                            type="text"
                            name="title"
                            value="{{ old('title') }}"
                            class="w-full bg-gray-50 border-2 border-gray-100 rounded-xl px-4 py-3 font-bold focus:outline-none focus:border-slate-900 transition"
                            placeholder="例：最強家系５選"
                            required
                        />
                    </div>

                    <div>
                        <label
                            class="block text-xs font-bold text-gray-500 mb-1"
                            >説明文（意気込みなど）</label
                        >
                        <textarea
                            name="description"
                            rows="3"
                            class="w-full bg-gray-50 border-2 border-gray-100 rounded-xl px-4 py-3 font-medium focus:outline-none focus:border-slate-900 transition"
                            placeholder="このラリーのテーマや魅力を書いてね"
                            >{{ old("description") }}</textarea
                        >
                    </div>
                </div>
            </div>

            {{-- 2. お店の選択 --}}
            <div
                class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100"
            >
                <h2
                    class="font-bold text-gray-800 mb-4 flex items-center gap-2"
                >
                    <span
                        class="bg-slate-900 text-white text-[10px] px-2 py-1 rounded"
                        >STEP 2</span
                    >
                    お店を選ぶ (最大5軒)
                </h2>

                <div class="space-y-3">
                    @for($i = 1; $i <= 5; $i++)
                    <div x-data="googleAutocomplete()" class="relative">
                        <div
                            class="absolute left-3 top-3.5 text-gray-400 font-bold text-xs select-none"
                        >
                            {{ $i }}.
                        </div>

                        {{-- 1. 店名入力欄 --}}
                        {{-- name属性を shops[1][name] のような多次元配列の形にする --}}
                        <input type="text" name="shops[{{ $i }}][name]"
                        x-ref="input" class="w-full bg-gray-50 border-2
                        border-gray-100 rounded-xl pl-8 pr-4 py-3 font-bold
                        focus:outline-none focus:border-orange-500
                        focus:bg-white transition placeholder-gray-300"
                        placeholder="店名を入力（候補が出ます）"
                        {{-- 最初のお店だけ必須にする --}}
                        @if($i == 1) required @endif autocomplete="off"
                        @keydown.enter.prevent>

                        {{-- 2. 隠しフィールド（Google Place ID） --}}
                        {{-- JSが x-ref="placeId" を探して値をセットしてくれます --}}
                        <input
                            type="hidden"
                            name="shops[{{ $i }}][google_place_id]"
                            x-ref="placeId"
                        />

                        {{-- 3. 隠しフィールド（住所） --}}
                        {{-- JSが x-ref="address" を探して値をセットしてくれます --}}
                        <input
                            type="hidden"
                            name="shops[{{ $i }}][address]"
                            x-ref="address"
                        />
                    </div>
                    @endfor
                </div>
                <p class="text-xs text-gray-400 mt-2 ml-1">
                    ※空欄のままでもOK（最低1軒あれば作れます）
                </p>
            </div>

            {{-- 送信ボタン --}}
            <button
                type="submit"
                class="w-full bg-slate-900 text-white font-black py-4 rounded-xl shadow-lg hover:bg-slate-800 hover:scale-[1.02] active:scale-95 transition flex items-center justify-center gap-2"
            >
                <span>🚀</span> ラリーを公開する
            </button>
        </form>
    </div>

    {{-- オートコンプリートJS（投稿画面と同じロジック） --}}
    <script>
        function googleAutocomplete() {
            return {
                init() {
                    if (typeof google === "undefined") return;

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
                            if (this.$refs.placeId) {
                                this.$refs.placeId.value = place.place_id || "";
                            }
                            if (this.$refs.address) {
                                // 住所のクリーニング
                                let clean = place.formatted_address || "";
                                clean = clean
                                    .replace(/^日本、\s*/, "")
                                    .replace(/〒\d{3}-\d{4}\s*/, "")
                                    .trim();
                                this.$refs.address.value = clean;
                            }

                            // 入力欄の見た目を整える
                            const simpleName = place.name
                                .replace(/^日本、\s*/, "")
                                .replace(/〒\d{3}-\d{4}\s*/, "")
                                .replace(/^.+?[0-9０-９]+.*?\s+/, "");

                            setTimeout(() => {
                                this.$refs.input.value = simpleName;
                                this.$refs.input.dispatchEvent(
                                    new Event("input")
                                );
                            }, 100);
                        }
                    });
                },
            };
        }
    </script>
</x-app-layout>
