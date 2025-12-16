<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="bg-gray-100 p-3 text-center font-bold text-gray-700 flex justify-between items-center">
        
        {{-- 前月へ（クラス js-calendar-nav を追加） --}}
        <a href="{{ route('daily.fetch_calendar', ['id' => $post->id ?? null, 'month' => $prevMonth]) }}" 
           class="js-calendar-nav text-gray-400 hover:text-orange-600 transition p-2">
            ◀
        </a>

        <span>{{ $startOfMonth->format('Y年 n月') }}</span>

        {{-- 次月へ（クラス js-calendar-nav を追加） --}}
        <a href="{{ route('daily.fetch_calendar', ['id' => $post->id ?? null, 'month' => $nextMonth]) }}" 
           class="js-calendar-nav text-gray-400 hover:text-orange-600 transition p-2">
            ▶
        </a>

    </div>
    
    <div class="grid grid-cols-7 border-b border-gray-200 text-center text-xs text-gray-500 font-bold bg-gray-50">
        <div class="py-2 text-red-400">日</div>
        <div class="py-2">月</div>
        <div class="py-2">火</div>
        <div class="py-2">水</div>
        <div class="py-2">木</div>
        <div class="py-2">金</div>
        <div class="py-2 text-blue-400">土</div>
    </div>
    
    <div class="grid grid-cols-7 bg-white">
        @for ($i = 0; $i < $startOfMonth->dayOfWeek; $i++)
            <div class="aspect-square border-r border-b border-gray-100"></div>
        @endfor
        @for ($day = 1; $day <= $startOfMonth->daysInMonth; $day++)
            @php
                $targetPost = $monthlyPosts[$day] ?? null;
                // $postが存在する場合のみ判定
                $isCurrent = ($post && $post->eaten_at->month == $startOfMonth->month && $post->eaten_at->day == $day);
            @endphp
            <div class="relative aspect-square border-r border-b border-gray-100 group">
                @if($targetPost)
                    {{-- 記事へのリンクは通常遷移のまま --}}
                    <a href="{{ route('daily.index', ['id' => $targetPost->id]) }}" class="block w-full h-full relative">
                        <img src="{{ asset($targetPost->image_path) }}" class="w-full h-full object-cover {{ $isCurrent ? 'opacity-40' : 'hover:opacity-80 transition' }}">
                        <span class="absolute inset-0 flex items-center justify-center text-lg font-black text-white drop-shadow-md" style="text-shadow: 0 2px 4px rgba(0,0,0,0.8);">{{ $day }}</span>
                        @if($isCurrent)
                            <div class="absolute inset-0 border-4 border-orange-500"></div>
                        @endif
                    </a>
                @else
                    <div class="w-full h-full flex items-center justify-center text-gray-300 text-sm">{{ $day }}</div>
                @endif
            </div>
        @endfor
    </div>
</div>