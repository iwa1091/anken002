<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

// MustVerifyEmail インターフェースを実装することで、Laravelのメール認証機能が有効になります。
class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     * 一括代入可能な属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id', // Roleモデルとのリレーションを考慮し、外部キーとして role_id を追加
        // 'department', // 現時点の要件には明示されていないため、必要に応じて追加を検討
    ];

    /**
     * The attributes that should be hidden for serialization.
     * 属性を隠す（シリアライズ時）
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     * キャスト対象の属性
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Get the attendances for the user.
     * 勤怠とのリレーション
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Determine if the user is an administrator.
     * 管理者かどうかを判定
     */
    public function isAdmin(): bool
    {
        // Roleモデルのリレーションを利用して判定
        // Roleモデルに 'admin' という name があることを想定
        return $this->role && $this->role->name === 'admin';
    }

    /**
     * Get the role that owns the user.
     * ロールとのリレーション
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}
