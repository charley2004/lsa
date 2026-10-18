<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class BookmakerOffer extends Model
{
    protected $fillable = [
        'name', 'slug', 'affiliate_url', 'cta_text', 'offer_detail',
        'logo_path', 'display_order', 'active', 'click_count',
    ];

    protected $casts = [
        'active' => 'boolean',
        'display_order' => 'integer',
        'click_count' => 'integer',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true)->orderBy('display_order');
    }
}
