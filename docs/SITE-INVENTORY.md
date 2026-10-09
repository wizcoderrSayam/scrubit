# scrubit.ca — Site Inventory

Inspected 2026-10-09. Source of truth for content migration into the Laravel rebuild.
Only facts observed on the live site are recorded here. Anything marked **Exclude** must not be migrated.

## 1. Page structure

| Live URL | Purpose | Laravel equivalent |
|---|---|---|
| `/` | Home: hero, about teaser, quote form, how it works, services grid, stats, why us, testimonials, CTA | `/` (`home`) |
| `/about/` | Mission, vision, values, company story, stats | `/about` |
| `/services/` | Services listing | `/services` |
| `/recurring-cleaning/` | Service landing page | `/services/recurring-cleaning` |
| `/one-time-cleaning/` | Service landing page | `/services/one-time-cleaning` |
| `/home-cleaning/` | Service landing page | `/services/home-cleaning` |
| `/custom-cleaning-plans/` | Service landing page | `/services/custom-cleaning-plans` |
| `/janitorial-cleaning/` | Service landing page | `/services/janitorial-cleaning` |
| `/commercial-cleaning/` | Service landing page | `/services/commercial-cleaning` |
| `/move-in-move-out-cleaning/` | Service landing page | `/services/move-in-move-out-cleaning` |
| `/product/{slug}/` | WooCommerce product ("Book Now" target) | Replaced by `/book/{service}/customize` |
| `/enquiry/` | Request a quote | `/request-a-quote` |
| `/contact/` | Contact | `/contact` |
| Login / Sign Up modal | WooCommerce account | `/login`, `/register` (Fortify) |

Legacy URLs should 301-redirect to their Laravel equivalents (Phase 7).

## 2. Contact details (verified)

- Phone: **+1 (778) 962-6999** (778 = British Columbia)
- Email: **Info@scrubit.ca**
- Location: **Canada** (no street address or service area published)
- Footer credit: "Website Designed by Peer Infotech"

## 3. Services (7, all verified on live site)

Category assignment is our own grouping. The live site has no categories.

| # | Service | Slug | Category | Card tagline (verbatim) |
|---|---|---|---|---|
| 1 | Recurring Cleaning | `recurring-cleaning` | Residential | Scheduled cleaning to keep your space consistently spotless |
| 2 | One Time Cleaning | `one-time-cleaning` | Residential | Thorough one-time cleaning for instant cleanliness boost |
| 3 | Home Cleaning | `home-cleaning` | Residential | Reliable home cleaning for everyday comfort and hygiene |
| 4 | Custom Cleaning Plans | `custom-cleaning-plans` | Residential | Personalized cleaning plans designed to perfectly match your needs |
| 5 | Janitorial Cleaning | `janitorial-cleaning` | Commercial | Professional janitorial cleaning for offices and commercial spaces |
| 6 | Commercial Cleaning | `commercial-cleaning` | Commercial | Professional commercial cleaning for spotless business environments |
| 7 | Move-In / Move Out Cleaning | `move-in-move-out-cleaning` | Residential | *(no tagline on home card)* |

**Pricing:** none published. Every WooCommerce product shows `$1,000.00`, which is a placeholder (**Exclude**).
All services are seeded as `custom_quote`.

### Service inclusions (from each landing page)

- **Recurring**: Dusting of furniture and decor · Vacuuming and microfiber mopping of floors · Disinfection of high-touch surfaces (handles, switches) · Kitchen countertop, sink and appliance exterior polishing · Bathroom basin, shower and toilet scrubbing. Frequencies: weekly, bi-weekly, monthly.
- **One Time**: Headline "A Fresh Start for Your Space". Intensive dusting · Deep floors · Grease removal · Scrubbing · Shine (glass, mirrors, stainless steel). Use cases: special occasions, seasonal, post-renovation, pre-sale, commercial refresh.
- **Home**: Surface care · Floor refresh (vacuum + sanitized mopping) · Kitchen clean · Bathroom sanitization · Living areas (tidying, polishing).
- **Custom Plans**: Headline "Tailored to Your Lifestyle". Zoned cleaning · Task rotation · Flexible scope · Special requests (e.g. laundry, interior windows) · Targeted detail.
- **Janitorial**: Workstations · Floor maintenance · Restroom hygiene (incl. restocking supplies) · Common areas. Scheduling: daily, nightly or weekly. Industries: offices, medical, schools, retail, warehouses, property management.
- **Commercial**: Headline "Business Excellence". Broad surface disinfection · Commercial floor care · Window & glass · Kitchen & breakrooms · Aesthetic polishing. Scheduling: daytime, evening, overnight.
- **Move-In / Move-Out**: Headline "Seamless Transitions". Interior closet/shelf/drawer dusting · Baseboards and door frames hand-wiped · Exterior appliance degreasing · Deep disinfection of bathrooms and kitchen · Debris and packing material removal.

No page lists durations, exclusions, or priced add-ons.

## 4. Homepage copy (verbatim)

- Hero: **"Reliable, insured and detail focused"** / "Professional Cleaning Services for Homes & Businesses"
- About teaser: "Scrubit is a professional cleaning company committed to delivering reliable, high-quality cleaning services for homes and businesses. We believe a clean space creates comfort, productivity, and peace of mind."
- How it works ("Our Simple Cleaning Process"):
  1. **Choose Your Service**: Select from recurring, one-time, home, commercial, or custom cleaning plans.
  2. **Schedule & Customize**: Pick a convenient time and personalize your cleaning based on your needs.
  3. **Relax While We Clean**: Our professional team delivers a spotless, fresh space every time, guaranteed
- Why Choose Us ("What Makes Us Different"): Professional & Trained Staff (Trained, insured, and background-checked.) · Flexible & Customized Plans · Reliable, punctual & professional · Transparent, upfront pricing (no hidden fees).
- CTA: "Ready for a Cleaner, Healthier Space?" / "Relax while Scrubit provides trusted, eco-friendly cleaning services."
- About page mission: "provide reliable, high-quality cleaning services that create healthy, comfortable, and spotless spaces". Vision: "a trusted leader in the cleaning industry by setting high standards for service, sustainability, and customer satisfaction".

**Business claims** ("insured", "background-checked", "guaranteed", "eco-friendly") are imported verbatim as **admin-editable content**, so the owner can confirm or remove them.

## 5. Testimonials (verbatim, admin-editable)

- **Rachel**: Scrubit has been an absolute pleasure to work with. The cleaners are professional, punctual, and very thorough. Our home feels fresh and spotless after every visit. Their attention to detail truly sets them apart.
- **Emily**: From start to finish, Scrubit delivered excellent service. They respected our schedule, worked efficiently, and left our space spotless. Their professionalism and consistency keep us coming back.
- **Daniel**: We use Scrubit for our office cleaning, and the results are consistently excellent. The team is reliable, respectful, and always arrives on time. Their eco-friendly products are a big plus for us. Outstanding service overall.
- **Sophia**: I booked a move-out cleaning with Scrubit, and they exceeded my expectations. The apartment looked brand new afterward. The booking process was easy, and pricing was very transparent. I couldn't be happier with the results.

## 6. Brand assets

- Logo: `https://scrubit.ca/wp-content/uploads/2026/01/logo1.png` (1892×644 RGBA, primary) and `.../2026/01/logo-1.jpg` (625×217). Glossy blue wordmark, pink bubble over the "i".
- Favicon: `.../2026/02/fav-icon-2-removebg-preview.png`
- Photos/illustrations: `.../2026/01/2149345546.jpg`, `2149345547.jpg`, `2149374423.jpg`, `2150520613.jpg`, `2e2.png`, `34r3.png`, `efref.png`, `fefe.png`, `t44t.png`, `imgi_1_default-1.png`; `.../2026/02/p2.png`, `p4.png`, `p5.jpg`, `Untitled-design-2026-05-15T120016.887-1.jpg`, `…T121504.301.jpg`, `…T121912.794.jpg`, `…T122510.435.jpg`; `.../2026/05/2150358945.jpg`
- Colours seen on the site: primary blue `#3C7CD0`, deep blue `#104A94`, theme blue `#046BD2` / hover `#045CB4`, accent pink `#F33FAE` (logo bubble), ink `#1E293B` / `#334155`, soft background `#F0F5FA`, border `#D1D5DB`. Orange `#F66123` appears twice (minor).
- Fonts loaded: Poppins, Inter, Sora, Open Sans. Rebuild uses **Poppins** (headings) + **Inter** (body).

## 7. Exclude (do not migrate)

| Item | Reason |
|---|---|
| `$1,000.00` on all products | WooCommerce placeholder |
| Phone `9876543210` (header, move-in/out page) | Dummy number |
| Stats "Houses Cleaned / Happy Customers / Expert Cleaners / Years of Experience: 0+" | Placeholder counters; would be invented figures |
| Social icons (Facebook, Instagram, YouTube, X) | No real link targets; add only when the admin supplies URLs |
| "Demo Title" popup, "Dismiss ad" text | Theme leftovers |
| Typos "Dicover More", "Exeperience" | Corrected in rebuild |

## 8. Gaps the owner must fill (via admin)

Real prices and rates · add-on services · service area · street address/hours · final Terms, Privacy and Cancellation text (seeded as DRAFT) · social URLs · confirmation of the insured/background-checked/guaranteed claims.
