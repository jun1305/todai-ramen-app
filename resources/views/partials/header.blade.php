<header class="bg-slate-900 text-white py-3 px-5 shadow-lg sticky top-0 z-30 flex justify-between items-center shrink-0">
    
    <div class="flex items-center gap-3">
        <div class="bg-white/10 p-1.5 rounded-full backdrop-blur-sm border border-white/10">
            <span class="text-xl filter drop-shadow-md block leading-none">🍜</span>
        </div>
        <h1 class="text-lg font-black tracking-wider text-white">
            東大ラーメンログ
        </h1>
    </div>
    
    @auth
    <div class="relative" 
         x-data="{ 
             open: false,
             // PHPから「未読があるか？」を受け取ってJSの変数にする
             hasUnread: {{ Auth::check() && Auth::user()->unreadNotifications->count() > 0 ? 'true' : 'false' }},
             
             // 読み込み処理
             markAsRead() {
                 if (this.hasUnread) {
                     // 裏側で既読にする
                     fetch('/notifications/read', { 
                         method: 'POST',
                         headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                     });
                     // 見た目はすぐに赤丸を消す
                     this.hasUnread = false;
                 }
             }
         }">
        
        <button @click="open = !open; markAsRead()" class="relative p-2 text-gray-300 hover:text-white hover:bg-white/10 rounded-full transition duration-300">
            
            <span x-show="hasUnread" 
                  x-transition.opacity
                  class="absolute top-1.5 right-1.5 h-2.5 w-2.5 rounded-full bg-red-500 border-2 border-slate-900 shadow-sm animate-pulse"></span>
            
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
        </button>

        <div x-show="open" 
             @click.away="open = false"
             style="display: none;"
             class="absolute right-0 mt-2 w-72 bg-white rounded-xl shadow-xl border border-gray-100 overflow-hidden text-gray-800 z-50">
            
            <div class="px-4 py-2 bg-gray-50 border-b border-gray-100 font-bold text-xs text-gray-500">
                お知らせ
            </div>

            <div class="max-h-64 overflow-y-auto">
                @auth
                    @forelse(Auth::user()->notifications as $notification)
                        <div class="p-3 border-b border-gray-50 hover:bg-orange-50 transition {{ $notification->read_at ? 'opacity-50 bg-gray-50' : 'bg-blue-50' }}">
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
                    @endforelse
                @endauth
            </div>
        </div>

    </div>
    @endauth
</header>