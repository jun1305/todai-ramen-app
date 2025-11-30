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
                <label for="shop_name" class="block text-sm font-bold text-gray-700">
                    店名 <span class="text-red-500">*</span>
                </label>
                
                <input type="text" name="shop_name" id="shop_name" 
                    x-ref="input"
                    placeholder="店名を入力（候補が出ます）"
                    class="w-full text-lg p-4 bg-gray-50 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-orange-500 focus:bg-white transition"
                    required autocomplete="off"
                    @keydown.enter.prevent> <p class="text-xs text-gray-400">※Googleマップの候補から選んでください</p>
            </div>

            @push('scripts')
                <script src="https://maps.googleapis.com/maps/api/js?key={{ env('GOOGLE_MAPS_API_KEY') }}&libraries=places&language=ja&callback=Function.prototype"></script>
            @endpush

            <script>
                function googleAutocomplete() {
                    return {
                        init() {
                            if (typeof google === 'undefined') return;

                            const autocomplete = new google.maps.places.Autocomplete(this.$refs.input, {
                                types: ['establishment'],
                                componentRestrictions: { country: 'jp' },
                                fields: ['name']
                            });

                            autocomplete.addListener('place_changed', () => {
                                const place = autocomplete.getPlace();
                                console.log('Googleから届いたデータ:', place); // 確認用

                                if (place.name) {
                                    // ★ここで「住所カット手術」を行う
                                    const simpleName = this.cleanName(place.name);
                                    
                                    console.log('整形後の名前:', simpleName); // 確認用

                                    setTimeout(() => {
                                        this.$refs.input.value = simpleName;
                                        this.$refs.input.dispatchEvent(new Event('input'));
                                    }, 100); 
                                }
                            });
                        },

                        // ★名前をきれいにする関数（強力版）
                        cleanName(fullName) {
                            let name = fullName;

                            // 1. "日本、" を削除
                            name = name.replace(/^日本、\s*/, ''); 

                            // 2. "〒xxx-xxxx" を削除
                            name = name.replace(/〒\d{3}-\d{4}\s*/, ''); 

                            // 3. 【ここが修正ポイント】 住所部分をごっそり削除
                            // 「何か文字」+「全角半角の数字」+「その後のごちゃごちゃ(丁目とかハイフン)」+「スペース」
                            // というパターンを見つけて、そこまでを全部消します。
                            name = name.replace(/^.+?[0-9０-９]+.*?\s+/, '');

                            return name;
                        }
                    }
                }
            </script>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">
                    点数 <span class="text-red-500">*</span>
                </label>
                <div class="flex justify-between gap-2">
                    @for($i = 1; $i <= 5; $i++)
                    <div class="flex-1">
                        <input
                            type="radio"
                            name="score"
                            id="score{{ $i }}"
                            value="{{ $i }}"
                            class="peer hidden"
                            required
                        />
                        <label
                            for="score{{ $i }}"
                            class="block text-center py-3 rounded-lg border-2 border-gray-200 bg-white text-gray-400 font-bold cursor-pointer peer-checked:bg-orange-500 peer-checked:text-white peer-checked:border-orange-500 transition shadow-sm"
                        >
                            {{ $i }}
                        </label>
                    </div>
                    @endfor
                </div>
                <div
                    class="flex justify-between text-xs text-gray-400 mt-1 px-1"
                >
                    <span>最悪</span>
                    <span>最高</span>
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
