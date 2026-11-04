---
name: react-checkout-build
description: Build, change or debug the React checkout module (Shabeeb_ReactCheckout) that replaces Hyvä's missing checkout page. Use for any work on /checkout.
---

# React checkout module

Module: `app/code/Shabeeb/ReactCheckout`. Guest checkout only, Flat Rate shipping, Check/Money Order payment, UAE addresses.

## Structure
| Path | Purpose |
|---|---|
| `registration.php`, `etc/module.xml` | register the module (sequence after `Magento_Checkout`, `Magento_QuoteGraphQl`, `Hyva_Theme`) |
| `ViewModel/CheckoutConfig.php` | reads the session cart, returns its masked ID (creates one if missing) + GraphQL URL, store code, URLs as JSON |
| `view/frontend/layout/checkout_index_index.xml` | removes Hyvä's `fallback.module.missing` block, adds `react.checkout` (`cacheable="false"`) with the ViewModel |
| `view/frontend/templates/checkout.phtml` | `<div id="react-checkout" data-config="…">` + `<script type="module">` for the bundle |
| `react-app/src/main.jsx` | mounts `<App>` on `#react-checkout` with the parsed config |
| `react-app/src/App.jsx` | state (cart, step, busy, error, orderNumber) and the step handlers that call mutations |
| `react-app/src/components/*.jsx` | AddressForm, ShippingMethods, PaymentMethods, OrderSummary, Success |
| `react-app/src/graphql/client.js` | `graphql(config, query, variables)` — fetch + throw on `errors` |
| `react-app/src/graphql/operations.js` | `CartFields` fragment + all queries/mutations |
| `view/frontend/web/js/checkout.js` | Vite build output, served by Magento — never edit by hand |

## Workflow
1. Edit source in `react-app/src`.
2. Build: `npm --prefix app/code/Shabeeb/ReactCheckout/react-app run build` (or `run watch` while developing).
3. New Tailwind classes in `.jsx`: rebuild the theme CSS (`/hyva-tailwind`) — `hyva.config.json` already scans `react-app/src`.
4. Make it show: `rm -rf pub/static/frontend/Shabeeb var/view_preprocessed/pub/static/frontend/Shabeeb`, bump the static version with `printf '%s' "$(date +%s)" > pub/static/deployed_version.txt`, `php bin/magento cache:flush`.
5. Test: add a jersey on `/real-madrid-home-jersey-2026-27.html`, open `/checkout`, complete all steps, confirm the order number and the order in admin (Sales → Orders).

## Rules
- All Magento communication goes through GraphQL; keep operations in `src/graphql/operations.js` and return `...CartFields` from cart mutations.
- The cart ID comes from the ViewModel (session cart); never create a second cart.
- Every request: disable buttons while `busy`, show `errors[].message` to the shopper.
- After `placeOrder`, dispatch `reload-customer-section-data` so Hyvä's mini-cart updates.
- Explain each new component (what state it holds, which mutation it calls, why).
