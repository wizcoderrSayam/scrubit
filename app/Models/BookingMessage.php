<?php

namespace App\Models;

use App\Enums\MessageSender;
use App\Enums\MessageType;
use Database\Factories\BookingMessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A message in a booking conversation. Written only by ChatService and admin note actions.
 */
#[Fillable(['user_id', 'sender', 'type', 'body', 'meta'])]
class BookingMessage extends Model
{
    /** @use HasFactory<BookingMessageFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sender' => MessageSender::class,
            'type' => MessageType::class,
            'meta' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array{id: int, sender: string, type: string, body: string, meta: array<string, mixed>|null, time: string}
     */
    public function toChatArray(): array
    {
        return [
            'id' => $this->id,
            'sender' => $this->sender->value,
            'type' => $this->type->value,
            'body' => $this->body,
            'meta' => $this->meta,
            'time' => $this->created_at?->format('g:i a') ?? '',
        ];
    }
}
