<?php

namespace App\Models;

use Database\Factories\BookingAddressFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['line1', 'line2', 'city', 'province', 'postal_code', 'country', 'notes'])]
class BookingAddress extends Model
{
    /** @use HasFactory<BookingAddressFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function oneLine(): string
    {
        return collect([$this->line1, $this->line2, $this->city, $this->province, $this->postal_code])->filter()->implode(', ');
    }
}
