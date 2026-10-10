<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\Payments\PaymentService;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(private PaymentService $payments) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::enum(PaymentStatus::class)],
            'attention' => ['nullable', 'boolean'],
        ]);

        return view('admin.payments.index', [
            'payments' => Payment::query()
                ->with('booking')
                ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
                ->when($request->boolean('attention'), fn ($query) => $query->where('needs_attention', true))
                ->latest('id')
                ->paginate(25)
                ->withQueryString(),
            'filters' => $filters,
            'attentionCount' => Payment::query()->where('needs_attention', true)->count(),
            'statuses' => PaymentStatus::cases(),
        ]);
    }

    public function refund(Request $request, Payment $payment): RedirectResponse
    {
        $amount = $request->validate([
            'amount' => ['required', 'string', 'regex:/^\d{1,7}(\.\d{1,2})?$/'],
        ], ['amount.regex' => 'Enter the refund in dollars, e.g. 50 or 49.99.'])['amount'];

        $this->payments->refund($payment, Money::parse($amount), $request->user());

        return back()->with('status', 'Refund recorded.');
    }

    public function resolve(Payment $payment): RedirectResponse
    {
        $this->payments->resolveAttention($payment);

        return back()->with('status', 'Payment marked as reviewed.');
    }
}
