<x-app-layout title="投稿詳細">
    {{-- ヘッダー（戻るボタンなど） --}}
    <div
        class="bg-white shadow-sm top-0 z-10 px-4 py-3 flex items-center gap-3"
    >
        <button
            onclick="history.back()"
            class="p-2 -ml-2 rounded-full hover:bg-gray-100 transition text-gray-500"
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
                    d="M15 19l-7-7 7-7"
                />
            </svg>
        </button>
        <h1 class="font-bold text-lg text-gray-800">投稿詳細</h1>
    </div>

    {{-- メインコンテンツ（余計な余白pb-32は削除） --}}
    <div class="pb-safe">
        <div class="bg-white pb-6 mb-2">
            {{-- 写真（画面幅いっぱい） --}}
            @if($post->image_path)
            <div class="w-full bg-gray-100">
                <img
                    src="{{ asset($post->image_path) }}"
                    class="w-full max-h-[500px] object-contain mx-auto"
                />
            </div>
            @endif

            <div class="px-4 pt-4">
                {{-- 投稿者情報 --}}
                <div class="flex justify-between items-center mb-4">
                    <a
                        href="{{ route('users.show', $post->user->id) }}"
                        class="flex items-center gap-2"
                    >
                        <div
                            class="h-10 w-10 rounded-full bg-gray-100 overflow-hidden border border-gray-100"
                        >
                            @if($post->user->icon_path)
                            <img
                                src="{{ asset($post->user->icon_path) }}"
                                class="w-full h-full object-cover"
                            />
                            @else
                            <div
                                class="w-full h-full flex items-center justify-center bg-gray-100 text-gray-400 font-bold"
                            >
                                {{ mb_substr($post->user->name, 0, 1) }}
                            </div>
                            @endif
                        </div>
                        <div>
                            <div class="font-bold text-sm text-gray-900">
                                {{ $post->user->name }}
                            </div>
                            <div class="text-xs text-gray-400">
                                {{ $post->eaten_at->format('Y/m/d H:i') }}
                            </div>
                        </div>
                    </a>

                    {{-- 自分の投稿なら編集・削除メニュー（簡易版） --}}
                    @if(Auth::id() === $post->user_id)
                    <div class="relative" x-data="{ open: false }">
                        <button
                            @click="open = !open"
                            class="text-gray-400 hover:text-gray-600 p-2"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-5 w-5"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                            >
                                <path
                                    d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"
                                />
                            </svg>
                        </button>
                        {{-- ドロップダウン --}}
                        <div
                            x-show="open"
                            @click.away="open = false"
                            class="absolute right-0 mt-2 w-32 bg-white rounded-md shadow-lg py-1 border border-gray-100 z-20"
                            style="display: none"
                        >
                            <a
                                href="{{ route('posts.edit', $post) }}"
                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                                >編集する</a
                            >
                            <form
                                action="{{ route('posts.destroy', $post) }}"
                                method="POST"
                                onsubmit="return confirm('本当に削除しますか？');"
                            >
                                @csrf @method('DELETE')
                                <button
                                    type="submit"
                                    class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50"
                                >
                                    削除する
                                </button>
                            </form>
                        </div>
                    </div>
                    @endif
                </div>

                {{-- お店情報カード --}}
                <a
                    href="{{ route('shops.show', $post->shop_id) }}"
                    class="flex items-center gap-3 p-3 rounded-xl bg-orange-50 border border-orange-100 mb-4 active:scale-[0.98] transition"
                >
                    <div
                        class="h-10 w-10 rounded-full bg-white flex items-center justify-center text-orange-500 font-bold border border-orange-100 shadow-sm shrink-0"
                    >
                        📍
                    </div>
                    <div class="min-w-0">
                        <div class="font-bold text-sm text-gray-800 truncate">
                            {{ $post->shop_name }}
                        </div>
                        <div class="text-xs text-gray-500 truncate">
                            {{ $post->shop->address ?? '住所未登録' }}
                        </div>
                    </div>
                    <div class="ml-auto text-gray-400">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-4 w-4"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 5l7 7-7 7"
                            />
                        </svg>
                    </div>
                </a>

                {{-- スコアと感想 --}}
                <div class="mb-6">
                    <div class="flex items-baseline gap-1 text-orange-600 mb-2">
                        <span
                            class="text-3xl font-black"
                            >{{ $post->score }}</span
                        >
                        <span class="text-sm font-bold">点</span>
                    </div>
                    <p class="text-gray-800 leading-relaxed whitespace-pre-wrap text-sm">{{ $post->comment }}</p>
                </div>

                {{-- いいねボタン --}}
                <div class="border-t border-gray-100 pt-3">
                    <div
                        x-data="{ liked: {{ $post->isLikedBy(Auth::user()) ? 'true' : 'false' }}, count: {{ $post->likes->count() }} }"
                    >
                        <button
                            @click="fetch('{{
                                route('posts.like', $post)
                            }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{
                                csrf_token()
                            }}' } }).then(res => res.json()).then(data => { liked = (data.status === 'added'); count = data.count; })"
                            class="flex items-center gap-2 px-4 py-2 rounded-full transition bg-gray-50 hover:bg-gray-100 active:scale-95 w-fit"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-6 w-6 transition-colors"
                                :class="liked ? 'text-pink-500 fill-current' : 'text-gray-400'"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"
                                />
                            </svg>
                            <span
                                class="font-bold text-gray-600"
                                x-text="count"
                            ></span>
                            <span class="text-xs text-gray-400">いいね</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ======================== --}}
        {{-- コメントエリア --}}
        {{-- ======================== --}}
        <div class="px-4 mb-6">
            <h3
                class="font-bold text-gray-600 text-sm mb-3 flex items-center gap-2"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-4 w-4"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"
                    />
                </svg>
                コメント ({{ $post->comments->count() }})
            </h3>

            {{-- コメント一覧 --}}
            <div class="space-y-4">
                @foreach($post->comments as $comment)
                <div class="flex gap-3 py-3 border-b border-gray-100 last:border-b-0">
                        {{-- アイコン（ここを修正） --}}
                        <a href="{{ route('users.show', $comment->user->id) }}" class="shrink-0">
                            <div class="h-9 w-9 rounded-full bg-gray-100 overflow-hidden border border-gray-100">
                                @if($comment->user->icon_path)
                                    <img src="{{ asset($comment->user->icon_path) }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-[10px] font-bold text-gray-400">
                                        {{ mb_substr($comment->user->name, 0, 1) }}
                                    </div>
                                @endif
                            </div>
                        </a>

                    {{-- 右側 --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between mb-1">
                            <div class="flex items-center gap-2">
                                <span
                                    class="font-bold text-sm text-gray-900"
                                    >{{ $comment->user->name }}</span
                                >
                                <span
                                    class="text-[10px] text-gray-400"
                                    >{{ $comment->created_at->diffForHumans() }}</span
                                >
                            </div>
                            {{-- 削除ボタン --}}
                        </div>

                        <p class="text-sm text-gray-800 ...">
                            {{ $comment->body }}
                        </p>
                    </div>
                </div>
                @endforeach @if($post->comments->isEmpty())
                <p class="text-center text-xs text-gray-400 py-4">
                    まだコメントはありません
                </p>
                @endif
            </div>
        </div>

        {{-- コメント入力フォーム（普通の配置） --}}
        {{-- fixed などを全て取り払い、素直にページの一番下に置きました --}}
        <div class="px-4 pb-6">
            <form
                action="{{ route('comments.store', $post) }}"
                method="POST"
                class="flex gap-2 max-w-xl mx-auto"
            >
                @csrf
                <input
                    type="text"
                    name="body"
                    placeholder="コメントを入力..."
                    required
                    autocomplete="off"
                    class="flex-1 bg-gray-100 border border-gray-200 rounded-full px-4 py-3 text-sm focus:ring-2 focus:ring-orange-400 focus:bg-white transition"
                />
                <button
                    type="submit"
                    class="bg-orange-500 text-white p-3 rounded-full font-bold shadow-md hover:bg-orange-600 active:scale-95 transition disabled:opacity-50 shrink-0"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5 rotate-90"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"
                        />
                    </svg>
                </button>
            </form>
        </div>
    </div>
    {{-- メインコンテンツの終わり --}}
</x-app-layout>
