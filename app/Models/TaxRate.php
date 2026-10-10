<?php

namespace App\Models;

use Database\Factories\TaxRateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'rate_bps', 'is_active', 'sort_order'])]
class TaxRate extends Model
{
    /** @use HasFactory<TaxRateFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rate_bps' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Human readable percentage, e.g. 500 => "5%", 1250 => "12.5%".
     */
    public function percentLabel(): string
    {
        $whole = intdiv($this->rate_bps, 100);
        $fraction = rtrim(str_pad((string) ($this->rate_bps % 100), 2, '0', STR_PAD_LEFT), '0');

        return $fraction === '' ? "{$whole}%" : "{$whole}.{$fraction}%";
    }
}
