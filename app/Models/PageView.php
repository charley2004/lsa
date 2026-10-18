<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PageView extends Model
{
    protected $fillable = [
        'session_id', 'path', 'referrer', 'device', 'duration_seconds',
    ];

    public function sections(): HasMany
    {
        return $this->hasMany(PageSectionView::class);
    }
}
