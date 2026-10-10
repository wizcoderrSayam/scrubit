<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use App\Services\EmailTemplateRenderer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailTemplateController extends Controller
{
    public function index(): View
    {
        return view('admin.email-templates.index', ['templates' => EmailTemplate::query()->orderBy('key')->get()]);
    }

    public function edit(EmailTemplate $emailTemplate): View
    {
        preg_match_all('/\{\{(\w+)\}\}/', ($emailTemplate->subject ?? '').' '.(EmailTemplateRenderer::DEFAULTS[$emailTemplate->key]['body'] ?? $emailTemplate->body), $matches);

        return view('admin.email-templates.form', [
            'template' => $emailTemplate,
            'placeholders' => array_values(array_unique([...$matches[1], 'business_name', 'business_phone', 'business_email'])),
        ]);
    }

    public function update(Request $request, EmailTemplate $emailTemplate): RedirectResponse
    {
        $emailTemplate->update($request->validate([
            'subject' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:10000'],
        ]));

        return to_route('admin.email-templates.index')->with('status', 'Email template saved.');
    }

    public function reset(EmailTemplate $emailTemplate): RedirectResponse
    {
        $defaults = EmailTemplateRenderer::DEFAULTS[$emailTemplate->key] ?? null;
        abort_if($defaults === null, 404);

        $emailTemplate->update(['subject' => $defaults['subject'], 'body' => $defaults['body']]);

        return to_route('admin.email-templates.edit', $emailTemplate)->with('status', 'Template restored to the default wording.');
    }
}
