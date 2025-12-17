<header
    x-data="{ menuOpen: false }"
    class="bg-slate-900 text-white py-3 px-4 shadow-lg sticky top-0 z-30 flex justify-between items-center shrink-0"
>
    {{-- ▼▼▼ 左側エリア（メニューボタン ＋ ロゴ） ▼▼▼ --}}
    <div class="flex items-center gap-3">
        <button
            @click="menuOpen = true"
            class="p-1 -ml-1 text-gray-300 hover:text-white transition"
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-7 w-7"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M4 6h16M4 12h16M4 18h16"
                />
            </svg>
        </button>

        <a href="/" class="flex items-center gap-2">
            <div
                class="bg-white/10 p-1.5 rounded-full backdrop-blur-sm border border-white/10"
            >
                <span class="text-lg filter drop-shadow-md block leading-none"
                    >🍜</span
                >
            </div>
            <h1 class="text-base font-black tracking-wider text-white">
                東大ラーメンログ
            </h1>
        </a>
    </div>

    {{-- ▼▼▼ 右側エリア（通知ベル）※変更なし ▼▼▼ --}}
    @auth
    <div
        class="relative"
        x-data="{ 
             open: false,
             hasUnread: {{ Auth::check() && Auth::user()->unreadNotifications->count() > 0 ? 'true' : 'false' }},
             markAsRead() {
                 if (this.hasUnread) {
                     fetch('/notifications/read', { 
                         method: 'POST',
                         headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                      });
                      this.hasUnread = false;
                 }
             }
         }"
    >
        <button
            @click="open = !open; markAsRead()"
            class="relative p-2 text-gray-300 hover:text-white hover:bg-white/10 rounded-full transition duration-300"
        >
            <span
                x-show="hasUnread"
                x-transition.opacity
                class="absolute top-1.5 right-1.5 h-2.5 w-2.5 rounded-full bg-red-500 border-2 border-slate-900 shadow-sm animate-pulse"
            ></span>
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
                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
                />
            </svg>
        </button>

        <div
            x-show="open"
            @click.away="open = false"
            x-cloak
            class="absolute right-0 mt-2 w-72 bg-white rounded-xl shadow-xl border border-gray-100 overflow-hidden text-gray-800 z-50"
        >
            <div
                class="px-4 py-2 bg-gray-50 border-b border-gray-100 font-bold text-xs text-gray-500"
            >
                お知らせ
            </div>
            <div class="max-h-64 overflow-y-auto">
                @auth @forelse(Auth::user()->notifications as $notification)
                <div
                    class="p-3 border-b border-gray-50 hover:bg-orange-50 transition {{ $notification->read_at ? 'opacity-50 bg-gray-50' : 'bg-blue-50' }}"
                >
                    <p class="text-sm text-gray-800">
                        {{ $notification->data['message'] ?? '通知があります' }}
                    </p>
                    <p class="text-xs text-gray-400 mt-1">
                        {{ $notification->created_at->diffForHumans() }}
                    </p>
                </div>
                @empty
                <div class="p-4 text-center text-gray-400 text-xs">
                    まだ通知はありません
                </div>
                @endforelse @endauth
            </div>
        </div>
    </div>
    @endauth

    {{-- ▼▼▼ スライドメニュー（ドロワー） ▼▼▼ --}}
    <div
        x-show="menuOpen"
        class="fixed inset-0 z-50 flex"
        style="display: none"
        x-cloak
    >
        <div
            @click="menuOpen = false"
            x-show="menuOpen"
            x-transition:enter="transition-opacity ease-linear duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-linear duration-300"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-black/60 backdrop-blur-sm"
        ></div>

        <nav
            x-show="menuOpen"
            x-transition:enter="transition ease-in-out duration-300 transform"
            x-transition:enter-start="-translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in-out duration-300 transform"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full"
            class="relative bg-white w-72 h-full shadow-2xl flex flex-col max-w-[85vw]"
        >
            {{-- 1. メニューヘッダー（ユーザー情報） --}}
            <div
                class="p-5 bg-slate-900 text-white flex justify-between items-start"
            >
                @auth
                <a
                    href="{{ route('profile.index') }}"
                    class="flex items-center gap-3 group"
                >
                    <div
                        class="h-12 w-12 rounded-full border-2 border-white/20 bg-slate-800 overflow-hidden shrink-0"
                    >
                        @if(Auth::user()->icon_path)
                        <img
                            src="{{ asset(Auth::user()->icon_path) }}"
                            class="w-full h-full object-cover"
                        />
                        @else
                        <div
                            class="w-full h-full flex items-center justify-center text-xl"
                        >
                            {{ mb_substr(Auth::user()->name, 0, 1) }}
                        </div>
                        @endif
                    </div>
                    <div>
                        <p
                            class="font-bold text-lg leading-tight group-hover:text-orange-400 transition"
                        >
                            {{ Auth::user()->name }}
                        </p>
                        <p class="text-xs text-gray-400 mt-0.5">
                            マイページへ ＞
                        </p>
                    </div>
                </a>
                @else
                <span class="font-bold text-lg">ゲストさん</span>
                @endauth

                <button
                    @click="menuOpen = false"
                    class="text-gray-400 hover:text-white p-1"
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
                            d="M6 18L18 6M6 6l12 12"
                        />
                    </svg>
                </button>
            </div>

            {{-- 2. メニューリンク一覧 --}}
            <div class="flex-1 overflow-y-auto py-2">
                {{-- ★ ここから新機能エリア ★ --}}
                <div class="px-4 pt-4 pb-2">
                    <p class="text-xs font-bold text-gray-400 mb-2 pl-2">
                        スペシャル
                    </p>

                    {{-- 今日の一杯 --}}
                    <a
                        href="{{ route('daily.index') }}"
                        class="flex items-center gap-3 px-4 py-3 mb-2 bg-gradient-to-r from-orange-500 to-red-500 text-white rounded-xl shadow-md hover:shadow-lg transition transform active:scale-95"
                    >
                        <span class="text-2xl bg-white/20 p-1.5 rounded-lg"
                            >📅</span
                        >
                        <div>
                            <span
                                class="font-black text-sm block leading-none mb-0.5 text-orange-100"
                                >Daily Ramen</span
                            >
                            <span class="font-bold text-base">今日の一杯</span>
                        </div>
                    </a>

                    {{-- ラーメンラリー --}}
                    {{-- これもリンク先は後で --}}
                    <a
                        href="{{ route('rallies.index') }}"
                        class="flex items-center gap-3 px-4 py-3 bg-white border-2 border-slate-100 rounded-xl text-slate-700 hover:border-slate-300 hover:bg-slate-50 transition transform active:scale-95"
                    >
                        <span class="text-2xl bg-slate-100 p-1.5 rounded-lg"
                            >🚩</span
                        >
                        <div>
                            <span
                                class="font-bold text-xs block text-slate-400 leading-none mb-0.5"
                                >制覇を目指せ！</span
                            >
                            <span class="font-bold text-base"
                                >ラーメンラリー</span
                            >
                        </div>
                    </a>
                </div>

                <div class="border-t border-gray-100 my-2"></div>

                {{-- 通常メニュー --}}
                <div class="px-2">
                    <p class="text-xs font-bold text-gray-400 mb-1 mt-2 pl-4">
                        メニュー
                    </p>
                    <a
                        href="/"
                        class="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-lg transition font-bold"
                    >
                        <span class="text-xl w-6 text-center">🏠</span>
                        ホーム
                    </a>
                    <a
                        href="{{ route('ranking.index') }}"
                        class="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-lg transition font-bold"
                    >
                        <span class="text-xl w-6 text-center">👑</span>
                        ランキング
                    </a>
                    <a
                        href="{{ route('shops.index') }}"
                        class="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-lg transition font-bold"
                    >
                        <span class="text-xl w-6 text-center">🍜</span>
                        お店図鑑
                    </a>
                </div>
            </div>

            {{-- 3. フッター（ログアウト） --}}
            @auth
            <div class="p-4 border-t border-gray-100 bg-gray-50">
                {{-- ▼▼▼ onsubmit属性を追加 ▼▼▼ --}}
                <form action="{{ route('logout') }}" method="POST" onsubmit="return confirm('ログアウトしますか？');">
                @csrf
                    <button
                        class="flex items-center justify-center gap-2 w-full py-3 bg-white border border-gray-200 rounded-xl text-gray-600 font-bold hover:bg-gray-100 hover:text-red-500 transition shadow-sm"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
                            />
                        </svg>
                        ログアウト
                    </button>
                </form>
            </div>
            @endauth
        </nav>
    </div>
</header>
