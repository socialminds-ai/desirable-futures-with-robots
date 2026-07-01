# LAMP migration — implementation plan

Migrating the *Desirable Futures with Robots* site from a static page into a
lightweight **LAMP** platform (Apache · MariaDB · PHP), per `AGENTS.md`: vanilla
stack, minimal external dependencies, GDPR-compliant, no third-party cookies,
WCAG 2.0.

Planning captured 2026-07-01. This document is the source of truth for scope and
sequencing; update it as decisions change.

## Scope of the first build

**In:**

1. **LAMP foundation** — Apache + PHP (PDO) + MariaDB, no framework. Local dev
   first, via a disposable `docker-compose` stack that mirrors real
   Apache/MariaDB. The app stays vanilla and deployable to any LAMP host.
2. **Facilitator registration + passwordless email auth** — double opt-in email
   verification, single-use magic-link login, first-party essential session
   cookie only (no passwords stored).
3. **Live facilitator map** — self-hosted Leaflet, OSM tiles loaded directly
   (third-party tile request accepted for this use case), **no Google**.
   Facilitators mark where they're based during registration; §IV renders the
   pins.
4. **Community what-ifs page** — a dedicated page (not the landing list) where
   registered users vote for and propose questions.

**Deferred — do NOT design yet:**

- Workshop **report** schema/mechanism (format still being finalized).
- `workshops` table, workshop-count stats.
- Hero "~100 / 5 continents" numbers stay **static** for now (they depend on the
  deferred workshop schema).

Foundation must stay general enough to bolt these on later via migrations.

## Target scale

~**100–200 facilitators**, not thousands. Deliberately simple: plain MariaDB +
basic indexes, manual/post-hoc moderation, simple PHP mail, honeypot +
server-side rate-limit for abuse only. Do not build anything that only pays off
at 10k+ rows (no caching layer, no clustering, no queue).

## Architecture

```
public/                 # Apache DocumentRoot — the ONLY web-served directory
  index.php             # was index.html; PHP partials only where dynamic
  what-ifs.php          # community what-ifs page
  styles.css  script.js
  assets/               # fonts, images, favicons, Leaflet (self-hosted)
  desirable-futures-kit.pdf
  register.php  verify.php  login.php  logout.php   # auth handlers
  api/                  # small JSON endpoints (map pins, etc.)
lib/                    # PHP, NOT web-served
  config.php            # loads DB settings (env first, then config/secrets.php)
  db.php                # PDO connection
  csrf.php  validate.php  mail.php  auth.php
db/
  migrations/NNNN_*.sql # forward-only, source of truth for schema
  migrate.php           # idempotent runner, tracks schema_migrations
config/
  secrets.example.php   # committed template
  secrets.php           # gitignored — real DB creds on the host
docker-compose.yml      # dev only (php:8.3-apache + mariadb)
kit.html  build-pdf.sh  # print artifact source — stays at repo root, not served
secrets                 # maintainer's private credential notes — gitignored
```

**Docroot = `public/`** so `lib/`, `db/`, `config/`, and `secrets` are un-served
by construction (not merely `.htaccess`-protected). Shared-host deploy repoints
the domain to `public/` (documented at deploy time).

### Schema evolution (expected to change often)

- **Forward-only versioned migrations**: `db/migrations/NNNN_description.sql`.
- Applied by `db/migrate.php`, which tracks applied versions in a
  `schema_migrations` table and is safe to re-run.
- Migrations are the **source of truth**. Every structural change ships as a new
  numbered file — never hand-edit live schema. Deferring the report tables is
  therefore cheap: they arrive as a later `00XX_add_reports.sql`.

### Configuration & secrets

- `lib/config.php` reads DB settings from environment variables first
  (docker-compose provides them in dev), falling back to a gitignored
  `config/secrets.php` returning an array (for the shared host).
- The root `secrets` file is the maintainer's freeform credential notes and is
  gitignored; it is **not** parsed by the app.

## Feature details

### Facilitator registration + auth (passwordless)

- Registration form (§IX) collects: name, email, institution, country,
  continent, **city-level location** (lat/lng rounded ~1 km + label), and
  consent (timestamp + version) shown at point of collection.
- **Double opt-in**: verification email confirms address *and* consent.
- **Login**: enter email → single-use, time-limited, hashed magic-link token →
  first-party essential session cookie (no consent banner needed for a strictly
  necessary session cookie).
- No passwords stored → data minimization.
- Tables: `facilitators`, `auth_tokens`.

### Live facilitator map

- Self-host Leaflet JS/CSS + marker assets under `public/assets/`.
- OSM tiles loaded directly with "© OpenStreetMap contributors" attribution and
  a note in the privacy policy. No Google.
- Registration page embeds a Leaflet picker (search + draggable pin), with a
  non-map fallback (manual place entry) for keyboard/no-JS/WCAG.
- **Pins default to anonymous city-level dots.** A separate opt-in attaches
  name + institution. Flags on `facilitators`: `show_on_map`, `show_identity`.
- ~100–200 pins → no clustering.

### Community what-ifs

- **Landing page §V stays hand-picked and static** — no DB rendering there.
- A separate **`/what-ifs` page** lists all questions vertically. **Publicly
  readable**; **voting + proposing require login**.
- Registered users favourite/vote (one toggle each) and propose new questions.
- **New questions appear immediately** — no pre-moderation. Admin **hides/removes
  post-hoc**.
- Seed the page with the 4 canonical questions (votable).
- The landing "Send it →" / "Propose a what if" links (§V/§VI/§IX) repoint from
  `mailto:` to `/what-ifs`.
- Tables: `whatifs` (prompt, author_facilitator_id nullable, status
  visible/hidden, timestamps), `whatif_votes` (whatif_id + facilitator_id,
  unique together). Sort by vote count, newest as tiebreak.
- Abuse risk is low (login-gated, ~100–200 users): post-hoc moderation + basic
  validation, no honeypot here.

## Security & GDPR baseline

- PDO prepared statements everywhere; CSRF tokens on all POST; `password_hash`
  for the admin account.
- Secrets outside docroot and gitignored; HTTPS in production.
- No third-party cookies; only a strictly-necessary first-party session cookie.
- No third-party anti-spam (no reCAPTCHA) — honeypot + server-side rate-limit.
- Personal data (email, location) minimized: city-level coords, consent
  timestamp + version at collection, erasure + retention from day one.
- Future image uploads must be re-encoded to strip EXIF/GPS.

## Sequencing

Each phase leaves the tree working; small atomic commits within.

- **Phase 0 — Hygiene.** `.gitignore` (`secrets`, `config/secrets.php`,
  `uploads/`, dev files); `config/secrets.example.php`.
- **Phase 1 — LAMP foundation + parity.** `public/` docroot; `docker-compose`;
  `lib/config.php` + `lib/db.php`; `db/migrate.php` + `0001` (schema_migrations);
  move web files into `public/`; `index.html` → `index.php`, visually identical;
  repoint what-if links to a `/what-ifs` stub; update `kit.html` asset paths +
  `build-pdf.sh` output path.
- **Phase 2 — Facilitator registration + email auth + map.**
- **Phase 3 — What-ifs page** (list + propose + vote + post-hoc moderation) and
  admin moderation area.
- **Phase 4 — GDPR + a11y polish** (privacy policy incl. OSM note, retention
  cron, erasure flow, WCAG pass on new forms + map picker).

## Untouched by this migration

`kit.html` / `build-pdf.sh` / `desirable-futures-kit.pdf` are print artifacts.
Only `kit.html`'s asset *paths* change (assets move under `public/`); its
rendered output is unchanged.
