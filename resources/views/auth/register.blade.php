<x-app-layout title="会員登録">
    <div class="p-6 pb-20">
        <h1 class="text-2xl font-bold text-center mb-8">🍜 部員登録</h1>

        <form action="{{ route('register') }}" method="POST" class="space-y-6">
            @csrf
            
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">名前（IDとして使います）</label>
                <input type="text" name="name" class="w-full p-3 border rounded-lg bg-gray-50" placeholder="例：ラーメン太郎" required>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">パスワード</label>
                <input type="password" name="password" class="w-full p-3 border rounded-lg bg-gray-50" required>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">パスワード（確認用）</label>
                <input type="password" name="password_confirmation" class="w-full p-3 border rounded-lg bg-gray-50" required>
            </div>

            <button class="w-full bg-orange-500 text-white font-bold py-4 rounded-full shadow-md hover:bg-orange-600 transition">
                登録して始める
            </button>
        </form>

        <div class="mt-6 text-center">
            <a href="{{ route('login') }}" class="text-sm text-blue-500 underline">すでに登録済みの方はこちら</a>
        </div>
    </div>
</x-app-layout>