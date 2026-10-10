@php
    $isNew = ! $service->exists;
    $chat = (array) $service->chat_script;
    $selectedAddons = old('addons', $service->exists ? $service->addons->pluck('id')->all() : []);
@endphp

<x-layouts.admin :title="$isNew ? 'New service' : 'Edit '.$service->name">
    <form method="POST" action="{{ $isNew ? route('admin.services.store') : route('admin.services.update', $service) }}" enctype="multipart/form-data" class="grid gap-6 xl:grid-cols-3"
        x-data="{ method: @js(old('pricing_method', $service->pricing_method?->value ?? 'custom_quote')) }">
        @csrf
        @unless ($isNew) @method('PUT') @endunless

        <div class="space-y-6 xl:col-span-2">
            <x-ui.card title="Basics">
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-ui.input name="name" label="Service name" :value="$service->name" required />
                    <x-ui.input name="slug" label="URL slug" :value="$service->slug" hint="Leave blank to generate from the name. Used in /services/{slug}." />
                    <x-ui.select name="service_category_id" label="Category" :options="$categoryOptions" :value="$service->service_category_id" placeholder="Choose a category" required />
                    <x-ui.input name="sort_order" label="Sort order" type="number" min="0" :value="$service->sort_order ?? 0" />
                    <x-ui.textarea name="short_description" label="Short description" :value="$service->short_description" rows="2" required class="sm:col-span-2" hint="Shown on service cards and as the chat introduction." />
                    <x-ui.textarea name="description" label="Detailed description (Markdown)" :value="$service->description" rows="6" class="sm:col-span-2" />
                    <x-ui.textarea name="inclusions" label="What is included (one per line)" :value="\App\Support\Lines::toText($service->inclusions)" rows="5" />
                    <x-ui.textarea name="exclusions" label="Not included (one per line)" :value="\App\Support\Lines::toText($service->exclusions)" rows="5" />
                    <x-ui.textarea name="instructions" label="Service-specific instructions for customers" :value="$service->instructions" rows="3" class="sm:col-span-2" />
                </div>
            </x-ui.card>

            <x-ui.card title="Pricing" description="Prices are always calculated on the server. Quote-based services are never charged until an admin approves a price.">
                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="space-y-1.5 sm:col-span-2">
                        <label for="pricing_method" class="block text-sm font-medium">Pricing method</label>
                        <select id="pricing_method" name="pricing_method" x-model="method" class="block w-full rounded-lg border border-line bg-white px-3 py-2.5 text-sm">
                            @foreach ($pricingMethods as $pricingMethod)
                                <option value="{{ $pricingMethod->value }}">{{ $pricingMethod->label() }}</option>
                            @endforeach
                        </select>
                        @error('pricing_method')<p class="text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>
                    <div x-show="method === 'fixed' || method === 'starting_quote'">
                        <x-ui.input name="price" label="Price / starting price" prefix="$" inputmode="decimal" :value="$service->price_cents !== null ? \App\Support\Money::toDecimal($service->price_cents) : null" />
                    </div>
                    <div x-show="method === 'hourly'">
                        <x-ui.input name="hourly_rate" label="Hourly rate" prefix="$" inputmode="decimal" :value="$service->hourly_rate_cents !== null ? \App\Support\Money::toDecimal($service->hourly_rate_cents) : null" />
                    </div>
                    <x-ui.input name="estimated_minutes" label="Estimated duration (minutes)" type="number" min="15" :value="$service->estimated_minutes" hint="Required for hourly pricing." />
                    <x-ui.checkbox name="allows_recurring" label="Allow recurring bookings (weekly, bi-weekly, monthly)" :checked="$service->allows_recurring" class="sm:col-span-2" />
                </div>
            </x-ui.card>

            <x-ui.card title="Customization chat" description="Service-specific prompts shown in the booking chatbox.">
                <div class="grid gap-5">
                    <x-ui.textarea name="chat_greeting" label="Greeting message" :value="$chat['greeting'] ?? null" rows="2" />
                    <x-ui.textarea name="chat_needs_prompt" label="First question" :value="$chat['needs_prompt'] ?? null" rows="2" />
                    <x-ui.textarea name="suggested_prompts" label="Suggested prompts (one per line, up to 8)" :value="\App\Support\Lines::toText($chat['suggested_prompts'] ?? [])" rows="4" />
                </div>
            </x-ui.card>

            <x-ui.card title="SEO">
                <div class="grid gap-5">
                    <x-ui.input name="seo_title" label="SEO title" :value="$service->seo_title" maxlength="160" />
                    <x-ui.textarea name="seo_description" label="Meta description" :value="$service->seo_description" rows="2" maxlength="300" />
                </div>
            </x-ui.card>
        </div>

        <div class="space-y-6">
            <x-ui.card title="Publishing">
                <x-ui.checkbox name="is_active" label="Published (visible on the website)" :checked="$service->is_active" />
                <x-ui.button class="mt-5 w-full">{{ $isNew ? 'Create service' : 'Save service' }}</x-ui.button>
                @unless ($isNew)
                    <a href="{{ route('services.show', $service) }}" target="_blank" class="mt-3 block text-center text-sm text-admin-accent hover:underline">View on website</a>
                @endunless
            </x-ui.card>

            <x-ui.card title="Featured image">
                @if ($service->featuredImageUrl())
                    <img src="{{ $service->featuredImageUrl() }}" alt="" class="mb-3 aspect-[4/3] w-full rounded-lg object-cover">
                    <x-ui.checkbox name="remove_featured_image" label="Remove current image" />
                @endif
                <div class="mt-3">
                    <label for="featured_image" class="block text-sm font-medium">Upload image</label>
                    <input id="featured_image" type="file" name="featured_image" accept="image/jpeg,image/png,image/webp" class="mt-1 block w-full text-sm">
                    <p class="mt-1 text-xs text-ink-500">JPG, PNG or WebP, up to 4 MB. Images are resized and converted to WebP.</p>
                    @error('featured_image')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
            </x-ui.card>

            <x-ui.card title="Available add-ons">
                @if ($addons->isEmpty())
                    <p class="text-sm text-ink-500">No add-ons yet. <a href="{{ route('admin.addons.create') }}" class="text-admin-accent underline">Create one</a>.</p>
                @else
                    <div class="space-y-2">
                        @foreach ($addons as $addon)
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="addons[]" value="{{ $addon->id }}" @checked(in_array($addon->id, $selectedAddons)) class="rounded border-line text-brand-600">
                                {{ $addon->name }} <span class="text-ink-500">({{ $addon->priceLabel() }}{{ $addon->is_active ? '' : ', inactive' }})</span>
                            </label>
                        @endforeach
                    </div>
                @endif
            </x-ui.card>
        </div>
    </form>

    @unless ($isNew)
        <div class="mt-8 grid gap-6 xl:grid-cols-3">
            <x-ui.card title="Gallery" class="xl:col-span-2">
                @if ($service->images->isNotEmpty())
                    <div class="mb-5 grid grid-cols-2 gap-4 sm:grid-cols-4">
                        @foreach ($service->images as $image)
                            <div class="space-y-2">
                                <img src="{{ $image->url() }}" alt="{{ $image->alt }}" class="aspect-square w-full rounded-lg object-cover">
                                <form method="POST" action="{{ route('admin.services.images.destroy', [$service, $image]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-xs font-medium text-red-700 hover:text-red-900">Remove</button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @endif
                <form method="POST" action="{{ route('admin.services.images.store', $service) }}" enctype="multipart/form-data" class="flex flex-wrap items-end gap-3">
                    @csrf
                    <div>
                        <label for="images" class="block text-sm font-medium">Add gallery images</label>
                        <input id="images" type="file" name="images[]" multiple accept="image/jpeg,image/png,image/webp" class="mt-1 text-sm">
                        @error('images')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                        @error('images.*')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>
                    <x-ui.button variant="secondary">Upload</x-ui.button>
                </form>
            </x-ui.card>

            <x-ui.card title="Delete service">
                <p class="text-sm text-ink-700">The service is removed from the website. Existing bookings keep their details.</p>
                <form method="POST" action="{{ route('admin.services.destroy', $service) }}" class="mt-4" x-data x-on:submit="if (! confirm('Delete this service?')) $event.preventDefault()">
                    @csrf
                    @method('DELETE')
                    <x-ui.button variant="danger">Delete service</x-ui.button>
                </form>
            </x-ui.card>
        </div>
    @endunless
</x-layouts.admin>
