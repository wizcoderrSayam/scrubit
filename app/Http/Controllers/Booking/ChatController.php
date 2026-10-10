<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\ChatMessageRequest;
use App\Http\Requests\Booking\ChatWidgetRequest;
use App\Models\Booking;
use App\Models\BookingMessage;
use App\Services\BookingDraftService;
use App\Services\Chat\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function __construct(
        private BookingDraftService $drafts,
        private ChatService $chat,
    ) {}

    public function message(ChatMessageRequest $request, Booking $booking): JsonResponse
    {
        $this->authorizeDraft($booking, $request);

        $messages = $this->chat->postText($booking, $request->validated('message'), $request->user());

        return $this->respond($booking, $messages);
    }

    public function step(ChatWidgetRequest $request, Booking $booking, string $step): JsonResponse
    {
        $this->authorizeDraft($booking, $request);

        $messages = $this->chat->submitWidget($booking, $step, $request->validated(), $request->user());

        return $this->respond($booking->refresh(), $messages);
    }

    private function authorizeDraft(Booking $booking, Request $request): void
    {
        abort_unless($this->drafts->canAccess($booking, $request), 404);
    }

    /**
     * @param  list<BookingMessage>  $messages
     */
    private function respond(Booking $booking, array $messages): JsonResponse
    {
        return response()->json([
            'messages' => array_map(fn (BookingMessage $message) => $message->toChatArray(), $messages),
            'state' => $this->chat->state($booking),
        ]);
    }
}
