---
name: hyva-tailwind
description: Set up, build or watch Tailwind CSS (v4) for the Shabeeb/hyva-child theme. Use when new CSS classes do not appear, when changing brand colours/fonts, or when the theme's web/tailwind folder does not exist yet.
---

# Tailwind CSS for the child theme

Folder: `app/design/frontend/Shabeeb/hyva-child/web/tailwind`. Output: `app/design/frontend/Shabeeb/hyva-child/web/css/styles.css`.
Hyvä 1.5 uses Tailwind **v4** — there is no `tailwind.config.js`.

## First-time setup (only if `web/tailwind` does not exist)
1. `cp -r vendor/hyva-themes/magento2-default-theme/web/tailwind app/design/frontend/Shabeeb/hyva-child/web/` (skip `node_modules`).
2. In the child's `hyva.config.json`, add the parent to `tailwind.include` so its templates are scanned:
   `{ "src": "vendor/hyva-themes/magento2-default-theme" }`.
3. `npm --prefix app/design/frontend/Shabeeb/hyva-child/web/tailwind install`
4. After the first build, remove the old static link that still points at the parent's CSS:
   `rm -rf pub/static/frontend/Shabeeb/hyva-child var/view_preprocessed/pub/static/frontend/Shabeeb/hyva-child`
   (Magento made `pub/static/.../css/styles.css` a symlink to the parent file while the child had none, and does not re-check it.)
   The browser may also hold the old file under the same URL — tell the user to hard-refresh (Ctrl+Shift+R).

## Build / watch
- One-off build: `npm --prefix app/design/frontend/Shabeeb/hyva-child/web/tailwind run build`
- While editing: `npm --prefix app/design/frontend/Shabeeb/hyva-child/web/tailwind run watch` (run in a terminal tab the user can see; stop with Ctrl+C)
- Then `php bin/magento cache:flush`.

## Where to change things
- Brand colours: `hyva.config.json` → `tokens.values.color` (`primary`, `secondary`, ...). Rebuild.
- Custom CSS / v4 config (`@theme`, `@source`, `@utility`): `tailwind-source.css` and the files it imports (`base/`, `components/`, `theme/`, `utilities/`).
- Extra folders to scan for classes (e.g. a module's templates): `hyva.config.json` → `tailwind.include` → `{ "src": "app/code/Shabeeb/<Module>" }`.

## Check
`grep -c "<new-class>" app/design/frontend/Shabeeb/hyva-child/web/css/styles.css` should be > 0. Explain to the user why the class was missing before (Tailwind only outputs classes it finds in scanned files).
