---
name: hyva-override
description: Change something on the Hyvä storefront by overriding a parent template or layout in the Shabeeb/hyva-child theme. Use when the user wants to change how a page part looks or what it shows (header, footer, product page, home page...).
---

# Override a Hyvä template or layout in the child theme

Child theme: `app/design/frontend/Shabeeb/hyva-child`. Parent: `vendor/hyva-themes/magento2-default-theme`. Never edit the parent.

1. **Find the template that renders it.**
   - Search the parent for visible text or a class: `grep -rn "<text>" vendor/hyva-themes/magento2-default-theme`.
   - Or find the block in layout XML: `grep -rn "name=\"<block-name>\"" vendor/hyva-themes/magento2-default-theme/*/layout`.
   - A template reference `Magento_Theme::html/footer/copyright.phtml` means file `Magento_Theme/templates/html/footer/copyright.phtml`.
2. **Decide: template override or layout change.**
   - Change markup/text of one part → copy the template.
   - Move, remove or add blocks, or change a block's arguments → add a layout XML file in the child with the same handle name (`default.xml` = all pages, `cms_index_index.xml` = home, `catalog_product_view.xml` = product page). Use `referenceBlock` / `referenceContainer`; do not copy the whole parent layout file.
3. **Copy to the same relative path** in the child, e.g.
   `cp vendor/hyva-themes/magento2-default-theme/Magento_Theme/templates/html/footer/copyright.phtml app/design/frontend/Shabeeb/hyva-child/Magento_Theme/templates/html/footer/copyright.phtml`
   (create folders with `mkdir -p`). Only copy the files you will change.
4. **Edit the copy.** Escape all output: `$escaper->escapeHtml()`, `escapeHtmlAttr()`, `escapeUrl()`. Use Tailwind classes for styling and Alpine.js for behaviour.
5. **Make it show:** run `/magento-refresh` (new Tailwind classes need the Tailwind build).
6. **Explain to the user:** the parent path, the child path, why the same path makes Magento use the child copy, and what each changed line does.
