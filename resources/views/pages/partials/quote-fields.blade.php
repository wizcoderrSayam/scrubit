<div class="hidden" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
<x-ui.input name="name" label="Name" autocomplete="name" required />
<x-ui.input name="phone" label="Phone" type="tel" autocomplete="tel" required />
<x-ui.input name="email" label="Email address" type="email" autocomplete="email" required />
<x-ui.select name="service_id" label="Select a service" placeholder="Not sure yet"
    :options="$services->pluck('name', 'id')->all()" :value="$services->firstWhere('slug', request('service'))?->id" />
<x-ui.textarea name="message" label="Additional information" rows="6" class="sm:col-span-2"
    hint="Property size, rooms, condition, preferred dates, anything that helps us quote accurately." />
