<x-app-layout title="丼顔フラッシュ">
    {{-- Alpine.js でゲームの状態を管理 --}}
    <div x-data="quizGame({{ Js::from($questions) }})" class="min-h-[calc(100vh-8rem)] flex flex-col relative pb-safe">
        
        {{-- ======================== --}}
        {{-- ① スタート画面 --}}
        {{-- ======================== --}}
        <div x-show="state === 'start'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-90" x-transition:enter-end="opacity-100 scale-100" class="flex-1 flex flex-col items-center justify-center text-center p-6 space-y-8">
            
            <div class="relative">
                <div class="absolute inset-0 bg-yellow-400 blur-3xl opacity-20 rounded-full"></div>
                <div class="relative bg-white p-6 rounded-3xl shadow-xl border border-yellow-100">
                    <div class="text-6xl mb-2">🧠</div>
                    <h1 class="text-2xl font-black text-gray-800 tracking-wider">
                        丼顔<span class="text-orange-500">フラッシュ</span>
                    </h1>
                    <p class="text-xs text-gray-400 font-bold mt-2">KNOWLEDGE OF RAMEN</p>
                </div>
            </div>

            <div class="space-y-2">
                <p class="text-gray-600 font-bold">全5問！君はいくつわかる？</p>
                <p class="text-xs text-gray-400">画像を見てお店の名前を当ててください</p>
            </div>

            <button @click="startGame" class="w-full max-w-xs bg-gradient-to-r from-orange-500 to-red-500 text-white font-black text-xl py-4 rounded-full shadow-lg hover:shadow-xl active:scale-95 transition duration-200">
                START!
            </button>
        </div>

        {{-- ======================== --}}
        {{-- ② ゲームプレイ画面 --}}
        {{-- ======================== --}}
        <div x-show="state === 'playing'" x-cloak class="flex-1 flex flex-col max-w-md mx-auto w-full px-4 pt-4">
            
            {{-- プログレスバー --}}
            <div class="flex items-center gap-2 mb-4">
                <div class="flex-1 h-2 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full bg-orange-500 transition-all duration-500 ease-out" :style="'width: ' + ((currentIndex + 1) / totalQuestions * 100) + '%'"></div>
                </div>
                <div class="text-xs font-bold text-gray-400">
                    <span x-text="currentIndex + 1"></span> / <span x-text="totalQuestions"></span>
                </div>
            </div>

            {{-- 問題画像エリア --}}
            <div class="relative w-full aspect-square bg-gray-100 rounded-2xl overflow-hidden shadow-inner border border-gray-200 mb-6">
                <img :src="currentQuestion.image_url" class="w-full h-full object-cover">
                
                {{-- 正解・不正解エフェクト（オーバーレイ） --}}
                <div x-show="feedback === 'correct'" x-transition.opacity.duration.200ms class="absolute inset-0 bg-white/80 flex items-center justify-center z-10">
                    <div class="text-9xl animate-bounce">⭕️</div>
                </div>
                <div x-show="feedback === 'wrong'" x-transition.opacity.duration.200ms class="absolute inset-0 bg-black/60 flex flex-col items-center justify-center z-10 text-white p-4 text-center">
                    <div class="text-8xl mb-4 animate-pulse">❌</div>
                    <div class="font-bold text-sm text-gray-300 mb-1">正解は...</div>
                    <div class="font-black text-xl" x-text="currentQuestion.correct_name"></div>
                </div>
            </div>

            {{-- 選択肢ボタンエリア --}}
            <div class="grid grid-cols-1 gap-3">
                <template x-for="(option, index) in currentQuestion.options" :key="index">
                    <button 
                        @click="checkAnswer(option)" 
                        :disabled="feedback !== null"
                        class="bg-white border-2 border-gray-100 text-gray-700 font-bold py-3.5 px-4 rounded-xl shadow-sm text-sm active:scale-[0.98] transition hover:bg-orange-50 hover:border-orange-200 disabled:opacity-50"
                        x-text="option.name"
                    ></button>
                </template>
            </div>
        </div>

        {{-- ======================== --}}
        {{-- ③ 結果発表画面 --}}
        {{-- ======================== --}}
        <div x-show="state === 'result'" x-cloak class="flex-1 flex flex-col items-center justify-center text-center p-6 space-y-8">
            
            <div class="text-center">
                <div class="text-sm font-bold text-gray-400 mb-2">SCORE</div>
                <div class="text-6xl font-black text-gray-800 mb-2">
                    <span class="text-orange-500" x-text="score"></span><span class="text-2xl text-gray-300">/5</span>
                </div>
                <div class="text-xl font-bold text-gray-600" x-text="getResultMessage()"></div>
            </div>

            <div class="space-y-3 w-full max-w-xs">
                <button @click="location.reload()" class="w-full bg-orange-500 text-white font-bold py-3 rounded-full shadow-md hover:bg-orange-600 active:scale-95 transition">
                    もう一度遊ぶ
                </button>
                <a href="/" class="block w-full bg-gray-100 text-gray-600 font-bold py-3 rounded-full hover:bg-gray-200 active:scale-95 transition">
                    ホームに戻る
                </a>
            </div>
        </div>

    </div>

    {{-- ゲームロジック (Alpine.js) --}}
    <script>
        function quizGame(questionsData) {
            return {
                state: 'start', // start, playing, result
                questions: questionsData,
                currentIndex: 0,
                score: 0,
                feedback: null, // null, correct, wrong

                get totalQuestions() {
                    return this.questions.length;
                },

                get currentQuestion() {
                    return this.questions[this.currentIndex];
                },

                startGame() {
                    this.state = 'playing';
                },

                checkAnswer(option) {
                    if (this.feedback !== null) return; // 連打防止

                    if (option.is_correct) {
                        this.score++;
                        this.feedback = 'correct';
                        // 正解音を入れたい場合はここで new Audio(...).play()
                    } else {
                        this.feedback = 'wrong';
                    }

                    // 少し待って次の問題へ
                    setTimeout(() => {
                        this.nextQuestion();
                    }, 1200); // 1.2秒後に次へ
                },

                nextQuestion() {
                    this.feedback = null;
                    if (this.currentIndex < this.totalQuestions - 1) {
                        this.currentIndex++;
                    } else {
                        this.state = 'result';
                    }
                },

                getResultMessage() {
                    if (this.score === 5) return '👑 神の領域！';
                    if (this.score === 4) return '🍜 かなりのラーメン通！';
                    if (this.score >= 2) return '👍 なかなかのもの！';
                    return '🔰 まだまだ修行が必要！';
                }
            }
        }
    </script>
</x-app-layout>