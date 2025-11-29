<x-app-layout title="裏管理画面">
    <div class="p-4 pb-32">
        <h1 class="text-2xl font-bold mb-6 text-red-600 flex items-center gap-2">
            管理者専用ページ
        </h1>

        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 mb-8">
            <h2 class="font-bold mb-4 text-lg">キャンペーンを開始する</h2>
            
            <form action="{{ route('admin.campaigns.store') }}" method="POST" class="space-y-4">
                @csrf
                
                <div>
                    <label class="block text-sm font-bold mb-1 text-gray-700">対象店舗</label>
                    <select name="shop_id" class="w-full p-3 border border-gray-300 rounded-lg bg-gray-50 focus:ring-2 focus:ring-red-200 focus:border-red-400 outline-none">
                        @foreach($shops as $shop)
                            <option value="{{ $shop->id }}">{{ $shop->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-bold mb-1 text-gray-700">見出し（任意）</label>
                    <input type="text" name="title" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-200 outline-none" placeholder="店名など（空欄なら店名が自動で入ります）">
                </div>

                <button class="bg-red-600 text-white px-4 py-3 rounded-lg font-bold w-full hover:bg-red-700 transition shadow-md flex items-center justify-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd" />
                    </svg>
                    キャンペーン開始（Pt2倍）
                </button>
            </form>
        </div>

        <h2 class="font-bold mb-4 text-lg border-b pb-2 flex items-center gap-2">
            <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
            現在開催中
        </h2>
        
        <div class="space-y-4">
            @foreach($campaigns as $campaign)
                <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200 flex justify-between items-center group">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="bg-red-100 text-red-600 text-xs font-bold px-2 py-0.5 rounded border border-red-200">
                                Pt 2倍
                            </span>
                            <p class="font-bold text-gray-800 text-lg">{{ $campaign->title }}</p>
                        </div>
                        <p class="text-xs text-gray-400">
                            {{ $campaign->created_at->format('m/d H:i') }} から開催中
                        </p>
                    </div>

                    <form action="{{ route('admin.campaigns.destroy', $campaign->id) }}" method="POST" onsubmit="return confirm('このキャンペーンを終了（削除）しますか？');">
                        @csrf
                        @method('DELETE')
                        <button class="bg-gray-100 hover:bg-red-50 text-gray-500 hover:text-red-500 font-bold p-3 rounded-lg transition border border-gray-200" title="終了する">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </form>
                </div>
            @endforeach

            @if($campaigns->isEmpty())
                <div class="text-center py-8 bg-gray-50 rounded-xl border border-dashed border-gray-300">
                    <p class="text-gray-400 text-sm">現在開催中のキャンペーンはありません</p>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>