<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enquiry;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EnquiryController extends Controller
{
    public function index(): View
    {
        return view('admin.enquiries.index', [
            'enquiries' => Enquiry::query()->with('service')->latest('id')->paginate(25),
        ]);
    }

    public function handle(Enquiry $enquiry): RedirectResponse
    {
        $enquiry->forceFill(['handled_at' => $enquiry->handled_at ? null : now()])->save();

        return back()->with('status', $enquiry->handled_at ? 'Marked as handled.' : 'Marked as open.');
    }
}
