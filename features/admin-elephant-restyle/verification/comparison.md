# Comparison: Elephant admin reference vs Scrubit admin

Reference: the dashboard screenshot supplied by the user (Elephant Spices admin, 2026-10-10).

| Element | Elephant | Scrubit (after) | Match |
|---|---|---|---|
| Sidebar | Dark vertical gradient, flat white item list, translucent rounded active item | Same layout and active style; gradient uses Scrubit colours (charcoal → navy) | Layout ✓, colour deliberately Scrubit |
| Brand block | Serif "Elephant" + teal uppercase "ADMIN PANEL" | Serif "Scrubit" + teal uppercase "ADMIN PANEL" | ✓ |
| Footer links | "View Store ↗", "Sign out" | "View website ↗", "Sign out" | ✓ |
| Header | White bar, serif title, grey subtitle, user name right | Same | ✓ |
| Stat cards | 3×2 bordered cards, grey label, large coloured serif value | Same; Services / Bookings / Pending quotes / Customers / CMS Pages / Revenue | ✓ (products → services, orders → bookings) |
| Recent table | Titled card + teal "View all", grey header row, teal reference links | Same ("Recent bookings") | ✓ |
| Canvas | Very light grey | `#f8fafc` | ✓ |

Deliberate differences:
- The sidebar gradient ends in Scrubit navy instead of Elephant maroon. To match exactly, set `--color-admin-sidebar-to: #5b1a24;` in `resources/css/app.css`.
- The sidebar keeps small section labels (Catalog, Content, Settings), because Scrubit has 18 admin sections vs Elephant's 11.
- An extra row of booking-status counts sits under the stat cards.

Not yet compared (no reference supplied): list page with filters, forms and the login page. These use the same card, table and link styles.

Evidence: `dashboard-desktop.png`, `dashboard-mobile.png`, `bookings-desktop.png`, `booking-detail-desktop.png`, `services-desktop.png`, `service-form-desktop.png`, `routes.diff` (empty), `grep-product.txt` (empty), `tests.txt`.
