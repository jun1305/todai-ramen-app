<x-app-layout title="まだ記録はありません">
    {{-- 
        修正ポイント: 
        1. h-[calc(100vh-80px)] : 画面の高さからヘッダー分(約80px)を引いて、スクロールを出さない
        2. justify-center + pb-20 : 垂直方向は中央揃えにしつつ、下の余白を多く取ることで「少し上」に見せる
    --}}
    <div class="h-[calc(100vh-80px)] flex flex-col items-center justify-center pb-20 bg-gray-50 text-center p-4">
        
        <div class="bg-white p-10 rounded-3xl shadow-xl max-w-md w-full">
            <div class="text-8xl mb-6">🍜</div>
            <h1 class="text-2xl font-black text-gray-800 mb-6">まだ記録はありません</h1>

            @if(Auth::id() === 1)
                {{-- ▼ 会長（自分）が見た場合 ▼ --}}
                <p class="text-gray-500 mb-8 font-bold">
                    会長、お待ちしておりました。<br>
                    最初の一杯を記録して伝説を始めましょう！
                </p>
                <a href="{{ route('daily.create') }}" 
                   class="block w-full bg-slate-900 text-white font-bold py-4 rounded-xl shadow-lg hover:scale-105 transition flex items-center justify-center gap-2">
                   <span>🚀</span> 最初の一杯を記録する
                </a>
            @else
                {{-- ▼ 一般ユーザーが見た場合 ▼ --}}
                <div class="space-y-4 font-bold text-gray-600">
                    <div class="bg-orange-50 text-orange-600 p-4 rounded-xl border border-orange-100">
                        <p class="mb-1 text-sm text-orange-400">STATUS</p>
                        <p class="text-lg">会長の投稿待ち...</p>
                    </div>
                    <p class="text-sm text-gray-400">
                        おいしい一杯が更新されるのを<br>
                        楽しみに待ちましょう 🧘
                    </p>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>