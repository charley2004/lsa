<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageSectionView extends Model
{
    protected $fillable = ['page_view_id', 'section', 'seconds'];

    public function pageView(): BelongsTo
    {
        return $this->belongsTo(PageView::class);
    }
}
