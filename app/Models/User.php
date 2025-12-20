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
        // ユーザーの投稿日付（重複なし）を最新順に取得
        $dates = $this->posts()
            ->selectRaw('DATE(eaten_at) as date')
            ->orderBy('date', 'desc')
            ->distinct() // 1日2回投稿しても1日とカウント
            ->pluck('date'); // Collectionとして取得

        if ($dates->isEmpty()) return 0;

        $streak = 0;
        
        // 今日の日付
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        // 最新の投稿日が「今日」か「昨日」でなければ、ストリークは途切れている
        $latestDate = Carbon::parse($dates->first());
        if (!$latestDate->isSameDay($today) && !$latestDate->isSameDay($yesterday)) {
            return 0;
        }

        // 連続チェック
        // ※ 最新の日付から遡って、1日ずつ空いてないかチェック
        // ただし、起点を「今日」にするか「最新投稿日」にするかでロジックが変わる。
        // 一般的には「最新投稿日」を1日目として遡る。
        
        $currentCheckDate = $latestDate->copy();

        foreach ($dates as $dateString) {
            $postDate = Carbon::parse($dateString);

            if ($postDate->isSameDay($currentCheckDate)) {
                $streak++;
                $currentCheckDate->subDay(); // 1日戻して次をチェック
            } else {
                // 日付が飛んだら終了
                break;
            }
        }

        return $streak;
    }

}
