# Desirable Futures with Robots — landing site

A single-page editorial site introducing the *Desirable Futures with Robots* workshop series to the HRI research community. Static HTML/CSS/JS, designed to be served from GitHub Pages with no build step.

## Files

```
index.html                     # The landing page.
styles.css                     # All landing-page styling. Editorial / manifesto.
script.js                      # Sticky-header state + scroll reveal. No deps.
form.html                      # One-page workshop report form (source of the PDF).
build-pdf.sh                   # Regenerates desirable-futures-form.pdf from form.html.
desirable-futures-form.pdf     # The form, ready to print or share.
assets/                        # Drop placeholder replacements here.
```

## The workshop report form

`form.html` is a print-ready, A4 one-pager that facilitators fill in for each *Desirable Future* their workshop produces (one future per page). It matches the site's visual identity — cream / ink / terracotta, Fraunces serif — but on a white field for ink economy.

Fields: workshop metadata (date, location, facilitator, target group, format, participants), the *what if* used, title, short description, image space, and four reflection questions (the imagined future, the robot's role/shape/place, what the robot refuses, why it's desirable).

### Regenerating the PDF

Edit `form.html`, then:

```bash
./build-pdf.sh
```

This renders `form.html` to `desirable-futures-form.pdf` via headless Chrome. Requirements: `google-chrome`, `google-chrome-stable`, or `chromium` on `PATH`. No other dependencies.

You can also preview the form live in a browser by visiting <http://localhost:8000/form.html> after starting the local server (see below).

## Local preview

Any static server works. Two options:

```bash
# Python (no install)
python3 -m http.server 8000

# Or Node, if you have it
npx serve .
```

Then open <http://localhost:8000>.

## Deploying to GitHub Pages

1. Create a new repo (e.g. `desirable-futures`).
2. Push these files to the `main` branch.
3. **Settings → Pages → Build and deployment → Source: Deploy from a branch → `main` / `(root)`.**
4. The site will be served at `https://<your-org>.github.io/desirable-futures/`.
5. For a custom domain: add a `CNAME` file containing the domain (e.g. `desirable-futures.org`) and configure the DNS `CNAME` record to point at `<your-org>.github.io`.

## Things to replace before going live

1. **Email**: `contact@desirable-futures.org` appears in two places in `index.html`. Replace.
2. **Repository link**: in the Join section and footer (`github.com/<your-org>/desirable-futures`).
3. **Proposal PDF link**: the "Read the full proposal" button currently links to `#`. Drop the PDF in `assets/` and update the `href`.
4. **Placeholders** (see below).

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

Done. An abstract two-mark composition echoing the hero — an ink organic form alongside a rotated terracotta square — on a rounded cream tile. SVG ships a `prefers-color-scheme: dark` variant for free. Wired into `<head>` of both `index.html` and `form.html`.

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
