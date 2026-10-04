# Beast Mode Motors — Product & Build Plan

> A full-stack luxury / performance car dealership platform, built 100% on the Laravel ecosystem.

## 1. Where this came from

Two earlier attempts existed:

| Repo | What it was | What we keep |
| --- | --- | --- |
| `BeastModeMotors` | An empty Laravel 11 skeleton (Bootstrap + Tailwind + Breeze in `composer.json`) and the BMM gold-lion logo. | The name, the logo, the Laravel choice. |
| `beast-mode-wheels-verse` | A Lovable-generated React + Supabase single page: hero carousel, car showcase, about, testimonials, contact form, Supabase auth. Tables: `car_brands`, `cars`, `profiles`, `test_drive_requests`, `contact_inquiries`. | The domain model, the dark "beast" aesthetic (black, silver, red accent, Bebas display type), the section ideas. |

Gaps in the prototypes: no real inventory browsing, test-drive booking was "coming soon", no admin side at all
(inventory could only be edited in the Supabase console), no tests, no deployment story, and the work was split across
two stacks.

**Decision:** start a single new Laravel 12 project from scratch, keep the best ideas, and design it as a product a
real dealership could run — a public showroom, a customer account area, and a back-office for staff.

## 2. Tech stack (Laravel only)

| Concern | Choice | Why |
| --- | --- | --- |
| Framework | Laravel 12, PHP 8.3 | Latest LTS-style release, enums, readonly props. |
| Interactive UI | Livewire 4 + Alpine (bundled) | SPA-like filters, booking and compare with zero custom JS build complexity. |
| Styling | Tailwind CSS 4 (CSS-first `@theme`) | Design tokens live in `resources/css/app.css`. |
| Admin back-office | Filament 5 | Production-grade CRUD, tables, widgets, actions — all PHP. |
| Auth | Laravel Breeze (Blade), restyled | Login, register, reset, email verification, profile. |
| DB | SQLite locally / PostgreSQL in production | Zero-setup dev, robust prod. Migrations are portable. |
| Queue / notifications | Database queue, Mail + database channels | No Redis needed to deploy, upgradeable later. |
| Tests | Pest 3 | Feature tests for every user-facing rule. |
| Quality | Laravel Pint, GitHub Actions CI | Lint + tests + asset build on every push. |
| Deploy | Docker image (Nginx + PHP-FPM) → Render / Railway / Fly; Laravel Cloud works out of the box | One-click portfolio demo. |

## 3. Personas

1. **Visitor / buyer** — browses, compares, finances, books a test drive, makes an offer, gets a trade-in estimate.
2. **Registered customer** — everything above, plus a "My Garage": saved cars, price-drop alerts, booking history.
3. **Sales staff / admin** — manages inventory, brands, test-drive calendar, a lead pipeline, testimonials, users.

## 4. Feature set

### Public showroom
- **Home** — cinematic hero rotating featured cars, live stats (cars in stock, brands, horsepower on the floor),
  featured inventory, shop-by-brand and shop-by-body-type, "why Beast Mode", testimonials, CTA.
- **Inventory** (Livewire) — keyword search, filters (brand, body type, condition, price range, year range, min HP),
  sort (newest, price ↑↓, horsepower, mileage), pagination; filters sync to the URL so searches are shareable.
- **Vehicle page** — gallery with lightbox, key specs, performance bars (0–60, top speed, HP, torque), feature list,
  similar vehicles, `schema.org/Car` JSON-LD for SEO, view counter, "price dropped" badge.
  - **Finance calculator** (Alpine) — price, deposit, APR, term → monthly payment and total cost.
  - **Book a test drive** (Livewire) — date picker + real time slots; slots already taken for that car disappear;
    business-hours / Sunday-closed rules; reference code issued.
  - **Make an offer** — creates an `offer` lead with amount.
  - **Save** (heart) and **Compare** toggles.
- **Compare** — up to 3 cars side by side; best value in each row highlighted.
- **Sell / Trade-in** — form with an *indicative* instant estimate (age + mileage depreciation model), creates a
  `trade_in` lead for staff follow-up.
- **Brands** — index + brand page with its inventory.
- **About / Contact** — contact form → `general` lead.
- **SEO** — slugs, meta + Open Graph tags, `sitemap.xml`, `robots.txt`.

### Customer area ("My Garage")
- Saved vehicles with current price and status (sold cars greyed out).
- Price-drop alerts: when staff lower a price, everyone who saved that car gets a mail + in-app notification.
- Test drives: upcoming / past, cancel an upcoming booking.
- My enquiries & offers with status.
- Profile, password, delete account (Breeze).

### Back-office (`/admin`, Filament, admins only)
- **Dashboard** — stock value, available cars, new leads this month, upcoming test drives; leads-per-week chart;
  latest leads table.
- **Vehicles** — full CRUD, image uploads, status (available / reserved / sold), featured toggle, quick "mark sold".
- **Brands**, **Testimonials**, **Users** (admin flag).
- **Test drives** — confirm / complete / cancel actions, filter by status & date; customers are emailed on change.
- **Leads** — a pipeline (new → contacted → qualified → won / lost) across general, vehicle, finance, offer and
  trade-in leads.

### Non-functional
- Rate limiting + honeypot on public forms.
- Authorization: `is_admin` gate for Filament (`FilamentUser`), policies for customer-owned records.
- Money stored as integer dollars; enums for every status; eager loading to avoid N+1.
- Responsive, dark, accessible (focus states, alt text, labels).

## 5. Data model

```
users          id, name, email, phone, is_admin, password, ...
brands         id, name, slug, country, founded_year, logo_url, description
vehicles       id, brand_id, model, trim, slug, year, price, previous_price, mileage,
               body_type, condition, status, fuel_type, transmission, drivetrain,
               engine, horsepower, torque, zero_to_sixty, top_speed,
               exterior_color, interior_color, vin, description, features(json),
               images(json), is_featured, views, published_at, sold_at
favorites      user_id, vehicle_id                      (pivot)
test_drives    id, reference, vehicle_id, user_id?, name, email, phone,
               scheduled_at, status, notes
leads          id, type, status, vehicle_id?, user_id?, name, email, phone,
               message, offer_amount, meta(json)
testimonials   id, name, title, quote, rating, vehicle, is_published
notifications  (Laravel database notifications)
```

## 6. Build phases

1. **Foundation** — fresh Laravel 12, Tailwind 4 theme, Breeze, Livewire, Filament, Pest.
2. **Domain** — enums, migrations, models, factories, rich seeders (real exotic line-up).
3. **Showroom** — layouts, home, inventory, vehicle page, compare, brands, sell, contact, SEO.
4. **Customer** — favorites, garage dashboard, bookings, price-drop notifications.
5. **Back-office** — Filament resources, actions, dashboard widgets.
6. **Quality** — Pest feature tests, Pint, GitHub Actions.
7. **Ship** — Dockerfile, `render.yaml`, README with screenshots and demo credentials.

## 7. Stretch ideas (post-v1)

- Stripe deposit to reserve a car (Laravel Cashier).
- Meilisearch via Laravel Scout for typo-tolerant search.
- Real-time "someone is viewing this car" with Laravel Reverb.
- Multi-language (EN / HI) and multi-currency display.
- AI-written listing descriptions from spec sheets.
- PDF spec sheet / invoice export.
