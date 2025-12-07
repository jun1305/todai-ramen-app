<x-app-layout title="パスワード再設定">
    <div class="p-6 pb-20">
        <h1 class="text-2xl font-bold text-center mb-8 flex items-center justify-center gap-2">
            <span>🔑</span> パスワード再設定
        </h1>

        <form action="{{ route('password.verify') }}" method="POST" class="space-y-6">
            @csrf
            
            @if($errors->has('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline text-sm font-bold">{{ $errors->first('error') }}</span>
                </div>
            @endif

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">あなたの名前（ID）</label>
                <input type="text" name="name" 
                       class="w-full p-3 border rounded-lg bg-gray-50 focus:ring-2 focus:ring-orange-200 outline-none transition" 
                       placeholder="登録時の名前" required>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">
                    秘密の質問：<br>好きなラーメンの種類は？
                </label>
                <input type="text" name="secret_answer" 
                       class="w-full p-3 border rounded-lg bg-gray-50 focus:ring-2 focus:ring-orange-200 outline-none transition" 
                       placeholder="例：味噌" required>
            </div>

            <button class="w-full bg-orange-500 text-white font-bold py-4 rounded-full shadow-md hover:bg-orange-600 transition transform active:scale-95">
                次へ進む
            </button>
        </form>

        <div class="mt-6 text-center">
            <a href="{{ route('login') }}" class="text-sm text-gray-500 underline">ログイン画面に戻る</a>
        </div>
    </div>
</x-app-layout>