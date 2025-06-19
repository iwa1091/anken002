<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     * 一括代入可能な属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',        // 例: admin, manager, staff
        // 'description', // 例: 管理者、部門長、一般社員などの説明
    ];

    /**
     * ユーザーとのリレーション
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
