<x-app-layout title="会員登録">
    <div class="p-6 pb-20">
        <h1 class="text-2xl font-bold text-center mb-8 flex items-center justify-center gap-2">
            <span>🍜</span> 部員登録
        </h1>

        <form action="{{ route('register') }}" method="POST" class="space-y-6">
            @csrf
            
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">名前（IDとして使います）</label>
                <input type="text" name="name" 
                       value="{{ old('name') }}" 
                       class="w-full p-3 border rounded-lg bg-gray-50 focus:ring-2 focus:ring-orange-200 outline-none transition @error('name') border-red-500 bg-red-50 @enderror" 
                       placeholder="例：ラーメン太郎" required>
                
                @error('name')
                    <p class="text-red-500 text-xs font-bold mt-1">⚠️ {{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">パスワード（4文字以上）</label>
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

            <div class="pt-4 border-t border-gray-100">
                <label class="block text-sm font-bold text-gray-700 mb-1">
                    🍜 秘密の質問：好きなラーメンの種類は？<br>
                    <span class="text-xs font-normal text-gray-500">※パスワードを忘れた際に使用します（例：味噌、二郎系、家系など）</span>
                </label>
                <input type="text" name="secret_answer" 
                       value="{{ old('secret_answer') }}"
                       class="w-full p-3 border rounded-lg bg-gray-50 focus:ring-2 focus:ring-orange-200 outline-none transition @error('secret_answer') border-red-500 bg-red-50 @enderror" 
                       placeholder="回答を入力" required>
                
                @error('secret_answer')
                    <p class="text-red-500 text-xs font-bold mt-1">⚠️ {{ $message }}</p>
                @enderror
            </div>

            <button class="w-full bg-orange-500 text-white font-bold py-4 rounded-full shadow-md hover:bg-orange-600 transition transform active:scale-95">
                登録して始める
            </button>
        </form>

        <div class="mt-6 text-center">
            <a href="{{ route('login') }}" class="text-sm text-blue-500 underline">すでに登録済みの方はこちら</a>
        </div>
    </div>
</x-app-layout>