<x-app-layout title="{{ $post->shop_name }} - {{ $post->menu_name }}">
    {{-- ▼▼▼ メインコンテンツエリア ▼▼▼ --}}
    <div class="max-w-4xl mx-auto bg-white min-h-screen pb-20">
        {{-- ヘッダーエリア --}}
        <div class="p-6 border-b border-gray-100 relative">
            <div class="flex justify-between items-start">
                {{-- 左側：店名とメニュー名 --}}
                <div class="flex-1 min-w-0 pr-4">
                    <h1
                        class="text-2xl font-black text-gray-900 tracking-tight leading-tight break-words"
                    >
                        {{ $post->shop_name }}
                    </h1>
                    <h2
                        class="text-xl text-gray-600 font-bold mt-1 leading-snug break-words"
                    >
                        {{ $post->menu_name ?? '不明なメニュー' }}
                    </h2>

                    {{-- 場所情報（修正：全体をリンク化） --}}
                    @if($post->address)
                    <a
                        href="{{ $post->map_url }}"
                        target="_blank"
                        class="mt-3 inline-flex items-center gap-1.5 text-sm font-medium text-gray-500 hover:text-orange-600 transition group"
                    >
                        {{-- ピンアイコン --}}
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-4 w-4 text-gray-400 group-hover:text-orange-600 transition"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z"
                                clip-rule="evenodd"
                            />
                        </svg>

                        {{-- 住所テキスト --}}
                        <span
                            class="tracking-wide border-b border-transparent group-hover:border-orange-200"
                        >
                            {{ $post->short_address }}
                        </span>

                        {{-- 地図アイコン（小さく添える） --}}
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-3 w-3 text-gray-300 group-hover:text-orange-400 ml-0.5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"
                            />
                        </svg>
                    </a>
                    @else
                    <div class="mt-3 text-sm text-gray-400">住所未登録</div>
                    @endif
                </div>

                {{-- 右側：日付バッジ ＆ 投稿ボタン --}}
                <div class="flex flex-col items-end gap-2 flex-shrink-0 -mt-1">
                    {{-- 日付バッジ --}}
                    <div
                        class="bg-gray-100 text-gray-600 px-3 py-1 rounded-lg text-xs font-bold text-center border border-gray-200"
                    >
                        <span
                            class="block text-[10px] uppercase text-gray-400 leading-none mb-0.5"
                            >EATEN AT</span
                        >
                        <span
                            class="text-lg leading-none text-orange-600 font-black"
                            >{{ $post->eaten_at->format('Y.m.d') }}</span
                        >
                    </div>

                    {{-- ▼▼▼ 修正: 会長メニュー（新規投稿・編集ボタン） ▼▼▼ --}}
                    @if(Auth::id() === 1)
                    <div class="flex gap-2 mt-2">
                        {{-- 編集ボタン --}}
                        <a
                            href="{{ route('daily.edit', $post->id) }}"
                            class="bg-gray-100 text-gray-500 w-10 h-10 rounded-full shadow-sm flex items-center justify-center hover:bg-gray-200 hover:text-gray-700 transition"
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
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                />
                            </svg>
                        </a>

                        {{-- 新規投稿ボタン --}}
                        <a
                            href="{{ route('daily.create') }}"
                            class="bg-orange-600 text-white w-10 h-10 rounded-full shadow-md flex items-center justify-center hover:bg-orange-700 transition"
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
                                    d="M12 4v16m8-8H4"
                                />
                            </svg>
                        </a>
                    </div>
                    @endif
                    {{-- ▲▲▲ 修正ここまで ▲▲▲ --}}
                </div>
            </div>
        </div>

        {{-- 写真エリア --}}
        <div class="w-full bg-gray-100 relative">
            <img
                src="{{ asset($post->image_path) }}"
                alt="ラーメン画像"
                class="w-full h-auto max-h-[600px] object-contain mx-auto shadow-inner"
            />
        </div>

        {{-- レビュー本文エリア --}}
        <div class="px-4 py-8">
            <div
                class="relative bg-orange-50/50 p-6 rounded-2xl border border-orange-100"
            >
                <div
                    class="absolute -top-3 left-6 bg-white border border-orange-100 px-3 py-1 rounded-full text-xs font-bold text-orange-500"
                >
                    会長のレビュー
                </div>
                <p
                    class="text-gray-800 leading-relaxed whitespace-pre-wrap font-medium text-lg break-words"
                >
                    {{ $post->comment }}
                </p>
            </div>
        </div>

        {{-- ▼▼▼ ナビゲーションエリア ▼▼▼ --}}
        <div class="mt-12 bg-slate-50 border-t border-gray-200 p-4 sm:p-8">
            {{-- 前後の記事ナビ --}}
            <div class="flex justify-between items-center mb-8">
                @if($prevPost)
                <a
                    href="{{ route('daily.index', ['id' => $prevPost->id]) }}"
                    class="group flex items-center gap-3 bg-white px-4 py-3 rounded-xl shadow-sm border border-gray-200 hover:border-orange-300 transition w-1/2 mr-2"
                >
                    <div
                        class="w-10 h-10 rounded bg-gray-100 overflow-hidden flex-shrink-0"
                    >
                        <img
                            src="{{ asset($prevPost->image_path) }}"
                            class="w-full h-full object-cover group-hover:scale-110 transition"
                        />
                    </div>
                    <div class="min-w-0">
                        <div class="text-[10px] text-gray-400 font-bold">
                            前の投稿
                        </div>
                        <div class="text-xs font-bold text-gray-800 truncate">
                            {{ $prevPost->shop_name }}
                        </div>
                    </div>
                </a>
                @else
                <div class="w-1/2 mr-2"></div>
                @endif @if($nextPost)
                <a
                    href="{{ route('daily.index', ['id' => $nextPost->id]) }}"
                    class="group flex items-center justify-end gap-3 bg-white px-4 py-3 rounded-xl shadow-sm border border-gray-200 hover:border-orange-300 transition w-1/2 ml-2 text-right"
                >
                    <div class="min-w-0">
                        <div class="text-[10px] text-gray-400 font-bold">
                            次の投稿
                        </div>
                        <div class="text-xs font-bold text-gray-800 truncate">
                            {{ $nextPost->shop_name }}
                        </div>
                    </div>
                    <div
                        class="w-10 h-10 rounded bg-gray-100 overflow-hidden flex-shrink-0"
                    >
                        <img
                            src="{{ asset($nextPost->image_path) }}"
                            class="w-full h-full object-cover group-hover:scale-110 transition"
                        />
                    </div>
                </a>
                @else
                <div class="w-1/2 ml-2"></div>
                @endif
            </div>
            {{-- カレンダーエリア --}}
            {{-- ▼▼▼ 修正：IDをつけて、別ファイルを読み込むようにする ▼▼▼ --}}
            <div id="calendar-wrapper">
                @include('daily_ramens.calendar', [ 'startOfMonth' =>
                $startOfMonth, 'monthlyPosts' => $monthlyPosts, 'post' => $post,
                'prevMonth' => $startOfMonth->copy()->subMonth()->format('Y-m'),
                'nextMonth' => $startOfMonth->copy()->addMonth()->format('Y-m')
                ])
            </div>
            {{-- ▲▲▲ 修正ここまで ▲▲▲ --}}

            <p class="text-center text-xs text-gray-400 mt-4">
                カレンダーの日付をタップするとその日の記録に飛びます
            </p>
        </div>
    </div>
</x-app-layout>

<script>
    // 共通のカレンダー更新関数
    function updateCalendar(url) {
        fetch(url)
            .then((response) => response.text())
            .then((html) => {
                document.getElementById("calendar-wrapper").innerHTML = html;
            })
            .catch((error) => console.error("Error:", error));
    }

    // すべてのクリックイベントをここで管理（イベント委譲）
    document.addEventListener("click", function (e) {
        // 1. カレンダーナビ（◀ ▶）のクリック
        const navLink = e.target.closest(".js-calendar-nav");
        if (navLink) {
            e.preventDefault();
            updateCalendar(navLink.href);
            return;
        }

        // 2. 年月選択メニューの開閉（トグルボタン）
        const toggleBtn = e.target.closest(".js-toggle-calendar-menu");
        if (toggleBtn) {
            const menu = document.getElementById("calendar-menu");
            if (menu) menu.classList.toggle("hidden");
            return;
        }

        // 3. メニューの外側をクリックしたら閉じる
        const menu = document.getElementById("calendar-menu");
        if (
            menu &&
            !menu.classList.contains("hidden") &&
            !e.target.closest("#calendar-menu")
        ) {
            menu.classList.add("hidden");
        }

        // 4. 「年」の変更（＜ 2025年 ＞）
        const yearBtn = e.target.closest(".js-change-year");
        if (yearBtn) {
            const yearSpan = document.getElementById("calendar-menu-year");
            let currentYear = parseInt(yearSpan.dataset.year);
            const val = parseInt(yearBtn.dataset.val);

            // 年を計算して表示とデータ属性を更新
            currentYear += val;
            yearSpan.dataset.year = currentYear;
            yearSpan.textContent = currentYear + "年";
            return;
        }

        // 5. 「月」の選択
        const monthBtn = e.target.closest(".js-select-month");
        if (monthBtn) {
            const menuDiv = document.getElementById("calendar-menu");
            const yearSpan = document.getElementById("calendar-menu-year");

            const year = yearSpan.dataset.year;
            // 月を2桁にする（例: 1 -> 01）
            const month = monthBtn.dataset.month.toString().padStart(2, "0");

            // data-base-url からURLを取得
            const baseUrl = menuDiv.dataset.baseUrl;
            const separator = baseUrl.includes("?") ? "&" : "?";
            const url = `${baseUrl}${separator}month=${year}-${month}`;

            // カレンダー更新＆メニューを閉じる
            updateCalendar(url);
            menuDiv.classList.add("hidden");
        }
    });
</script>
