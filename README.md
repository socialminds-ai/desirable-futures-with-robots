# Desirable Futures with Robots — landing site

An editorial site introducing the *Desirable Futures with Robots* workshop series to the HRI research community. Being migrated from a static page into a lightweight **LAMP** platform (Apache · MariaDB · PHP) — vanilla stack, no build step, minimal dependencies. See [`docs/lamp-migration-plan.md`](docs/lamp-migration-plan.md) for scope and sequencing.

**Live:** <https://desirable-futures-with-robots.org>
**Repository:** <https://github.com/socialminds-ai/desirable-futures-with-robots>

## Files

```
public/                        # Apache DocumentRoot — the only web-served dir.
  index.php                    #   The landing page.
  what-ifs.php                 #   The community what-if bank (stub for now).
  styles.css                   #   All styling. Editorial / manifesto.
  script.js                    #   Sticky-header state + scroll reveal. No deps.
  assets/                      #   Hero, favicons, map, fonts, coordinator photos.
  desirable-futures-kit.pdf    #   The kit, ready to print or share.
lib/                           # PHP (config, PDO) — NOT web-served.
db/                            # migrate.php runner + migrations/ (schema source of truth).
config/                        # secrets.example.php (copy to secrets.php on the host).
docker/  docker-compose.yml    # Local dev stack (Apache + PHP + MariaDB).
kit.html                       # Nine-page workshop kit (source of the PDF).
build-pdf.sh                   # Regenerates public/desirable-futures-kit.pdf from kit.html.
```

## The workshop kit

`kit.html` is a print-ready, nine-page A4 document that bundles everything a researcher needs to run a workshop. Each `<div class="page">` renders as one PDF page. It matches the site's visual identity — cream / ink / terracotta, Fraunces serif — but on a white field for ink economy.

Pages:

1. **Cover** — title, hero illustration, version, license.
2. **Rationale** — the three-section argument (laboratory→industry / dominant framing / reclaiming the narrative) plus the featured question.
3. **Reversing the narratives** — the *what if* bank: four starting prompts plus three blank slots for the researcher to add their own.
4. **Translating for your audience** — short notes for children, adolescents, older adults, and workers (warehouse/care/service).
5. **Workshop formats** — role-play (recommended), thing interviews, drawing, speculative fictions, scene-building, brainstorming, interviews. Each with structure and what to capture.
6. **Workshop report form** — one fillable A4 page per *Desirable Future* the workshop produces.
7. **Participant information sheet** *(template)* — what the workshop is, what is recorded, and how data is used and protected.
8. **Participant consent form** *(template)* — initialled statements, an optional image-sharing choice, and an optional minors / guardian authorisation panel.
9. **Data management plan** *(template)* — what is collected, lawful basis, storage, sharing, retention, and rights, GDPR-aligned.

Pages 7–9 are GDPR-oriented **templates**: every adaptable field is marked in terracotta italic (e.g. `[institution]`). The data policy baked in: raw recordings are never shared; only anonymised, consolidated results and the open-access corpus of co-created imaginaries are; participant photos / short video excerpts only with explicit consent.

### Regenerating the PDF

Edit `kit.html`, then:

```bash
./build-pdf.sh
```

This renders `kit.html` to `desirable-futures-kit.pdf` via headless Chrome. Requirements: `google-chrome`, `google-chrome-stable`, or `chromium` on `PATH`. No other dependencies.

You can also preview the kit live in a browser by visiting <http://localhost:8000/kit.html> after starting the local server (see below).

## Local development

The site now needs PHP + MariaDB. A disposable docker stack mirrors the target
LAMP host:

```bash
docker-compose up --build       # or `docker compose` on Compose v2
```

Then open <http://localhost:8080>. Apply database migrations with:

```bash
docker-compose exec web php db/migrate.php
```

The dev stack reads DB settings from environment variables (see
`docker-compose.yml`). On a real host, copy `config/secrets.example.php` to
`config/secrets.php` and fill in the credentials instead.

## Deploying

The site is moving off static GitHub Pages onto a LAMP host (SFTP + MariaDB).
Deploy tooling is not finalized yet; the essentials:

1. Serve the domain from the `public/` directory (set the domain's document root
   to `public/`, keeping `lib/`, `db/`, `config/`, and `secrets` outside it).
2. Copy `config/secrets.example.php` to `config/secrets.php` and fill in the host
   database credentials.
3. Run `php db/migrate.php` on the host to apply schema migrations.

## Things to replace before going live

1. **Placeholders** (see below).

## Placeholders to commission

One structural visual is still stubbed in the page (the workshops map). The hero illustration and the favicon are in.

### ~~1. Hero illustration~~ — `assets/hero.png` ✓

Integrated. Rendered with `mix-blend-mode: multiply` against the cream background so the illustration sits on the page without a visible rectangle edge. To swap for a new version, drop a new `assets/hero.png` (4:5 aspect, cream-on-cream backdrop reads best).

### 2. Distributed-workshops map — `assets/map.svg`

- **Location**: full-width band inside section VI ("The Series"), aspect ~16:7.
- **Brief**: Sparse, dotted-style world map. Ink-on-cream. Pins (small terracotta circles) at workshop sites. Ideally driven by a tiny `workshops.yaml` later so it updates as workshops register — but a static SVG is fine for launch.
- **Replacement**: same pattern — swap the `.placeholder--map` div for the map image or inline SVG.

### 3. Open Graph card — `assets/og-image.png`

- **Size**: 1200 × 630.
- **Brief**: One line of typography over the cream background: "Desirable Futures with robots" (matching the wordmark style), with the accent-coloured italic "with robots". Bottom-right: small marker "100 workshops · 2026—2027".
- This is what people see when the URL is shared on Slack, Mastodon, BlueSky, X.

### ~~4. Favicon~~ — `assets/favicon.svg` (+ `favicon-32.png`, `favicon-180.png`) ✓

Done. An abstract two-mark composition echoing the hero — an ink organic form alongside a rotated terracotta square — on a rounded cream tile. SVG ships a `prefers-color-scheme: dark` variant for free. Wired into `<head>` of both `index.html` and `kit.html`.

## Editorial choices baked in

- **Audience is HRI researchers, not the general public.** Copy assumes the reader knows what HRI is and what an autonomous robot is. No glossary.
- **Tone is manifesto, not pitch deck.** Long sentences, em-dashes, deliberate restraint with adjectives.
- **The "what if" inversions are the visual centrepiece.** Section IV uses display-scale type per question intentionally — they are the most quotable, screenshot-shareable part of the page.
- **Two dark sections** ("The Dominant Narrative" and "The Horizon") plus the dark Join section break the cream rhythm and carry the emotional turns.
- **No carousels, no parallax, no robot 3D model.** This site is meant to age well.

## Updating coordinators / what-ifs

Both are plain HTML in `index.html`:

- Coordinators: search for `<ul class="coordinators">`.
- What-ifs: search for `<ol class="whatifs">`.

Add or remove `<li>` blocks. The CSS handles layout automatically.

## License

Content and code: CC BY-SA 4.0 unless noted otherwise.
