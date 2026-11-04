# Shabeeb Hyvä — learning project

A deliberately simple Magento 2.4.9 store used to learn Hyvä, Alpine.js, Tailwind v4, GraphQL and a React checkout.
The user is learning: build only what is asked, explain every file (what / why / how), and wait for an OK before the next phase.

## Sites on this machine (keep them separate)

| Site | Folder | Notes |
|---|---|---|
| http://shabeeb-hyva.test | `~/Projects/shabeeb-hyva` (this project) | DB `shabeeb_hyva`, theme `Shabeeb/hyva-child` |
| http://shabeeb-store.test | `~/Projects/shabeeb-store` | Luma store — never touch from here |
| http://localhost | `~/magento` | Demo — never touch |

Admin: http://shabeeb-hyva.test/admin_shabeeb (user `shabeeb_admin`).

## What the store contains

- Storefront brand: **Peak Sports** (football jerseys). Never show "Shabeeb" or other real names/brands on the storefront.
- One category **Football Jerseys** (`/football-jerseys.html`, category id 5, page layout "1column" set on the category) with three products, AED 349 each: `JER-RMA-H2627` Real Madrid, `JER-LFC-H2627` Liverpool, `JER-FCB-H2627` FC Barcelona (Home Jersey 2026/27). Images: the user's own 500×500 square kit photos (real club/brand logos — local learning only, not for a public store). Hyvä product image frames are square (`etc/view.xml` 360/700), so use square photos.
- Prices show "AED" (custom currency symbol `currency/options/customsymbol` = `{"AED":"AED"}`), not the Arabic "د.إ".
- Favicon: `Magento_Theme/web/favicon.ico` in the child theme.
- One custom module: `Shabeeb_ReactCheckout` — `/checkout` is a React app (guest checkout: address → Flat Rate → Check/Money order → place order) talking to Magento GraphQL. Logged-in customers get a "guest only" notice. UAE postcode is optional (`general/country/optional_zip_countries` includes `AE`).
- GraphQL practice files: `docs/graphql/*.graphql` + `docs/graphql/README.md`.
- Header: `Magento_Theme/layout/default.xml` adds `html/header/top-bar.phtml` and sets the logo size; logo file is `web/images/logo.svg`.
- Footer: `Magento_Theme/templates/html/footer.phtml` (full override of Hyvä's footer).
- Home page: `Magento_Cms/layout/cms_index_index.xml` + `Magento_Cms/templates/home/{hero,products}.phtml`; hero image `web/images/hero.jpg`. Hyvä's demo home content is off (`hyva_theme_general/demo_content/show_homepage_demo_content` = 0).
- Store name/title/copyright live in config: `general/store_information/name`, `design/head/*`, `design/footer/copyright`.
- Do not add features, plugins or modules that were not asked for.

## Theme chain

`Hyva/default` (parent, `vendor/hyva-themes/magento2-default-theme`, never edit)
→ `Shabeeb/hyva-child` (our child, `app/design/frontend/Shabeeb/hyva-child`, all theme work goes here)

Magento looks for a file in the child first, then the parent, then the module. To change a parent file, copy it to the **same relative path** in the child and edit the copy.

## Folder map

| Path | Owner | Edit? |
|---|---|---|
| `app/code/<Vendor>/<Module>` | us | yes — custom modules |
| `app/design/frontend/Shabeeb/hyva-child` | us | yes — theme |
| `app/etc/config.php` | Magento | only via `module:enable/disable` |
| `app/etc/env.php` | local secrets | never commit, never print passwords |
| `vendor/` | Composer | **never** |
| `generated/`, `var/`, `pub/static/` | Magento (generated) | never by hand; safe to clear |

## Making changes show on the site

Developer mode is on, but Magento caches a lot. After a change, run the matching command (from the project root):

| Changed | Run |
|---|---|
| `.phtml` template or layout `.xml` | `php bin/magento cache:flush` |
| Tailwind classes in a template, or theme CSS | `npm --prefix app/design/frontend/Shabeeb/hyva-child/web/tailwind run build` then `cache:flush` |
| PHP class, `di.xml`, plugin, observer | `php bin/magento cache:flush` (if a "class does not exist" error appears: clear `generated/code` and `generated/metadata`) |
| New/removed module, `db_schema.xml`, data patch | `php bin/magento setup:upgrade` |
| Static JS/CSS under `web/` (not Tailwind) | clear `pub/static/frontend/Shabeeb` and `var/view_preprocessed`, then `cache:flush` |
| React checkout source | `npm --prefix app/code/Shabeeb/ReactCheckout/react-app run build` then `cache:flush` |
| Product, price, category or stock data | `php bin/magento indexer:reindex` |

Still stale? Hard-refresh the browser (Ctrl+Shift+R) — Hyvä caches customer data in localStorage.
If a static file changed but kept the same URL (logo, favicon, CSS), give all static URLs a new version so every browser reloads them:
`printf '%s' "$(date +%s)" > pub/static/deployed_version.txt` — use `printf`, never `echo`/`date >`: a trailing newline breaks every static URL and Hyvä's JS (`BASE_URL is not defined`).

Category page layout (1 vs 2 columns) is a category setting (admin: Catalog → Categories → Design → Layout); a child-theme `catalog_category_view.xml` cannot override it because same-name layout files merge with the parent's.

## Tech notes

- Hyvä 1.5 uses **Tailwind v4**: there is no `tailwind.config.js`. Config lives in `web/tailwind/tailwind-source.css` (`@theme`, `@source`) and `web/tailwind/hyva.config.json` (tokens, included folders). Output: `web/css/styles.css`.
- JavaScript in templates is **Alpine.js** (`x-data`, `x-show`, `@click`, `x-init`, `x-for`). No jQuery, no RequireJS, no Knockout on Hyvä pages.
- GraphQL endpoint: `POST http://shabeeb-hyva.test/graphql`. Guest checkout flow: `createGuestCart` → `addProductsToCart` → `setGuestEmailOnCart` → `setShippingAddressesOnCart` → `setBillingAddressOnCart` → `setShippingMethodsOnCart` (`flatrate`) → `setPaymentMethodOnCart` (`checkmo`) → `placeOrder`.

## Skills in this project (`.claude/skills/`)

- `/magento-refresh` — work out what changed and run the right command from the table above.
- `/hyva-override` — override a parent Hyvä template/layout in the child theme.
- `/hyva-tailwind` — set up, build or watch the child theme's Tailwind CSS.
- `/graphql-test` — run a GraphQL query or mutation against the site and explain the result.
- `/react-checkout-build` — build and wire the React checkout module.

## Rules

- Never edit `vendor/`, `~/Projects/shabeeb-store` or `~/magento`.
- Never print or commit passwords from `app/etc/env.php`.
- No git commits unless the user asks.
- Ask before deleting anything or adding anything beyond the current step.
