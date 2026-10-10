<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceImage;
use App\Services\ImageUploader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ServiceImageController extends Controller
{
    public function __construct(private ImageUploader $images) {}

    public function store(Request $request, Service $service): RedirectResponse
    {
        $validated = $request->validate([
            'images' => ['required', 'array', 'max:10'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096', 'dimensions:max_width=6000,max_height=6000'],
            'alt' => ['nullable', 'string', 'max:200'],
        ]);

        $nextOrder = (int) $service->images()->max('sort_order') + 1;

        foreach ($request->file('images') as $file) {
            $service->images()->create([
                'path' => $this->images->store($file),
                'alt' => $validated['alt'] ?? $service->name,
                'sort_order' => $nextOrder++,
            ]);
        }

        return to_route('admin.services.edit', $service)->with('status', 'Gallery images uploaded.');
    }

    public function destroy(Service $service, ServiceImage $image): RedirectResponse
    {
        abort_unless($image->service_id === $service->id, 404);

        $this->images->delete($image->path);
        $image->delete();

        return to_route('admin.services.edit', $service)->with('status', 'Image removed.');
    }
}
