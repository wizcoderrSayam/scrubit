<?php

namespace App\Services\Chat;

use App\Enums\AddonPricingMethod;
use App\Enums\AddonSource;
use App\Enums\BookingStatus;
use App\Enums\MessageSender;
use App\Enums\MessageType;
use App\Enums\RecurringFrequency;
use App\Enums\TimeWindow;
use App\Exceptions\BookingActionException;
use App\Models\Booking;
use App\Models\BookingMessage;
use App\Models\ServiceAddon;
use App\Models\User;
use App\Services\AvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Orchestrates the customization conversation and stores every message.
 */
class ChatService
{
    public function __construct(
        private ChatResponder $responder,
        private AvailabilityService $availability,
    ) {}

    /**
     * Seed a new draft with the greeting and first question.
     */
    public function start(Booking $booking): void
    {
        $booking->forceFill(['chat_state' => ['needs_done' => false, 'extras_done' => false]])->save();

        $this->assistant($booking, $this->responder->greeting($booking));
        $this->assistant($booking, $this->responder->promptFor('needs', $booking));
    }

    /**
     * @return list<BookingMessage>
     */
    public function postText(Booking $booking, string $text, ?User $user): array
    {
        $this->ensureDraft($booking);

        return DB::transaction(function () use ($booking, $text, $user): array {
            $this->lockDraft($booking);
            $step = $this->currentStep($booking);
            $created = [$this->customer($booking, $text, $user)];

            [$reply, $completed] = $this->responder->replyToText($step, $text, $booking);

            if ($completed) {
                $this->markState($booking, "{$step}_done", true);
            }

            if ($reply !== null) {
                $created[] = $this->assistant($booking, $reply);
            }

            if ($completed) {
                $created[] = $this->assistant($booking, $this->responder->promptFor($this->currentStep($booking), $booking));
            }

            return $created;
        });
    }

    /**
     * Apply a structured widget answer (schedule, address, contact...).
     *
     * @param  array<string, mixed>  $data
     * @return list<BookingMessage>
     *
     * @throws ValidationException
     */
    public function submitWidget(Booking $booking, string $step, array $data, ?User $user): array
    {
        $this->ensureDraft($booking);

        if (! in_array($step, $this->responder->steps($booking), true) || in_array($step, ScriptedResponder::TEXT_STEPS, true)) {
            throw ValidationException::withMessages(['step' => 'This step is not part of your booking.']);
        }

        $previousStep = $this->currentStep($booking);
        $wasEditing = $previousStep !== $step;

        return DB::transaction(function () use ($booking, $step, $data, $user, $wasEditing): array {
            $this->lockDraft($booking);

            $summary = match ($step) {
                'schedule' => $this->applySchedule($booking, $data),
                'details' => $this->applyDetails($booking, $data, $user),
                'extras' => $this->applyExtras($booking, $data),
            };

            $created = [$this->customer($booking, $summary, $user, MessageType::Widget, ['step' => $step])];
            $next = $this->currentStep($booking);
            $created[] = $this->assistant($booking, $wasEditing && $next === 'done'
                ? 'Thanks, your booking has been updated.'
                : $this->responder->promptFor($next, $booking));

            return $created;
        });
    }

    /**
     * The first unanswered step, or "done".
     */
    public function currentStep(Booking $booking): string
    {
        $booking->loadMissing(['service', 'address']);

        foreach ($this->responder->steps($booking) as $step) {
            if (! $this->isStepComplete($booking, $step)) {
                return $step;
            }
        }

        return 'done';
    }

    public function isComplete(Booking $booking): bool
    {
        return $this->currentStep($booking) === 'done';
    }

    public function isStepComplete(Booking $booking, string $step): bool
    {
        $state = (array) $booking->chat_state;

        return match ($step) {
            'needs', 'extras' => (bool) ($state["{$step}_done"] ?? false),
            'schedule' => $booking->preferred_date !== null
                && $booking->time_window !== null
                && (! $booking->service->allows_recurring || $booking->frequency !== null),
            'details' => $booking->address !== null
                && filled($booking->contact_name) && filled($booking->contact_email) && filled($booking->contact_phone),
            default => true,
        };
    }

    /**
     * Data the chat UI needs to render the current state.
     *
     * @return array{step: string, steps: list<string>, complete: bool}
     */
    public function state(Booking $booking): array
    {
        return [
            'step' => $this->currentStep($booking),
            'steps' => $this->responder->steps($booking),
            'complete' => $this->isComplete($booking),
        ];
    }

    /**
     * Preferred date, time window and (for recurring-capable services) frequency, all in one step.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    private function applySchedule(Booking $booking, array $data): string
    {
        $date = CarbonImmutable::createFromFormat('Y-m-d', $data['preferred_date'])->startOfDay();
        $window = TimeWindow::from($data['time_window']);

        if ($reason = $this->availability->unavailableReason($date, $window)) {
            throw ValidationException::withMessages(['preferred_date' => $reason]);
        }

        $attributes = ['preferred_date' => $date, 'time_window' => $window];
        $summary = 'Preferred visit: '.$date->format('l, F j, Y').', '.$window->label();

        if ($booking->service->allows_recurring) {
            if (blank($data['frequency'] ?? null)) {
                throw ValidationException::withMessages(['frequency' => 'Please choose how often you would like us to come.']);
            }

            $frequency = RecurringFrequency::from($data['frequency']);
            $attributes['frequency'] = $frequency;
            $summary .= ' · Frequency: '.$frequency->label();
        }

        $booking->forceFill($attributes)->save();

        return $summary;
    }

    /**
     * Service address and contact details, combined into a single step.
     *
     * @param  array<string, mixed>  $data
     */
    private function applyDetails(Booking $booking, array $data, ?User $user): string
    {
        $fields = [
            'line1' => $data['line1'],
            'line2' => $data['line2'] ?? null,
            'city' => $data['city'],
            'province' => $data['province'],
            'postal_code' => strtoupper(trim($data['postal_code'])),
            'country' => 'CA',
            'notes' => $data['notes'] ?? null,
        ];

        $address = $booking->address()->updateOrCreate([], $fields);
        $booking->setRelation('address', $address);

        if ($user !== null && ! empty($data['save_address'])) {
            $user->addresses()->firstOrCreate(
                ['line1' => $fields['line1'], 'postal_code' => $fields['postal_code']],
                [...$fields, 'label' => 'Saved address', 'is_default' => ! $user->addresses()->exists()],
            );
        }

        $booking->forceFill([
            'contact_name' => $data['contact_name'],
            'contact_email' => strtolower($data['contact_email']),
            'contact_phone' => $data['contact_phone'],
        ])->save();

        return 'Service address: '.$address->oneLine()
            ."\nContact: {$booking->contact_name}, {$booking->contact_email}, {$booking->contact_phone}";
    }

    /**
     * Snapshot selected add-ons. Free-text extras are flagged for a quote and never priced here.
     *
     * @param  array<string, mixed>  $data
     */
    private function applyExtras(Booking $booking, array $data): string
    {
        $selectedIds = array_map('intval', (array) ($data['addons'] ?? []));
        $available = $booking->service->addons()->active()->whereIn('service_addons.id', $selectedIds)->get();

        if ($available->count() !== count(array_unique($selectedIds))) {
            throw ValidationException::withMessages(['addons' => 'One of the selected add-ons is no longer available.']);
        }

        $booking->addons()->delete();

        foreach ($available as $addon) {
            /** @var ServiceAddon $addon */
            $priced = $addon->isPriced();
            $booking->addons()->create([
                'service_addon_id' => $addon->id,
                'name' => $addon->name,
                'description' => $addon->description,
                'pricing_method' => $addon->pricing_method,
                'unit_price_cents' => $priced ? $addon->price_cents : null,
                'quantity' => 1,
                'line_total_cents' => $priced ? $addon->price_cents : null,
                'needs_quote' => ! $priced,
                'source' => AddonSource::Selected,
            ]);
        }

        $extraRequest = trim((string) ($data['extra_request'] ?? ''));

        if ($extraRequest !== '') {
            $booking->addons()->create([
                'name' => 'Additional request',
                'description' => $extraRequest,
                'pricing_method' => AddonPricingMethod::Quote,
                'quantity' => 1,
                'needs_quote' => true,
                'source' => AddonSource::ChatFlag,
            ]);
        }

        $booking->unsetRelation('addons');
        $this->markState($booking, 'extras_done', true);

        $parts = $available->pluck('name')->all();

        if ($extraRequest !== '') {
            $parts[] = "Additional request (to be quoted): {$extraRequest}";
        }

        return $parts === [] ? 'No add-ons needed.' : 'Add-ons: '.implode('; ', $parts);
    }

    private function markState(Booking $booking, string $key, bool $value): void
    {
        $state = (array) $booking->chat_state;
        $state[$key] = $value;
        $booking->forceFill(['chat_state' => $state])->save();
    }

    /**
     * @param  array<string, mixed>|null  $meta
     */
    private function customer(Booking $booking, string $body, ?User $user, MessageType $type = MessageType::Text, ?array $meta = null): BookingMessage
    {
        return $booking->messages()->create([
            'sender' => MessageSender::Customer,
            'type' => $type,
            'body' => $body,
            'user_id' => $user?->getKey(),
            'meta' => $meta,
        ]);
    }

    private function assistant(Booking $booking, string $body): BookingMessage
    {
        return $booking->messages()->create([
            'sender' => MessageSender::Assistant,
            'type' => MessageType::Text,
            'body' => $body,
        ]);
    }

    /**
     * @throws BookingActionException
     */
    private function ensureDraft(Booking $booking): void
    {
        if ($booking->status !== BookingStatus::Draft) {
            throw new BookingActionException('This booking has already been submitted and can no longer be changed here.');
        }
    }

    /**
     * Lock the booking row and re-check it is still a draft (guards against a concurrent submit).
     *
     * @throws BookingActionException
     */
    private function lockDraft(Booking $booking): void
    {
        $locked = Booking::query()->lockForUpdate()->findOrFail($booking->getKey());
        $booking->forceFill(['chat_state' => $locked->chat_state])->syncOriginalAttribute('chat_state');

        $this->ensureDraft($locked);
    }
}
