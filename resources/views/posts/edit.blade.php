<x-app-layout title="投稿を編集">
    {{-- Google Maps API --}}
    @push('scripts')
    <script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.key') }}&libraries=places&language=ja&callback=Function.prototype"></script>
    @endpush

    <div class="max-w-xl mx-auto p-6 pb-20">
        
        {{-- ヘッダー --}}
        <div class="flex items-center gap-2 mb-6">
            <div class="bg-orange-100 p-2 rounded-lg text-orange-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
            </div>
            <h2 class="text-xl font-black text-gray-800">投稿を編集する</h2>
        </div>

        <form action="{{ route('posts.update', $post) }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            @csrf
            @method('PUT')

            {{-- ① 店名入力（枠をつけて分かりやすく） --}}
            <div class="mb-6" x-data="googleAutocomplete()">
                <label class="block text-sm font-bold text-gray-700 mb-2 flex items-center gap-1">
                    <span class="text-orange-400">🍜</span> お店の名前
                </label>
                
                {{-- ▼▼▼ デザイン変更: 枠線と背景色を追加 ▼▼▼ --}}
                <input type="text" 
                    name="shop_name" 
                    x-ref="input"
                    value="{{ old('shop_name', $post->shop->name) }}" 
                    class="w-full p-3 bg-gray-50 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-orange-500 focus:bg-white transition font-bold text-gray-800 placeholder-gray-400"
                    placeholder="店名を入力"
                    required 
                    autocomplete="off"
                    @keydown.enter.prevent>

                <input type="hidden" name="google_place_id" x-ref="placeId">
                <input type="hidden" name="address" x-ref="address">
                
                <p class="text-xs text-gray-400 mt-1 ml-1">※Googleマップの候補から修正できます</p>
            </div>

            {{-- ▼▼▼ 追加: ジャンル選択エリア ▼▼▼ --}}
            <div class="mb-6">
                <label class="block text-sm font-bold text-gray-700 mb-2 flex items-center gap-1">
                    <span class="text-gray-400">🏷️</span> ジャンル <span class="text-xs font-normal text-gray-400 ml-1">（複数選択可）</span>
                </label>
                
                <div class="flex flex-wrap gap-2">
                    @foreach($genres as $genre)
                        <label class="cursor-pointer">
                            <input type="checkbox" name="genres[]" value="{{ $genre->id }}" class="peer sr-only"
                                   {{-- 既に紐付いているジャンルならチェックを入れる --}}
                                   @if($post->shop->genres->contains($genre->id)) checked @endif
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
            {{-- ▲▲▲ 追加ここまで ▲▲▲ --}}

            {{-- ② 評価（100点満点版） --}}
            <div class="mb-8" x-data="{ 
                score: {{ old('score', $post->score) }},
                step: 0.1, 
                changeScore(amount) {
                    let current = parseFloat(this.score);
                    if (isNaN(current)) current = 0;
                    let newVal = current + parseFloat(amount);
                    if (newVal > 100) newVal = 100;
                    if (newVal < 0) newVal = 0;
                    this.score = parseFloat(newVal.toFixed(1)); 
                },
                validate() {
                    if (this.score === '') return;
                    let val = parseFloat(this.score);
                    if (val > 100) this.score = 100;
                    if (val < 0) this.score = 0;
                }
            }">
                <label class="block text-sm font-bold text-gray-700 mb-2 flex items-center gap-1">
                    <span class="text-yellow-400">⭐</span> 評価 <span class="text-xs font-normal text-gray-400 ml-1">（100点満点）</span>
                </label>

                <div class="bg-gray-50 p-4 rounded-xl border-2 border-gray-100">
                    {{-- プリセットボタン --}}
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

                    {{-- 入力エリア --}}
                    <div class="flex items-center justify-center gap-4 mb-2">
                        <button type="button" @click="changeScore(-step)" class="w-12 h-12 rounded-full bg-white border-2 border-gray-200 text-gray-400 hover:text-orange-500 hover:border-orange-200 font-bold text-2xl shadow-sm active:scale-95 transition flex items-center justify-center">-</button>

                        <div class="relative w-32">
                            <input type="number" name="score" x-model="score" @input="validate()" @blur="if(score === '') score = 0" @keydown="['e', 'E', '+', '-'].includes($event.key) && $event.preventDefault()" min="0" max="100" :step="step" class="w-full text-center text-5xl font-black text-gray-800 bg-transparent focus:outline-none p-1" />
                            <span class="absolute top-2 right-0 text-xs text-gray-400 font-bold pointer-events-none">点</span>
                        </div>

                        <button type="button" @click="changeScore(step)" class="w-12 h-12 rounded-full bg-white border-2 border-gray-200 text-gray-400 hover:text-orange-500 hover:border-orange-200 font-bold text-2xl shadow-sm active:scale-95 transition flex items-center justify-center">+</button>
                    </div>

                    {{-- スライダー --}}
                    <div class="px-2 mb-4">
                        <input type="range" x-model="score" min="0" max="100" :step="step" class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-orange-500">
                    </div>

                    {{-- 増減幅切り替え --}}
                    <div class="flex justify-center items-center gap-2">
                        <span class="text-[10px] font-bold text-gray-400">増減:</span>
                        <div class="flex bg-white rounded-lg p-1 border border-gray-200 shadow-sm">
                            @foreach([0.1, 0.5, 1] as $s)
                            <button type="button" @click="step = {{ $s }}" class="px-3 py-1 text-xs font-bold rounded transition" :class="step === {{ $s }} ? 'bg-orange-100 text-orange-600' : 'text-gray-400 hover:bg-gray-50'">{{ $s }}</button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- ③ コメント（枠をつけて分かりやすく） --}}
            <div class="mb-6">
                <label class="block text-sm font-bold text-gray-700 mb-2 flex items-center gap-1">
                    <span class="text-gray-400">💬</span> コメント
                </label>
                
                {{-- ▼▼▼ デザイン変更: 枠線と背景色を追加 ▼▼▼ --}}
                <textarea name="comment" 
                    rows="3" 
                    class="w-full p-3 bg-gray-50 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-orange-500 focus:bg-white transition text-gray-800 placeholder-gray-400 leading-relaxed" 
                    placeholder="味の感想などを記録しましょう">{{ old('comment', $post->comment) }}</textarea>
            </div>

            {{-- 画像 --}}
            <div class="mb-8">
                <label class="block text-sm font-bold text-gray-700 mb-2 flex items-center gap-1">
                    <span class="text-gray-400">📷</span> 画像を変更 <span class="text-xs font-normal text-gray-400 ml-1">（任意）</span>
                </label>
                
                @if($post->image_path)
                <div class="mb-3 p-2 bg-gray-50 rounded-lg border border-dashed border-gray-300 inline-block">
                    <p class="text-[10px] text-gray-400 mb-1 text-center">現在の画像</p>
                    <img src="{{ asset($post->image_path) }}" class="h-24 rounded object-cover mx-auto">
                </div>
                @endif

                <input type="file" name="image" accept="image/*" class="w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-bold file:bg-orange-50 file:text-orange-600 hover:file:bg-orange-100 cursor-pointer">
            </div>

            {{-- ボタンエリア --}}
            <div class="flex gap-3">
                <a href="{{ route('profile.index') }}" class="flex-1 bg-gray-100 text-gray-600 py-3 rounded-xl font-bold text-center hover:bg-gray-200 transition-colors">
                    キャンセル
                </a>
                <button type="submit" class="flex-[2] bg-orange-500 text-white py-3 rounded-xl font-bold hover:bg-orange-600 transition-all shadow-lg shadow-orange-200">
                    更新する
                </button>
            </div>
        </form>
    </div>

    {{-- オートコンプリート用スクリプト --}}
    <script>
        function googleAutocomplete() {
            return {
                init() {
                    if (typeof google === 'undefined') return;
                    
                    const autocomplete = new google.maps.places.Autocomplete(this.$refs.input, {
                        types: ['establishment'],
                        componentRestrictions: { country: 'jp' },
                        // ★修正: fields に住所とIDを追加
                        fields: ['name', 'formatted_address', 'place_id']
                    });

                    autocomplete.addListener('place_changed', () => {
                        const place = autocomplete.getPlace();
                        
                        if (place.name) {
                            // ▼▼▼ 追加: 隠し項目にセットする処理 ▼▼▼
                            // Place ID
                            if (this.$refs.placeId) {
                                this.$refs.placeId.value = place.place_id || '';
                            }
                            // 住所
                            if (this.$refs.address && place.formatted_address) {
                                this.$refs.address.value = this.cleanAddress(place.formatted_address);
                            }
                            // ▲▲▲ 追加ここまで ▲▲▲

                            const simpleName = this.cleanName(place.name);
                            setTimeout(() => {
                                this.$refs.input.value = simpleName;
                                this.$refs.input.dispatchEvent(new Event('input'));
                            }, 100); 
                        }
                    });
                },
                // 店名整形（既存）
                cleanName(fullName) {
                    let name = fullName;
                    name = name.replace(/^日本、\s*/, ''); 
                    name = name.replace(/〒\d{3}-\d{4}\s*/, ''); 
                    name = name.replace(/^.+?[0-9０-９]+.*?\s+/, '');
                    return name;
                },
                // ★追加: 住所整形関数
                cleanAddress(address) {
                    let clean = address;
                    clean = clean.replace(/^日本、\s*/, "");
                    clean = clean.replace(/〒\d{3}-\d{4}\s*/, "");
                    return clean.trim();
                }
            }
        }
    </script>
</x-app-layout>