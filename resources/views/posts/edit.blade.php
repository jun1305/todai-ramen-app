<x-app-layout title="投稿を編集">
    <div class="max-w-xl mx-auto p-6">
        
        {{-- ヘッダーにアイコンを追加して楽しげに --}}
        <div class="flex items-center gap-2 mb-6">
            <div class="bg-orange-100 p-2 rounded-lg text-orange-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
            </div>
            <h2 class="text-xl font-black text-gray-800">投稿を編集する</h2>
        </div>

        {{-- カードに border-t-4 border-orange-400 を足してアクセント追加 --}}
        <form action="{{ route('posts.update', $post) }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-xl border border-gray-100 border-t-4 border-t-orange-400 p-6 shadow-sm">
            @csrf
            @method('PUT')

            {{-- 店舗名 --}}
            <div class="mb-5">
                <label class="block text-sm font-bold text-gray-700 mb-2 flex items-center gap-1">
                    <span class="text-orange-400">🍜</span> お店の名前
                </label>
                {{-- focus時の色を blue から orange に変更 --}}
                <input type="text" name="shop_name" value="{{ old('shop_name', $post->shop->name) }}" class="w-full rounded-lg border-gray-300 focus:ring-orange-500 focus:border-orange-500 transition-colors" required>
            </div>

            {{-- 評価 --}}
            <div class="mb-5">
                <label class="block text-sm font-bold text-gray-700 mb-2 flex items-center gap-1">
                    <span class="text-yellow-400">⭐</span> 評価
                </label>
                <select name="score" class="w-full rounded-lg border-gray-300 focus:ring-orange-500 focus:border-orange-500 cursor-pointer">
                    @for($i = 1; $i <= 5; $i++)
                        <option value="{{ $i }}" {{ old('score', $post->score) == $i ? 'selected' : '' }}>
                            {{ str_repeat('★', $i) }}
                        </option>
                    @endfor
                </select>
            </div>

            {{-- コメント --}}
            <div class="mb-5">
                <label class="block text-sm font-bold text-gray-700 mb-2 flex items-center gap-1">
                    <span class="text-gray-400">💬</span> コメント
                </label>
                <textarea name="comment" rows="3" class="w-full rounded-lg border-gray-300 focus:ring-orange-500 focus:border-orange-500" placeholder="味の感想などを記録しましょう">{{ old('comment', $post->comment) }}</textarea>
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

                {{-- ファイル選択ボタンのデザインをカスタマイズ --}}
                <input type="file" name="image" accept="image/*" class="w-full text-sm text-gray-500 
                    file:mr-4 file:py-2.5 file:px-4 
                    file:rounded-full file:border-0 
                    file:text-sm file:font-bold 
                    file:bg-orange-50 file:text-orange-600 
                    hover:file:bg-orange-100 cursor-pointer">
            </div>

            {{-- ボタンエリア --}}
            <div class="flex gap-3">
                <a href="{{ route('profile.index') }}" class="flex-1 bg-gray-100 text-gray-600 py-3 rounded-xl font-bold text-center hover:bg-gray-200 transition-colors">
                    キャンセル
                </a>
                {{-- シャドウの色も shadow-orange-200 にして統一感 --}}
                <button type="submit" class="flex-[2] bg-orange-500 text-white py-3 rounded-xl font-bold hover:bg-orange-600 transition-all shadow-lg shadow-orange-200">
                    更新する
                </button>
            </div>
        </form>
    </div>
</x-app-layout>