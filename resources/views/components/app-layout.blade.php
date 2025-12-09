<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta charset="UTF-8" />
        <meta
            name="viewport"
            content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"
        />
        <title>{{ $title ?? "東大ラーメンログ" }}</title>
        <script
            defer
            src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"
        ></script>

        <link
            href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css"
            rel="stylesheet"
        />
        <link rel="manifest" href="/manifest.json" />
        <link rel="apple-touch-icon" href="/images/icon-512.png" />
        <meta name="theme-color" content="#f97316" />
        <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @stack('scripts')
    </head>
    <body class="bg-gray-50 text-gray-900 antialiased">
        <div
            class="flex flex-col h-[100dvh] max-w-md mx-auto bg-white shadow-2xl overflow-hidden relative"
        >
            @include('partials.header')

            <main class="flex-1 overflow-y-auto p-4">
                {{ $slot }}
            </main>

            @include('partials.footer')
        </div>
        <script>
            if ("serviceWorker" in navigator) {
                navigator.serviceWorker.register("/sw.js").then(function () {
                    console.log("Service Worker Registered");
                });
            }
        </script>

        {{-- ▼▼▼ PWAインストール訴求ポップアップ ▼▼▼ --}}
        <div x-data="pwaInstaller()" x-init="init()" x-cloak>
            {{-- モーダル本体（showがtrueのときだけ表示） --}}
            <div
                x-show="show"
                class="fixed inset-0 z-50 flex items-end justify-center sm:items-center pointer-events-none"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            >
                {{-- 背景の黒透過（クリックで閉じる） --}}
                <div
                    class="absolute inset-0 bg-black/40 pointer-events-auto"
                    @click="show = false"
                ></div>

                {{-- ポップアップの中身 --}}
                <div
                    class="bg-white w-full max-w-sm mx-4 mb-6 sm:mb-0 rounded-2xl shadow-xl overflow-hidden pointer-events-auto relative"
                >
                    {{-- 閉じるボタン --}}
                    <button
                        @click="show = false"
                        class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 p-1"
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
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>

                    <div class="p-6 text-center">
                        {{-- アイコン表示 --}}
                        <div
                            class="w-16 h-16 bg-orange-100 rounded-2xl mx-auto flex items-center justify-center mb-4 text-3xl shadow-sm"
                        >
                            🍜
                        </div>

                        <h3 class="text-lg font-bold text-gray-800 mb-2">
                            アプリ版を利用しませんか？
                        </h3>
                        <p class="text-sm text-gray-500 mb-6">
                            ホーム画面に追加すると、アプリのように全画面でサクサク使えます！
                        </p>

                        {{-- Android / PC (Chrome) の場合 --}}
                        <template x-if="canInstall">
                            <button
                                @click="install"
                                class="w-full bg-orange-500 text-white font-bold py-3 rounded-full shadow-md hover:bg-orange-600 transition active:scale-95"
                            >
                                インストールする
                            </button>
                        </template>

                        {{-- iPhone (iOS) の場合 --}}
                        <template x-if="isIOS">
                            <div
                                class="bg-gray-50 rounded-lg p-3 text-left border border-gray-100"
                            >
                                <p
                                    class="text-xs font-bold text-gray-600 mb-2 text-center"
                                >
                                    👇 追加方法
                                </p>
                                <ol
                                    class="text-xs text-gray-500 space-y-2 list-decimal list-inside"
                                >
                                    <li>
                                        画面下の
                                        <span
                                            class="inline-block px-1 bg-gray-200 rounded text-[10px] font-bold"
                                            >共有</span
                                        >
                                        ボタンをタップ
                                    </li>
                                    <li>
                                        メニューから
                                        <span class="font-bold text-gray-700"
                                            >ホーム画面に追加</span
                                        >
                                        を選択
                                    </li>
                                    <li>
                                        右上の
                                        <span class="font-bold text-blue-500"
                                            >追加</span
                                        >
                                        をタップ
                                    </li>
                                </ol>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <script>
            function pwaInstaller() {
                return {
                    show: false,
                    canInstall: false, // Android/PC用フラグ
                    isIOS: false, // iOS用フラグ
                    deferredPrompt: null,

                    init() {
                        // すでにアプリモードで開いている場合は表示しない
                        if (
                            window.matchMedia("(display-mode: standalone)")
                                .matches
                        ) {
                            return;
                        }

                        // 1. Android / PC (Chrome) のインストールイベント待機
                        window.addEventListener("beforeinstallprompt", (e) => {
                            e.preventDefault(); // 勝手に出るバナーを防ぐ
                            this.deferredPrompt = e;
                            this.canInstall = true;
                            this.show = true; // ポップアップを表示
                        });

                        // 2. iOS判定
                        const userAgent =
                            window.navigator.userAgent.toLowerCase();
                        if (
                            /iphone|ipad|ipod/.test(userAgent) &&
                            !window.MSStream
                        ) {
                            this.isIOS = true;
                            // iOSの場合はページ読み込みの2秒後くらいにフワッと出す
                            setTimeout(() => {
                                this.show = true;
                            }, 2000);
                        }
                    },

                    async install() {
                        if (this.deferredPrompt) {
                            this.deferredPrompt.prompt();
                            const { outcome } = await this.deferredPrompt
                                .userChoice;
                            this.deferredPrompt = null;
                            this.show = false;
                        }
                    },
                };
            }
        </script>
    </body>
</html>
