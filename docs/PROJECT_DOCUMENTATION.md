# Shabeeb Store — Technical Documentation

| | |
|---|---|
| Project directory | `~/Projects/shabeeb-store` |
| Platform | Magento Open Source 2.4.9 (developer mode) |
| Storefront | `http://shabeeb-store.test/` |
| Admin | `http://shabeeb-store.test/admin_shabeeb` |
| Theme | `Shabeeb/furniture` (parent: `Magento/luma`) |
| Custom modules | `Shabeeb_Storefront`, `Shabeeb_GuestWishlist` |
| Design reference | `https://2xlhome.com/ae-en/glowy-hydraulic-bed-180x200cm-130977` |
| Documented state | Inspected on 2026-10-23 |

All paths below are relative to `~/Projects/shabeeb-store` unless stated otherwise.

---

## 1. What this project is

A small Magento store used as a design and behaviour prototype:

- **One category** (`Furniture`, URL `furniture.html`) and **one product** (SKU `SF-BED-K180`, URL `serene-bed.html`, AED 3,599).
- A **custom theme** that restyles Luma to follow the reference store's look (header, navigation, listing, product page, cart, wishlist, footer).
- **Guest wishlist**: guests can save products without logging in; the list moves into their account when they log in.
- **Guest cart merge** on login uses Magento's built-in behaviour (no custom code).
- Storefront text branding is set to **"2XL Home"** (configuration and the text logo). The theme, modules, database and URLs keep the `shabeeb` names.

> **Demo data note:** the product's name and description still describe the "Serene Hydraulic Bed", but its visible images are four photos of a sofa (see §4.4). This was a deliberate visual-demo choice; the original bed images are still in the gallery, disabled.

---

## 2. Architecture

### 2.1 Theme structure and inheritance

```
app/design/frontend/Shabeeb/furniture/
├── registration.php                 registers the theme as frontend/Shabeeb/furniture
├── theme.xml                        title + <parent>Magento/luma</parent>
├── etc/view.xml                     product image sizes used by the theme
├── web/
│   ├── css/source/_extend.less      ALL custom styling (one file)
│   ├── fonts/jost/                  self-hosted Jost font + OFL licence
│   └── js/                          qty-stepper.js, product-gallery.js
├── Magento_Theme/                   overrides for the Magento_Theme module
│   ├── layout/default.xml           header/footer blocks on every page
│   ├── layout/default_head_blocks.xml   font preload
│   └── templates/html/…             logo, header services bar, account icon, footer
├── Magento_Catalog/                 overrides for Magento_Catalog
│   ├── layout/catalog_category_view.xml   listing page (PLP)
│   ├── layout/catalog_product_view.xml    product page (PDP)
│   └── templates/product/view/…     gallery, add-to-cart, accordion, category label
└── Magento_Checkout/                overrides for Magento_Checkout
    ├── layout/checkout_cart_index.xml     cart page
    └── templates/cart/steps.phtml
```

**How inheritance works (Magento built-in):** Magento looks for every layout file, template, image, LESS file and JS file first in `Shabeeb/furniture`, then in `Magento/luma`, then in `Magento/blank`, then in the module itself. So we only keep the files we actually change; everything else comes from Luma.

- A **template** with the same path in our theme (e.g. `Magento_Catalog/templates/product/view/gallery.phtml`) **replaces** the module's template.
- A **layout XML** file with the same name (e.g. `catalog_product_view.xml`) is **merged** with the module's and Luma's versions — ours is applied last, so it can move, remove or add blocks.
- `web/css/source/_extend.less` is a hook Luma/Blank provide: it is imported **after** all Luma styles, so our variables and rules win.

### 2.2 Custom modules

| Module | Location | Responsibility |
|---|---|---|
| `Shabeeb_Storefront` | `app/code/Shabeeb/Storefront` | Catalog/content **data patches** (sample category and product, product text, homepage reset, sofa images) and the **Furniture → Sofa menu plugin**. |
| `Shabeeb_GuestWishlist` | `app/code/Shabeeb/GuestWishlist` | Guest wishlist: DB table, add/remove/list controllers, header counter, redirecting "Add to Wish List" buttons, and **merge into the customer wishlist on login**. |

Both are enabled in `app/etc/config.php` (`'Shabeeb_Storefront' => 1`, `'Shabeeb_GuestWishlist' => 1`).

### 2.3 How the pieces work together

```
URL ──► router ──► controller ──► page result
                                    │
                    layout handles (default, catalog_product_view, …)
                                    │
                    layout XML (module → Luma → our theme, merged)
                                    │
                    blocks (+ plugins that wrap block methods)
                                    │
                    PHTML templates (theme override or module default)
                                    │
                    HTML + <script type="text/x-magento-init"> + CSS links
                                    │
         browser: styles-m.css / styles-l.css (compiled from LESS, incl. _extend.less)
                  RequireJS runs JS components (qty-stepper, product-gallery, header-link)
                  customer-data sections fill private content (cart count, wishlist count)
```

- **Layout XML** decides *which blocks* appear and *where* (containers).
- **Blocks** (PHP classes) provide data; **templates** turn it into HTML.
- **Plugins** (interceptors, declared in `di.xml`) change the behaviour of a core class method without editing core code.
- **LESS** compiles into `styles-m.css` (all widths) and `styles-l.css` (≥768px). In developer mode this happens on first request.
- **JavaScript** is attached with `data-mage-init` / `text/x-magento-init`; RequireJS loads the module (e.g. `js/product-gallery`) from the theme.
- **Customer-data sections** (`Magento_Customer/js/customer-data`): pages are full-page cached, so per-visitor data (mini-cart count, wishlist count) is loaded by AJAX from `/customer/section/load/` after the page is shown.

### 2.4 Where product data, CMS, cart, wishlist and login live

| Area | Built into Magento | Our custom part |
|---|---|---|
| Product data (name, SKU, price, images, category) | Catalog EAV tables; edited in Admin → Catalog | Initial data and image assignment via data patches in `Shabeeb_Storefront` |
| CMS pages (homepage) | `cms_page` table; Admin → Content → Pages | `RestoreDefaultHomePage` patch keeps the default Luma homepage |
| Cart | Quote (`quote`, `quote_item`), mini-cart section, login merge | Styling only |
| Wishlist (customers) | `Magento_Wishlist` (`wishlist`, `wishlist_item`) | Add button rerouted; header counter section |
| Wishlist (guests) | Not supported by Magento | `Shabeeb_GuestWishlist` (own table + cookie token) |
| Login | `Magento_Customer`, dispatches `customer_login` event | Observer merges guest wishlist on that event |
| Stock | MSI (`Magento_Inventory*`) | None |

---

## 3. Important files

### 3.1 Theme registration and global styling

| File | Purpose | What we changed | Used by | Connects to |
|---|---|---|---|---|
| `app/design/frontend/Shabeeb/furniture/registration.php` | Registers the theme with Magento | Created | All storefront pages | `theme.xml`; theme row `Shabeeb/furniture` (theme_id 4) |
| `app/design/frontend/Shabeeb/furniture/theme.xml` | Theme title + parent | `<parent>Magento/luma</parent>` | All pages | Selected via config `design/theme/theme_id = 4` |
| `app/design/frontend/Shabeeb/furniture/web/css/source/_extend.less` | All custom CSS (variables, header, PLP, PDP, cart, wishlist, footer) | Created; ~1,800 lines in clearly commented sections | All pages | Compiled into `styles-m.css`/`styles-l.css`; targets classes from our templates and Luma's |
| `app/design/frontend/Shabeeb/furniture/Magento_Theme/layout/default_head_blocks.xml` | `<head>` assets | Removes Luma's Open Sans preloads, preloads Jost | All pages | `web/fonts/jost/jost-latin-wght-normal.woff2`, `@font-face` in `_extend.less` |
| `app/design/frontend/Shabeeb/furniture/web/fonts/jost/` | Self-hosted Jost variable font + `OFL.txt` licence | Added | All pages | Loaded by `@font-face` in `_extend.less` |
| `app/design/frontend/Shabeeb/furniture/etc/view.xml` | Product image sizes per image ID | Listing 640, gallery 900, thumbnails 360; `<frame>false</frame>` to keep the photo's proportions | PLP, PDP, wishlist | Image IDs used by `ListProduct` and `gallery.phtml` |

### 3.2 Header, navigation, dropdown, footer

| File | Purpose | What we changed | Used by | Connects to |
|---|---|---|---|---|
| `…/Magento_Theme/layout/default.xml` | Blocks on every page | Text logo template + alt "2XL Home"; services bar block in `header.panel`; account icon block in `header-wrapper`; footer block; newsletter moved into footer; removed compare/footer-links/store-switcher blocks | All pages | Templates below; `shabeeb.wishlist.link` from `Shabeeb_GuestWishlist` |
| `…/Magento_Theme/templates/html/header/logo.phtml` | Text logo "2XL / Home" | Replaces core image logo; keeps the core `nav-toggle` (mobile menu button) | All pages | `.shabeeb-logo` styles |
| `…/Magento_Theme/templates/html/header/services.phtml` | Black top bar notes | Created | All pages | `.shabeeb-services` styles |
| `…/Magento_Theme/templates/html/header/account-link.phtml` | Account icon | Links to `customer/account` (guests are redirected to sign-in) | All pages | `.shabeeb-account-link` styles |
| `…/Magento_Theme/templates/html/footer.phtml` | Footer link columns + newsletter | Created (Shop, My Account, Customer Service, Policies, Sign Up for Newsletter) | All pages | `form.subscribe` child block; footer styles |
| `app/code/Shabeeb/Storefront/etc/frontend/di.xml` | Declares the menu plugin | Plugin on `Magento\Theme\Block\Html\Topmenu`, `sortOrder="100"` | All pages (top menu) | `Plugin/FurnitureSubmenu.php` |
| `app/code/Shabeeb/Storefront/Plugin/FurnitureSubmenu.php` | Adds "Sofa" under "Furniture" | `beforeGetHtml()` adds a child menu node pointing to the Furniture URL | Header navigation (desktop dropdown, mobile drawer) | Runs after core `catalogTopmenu` plugin which builds category nodes |

### 3.3 Product listing page (PLP)

| File | Purpose | What we changed | Used by | Connects to |
|---|---|---|---|---|
| `…/Magento_Catalog/layout/catalog_category_view.xml` | Category page layout | `layout="1column"`; breadcrumbs moved under the title; category description and compare removed | `/furniture.html` | Core `category.products.list` block (core `list.phtml` template) |
| `_extend.less` → "Product listing" + "Card details" + "Listing grid width" sections | Card and grid styling | 1000:820 image frame with `object-fit: contain`; name + heart / price + cart-icon rows; grid width 88vw; 2/3/4 columns | PLP, search results (`.page-products`) | Classes from core `list.phtml` |
| `…/etc/view.xml` (`category_page_grid`) | Listing image size | 640px, no square frame | PLP | Image URL in core list template |

### 3.4 Product detail page (PDP), gallery, quantity + Add to Cart

| File | Purpose | What we changed | Used by | Connects to |
|---|---|---|---|---|
| `…/Magento_Catalog/layout/catalog_product_view.xml` | PDP block arrangement | Adds category label and accordion blocks; moves SKU and review summary above the price; removes description/overview tabs, compare and email-to-friend | `/serene-bed.html` | Templates below |
| `…/Magento_Catalog/templates/product/view/gallery.phtml` | Product gallery | Replaces Luma's Fotorama carousel with one main image + thumbnail buttons | PDP | `Magento\Catalog\Block\Product\View\Gallery` (core block), `web/js/product-gallery.js` |
| `…/web/js/product-gallery.js` | Thumbnail click → main image | Created | PDP | `data-role="gallery-main"` / `gallery-thumb` in `gallery.phtml` |
| `…/Magento_Catalog/templates/product/view/addtocart.phtml` | Quantity + Add to Cart | Based on core template; adds −/+ buttons and "Quantity" label | PDP | `web/js/qty-stepper.js`; core `Magento_Catalog/js/validate-product` |
| `…/web/js/qty-stepper.js` | −/+ buttons | Created; changes the real `qty` input so validation still applies | PDP | `addtocart.phtml` |
| `…/Magento_Catalog/templates/product/view/accordion.phtml` | "Product Description" and "Shipping and Returns" sections | Created (native `<details>` elements, no JS) | PDP | Product `description` attribute via `Magento\Catalog\Helper\Output` |
| `…/Magento_Catalog/templates/product/view/category-label.phtml` | Category name under the title | Created | PDP | Product's category collection |
| `_extend.less` → "Product page" + "Gallery" sections | PDP layout and controls | Gallery left (53.4%) / info right (41.7%); main frame 1000:820; quantity row as a 2-column grid | PDP | Templates above |

### 3.5 Cart and wishlist pages

| File | Purpose | What we changed | Used by | Connects to |
|---|---|---|---|---|
| `…/Magento_Checkout/layout/checkout_cart_index.xml` | Cart page layout | Adds the steps line under the title | `/checkout/cart/` | `cart/steps.phtml` |
| `…/Magento_Checkout/templates/cart/steps.phtml` | "Shopping Cart → Checkout → Order Complete" | Created (static text) | Cart | `.shabeeb-cart-steps` styles |
| `_extend.less` → "Cart" and "Wish lists" sections | Cart table, summary, checkout button, wishlist cards | Styling only | Cart, `/guestwishlist/`, `/wishlist/` | Core cart templates; `Shabeeb_GuestWishlist::list.phtml` |

### 3.6 Guest wishlist module (`app/code/Shabeeb/GuestWishlist`)

| File | Purpose | Connects to |
|---|---|---|
| `registration.php`, `etc/module.xml` | Registers module; loads after Catalog, Customer, Wishlist | — |
| `etc/db_schema.xml` (+ `db_schema_whitelist.json`) | Table `shabeeb_guest_wishlist_item` (token, product, store, qty); **unique (token, product, store)**; foreign keys to product and store with `ON DELETE CASCADE` | `Model/GuestItemStorage.php` |
| `Model/GuestToken.php` | Reads/creates the `shabeeb_gwl` cookie: a random 64-hex token, HttpOnly, SameSite=Lax, 30 days | Controllers, `WishlistManager`, observer |
| `Model/GuestItemStorage.php` | SQL access to the guest table (add/remove/list/count/deleteAll) | `WishlistManager`, `Block/GuestList.php` |
| `Model/WishlistManager.php` | Business rules: product checks, add for guest/customer, **merge**, counts, duplicate check | Controllers, observer, section source |
| `etc/frontend/routes.xml` | Front name `guestwishlist` | Controllers below |
| `Controller/Item/Add.php` | POST add (guest table or native customer wishlist) | `WishlistManager` |
| `Controller/Item/Remove.php` | POST remove (guests) | `WishlistManager` |
| `Controller/Index/Index.php` | `/guestwishlist/` page; logged-in customers are redirected to `/wishlist/` | `guestwishlist_index_index.xml` |
| `Block/GuestList.php` + `view/frontend/templates/list.phtml` + `view/frontend/layout/guestwishlist_index_index.xml` | Guest wishlist page | `GuestItemStorage`, product collection |
| `etc/frontend/di.xml` | (1) plugin on `Magento\Wishlist\Helper\Data`; (2) registers the `shabeeb-wishlist` customer-data section | `Plugin/WishlistHelperPlugin.php`, `CustomerData/Wishlist.php` |
| `Plugin/WishlistHelperPlugin.php` | `afterGetAddParams()` changes every "Add to Wish List" button's target to `guestwishlist/item/add` | Core wishlist links on PLP/PDP |
| `CustomerData/Wishlist.php` + `etc/frontend/sections.xml` | Header counter data; which actions refresh it | `view/frontend/web/js/header-link.js` |
| `view/frontend/layout/default.xml` + `templates/header-link.phtml` + `web/js/header-link.js` | Header heart icon with counter (Knockout component) | `shabeeb-wishlist` section |
| `etc/frontend/events.xml` + `Observer/MergeGuestWishlist.php` | On `customer_login`, merge guest items into the customer wishlist | `WishlistManager::mergeGuestIntoCustomer()` |

### 3.7 Storefront module: catalog data patches and media

Data patches run once each (tracked in the `patch_list` table) during `bin/magento setup:upgrade`. Dependencies define the order.

| Patch (`app/code/Shabeeb/Storefront/Setup/Patch/Data/…`) | What it does | Depends on |
|---|---|---|
| `AddSampleCatalog.php` | Creates the Furniture category and the bed product (SKU, price, stock 50, fixture images from `fixtures/images/`) | — |
| `RepairSampleCategoryPaths.php` | Fixes category `path` so the category appears in the tree and menu | `AddSampleCatalog` |
| `SimplifySampleCatalog.php` | Shrinks an older 2-category / 8-product sample set to 1 category + 1 product | both above |
| `RefreshStorefrontContent.php` | Product name/descriptions; category description and `1column` layout | `SimplifySampleCatalog` |
| `RestoreDefaultHomePage.php` | Sets CMS page `home` back to Magento's default title, heading and content | `RefreshStorefrontContent` |
| `UseSofaDemoImage.php` | Adds `pub/media/catalog/product/shabeeb/sofa-pexels-7018400.jpg` as the main/small/thumbnail image; **disables** (does not delete) the three bed images | `RefreshStorefrontContent` |
| `AddSofaGalleryViews.php` | Adds three more views of the same sofa (7018401, 7018389, 7018406) | `UseSofaDemoImage` |

Media source files (Pexels photos by Max Vakhtbovych, Pexels licence) are stored in `pub/media/catalog/product/shabeeb/`. Magento copies each into its own storage path when added to the gallery (e.g. `/s/o/sofa-pexels-7018400_1.jpg`).

### 3.8 CMS homepage

No theme or layout file customises the homepage. It is the default CMS page `home` (Admin → Content → Pages), reset by `RestoreDefaultHomePage.php`. It still uses the shared header and footer from `Magento_Theme/layout/default.xml`.

---

## 4. How the important parts work

### 4.1 How the theme is selected and inherits from Luma

**Built-in:** Magento stores the frontend theme per scope in `core_config_data` as `design/theme/theme_id`. In this project it is `4` at default scope, and the `theme` table maps id 4 → `Shabeeb/furniture` with `parent_id` 3 (`Magento/luma`).

**Ours:**

```php
// app/design/frontend/Shabeeb/furniture/registration.php
ComponentRegistrar::register(ComponentRegistrar::THEME, 'frontend/Shabeeb/furniture', __DIR__);
```

```xml
<!-- app/design/frontend/Shabeeb/furniture/theme.xml -->
<theme …>
    <title>Shabeeb Furniture</title>
    <parent>Magento/luma</parent>
</theme>
```

`_extend.less` overrides Luma's LESS **variables** (font, colours, buttons, inputs). LESS uses the *last* definition of a variable everywhere, so Luma's own rules pick up our values:

```less
// _extend.less
@font-family__base: 'Jost', 'Helvetica Neue', Helvetica, Arial, sans-serif;
@button-primary__background: @sf-ink;   // black primary buttons on every page
@footer__background-color: #fff;
```

### 4.2 How Magento chooses which layout XML and template to render

**Built-in:**
1. The router maps the URL to a controller (e.g. `serene-bed.html` → URL rewrite → `catalog/product/view/id/5`).
2. The controller's page result adds **layout handles**: always `default`, plus a page handle such as `catalog_product_view` (and more specific ones like `catalog_product_view_type_simple`).
3. For each handle Magento merges every `layout/<handle>.xml` it can find: modules → `Magento/blank` → `Magento/luma` → `Shabeeb/furniture`.
4. Each block's `template="Module::path.phtml"` is resolved through the same fallback, so a file at `Shabeeb/furniture/Module/templates/path.phtml` wins.

**Ours** (example — `Magento_Catalog/layout/catalog_product_view.xml`):

```xml
<referenceContainer name="product.info.main">
    <block class="Magento\Catalog\Block\Product\View" name="shabeeb.product.category"
           template="Magento_Catalog::product/view/category-label.phtml" before="product.info.price"/>
    <block class="Magento\Catalog\Block\Product\View" name="shabeeb.product.accordion"
           template="Magento_Catalog::product/view/accordion.phtml" after="-"/>
</referenceContainer>
<move element="product.info.stock.sku" destination="product.info.main" before="product.info.price"/>
<referenceBlock name="product.info.description" remove="true"/>
```

### 4.3 How the header dropdown works

**Built-in:** `Magento\Theme\Block\Html\Topmenu` renders the navigation. The core plugin `Magento\Catalog\Plugin\Block\Topmenu::beforeGetHtml()` adds category nodes. Luma's `menu` jQuery widget opens submenus on hover (desktop) and as an accordion in the mobile drawer.

**Ours:** a second `beforeGetHtml()` plugin (sortOrder 100, so it runs after the catalog plugin) adds a child node under the node whose URL ends with `/furniture.html`. Its URL is the Furniture URL, so clicking "Sofa" opens the existing listing.

```php
// app/code/Shabeeb/Storefront/Plugin/FurnitureSubmenu.php
foreach ($subject->getMenu()->getChildren() as $node) {
    $url = (string)$node->getUrl();
    if (!str_ends_with($url, self::PARENT_URL_SUFFIX) || $node->getChildren()->searchById(self::NODE_ID)) {
        continue;
    }
    $node->addChild(new Node(['id' => self::NODE_ID, 'name' => __('Sofa'), 'url' => $url, …], 'id', $node->getTree(), $node));
}
```

```
Any page request
→ layout handle "default" → block catalog.topnav (Magento\Theme\Block\Html\Topmenu)
→ plugins: Magento\Catalog\Plugin\Block\Topmenu (adds "Furniture")
           Shabeeb\Storefront\Plugin\FurnitureSubmenu (adds "Sofa" under it)
→ Magento_Theme::html/topmenu.phtml (core template)
→ _extend.less "Grey navigation bar with dropdowns" (.navigation .level0 .submenu)
→ Luma "menu" widget shows the submenu on hover / tap
```

There is **no Sofa category** — "Sofa" is only a menu link.

### 4.4 How product images are assigned and rendered

**Data (custom, via patches):** the product's media gallery contains 7 entries. The three bed images are `disabled = 1`; the four sofa photos are enabled, and `sofa-pexels-7018400` holds the `image`, `small_image` and `thumbnail` roles.

```php
// UseSofaDemoImage.php (simplified)
foreach ($gallery['images'] as $key => $image) {
    $gallery['images'][$key]['disabled'] = 1;          // hide bed images, keep them
}
$product->setMediaGallery($gallery);
$product->addImageToMediaGallery($media->getAbsolutePath(self::SOURCE),
    ['image', 'small_image', 'thumbnail'], false, false);
$this->productRepository->save($product);
```

**Sizes (theme):** `etc/view.xml` defines resize targets. `<frame>false</frame>` stops Magento padding the photo into a white square, so the resized file keeps the photo's own ratio (e.g. 640×490).

**PLP (built-in template, our CSS):** the core list template asks for image ID `category_page_grid` (uses `small_image`). CSS forces a 1000:820 frame (same ratio as the reference) and fits the photo inside without cropping:

```less
.product-image-wrapper { height: 0 !important; padding-bottom: 82% !important; }  // 820/1000
.product-image-photo   { height: 100%; object-fit: contain; width: 100%; }
```

**PDP (our template + JS):** `gallery.phtml` uses the core gallery block's enabled images (`getGalleryImages()` skips disabled ones). It prints one main `<img>` (`medium_image_url`, 900px) and one `<button>` per image with a 360px thumbnail and the large URL in `data-src`. `product-gallery.js` swaps the main image on click:

```js
$root.on('click', '[data-role=gallery-thumb]', function () {
    var $thumb = $(this);
    $main.attr({src: $thumb.data('src'), alt: $thumb.data('alt')});
    …
});
```

```
Product page request
→ catalog/product/view (core controller)
→ layout catalog_product_view (core + Luma + our theme XML)
→ block product.info.media.image (Magento\Catalog\Block\Product\View\Gallery)
→ template Shabeeb/furniture/Magento_Catalog/templates/product/view/gallery.phtml
→ JS js/product-gallery (data-mage-init), CSS "Gallery" section in _extend.less
→ main image + 4 clickable thumbnails
```

### 4.5 How the CSS/LESS changes affect pages

All styling is in one file, organised by section:

| Section in `_extend.less` | Affects |
|---|---|
| Variables at the top (palette, icons, typography, buttons, forms, footer) | Every page — Luma's own rules use these |
| `& when (@media-common = true)` block | Rules for all widths (compiled into `styles-m.css`) |
| `.media-width(... 'max' ... @screen__m)` | Mobile only (<768px) |
| `.media-width(... 'min' ... @screen__m)` | Tablet and up (≥768px, compiled into `styles-l.css`) |
| `.media-width(... 'min' ... @screen__l)` | Desktop (≥1024px): 40px page gutters, footer columns |
| `@media (min-width: 1680px)` | 4 listing columns |

Important detail: `styles-l.css` loads **after** `styles-m.css`, so desktop rules from Luma can override our mobile/common rules. Where that happened, the fix was either a more specific selector (e.g. `.catalog-product-view …`) or putting the rule into the min-width block.

**Quantity + Add to Cart row** — a 2-column grid; the label spans row 1, the quantity box and button share row 2 and align at the top, so extra payment buttons rendered under Add to Cart (Braintree container) cannot shift the alignment:

```less
.box-tocart .fieldset { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); column-gap: 8px; row-gap: 12px; align-items: start; }
.box-tocart .field.qty { display: contents; }
.box-tocart .field.qty .label   { grid-column: 1 / -1; grid-row: 1; }
.box-tocart .field.qty .control { grid-column: 1; grid-row: 2; }
.box-tocart .actions            { grid-column: 2; grid-row: 2; }
```

### 4.6 Guest cart retention and merge on login (Magento built-in — no custom code)

- A guest's cart is a **quote** row (`customer_id` NULL). Its id is kept in the checkout session (session cookie), so the cart survives navigation.
- The mini-cart count comes from the `cart` customer-data section.
- On login Magento fires `customer_login`. `Magento_Checkout/etc/frontend/events.xml` registers `Magento\Checkout\Observer\LoadCustomerQuoteObserver`, which calls `Magento\Checkout\Model\Session::loadCustomerQuote()`:

```php
// vendor/magento/module-checkout/Model/Session.php (core, simplified)
if ($customerQuote->getId() && $this->getQuoteId() != $customerQuote->getId()) {
    if ($this->getQuoteId()) {                       // a guest cart exists
        $customerQuote->merge($quote)->collectTotals();
        …
    }
}
```

```php
// vendor/magento/module-quote/Model/Quote.php::merge() (core, simplified)
if ($quoteItem->compare($item)) {                    // same product/options
    $quoteItem->setQty($quoteItem->getQty() + $item->getQty());   // quantities are added
} else {
    $this->addItem($newItem);                        // new line
}
```

So existing customer items are kept, matching items are summed, and new items are added.

### 4.7 Guest wishlist storage and merge without duplicates (custom)

**Storage:** each guest gets a random token in the HttpOnly cookie `shabeeb_gwl`; rows live in `shabeeb_guest_wishlist_item`. A unique key on `(guest_token, product_id, store_id)` makes duplicates impossible at the database level — `GuestItemStorage::add()` catches `DuplicateException` and reports "already in your Wish List".

**Routing the button:** every "Add to Wish List" link gets its POST data from `Magento\Wishlist\Helper\Data::getAddParams()`. Our plugin changes only the target URL, so the same button works for guests and customers:

```php
// Plugin/WishlistHelperPlugin.php
$params['action'] = $this->url->getUrl('guestwishlist/item/add');
```

`Controller/Item/Add.php` then writes to the guest table (guest) or the native customer wishlist (logged in).

**Merge on login:**

```
customer_login event (built-in)
→ etc/frontend/events.xml → Observer\MergeGuestWishlist::execute()
→ GuestToken::get()  (no cookie → nothing to do)
→ WishlistManager::mergeGuestIntoCustomer($customerId, $token)
     for each guest row:
        containsProduct() checks wishlist_item directly → skip if already there
        else $wishlist->addNewItem($product, qty)
     $wishlist->save(); deleteAll($token)   // guest rows removed only after the loop
→ GuestToken::delete()  (cookie removed)
```

`containsProduct()` queries the `wishlist_item` table directly instead of the item collection, because the collection hides out-of-stock/invisible items and would let a duplicate through. Errors are logged and never block the login.

**Header counter:** `CustomerData/Wishlist.php` returns `{count, url}` for guests (guest table) and customers (native wishlist). `sections.xml` tells Magento which actions must refresh that section; `header-link.js` (Knockout) renders the number.

### 4.8 Stock validation (Magento built-in — MSI)

- The product has quantity 50 in source `default` (`inventory_source_item`), stock managed (config default).
- When a quote item's quantity is set, MSI checks salable quantity (`Magento\InventorySales\Model\IsProductSalableForRequestedQtyCondition\IsSalableWithReservationsCondition`). If the request exceeds it, the item and quote get the error **"Not enough items for sale"** and Magento hides/blocks the checkout button until the quantity is fixed.
- Merging does not cap quantities: 30 (customer) + 30 (guest) becomes 60 with that error shown. No custom code is involved.

### 4.9 How the custom modules are registered, configured and executed

```
registration.php        → ComponentRegistrar::register(MODULE, 'Shabeeb_X', __DIR__)
etc/module.xml          → module name + <sequence> (load after the listed modules)
app/etc/config.php      → 'Shabeeb_X' => 1 (enabled)
etc/frontend/di.xml     → plugins, section sources (frontend area only)
etc/frontend/events.xml → observers (frontend area only)
etc/frontend/routes.xml → URL front name (GuestWishlist only)
etc/db_schema.xml       → table created by setup:upgrade (GuestWishlist only)
Setup/Patch/Data/*.php  → run once by setup:upgrade, recorded in patch_list (Storefront only)
```

At runtime, Magento's generated interceptors (`generated/code/…/Interceptor.php`) call our plugin methods around the core methods; observers run when the event is dispatched.

---

## 5. Request-to-render flows

**Listing page (`/furniture.html`)**
```
URL rewrite furniture.html → catalog/category/view/id/3 (core controller)
→ handles: default, catalog_category_view
→ layout: core + Luma + Shabeeb/furniture/Magento_Catalog/layout/catalog_category_view.xml
     (1column, breadcrumbs under title, description/compare removed)
→ block category.products.list (Magento\Catalog\Block\Product\ListProduct) with core list.phtml
     image: small_image @ category_page_grid (view.xml, 640, frame=false)
     wishlist link: Magento\Wishlist\Helper\Data::getAddParams() → WishlistHelperPlugin
→ CSS: _extend.less "Product listing", "Card details", "Listing grid width" sections
→ 1 card: 1000:820 image frame, name + heart, price + cart icon
```

**Product page (`/serene-bed.html`)**
```
URL rewrite serene-bed.html → catalog/product/view/id/5 (core controller)
→ handles: default, catalog_product_view, …
→ layout: core + Luma + Shabeeb/furniture/Magento_Catalog/layout/catalog_product_view.xml
→ blocks: Gallery → gallery.phtml; View → addtocart.phtml, category-label.phtml, accordion.phtml
→ JS: js/product-gallery, js/qty-stepper, core catalogAddToCart + validate-product
→ CSS: _extend.less "Product page", "Gallery"
→ gallery left, info right; Add to Cart posts to checkout/cart/add (core)
```

**Add to Wish List (guest)**
```
Click heart (PLP or PDP) → POST guestwishlist/item/add (URL from WishlistHelperPlugin)
→ Controller\Item\Add → WishlistManager::addForGuest() → GuestItemStorage::add()
→ redirect back; sections.xml invalidates "shabeeb-wishlist"
→ header-link.js reloads the section → counter shows the new count
```

**Login**
```
POST customer/account/loginPost (core) → customer_login event
→ core LoadCustomerQuoteObserver: guest quote merged into customer quote
→ our MergeGuestWishlist: guest rows merged into customer wishlist (no duplicates)
→ all customer-data sections reloaded → mini-cart and wishlist counters updated
```

---

## 6. Admin and configuration

| Task | Where |
|---|---|
| Admin URL | `http://shabeeb-store.test/admin_shabeeb` — front name `admin_shabeeb` is set in `app/etc/env.php` → `backend.frontName` (set at install with `--backend-frontname`) |
| Admin account | Username `shabeeb_admin`. The password is not in the project; it is kept in the developer's local config outside the project directory. Two-factor auth modules are disabled in this install. |
| CMS pages | Content → Pages (homepage is identifier `home`) |
| Which CMS page is the homepage | Stores → Configuration → General → Web → Default Pages → CMS Home Page. Not overridden in this project, so Magento's default `home` applies (`Magento_Cms/etc/config.xml`). |
| Product name, SKU, price, stock | Catalog → Products → "Serene Hydraulic Bed 180x200cm" |
| Product images | Same product → Images and Videos (bed images are present but hidden from the product page) |
| Categories | Catalog → Categories → Furniture |
| Active store view | Stores → All Stores: website `base`, store `main_website_store`, store view `default` ("Default Store View") |
| Active theme | Content → Design → Configuration → Default Store View → Applied Theme = "Shabeeb Furniture" (`design/theme/theme_id` = 4) |
| Storefront branding text | Stores → Configuration → General → Store Information → Store Name; Content → Design → Configuration → HTML Head (title) and Footer (copyright) — set to "2XL Home" |
| Currency | Stores → Configuration → General → Currency Setup (AED) |

---

## 7. Testing and known limitations

### 7.1 Tests performed (2026-10-23, headless Chrome scripted via DevTools protocol, plus database checks)

| Test | Result |
|---|---|
| Guest adds qty 2 to cart, visits listing, homepage, cart | Passed — cart still qty 2 |
| Guest adds to wishlist; `/guestwishlist/` lists the item; header counter | Passed — item listed, counter 1 |
| Customer cart qty 1 + guest cart qty 2 → login | Passed — one line, qty 3 (run twice) |
| Customer wishlist has the product, guest wishlist has the same product → login | Passed — 1 `wishlist_item` row in DB; log: "added 0, skipped 1" |
| Customer wishlist empty, guest wishlist has the product → login | Passed — item moved; guest rows deleted |
| Registration while guest items exist (registration also fires `customer_login`) | Passed — wishlist item present after registering |
| Login with empty guest cart | Passed — customer cart (qty 3) unchanged |
| Customer qty 30 + guest qty 30 (stock 50) → login | Passed — merged to 60, "Not enough items for sale" shown, Proceed to Checkout not offered |
| Furniture hover dropdown shows "Sofa" (text only); click opens `/furniture.html` | Passed (desktop and mobile drawer) |
| PLP image frame vs reference at 1920/1600/1440/1280/1024/768/390 px | Passed — e.g. 402×330 at 1440 (reference 402×330); all 1:1.22 |
| PDP gallery: 4 images load; each thumbnail changes the main image | Passed (desktop and 390px) |
| Quantity box and Add to Cart: same height, tops/bottoms equal, 8px gap at 1440/1024/768/390/320 px | Passed |
| Browser console / failed requests on tested pages | No errors after caches were rebuilt |
| Demo isolation | `~/magento` files unchanged (checked by modification time); `http://localhost/` responds; this project uses its own DB `shabeeb_store` |

### 7.2 Not tested

- Checkout and order placement, payment methods (Braintree/PayPal not configured), emails (no SMTP — the welcome email failed to send).
- Merging carts or wishlists that contain **different** products (only one product exists).
- Configurable/bundle products in the guest wishlist; the 100-item guest limit; cookie expiry after 30 days.
- Real mobile devices and non-Chrome browsers (only Chrome at 390/320 px emulated widths).
- Production mode / static content deploy (only developer mode was used).
- Admin sign-in in a browser (the stored password was only verified against the hash).
- Search results page appearance.

### 7.3 Known limitations

- **Product content mismatch:** sofa photos are shown on a product named and described as a bed (visual demo).
- **"Sofa" is a menu link only** and opens the Furniture listing; there is no Sofa category.
- **Font:** Jost is used as a free alternative to the reference's licensed Futura; it is similar, not identical.
- **Branding:** "2XL Home" is text only; for a local prototype. The services bar texts are placeholder copy.
- **Merge does not cap quantity at stock:** the customer must reduce the quantity before checkout.
- **Leftover data:** `inventory_source_item` still holds rows for the 7 sample SKUs deleted earlier (harmless).
- **Fresh installs:** the sofa images are added from `pub/media/catalog/product/shabeeb/`; if those files are missing, the two sofa patches skip silently.
- **Leftover config:** Fotorama `gallery` vars in `etc/view.xml` are no longer used (custom gallery). The comment "brand + link columns" in `Magento_Theme/layout/default.xml` is out of date (there is no brand column).
- **768px:** the page can scroll ~15px horizontally because two visually hidden accessibility labels (header "Skip to Content", footer newsletter label) extend past the edge.
- **PDP breadcrumbs** show "Home / product" when the product URL is opened directly (Magento's default without category paths in product URLs).
- **Wishlist quantities:** the merge keeps the customer's existing item as-is (guest qty is not added).

---

## 8. Quick reference — "Where did you change X?"

| If asked about… | Open |
|---|---|
| Theme setup / parent theme | `app/design/frontend/Shabeeb/furniture/theme.xml`, `registration.php` |
| Fonts, colours, buttons | `…/web/css/source/_extend.less` (top variables), `…/Magento_Theme/layout/default_head_blocks.xml` |
| Header (top bar, logo, icons, search) | `…/Magento_Theme/layout/default.xml`, `…/Magento_Theme/templates/html/header/*.phtml`, `_extend.less` "Header" |
| Furniture → Sofa dropdown | `app/code/Shabeeb/Storefront/Plugin/FurnitureSubmenu.php`, `app/code/Shabeeb/Storefront/etc/frontend/di.xml`, `_extend.less` "Grey navigation bar" |
| Footer | `…/Magento_Theme/templates/html/footer.phtml`, `…/Magento_Theme/layout/default.xml`, `_extend.less` "Footer" |
| Listing page layout and cards | `…/Magento_Catalog/layout/catalog_category_view.xml`, `_extend.less` "Product listing", `…/etc/view.xml` |
| Product page layout | `…/Magento_Catalog/layout/catalog_product_view.xml`, `_extend.less` "Product page" |
| Product gallery / thumbnails | `…/Magento_Catalog/templates/product/view/gallery.phtml`, `…/web/js/product-gallery.js`, `_extend.less` "Gallery" |
| Quantity −/+ and Add to Cart alignment | `…/Magento_Catalog/templates/product/view/addtocart.phtml`, `…/web/js/qty-stepper.js`, `_extend.less` "Quantity box and Add to Cart" |
| Description / shipping accordion | `…/Magento_Catalog/templates/product/view/accordion.phtml` |
| Cart page look | `…/Magento_Checkout/layout/checkout_cart_index.xml`, `…/Magento_Checkout/templates/cart/steps.phtml`, `_extend.less` "Cart" |
| Wishlist page look | `app/code/Shabeeb/GuestWishlist/view/frontend/templates/list.phtml`, `_extend.less` "Wish lists" |
| Guest wishlist logic | `app/code/Shabeeb/GuestWishlist/Model/WishlistManager.php`, `GuestItemStorage.php`, `GuestToken.php`, `Controller/Item/Add.php` |
| Wishlist merge on login | `app/code/Shabeeb/GuestWishlist/etc/frontend/events.xml`, `Observer/MergeGuestWishlist.php`, `WishlistManager::mergeGuestIntoCustomer()` |
| Cart merge on login | Core: `vendor/magento/module-checkout/Model/Session.php::loadCustomerQuote()`, `vendor/magento/module-quote/Model/Quote.php::merge()` (not customised) |
| Header wishlist counter | `app/code/Shabeeb/GuestWishlist/CustomerData/Wishlist.php`, `etc/frontend/sections.xml`, `view/frontend/web/js/header-link.js` |
| Product data, sofa images | `app/code/Shabeeb/Storefront/Setup/Patch/Data/*.php`, `pub/media/catalog/product/shabeeb/` |
| Homepage | Admin → Content → Pages → `home`; `RestoreDefaultHomePage.php` |

## 9. Request-to-render in one paragraph

A request URL is matched by Magento's router (often via a URL rewrite) to a controller. The controller returns a page with layout handles (`default` plus the page's handle). Magento merges every matching layout XML file from the modules, Luma and our theme (ours last), creating blocks in containers. Plugins can change block or helper behaviour (our menu plugin and wishlist-button plugin). Each block renders its template, found through the theme fallback, so our overrides in `Shabeeb/furniture` replace the module templates. The HTML links `styles-m.css` and `styles-l.css` (compiled from Luma LESS plus our `_extend.less`) and starts JS components through `data-mage-init` (gallery, quantity stepper, header wishlist counter). Because pages are full-page cached, per-visitor data (cart and wishlist counts) is loaded afterwards through customer-data sections.

## 10. Files inspected and confirmation status

**Inspected:** every file under `app/code/Shabeeb/` and `app/design/frontend/Shabeeb/furniture/` (listed in §3, plus `etc/db_schema_whitelist.json`, `view/frontend/layout/default.xml`, `view/frontend/templates/header-link.phtml`, `view/frontend/templates/list.phtml` of the GuestWishlist module), `app/etc/config.php` (module status), `app/etc/env.php` (admin front name only), `composer.lock` (Magento version), and read-only database queries on `core_config_data`, `theme`, `store`, `cms_page`, `patch_list`, `catalog_product_entity*`, `cataloginventory_stock_item`, `inventory_source_item`, `url_rewrite` and `admin_user` (username and status only). Core references checked: `vendor/magento/module-checkout/etc/frontend/events.xml`, `module-checkout/Model/Session.php`, `module-quote/Model/Quote.php`, `module-catalog/Plugin/Block/Topmenu.php`, `module-cms/etc/config.xml`, `module-inventory-sales` (stock message).

**Could not be fully confirmed from code alone:**
- That the checkout button is *blocked server-side* for over-stock quantities (observed in the browser as hidden; checkout itself was not run).
- Behaviour with more than one product (only one product exists).
