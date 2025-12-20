<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use NotificationChannels\WebPush\HasPushSubscriptions;
use Illuminate\Support\Carbon;


class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;
    use HasPushSubscriptions;
    

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'icon_path',
        // 'points', // ← これが昔のカラム名なら削除してOK
        'total_score',             // ★追加
        'posts_count',             // ★追加
        'completed_rallies_count', // ★追加
        'secret_answer',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    // ▼▼▼ 追加: 自分が作成したラリー ▼▼▼
    public function myRallies()
    {
        return $this->hasMany(Rally::class);
    }

    // ▼▼▼ 追加: 挑戦中のラリー ▼▼▼
    public function joinedRallies()
    {
        return $this->belongsToMany(Rally::class, 'user_rallies')
                    ->withPivot('is_completed', 'completed_at')
                    ->withTimestamps();
    }

    // このメソッドを追加（または修正）
    public function likedRallies()
    {
        // 第2引数 'rally_likes' が超重要です！
        return $this->belongsToMany(Rally::class, 'rally_likes', 'user_id', 'rally_id');
    }

    // 行きたいお店（ブックマーク）
    public function bookmarks()
    {
        return $this->belongsToMany(Shop::class, 'shop_bookmarks', 'user_id', 'shop_id')
                    ->withTimestamps()
                    ->orderByPivot('created_at', 'desc'); // 追加した順に表示
    }

    public function getStreakDaysAttribute()
    {
        // 1. 投稿日付のリストを取得（重複なし・最新順）
        $dates = $this->posts()
            ->orderBy('eaten_at', 'desc')
            ->get() // 一旦取得してから加工
            ->map(fn($post) => $post->eaten_at->format('Y-m-d'))
            ->unique()
            ->values();

        if ($dates->isEmpty()) return 0;

        // 2. ストリークが「現役」かチェック
        // 最新の投稿が「今日」でも「昨日」でもなければ、記録は途切れているとみなす
        $latestDateStr = $dates[0];
        $latestDate = \Carbon\Carbon::parse($latestDateStr);
        
        $today = \Carbon\Carbon::today();
        $yesterday = \Carbon\Carbon::yesterday();

        if (!$latestDate->isSameDay($today) && !$latestDate->isSameDay($yesterday)) {
            return 0; // 途切れているので表示しない
        }

        // 3. 連続日数をカウント
        $streak = 0;
        // チェックの起点は「最新の投稿日」（今日かもしれないし、昨日かもしれない）
        $checkDate = $latestDate->copy();

        foreach ($dates as $dateStr) {
            $currentPostDate = \Carbon\Carbon::parse($dateStr);

            // 期待する日付と一致していればカウントアップ
            if ($currentPostDate->isSameDay($checkDate)) {
                $streak++;
                // 次のチェック対象を「その前日」に設定
                $checkDate->subDay();
            } else {
                // 日付が飛んだらそこで終了（過去の記録は合算しない）
                break;
            }
        }

        return $streak;
    }

}
