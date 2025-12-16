<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-visible relative" style="z-index: 20;">
    
    <div class="bg-gray-50 p-3 flex justify-between items-center">
        
        {{-- 前月へ --}}
        <a href="{{ route('daily.fetch_calendar', ['id' => $post->id ?? null, 'month' => $prevMonth]) }}" 
           class="js-calendar-nav text-gray-400 hover:text-orange-600 hover:bg-white hover:shadow-sm rounded-full p-2 transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </a>

        {{-- ▼▼▼ 年月選択ドロップダウン ▼▼▼ --}}
        <div class="relative">
            {{-- トリガーボタン --}}
            <button type="button" 
                    class="js-toggle-calendar-menu flex items-center gap-2 bg-white border border-gray-300 rounded-lg px-4 py-2 shadow-sm text-gray-700 font-bold text-sm hover:border-orange-400 hover:text-orange-600 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span>{{ $startOfMonth->format('Y年 n月') }}</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>

            {{-- ドロップダウンメニュー本体 --}}
            {{-- id="calendar-menu" でJSから制御、data-base-url にURLを持たせる --}}
            <div id="calendar-menu" 
                 class="hidden absolute top-full left-1/2 -translate-x-1/2 mt-2 w-64 bg-white rounded-xl shadow-xl border border-gray-100 p-4 z-50"
                 data-base-url="{{ route('daily.fetch_calendar', ['id' => $post->id ?? null]) }}">
                
                {{-- 年の切り替え --}}
                <div class="flex items-center justify-between mb-4 border-b border-gray-100 pb-2">
                    <button type="button" class="js-change-year p-1 hover:bg-gray-100 rounded-full text-gray-500" data-val="-1">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                    {{-- 現在の年を保持する要素 --}}
                    <span id="calendar-menu-year" class="font-black text-lg text-gray-800" data-year="{{ $startOfMonth->year }}">
                        {{ $startOfMonth->year }}年
                    </span>
                    <button type="button" class="js-change-year p-1 hover:bg-gray-100 rounded-full text-gray-500" data-val="1">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>

                {{-- 月のグリッド --}}
                <div class="grid grid-cols-4 gap-2">
                    @foreach(range(1, 12) as $m)
                        <button type="button" 
                                class="js-select-month py-2 rounded-lg text-sm font-bold transition {{ $m == $startOfMonth->month ? 'bg-orange-500 text-white shadow-md shadow-orange-200' : 'text-gray-600 hover:bg-orange-50 hover:text-orange-600' }}"
                                data-month="{{ $m }}">
                            {{ $m }}月
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
        {{-- ▲▲▲ ドロップダウンここまで ▲▲▲ --}}

        {{-- 次月へ --}}
        <a href="{{ route('daily.fetch_calendar', ['id' => $post->id ?? null, 'month' => $nextMonth]) }}" 
           class="js-calendar-nav text-gray-400 hover:text-orange-600 hover:bg-white hover:shadow-sm rounded-full p-2 transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
        </a>

    </div>
    
    {{-- Calendar Grid (Unchanged) --}}
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
                $isCurrent = ($post && $post->eaten_at->month == $startOfMonth->month && $post->eaten_at->day == $day);
            @endphp
            <div class="relative aspect-square border-r border-b border-gray-100 group">
                @if($targetPost)
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

{{-- ▼▼▼ Custom Month Picker Modal (Added at the bottom of the included file) ▼▼▼ --}}
<div id="monthPickerModal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    {{-- Backdrop --}}
    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="document.getElementById('monthPickerModal').classList.add('hidden')"></div>

    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-sm w-full max-w-xs">
                
                {{-- Modal Header --}}
                <div class="bg-gray-50 px-4 py-3 sm:px-6 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="text-base font-semibold leading-6 text-gray-900" id="modal-title">年月を選択</h3>
                    <button type="button" class="text-gray-400 hover:text-gray-500" onclick="document.getElementById('monthPickerModal').classList.add('hidden')">
                        <span class="sr-only">Close</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Modal Body --}}
                <div class="px-4 py-4 sm:p-6">
                    {{-- Year Selection --}}
                    <div class="flex items-center justify-between mb-4">
                        <button type="button" class="p-2 hover:bg-gray-100 rounded-full" onclick="changeYear(-1)">
                            <svg class="h-5 w-5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>
                        <span id="modalYearDisplay" class="text-lg font-bold text-gray-900">{{ $startOfMonth->year }}</span>
                        <button type="button" class="p-2 hover:bg-gray-100 rounded-full" onclick="changeYear(1)">
                            <svg class="h-5 w-5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    </div>

                    {{-- Month Grid --}}
                    <div class="grid grid-cols-4 gap-2">
                        @foreach(range(1, 12) as $m)
                            <button type="button" 
                                    class="py-2 rounded-lg text-sm font-medium hover:bg-orange-100 hover:text-orange-700 transition {{ $m == $startOfMonth->month ? 'bg-orange-500 text-white hover:bg-orange-600 hover:text-white' : 'text-gray-700 bg-gray-50' }}"
                                    onclick="selectMonth({{ $m }})">
                                {{ $m }}月
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Simple state management for the modal
    let currentModalYear = {{ $startOfMonth->year }};
    const baseUrl = "{{ route('daily.fetch_calendar', ['id' => $post->id ?? null]) }}";

    function changeYear(delta) {
        currentModalYear += delta;
        document.getElementById('modalYearDisplay').textContent = currentModalYear;
    }

    function selectMonth(month) {
        // Construct YYYY-MM string
        const year = currentModalYear;
        const formattedMonth = month.toString().padStart(2, '0');
        const dateString = `${year}-${formattedMonth}`;

        // Construct URL
        const separator = baseUrl.includes('?') ? '&' : '?';
        const url = `${baseUrl}${separator}month=${dateString}`;

        // Close modal
        document.getElementById('monthPickerModal').classList.add('hidden');

        // Trigger update (assuming updateCalendar is defined in parent)
        if (typeof updateCalendar === 'function') {
            updateCalendar(url);
        } else {
            // Fallback if function not found (e.g. direct load)
            window.location.href = url;
        }
    }
</script>