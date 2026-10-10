# Admin restyle: Elephant look, services instead of products

Status: todo (waiting for reference screenshots)

## Goal
Administrators see the layout, sidebar, cards, tables and forms of the Elephant Spices admin in the Scrubit admin.
Every existing feature still works, and all wording refers to services, add-ons and bookings.

## Context
- Shell: `resources/views/components/layouts/admin.blade.php`; shared UI: `resources/views/components/ui/*`
- Pages: `resources/views/admin/**`; tokens: `resources/css/app.css` `@theme` (public `brand-*`, `bubble-*`, `ink-*` unchanged)
- Route baseline: `verification/routes-before.json`; tests baseline: 202 passing
- Reference: `docs/reference/elephant-admin/*.png`. Never log in to elephantspices.com.

## Requirements
- [ ] Reference palette, font and spacing recorded in `docs/reference/elephant-admin/tokens.md`
- [ ] Admin-only tokens added to `@theme`; public tokens unchanged
- [ ] Sidebar matches the reference (background, groups, icons, active state)
- [ ] Top bar matches the reference (title, user area, log-out)
- [ ] Mobile sidebar opens and closes at 520px
- [ ] New components `x-admin.stat-card`, `x-admin.table`, `x-admin.filter-bar`, `x-admin.section`
- [ ] Every admin page uses the new components: dashboard, bookings (index/detail), quotes, payments, customers (index/detail), enquiries, services (index/form), categories, add-ons, homepage, pages, FAQs, testimonials, email templates, business, tax, availability, payment settings
- [ ] Admin login page matches `login.png` (if provided)
- [ ] `grep -rni product resources/views/admin` returns nothing
- [ ] Route list unchanged; only views, CSS, JS, docs and tests are modified
- [ ] Text contrast ≥ 4.5:1, visible focus rings, no horizontal scroll at 1366px or 520px

## Out of scope
New admin modules, public-site styling, business logic or route changes, and any Elephant branding.

## Verification (evidence in `verification/`)
- [ ] `php artisan test --compact` passes (≥202 tests), output in `tests.txt`
- [ ] `npm run build` and Pint pass, output in `build.txt`
- [ ] `routes-after.json` matches `routes-before.json` (`routes.diff` empty)
- [ ] Desktop (1366px) screenshots of every admin page, plus mobile (520px) screenshots of dashboard, bookings, booking detail and service form
- [ ] `comparison.md` pairs each reference screenshot with the matching Scrubit screenshot

## Done when
Every box above is checked and the evidence exists.
