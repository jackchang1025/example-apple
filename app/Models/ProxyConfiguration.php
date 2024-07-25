<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProxyConfiguration extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'configuration', 'is_active'];

    protected $casts = [
        'configuration' => 'array',
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            if ($model->is_active) {
                // 如果当前模型被设置为活动状态，将其他所有配置设置为非活动状态
                static::where('id', '!=', $model->id)->update(['is_active' => false]);
            }
        });
    }
}
