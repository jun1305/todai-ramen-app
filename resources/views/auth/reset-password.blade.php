<x-app-layout title="新しいパスワード">
    <div class="p-6 pb-20">
        <h1 class="text-2xl font-bold text-center mb-8">
            新しいパスワードを入力
        </h1>

        <form action="{{ route('password.update') }}" method="POST" class="space-y-6">
            @csrf
            
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">新しいパスワード（4文字以上）</label>
                <input type="password" name="password" 
                       class="w-full p-3 border rounded-lg bg-gray-50 focus:ring-2 focus:ring-orange-200 outline-none transition @error('password') border-red-500 bg-red-50 @enderror" 
                       required>
                @error('password')
                    <p class="text-red-500 text-xs font-bold mt-1">⚠️ {{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">パスワード（確認用）</label>
                <input type="password" name="password_confirmation" 
                       class="w-full p-3 border rounded-lg bg-gray-50 focus:ring-2 focus:ring-orange-200 outline-none transition" 
                       required>
            </div>

            <button class="w-full bg-blue-500 text-white font-bold py-4 rounded-full shadow-md hover:bg-blue-600 transition transform active:scale-95">
                パスワードを変更する
            </button>
        </form>
    </div>
</x-app-layout>