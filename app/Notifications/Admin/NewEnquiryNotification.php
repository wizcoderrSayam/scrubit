<?php

namespace App\Notifications\Admin;

use App\Models\Enquiry;
use App\Notifications\TemplatedNotification;

class NewEnquiryNotification extends TemplatedNotification
{
    public function __construct(public Enquiry $enquiry) {}

    protected function templateKey(): string
    {
        return 'admin.new_enquiry';
    }

    protected function values(): array
    {
        return [
            'enquiry_type' => strtolower($this->enquiry->type->label()),
            'customer_name' => $this->enquiry->name,
            'customer_email' => $this->enquiry->email,
            'customer_phone' => $this->enquiry->phone ?? 'Not provided',
            'service' => $this->enquiry->service->name ?? 'Not specified',
            'message' => trim(($this->enquiry->subject ? $this->enquiry->subject."\n\n" : '').($this->enquiry->message ?? '')),
        ];
    }

    protected function actionUrl(): ?string
    {
        return route('admin.enquiries.index');
    }

    protected function actionText(): string
    {
        return 'View enquiries';
    }
}
