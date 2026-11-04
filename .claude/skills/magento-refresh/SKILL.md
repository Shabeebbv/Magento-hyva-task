---
name: magento-refresh
description: Make recent code changes visible on shabeeb-hyva.test. Use when the user says a change "is not showing", or after editing templates, layout XML, PHP, modules, Tailwind CSS or the React checkout.
---

# Make changes show on the site

1. Find what changed: `git status --short` (and the files edited in this conversation).
2. Pick the commands from this table. Run each one once, in this order, from the project root:

   | Changed files | Command |
   |---|---|
   | `app/code/*/etc/module.xml`, `registration.php`, `db_schema.xml`, `Setup/Patch/**` | `php bin/magento setup:upgrade` |
   | `hyva-child/**/*.phtml` or `app/code/**/*.phtml` that add/change CSS classes | `npm --prefix app/design/frontend/Shabeeb/hyva-child/web/tailwind run build` |
   | `hyva-child/web/tailwind/**` | same Tailwind build |
   | `app/code/Shabeeb/ReactCheckout/react-app/src/**` | `npm --prefix app/code/Shabeeb/ReactCheckout/react-app run build` |
   | non-Tailwind files under any `view/frontend/web/` or theme `web/` | `rm -rf pub/static/frontend/Shabeeb var/view_preprocessed/pub/static/frontend/Shabeeb` |
   | anything (always last) | `php bin/magento cache:flush` |

   If a static file kept its URL but changed content (logo.svg, favicon.ico, images), bump the static version so browsers stop using their cached copy:
   `printf '%s' "$(date +%s)" > pub/static/deployed_version.txt` (no trailing newline — a newline breaks all static URLs and Hyvä's JS).

   Only if the site shows "Class ... does not exist" or an interceptor error: `rm -rf generated/code/* generated/metadata/*` then reload the page.
3. Check the result: `curl -s -o /dev/null -w "%{http_code}\n" http://shabeeb-hyva.test/<page>` should be `200`. If not, show the last lines of `var/log/exception.log`.
4. Tell the user which commands ran and **why each one was needed** (one line each). Remind them to hard-refresh (Ctrl+Shift+R) if the browser still shows old content.
