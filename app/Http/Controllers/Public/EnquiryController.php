<?php

namespace App\Http\Controllers\Public;

use App\Enums\EnquiryType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StoreEnquiryRequest;
use App\Models\Enquiry;
use App\Models\Service;
use App\Notifications\Admin\NewEnquiryNotification;
use App\Services\BookingNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EnquiryController extends Controller
{
    public function __construct(private BookingNotifier $notifier) {}

    public function contact(): View
    {
        return view('pages.contact');
    }

    public function quote(): View
    {
        return view('pages.request-quote', [
            'services' => Service::query()->active()->ordered()->get(['id', 'name', 'slug']),
        ]);
    }

    public function storeContact(StoreEnquiryRequest $request): RedirectResponse
    {
        $this->store($request, EnquiryType::Contact);

        return to_route('contact')->with('status', 'Thanks for getting in touch. We will reply as soon as possible.');
    }

    public function storeQuote(StoreEnquiryRequest $request): RedirectResponse
    {
        $this->store($request, EnquiryType::Quote);

        return to_route('quote.create')->with('status', 'Thanks! Your quote request has been received. We will be in touch shortly.');
    }

    private function store(StoreEnquiryRequest $request, EnquiryType $type): void
    {
        $enquiry = new Enquiry($request->safe()->only(['name', 'email', 'phone', 'subject', 'service_id', 'message']));
        $enquiry->type = $type;
        $enquiry->save();

        $this->notifier->admins(new NewEnquiryNotification($enquiry));
    }
}
