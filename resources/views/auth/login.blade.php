<x-app-layout title="ログイン">
    <div class="p-6 pb-20">
        <h1 class="text-2xl font-bold text-center mb-8">🍜 ログイン</h1>

        @if ($errors->any())
        <div class="bg-red-100 text-red-700 p-3 rounded mb-4 text-sm">
            {{ $errors->first() }}
        </div>
        @endif

        <form action="{{ route('login') }}" method="POST" class="space-y-6">
            @csrf

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1"
                    >名前</label
                >
                <input
                    type="text"
                    name="name"
                    class="w-full p-3 border rounded-lg bg-gray-50"
                    required
                />
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1"
                    >パスワード</label
                >
                <input
                    type="password"
                    name="password"
                    class="w-full p-3 border rounded-lg bg-gray-50"
                    required
                />
            </div>

            <button
                class="w-full bg-blue-600 text-white font-bold py-4 rounded-full shadow-md hover:bg-blue-700 transition"
            >
                ログイン
            </button>
        </form>

        <div class="mt-4 text-center">
            <a
                href="{{ route('password.forgot') }}"
                class="text-xs text-gray-500 hover:text-orange-500 transition"
            >
                パスワードを忘れた方はこちら
            </a>
        </div>

        <div class="mt-6 mb-4 text-center">
            <a
                href="{{ route('register') }}"
                class="text-sm text-orange-500 underline"
                >新しく部員登録する</a
            >
        </div>

        <div class="mt-10 border-t border-gray-100 pt-6">
            <div
                class="bg-orange-50 rounded-xl p-4 text-left border border-orange-100"
            >
                <h3
                    class="font-bold text-gray-800 text-sm flex items-center gap-2 mb-2"
                >
                    <span>📱</span> アイコン追加のご案内
                </h3>
                <p class="text-xs text-gray-600 leading-relaxed">
                    ブラウザのメニューから
                    <span
                        class="font-bold text-orange-600 bg-white px-1 rounded border border-orange-200"
                        >ホーム画面に追加</span
                    >
                    すると、快適にアクセスできます！
                </p>
            </div>
        </div>
    </div>
</x-app-layout>
