<x-layouts.admin title="Homepage content">
    <form method="POST" action="{{ route('admin.content.home.update') }}" class="max-w-3xl space-y-6">
        @csrf
        @method('PUT')
        <x-ui.card title="Hero">
            <div class="grid gap-5">
                <x-ui.input name="hero_eyebrow" label="Eyebrow line" :value="$settings['home.hero_eyebrow']" required />
                <p class="text-sm text-ink-500">The main headline uses the business tagline from <a href="{{ route('admin.settings.business.edit') }}" class="text-admin-accent underline">Business settings</a>.</p>
                <x-ui.textarea name="intro" label="About teaser" :value="$settings['home.intro']" rows="4" required
                    hint="Shown in the About block on the homepage, with a link to the About page." />
            </div>
        </x-ui.card>
        <x-ui.card title="Statistics" description="The homepage hides this row until at least one number is above zero.">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-ui.input name="houses_cleaned" label="Houses cleaned" type="number" min="0" max="1000000" :value="$settings['home.stats.houses_cleaned']" required />
                <x-ui.input name="happy_customers" label="Happy customers" type="number" min="0" max="1000000" :value="$settings['home.stats.happy_customers']" required />
                <x-ui.input name="expert_cleaners" label="Expert cleaners" type="number" min="0" max="1000000" :value="$settings['home.stats.expert_cleaners']" required />
                <x-ui.input name="years_experience" label="Years of experience" type="number" min="0" max="1000000" :value="$settings['home.stats.years_experience']" required />
            </div>
        </x-ui.card>
        <x-ui.card title="Why choose us" description="One item per line, in the form: Title | Description">
            <x-ui.textarea name="why_items" label="Items" :value="$whyItems" rows="6" />
        </x-ui.card>
        <x-ui.card title="Call to action">
            <div class="grid gap-5">
                <x-ui.input name="cta_heading" label="Heading" :value="$settings['home.cta_heading']" required />
                <x-ui.input name="cta_text" label="Text" :value="$settings['home.cta_text']" required />
            </div>
        </x-ui.card>
        <x-ui.button size="lg">Save homepage</x-ui.button>
    </form>
</x-layouts.admin>
