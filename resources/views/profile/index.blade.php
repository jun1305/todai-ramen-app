<x-app-layout title="マイページ">
    @push('styles')
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet">
    @endpush
    @push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
    @endpush



    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 mb-6 text-center relative overflow-hidden">
        
        <a 
            href="{{ route('home') }}" 
            onclick="event.preventDefault(); history.back();"
            class="absolute top-3 left-3 z-20 p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-50 rounded-full transition"
            title="戻る"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </a>
        @if(Auth::id() === $user->id)
        <form action="{{ route('logout') }}" method="POST" class="absolute top-3 right-3 z-20" onsubmit="return confirm('ログアウトしますか？');">
            @csrf
            <button class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-full transition" title="ログアウト">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
            </button>
        </form>
        @endif

        <div class="absolute top-0 left-0 w-full h-24 bg-gradient-to-r from-blue-500 to-cyan-400 opacity-10"></div>

        <div class="relative z-10 mx-auto h-24 w-24 rounded-full bg-white p-1 shadow-md mb-3 
            @if(Auth::id() === $user->id) cursor-pointer group @endif"
            @if(Auth::id() === $user->id) onclick="openModal()" @endif
        >
            <div class="h-full w-full rounded-full overflow-hidden relative">
                @if($user->icon_path)
                    <img src="{{ asset($user->icon_path) }}" loading="lazy" class="w-full h-full object-cover" />
                @else
                    <div class="h-full w-full bg-blue-100 flex items-center justify-center text-blue-600 font-black text-3xl">
                        {{ mb_substr($user->name, 0, 1) }}
                    </div>
                @endif

                @if(Auth::id() === $user->id)
                <div class="absolute inset-0 bg-black/40 hidden group-hover:flex items-center justify-center text-white transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                @endif
            </div>
        </div>

        <div class="flex items-center justify-center gap-2 mb-3 relative z-10">
            <h2 class="text-xl font-black text-gray-800">
                {{ $user->name }}
            </h2>
            
            @if(Auth::id() === $user->id)
            <button onclick="openNameModal()" class="text-gray-400 hover:text-blue-500 transition p-1" title="名前を変更">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                </svg>
            </button>
            @endif
        </div>

        @if(Auth::id() === $user->id)
        {{-- 通知設定ボタン（省略可） --}}
        <div x-data="pushNotifications()" x-init="init()" class="text-center h-10 flex items-center justify-center mb-4">
            {{-- （以前と同じコードが入ります。長くなるので中身はそのまま維持してください） --}}
             <div x-show="loading" class="text-gray-400 text-xs font-bold animate-pulse">処理中...</div>
             <button x-show="!loading" @click="togglePush" x-cloak class="inline-flex items-center gap-2 px-5 py-2 rounded-full font-bold text-xs transition border" :class="isSubscribed ? 'bg-orange-50 text-orange-600 border-orange-200 shadow-sm hover:bg-orange-100' : 'bg-gray-100 text-gray-500 border-transparent hover:bg-gray-200'">
                <span x-show="!isSubscribed" class="flex items-center gap-1">通知を受け取る</span>
                <span x-show="isSubscribed" class="flex items-center gap-1">通知中</span>
             </button>
        </div>
        
        <script>
            window.VAPID_PUBLIC_KEY = "{{ env('VAPID_PUBLIC_KEY') }}";
            function pushNotifications() {
                return {
                    isSubscribed: false, loading: true, errorMessage: '',
                    async init() {
                        if (!('serviceWorker' in navigator) || !('PushManager' in window)) { this.loading = false; return; }
                        try {
                            const reg = await navigator.serviceWorker.ready;
                            const sub = await reg.pushManager.getSubscription();
                            this.isSubscribed = !!sub;
                        } catch (e) { console.error(e); } finally { this.loading = false; }
                    },
                    async togglePush() {
                        this.loading = true;
                        try {
                            const reg = await navigator.serviceWorker.ready;
                            if (this.isSubscribed) {
                                const sub = await reg.pushManager.getSubscription();
                                if (sub) { await sub.unsubscribe(); this.isSubscribed = false; }
                            } else {
                                const sub = await reg.pushManager.subscribe({
                                    userVisibleOnly: true,
                                    applicationServerKey: this.urlBase64ToUint8Array(window.VAPID_PUBLIC_KEY)
                                });
                                await fetch('/push/subscribe', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                                    body: JSON.stringify(sub)
                                });
                                this.isSubscribed = true;
                            }
                        } catch (e) { console.error(e); } finally { this.loading = false; }
                    },
                    urlBase64ToUint8Array(base64String) {
                        const padding = '='.repeat((4 - base64String.length % 4) % 4);
                        const base64 = (base64String + padding).replace(/\-/g, '+').replace(/_/g, '/');
                        const rawData = window.atob(base64);
                        const outputArray = new Uint8Array(rawData.length);
                        for (let i = 0; i < rawData.length; ++i) { outputArray[i] = rawData.charCodeAt(i); }
                        return outputArray;
                    }
                }
            }
        </script>
        @endif

        {{-- ステータス表示エリア --}}
        {{-- ▼▼▼ 修正: gap-4 を gap-2 にして3つ並んでも崩れないように調整 ▼▼▼ --}}
        <div class="flex justify-center gap-2 relative z-10 mt-4 px-2">
            
            {{-- 1. 食べた杯数 --}}
            <div class="flex-1 bg-gray-50 rounded-xl p-2 py-3 border border-gray-100">
                <p class="text-[10px] text-gray-400 font-bold mb-1">食べた杯数</p>
                <p class="text-xl font-black text-gray-800">
                    {{-- カラムから直接表示 --}}
                    {{ number_format($user->posts_count) }}<span class="text-[10px] font-normal ml-0.5">杯</span>
                </p>
            </div>

            {{-- 2. 制覇ラリー --}}
            <div class="flex-1 bg-yellow-50 rounded-xl p-2 py-3 border border-yellow-100">
                <p class="text-[10px] text-yellow-600 font-bold mb-1">制覇ラリー</p>
                <p class="text-xl font-black text-yellow-600">
                    {{-- カラムから直接表示 --}}
                    {{ number_format($user->completed_rallies_count) }}<span class="text-[10px] font-normal ml-0.5">個</span>
                </p>
            </div>

            {{-- 3. 獲得ポイント --}}
            <div class="flex-1 bg-orange-50 rounded-xl p-2 py-3 border border-orange-100">
                <p class="text-[10px] text-orange-400 font-bold mb-1">獲得ポイント</p>
                <p class="text-xl font-black text-orange-600">
                    {{-- ▼▼▼ 修正: 変数 $totalPoints ではなく、カラムを直接読む ▼▼▼ --}}
                    {{ number_format($user->total_score) }}<span class="text-[10px] font-normal ml-0.5">Pt</span>
                </p>
            </div>
        </div>
        
    </div>

    <div class="pb-20">
    <h3 class="font-bold text-gray-500 text-sm mb-4 px-2 flex items-center gap-2">
        <span>📅</span> 麺活ログ
    </h3>

    @foreach($posts as $post)
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden mb-3 flex relative group transition hover:shadow-md">
        
        {{-- カード全体リンク --}}
        <a href="{{ route('posts.show', $post) }}" class="absolute inset-0 z-0"></a>
        
        {{-- ▼▼▼ 修正1: pointer-events-none を追加（これでクリックが貫通して全体リンクが反応するようになります） ▼▼▼ --}}
        <div class="flex-1 p-3 pl-4 relative pointer-events-none">
            
            <div class="flex justify-between items-start mb-1">
                {{-- 日付 --}}
                <p class="text-[10px] text-gray-400 font-bold flex items-center gap-1">
                    {{ $post->eaten_at->format('Y/m/d') }}
                    <span class="text-gray-300">•</span>
                    <span>{{ $post->eaten_at->diffForHumans() }}</span>
                </p>
                
                {{-- 編集・削除ボタン --}}
                @if(Auth::id() === $post->user_id)
                {{-- ▼▼▼ 修正2: ここはクリックさせたいので pointer-events-auto を追加 ▼▼▼ --}}
                <div class="relative z-10 flex items-center gap-1 pointer-events-auto"> 
                    <a href="{{ route('posts.edit', $post) }}" class="text-gray-300 hover:text-blue-500 transition-colors p-1" title="編集">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                        </svg>
                    </a>
                    <form action="{{ route('posts.destroy', $post) }}" method="POST" onsubmit="return confirm('本当に削除してもよろしいですか？');">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-gray-300 hover:text-red-500 transition-colors p-1" title="削除">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </form>
                </div>
                @endif
            </div>

            <h4 class="font-bold text-gray-800 mb-1 line-clamp-1">
                {{-- ▼▼▼ 修正3: 店名リンクも pointer-events-auto を追加 ▼▼▼ --}}
                <a href="{{ route('shops.show', $post->shop->id) }}" class="relative z-10 hover:text-orange-600 hover:underline pointer-events-auto">
                    {{ $post->shop->name }}
                </a>
            </h4>

            {{-- 点数表示 --}}
            <div class="flex items-baseline gap-0.5 text-orange-600 leading-none mb-1">
                <span class="text-xl font-black tracking-tighter">{{ $post->score }}</span>
                <span class="text-[10px] font-bold text-orange-400">点</span>
            </div>

            {{-- コメント --}}
            <p class="text-xs text-gray-500 line-clamp-2 pr-8">
                {{ $post->comment }}
            </p>

            {{-- いいねボタン --}}
            {{-- ▼▼▼ 修正4: いいねボタンも pointer-events-auto を追加 ▼▼▼ --}}
            <div class="absolute bottom-3 right-3 z-10 pointer-events-auto" x-data="{ liked: {{ $post->isLikedBy(Auth::user()) ? 'true' : 'false' }}, count: {{ $post->likes->count() }} }">
                <button @click="fetch('/posts/{{ $post->id }}/like', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } }).then(res => res.json()).then(data => { liked = (data.status === 'added'); count = data.count; })"
                    class="flex items-center gap-1 group p-1 transition active:scale-95">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 transition-colors duration-300" :class="liked ? 'text-red-500 fill-current' : 'text-gray-300 group-hover:text-gray-400'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                    </svg>
                    <span x-show="count > 0" x-text="count" class="text-xs font-bold text-gray-400"></span>
                </button>
            </div>
        </div>

        @if($post->image_path)
        {{-- 画像エリア（元々 pointer-events-none があるのでそのままでOK） --}}
        <div class="w-24 bg-gray-100 shrink-0 pointer-events-none">
            <img src="{{ asset($post->image_path) }}" loading="lazy" class="w-full h-full object-cover" />
        </div>
        @endif
    </div>
    @endforeach

    @if($posts->isEmpty())
    <div class="text-center py-10 text-gray-400">
        まだ記録がありません。<br />今日の一杯を食べに行こう！
    </div>
    @endif

    <div class="mt-8 pb-10">
        {{ $posts->links('vendor.pagination.ramen') }}
    </div> 
</div>

    {{-- 以下、モーダル用コード（変更なし） --}}
    @if(Auth::id() === $user->id)
    <div id="iconModal" class="fixed inset-0 z-50 hidden bg-black/70 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl w-full max-w-md overflow-hidden">
            <div class="p-4 border-b flex justify-between items-center">
                <h3 class="font-bold text-gray-800">アイコンを変更</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">✕</button>
            </div>
            <div class="p-4">
                <input type="file" id="iconInput" accept="image/*" class="block w-full text-sm text-gray-500 mb-4 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
                <div class="h-64 w-full bg-gray-100 rounded-lg overflow-hidden relative mb-4">
                    <img id="imageToCrop" class="hidden max-w-full block" src="" />
                </div>
                <button id="cropButton" class="w-full bg-blue-500 text-white py-2 rounded-lg font-bold hover:bg-blue-600 disabled:opacity-50" disabled>
                    保存する
                </button>
            </div>
        </div>
    </div>

    <div id="nameModal" class="fixed inset-0 z-50 hidden bg-black/70 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl w-full max-w-sm overflow-hidden p-6">
            <h3 class="font-bold text-gray-800 mb-4 text-lg">名前の変更</h3>
            <input type="text" id="newNameInput" value="{{ $user->name }}" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500 px-3 py-2 border mb-4" placeholder="新しい名前" />
            <div class="flex gap-2">
                <button onclick="closeNameModal()" class="flex-1 bg-gray-100 text-gray-600 py-2 rounded-lg font-bold hover:bg-gray-200">キャンセル</button>
                <button onclick="saveName()" class="flex-1 bg-blue-500 text-white py-2 rounded-lg font-bold hover:bg-blue-600">保存</button>
            </div>
        </div>
    </div>

    <script>
        // --- アイコン変更機能 ---
        let cropper;
        const modal = document.getElementById("iconModal");
        const image = document.getElementById("imageToCrop");
        const input = document.getElementById("iconInput");
        const cropBtn = document.getElementById("cropButton");

        function openModal() { modal.classList.remove("hidden"); }
        function closeModal() {
            modal.classList.add("hidden");
            if (cropper) cropper.destroy();
            input.value = "";
            image.src = "";
            image.classList.add("hidden");
        }

        // 画像選択時
        input.addEventListener("change", function (e) {
            const files = e.target.files;
            if (files && files.length > 0) {
                const file = files[0];
                const reader = new FileReader();
                reader.onload = function (e) {
                    image.src = e.target.result;
                    image.classList.remove("hidden");
                    if (cropper) cropper.destroy();
                    
                    cropper = new Cropper(image, {
                        aspectRatio: 1,
                        viewMode: 1,
                        dragMode: "move",
                        autoCropArea: 1,
                    });
                    cropBtn.disabled = false;
                };
                reader.readAsDataURL(file);
            }
        });

        // 保存ボタンクリック（アイコン）
        cropBtn.addEventListener("click", function () {
            const canvas = cropper.getCroppedCanvas({ width: 300, height: 300 });
            canvas.toBlob(function (blob) {
                const formData = new FormData();
                formData.append("icon", blob);
                formData.append("_token", "{{ csrf_token() }}");

                fetch('{{ route("profile.update_icon") }}', {
                    method: "POST",
                    body: formData,
                })
                .then((response) => {
                    if (response.ok) location.reload();
                    else alert("アップロードに失敗しました");
                })
                .catch((error) => console.error("Error:", error));
            });
        });

        // --- 名前変更機能 ---
        const nameModal = document.getElementById("nameModal");
        const newNameInput = document.getElementById("newNameInput");

        function openNameModal() { nameModal.classList.remove("hidden"); }
        function closeNameModal() { nameModal.classList.add("hidden"); }

        // 保存ボタンクリック（名前）
        function saveName() {
            const name = newNameInput.value;
            if (!name) return alert("名前を入力してください");

            const formData = new FormData();
            formData.append("name", name);
            formData.append("_token", "{{ csrf_token() }}");

            fetch('{{ route("profile.update_name") }}', {
                method: "POST",
                body: formData,
            })
            .then((response) => {
                if (response.ok) location.reload();
                else alert("エラーが発生しました");
            })
            .catch((error) => console.error("Error:", error));
        }
    </script>
    @endif
</x-app-layout>