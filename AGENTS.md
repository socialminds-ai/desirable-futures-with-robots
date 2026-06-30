# AGENTS.md — Desirable Futures website

## Project direction

This website is being transformed from a static site into a **lightweight LAMP
platform** (Linux, Apache, MySQL/MariaDB, PHP).

## Architecture rules

These constraints govern all work on this codebase. Follow them by default and
flag any proposal that would violate them.

- **Vanilla stack.** Use plain HTML, CSS, JavaScript, and PHP as much as is
  sensible. Avoid frameworks and build tooling unless there is a clear,
  justified need.
- **Limited external dependencies.** Minimize reliance on external assets, CDNs,
  third-party scripts, and package managers. Self-host assets (fonts, icons,
  libraries) rather than loading them from external origins.
- **Full GDPR compliance.** Any data collection, storage, or processing must be
  GDPR-compliant by design (lawful basis, data minimization, retention limits,
  user rights).
- **No third-party cookies.** Do not set or rely on third-party cookies. Keep
  first-party cookies to the minimum strictly necessary, and treat anything
  non-essential as requiring consent.
- **WCAG 2.0 accessibility.** The website must be fully WCAG 2.0 compliant.
  Use semantic HTML, proper heading structure, alt text, sufficient color
  contrast, keyboard navigability, and accessible labels/ARIA where needed.

When in doubt, prefer the simpler, more self-contained, more privacy-preserving
option.

## Project structure notes

- **`index.html`** is the actual website (the page users browse). WCAG and other
  web-facing rules apply here.
- **`kit.html`** is *not* a browsable page — it is only the source used to
  generate the workshop toolkit PDF (`desirable-futures-kit.pdf`, via
  `build-pdf.sh`). Treat it as a print artifact: keep its visual identity in sync
  with the site, but web-only accessibility patterns (skip links, focus
  indicators, keyboard nav) do not apply. After editing `kit.html`, the PDF must
  be regenerated.

## Commit hygiene

- **Small, atomic commits.** Each commit should represent one logical change and
  leave the tree in a working state.
- **Commit regularly.** Don't batch unrelated changes into a large commit;
  commit as work progresses.
- **Clear messages.** Write concise, descriptive commit messages explaining the
  what and why of the change.
