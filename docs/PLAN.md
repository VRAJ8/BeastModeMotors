# Beast Mode Motors — Car Passport: product plan

## Positioning

**A car's history should belong to the car.** Beast Mode Motors is a passport for a vehicle. The owner keeps it as they go, the shops that work on the car confirm it, and it transfers to the next owner on sale. The private-sale marketplace and deal room are where the passport pays off. A well-documented car is easier to sell, and a buyer can see exactly how it was looked after.

### Why this, and why now
- **History reports cover the wrong data.** They aggregate what insurers, auctions and some dealers report: accidents, title brands, a few odometer readings. They miss the thing that decides whether a used car is good, which is whether it was maintained, especially at independent shops or at home.
- **Owner apps keep data private.** Maintenance trackers exist, but their logs are self-reported, can't be verified, and die with the owner's account.
- **Private sales are under-served and scam-prone.** Dealers have tooling. Private sellers have a listings site and their phone.

The wedge is **shop verification**: a signed link a shop can answer in ten seconds without signing up. It turns an owner's claim into evidence, and it's the one thing a buyer can't get anywhere else.

## Users

| User | Job to be done | Where they live in the app |
| --- | --- | --- |
| Owner | Keep track of the car, never miss maintenance, prove its care when selling | Garage, vehicle tabs, Share, Sell |
| Buyer | Find a well-documented car, negotiate and complete the purchase safely | Marketplace, listing, deal room |
| Shop | Confirm their work for a customer with minimal effort | Signed verification page (no account) |
| Trust & safety staff | Remove bad listings, spot scams and fake verifications | `/admin` |

## Data model

```
User ─┬─< Vehicle (current owner) ─┬─< Ownership (Owner 1, 2, …)   ← the chain of custody
      │                            ├─< ServiceRecord ─┬─< Document (receipts)
      │                            │                  ├─< ShopVerification ─> Shop (keyed by email)
      │                            │                  └── OdometerReading (1:1, kept in sync)
      │                            ├─< OdometerReading (purchase, manual, sale…)
      │                            ├─< Reminder (miles / months intervals)
      │                            ├─< Expense (per ownership — private)
      │                            ├─< Recall (NHTSA campaigns, resolved by a record)
      │                            ├─< Document (title, insurance… — private)
      │                            ├─< VehiclePhoto
      │                            ├─< ShareLink (per-link privacy, expiry, views)
      │                            └─< Listing ─┬─< Deal ─┬─< DealMessage (scam flags)
      │                                         │         ├─< Offer (counter chain)
      │                                         │         └── Inspection (checklist results)
      │                                         └─< Report
      └─< saved_listings
```

Key rules:
- **Evidence levels:** shop verified > receipt attached > self-reported; disputed records count for nothing. Records logged more than 30 days after the work count for less, unless verified.
- **A verified record is locked.** Its date, mileage, cost and work can't be edited afterwards.
- **Transfer rules:** records, receipts, inspection reports, warranties, manuals, photos, readings, recalls and reminders go with the car. Expenses, title, registration and insurance scans, and share links stay behind or are deleted.
- **Exactly one agreed deal per listing.** Accepting an offer locks the listing row and marks it pending. Cancelling an agreed deal puts the car back on the market.

## Passport Score

| Component | Max | Measure |
| --- | --- | --- |
| Identity | 15 | VIN passes check digit (10), has photos (5) |
| History coverage | 25 | Share of documented years (up to 10) with at least one record |
| Quality of evidence | 30 | Average record weight: verified 1.0, receipt 0.7, self 0.3, disputed 0; ×0.6 if logged later and not verified |
| Odometer integrity | 15 | No backwards readings (10), a reading within 6 months (5) |
| Upkeep & recalls | 15 | No overdue maintenance (8, −3 per overdue item), no open recalls (7) |

Grades: A ≥ 85, B ≥ 70, C ≥ 50, D below 50.

## Shipped in v1
- Garage: VIN decode with offline fallback, records with receipts and line items, maintenance plans, documents with expiry, costs and fuel economy, recalls, share links, PDF report, QR window sign.
- Shop verification by signed link, with confirm/dispute, record locking and owner notifications.
- Marketplace with score-first sorting and filters. Listings require a valid VIN, a photo and at least one record.
- Deal room: messages with scam shield, offers and counters, inspection checklist, per-role handover checklist, bill of sale, two-sided confirmation, ownership transfer.
- Scheduled jobs: daily reminders and expiry alerts, weekly recall sync, hourly expiry of offers and verification links.
- Trust & safety console.
- 131 Pest tests, CI, Docker image, Render blueprint, seeded demo world.

## Shipped in v1.1
- **Shop profiles and directory.** Shops get a public page built from their verification track record (confirmed records, cars, response rate, typical answer time, a fast-answer badge). They edit it through a signed link, and owners pick listed shops instead of typing an email.
- **Read-only history after a sale.** Records from earlier ownerships can't be edited or deleted, and their costs stay private.
- **Production setup.** S3/R2 storage for receipts and photos, SMTP email, PostgreSQL (with a CI job running the whole suite on it).
- 146 Pest tests, and a recorded sale walkthrough in the README.

## Shipped in v1.2 — pre-launch hardening
A full audit (security, privacy, deal state, history integrity, production, jobs) and fixes for every confirmed finding:
- **Security and privacy.** Escaped JSON-LD and email Markdown, trusted hosts, EXIF stripped from uploads, rate-limited verification mail, staff accounts that can actually be revoked, read-only demo admin.
- **Deals.** Locked, re-checked state transitions; a sale can only move a car its seller still owns; removed listings end their deals; handover mileage bounded and re-checked.
- **Honest history.** Self-verification through second mailboxes refused; disputes and shop-answered records can't be erased; shops name themselves; reminders derived from records; odometer timeline validation; disputed records earn no coverage.
- **Running it.** After-commit notifications, a queue worker and scheduler in the image, new migrations instead of edited ones, foreign-key indexes, Postgres-safe input handling, unowned passports on account deletion with a staff claim flow, and a demo seed that is deterministic and atomic.
- 251 Pest tests, run on SQLite and PostgreSQL in CI.

## Roadmap
1. **Receipt OCR.** Upload a photo and the date, mileage, line items and shop are filled in for you.
2. **Telematics and OBD readings.** Odometer readings from connected-car APIs (Smartcar) or a Bluetooth OBD dongle, tagged as device-sourced.
3. **Market value.** Price guidance from comparable sales on the platform, adjusted for Passport Score, to show what documentation is worth.
4. **Verified identity for sellers** (ID plus selfie) and a "met in person" confirmation in the deal room.
5. **State paperwork packs.** State-specific bill of sale and odometer disclosure forms.
6. **Insurer and warranty partnerships.** Share a passport to get a quote; extended-warranty providers accept verified maintenance as proof.
7. **Native mobile app** for logging fuel and services at the pump or the counter.
