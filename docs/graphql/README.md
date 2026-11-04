# GraphQL practice — Peak Sports

Endpoint: `POST http://shabeeb-hyva.test/graphql` (header `Content-Type: application/json`).
Each `.graphql` file is one operation. Run them in order; paste the file into Altair's query box and the
variables below into its "Variables" pane. Replace `CART_ID` with the id returned by step 04.

| Step | File | Variables |
|---|---|---|
| 01 | `01-store-config.graphql` | none |
| 02 | `02-category-products.graphql` | `{"urlKey": "football-jerseys"}` |
| 03 | `03-product.graphql` | `{"sku": "JER-RMA-H2627"}` |
| 04 | `04-create-guest-cart.graphql` | none → copy `data.createGuestCart.cart.id` |
| 05 | `05-add-to-cart.graphql` | `{"cartId": "CART_ID", "sku": "JER-RMA-H2627", "qty": 2}` |
| 06 | `06-set-guest-email.graphql` | `{"cartId": "CART_ID", "email": "test.buyer@example.com"}` |
| 07 | `07-set-shipping-address.graphql` | `{"cartId": "CART_ID", "address": {"firstname": "Test", "lastname": "Buyer", "street": ["1 Test Street"], "city": "Dubai", "region": "Dubai", "country_code": "AE", "postcode": "00000", "telephone": "0500000000", "save_in_address_book": false}}` |
| 08 | `08-set-billing-address.graphql` | `{"cartId": "CART_ID"}` |
| 09 | `09-set-shipping-method.graphql` | `{"cartId": "CART_ID", "carrier": "flatrate", "method": "flatrate"}` |
| 10 | `10-set-payment-method.graphql` | `{"cartId": "CART_ID", "code": "checkmo"}` |
| 11 | `11-place-order.graphql` | `{"cartId": "CART_ID"}` → order number; the cart becomes inactive |

Step 11 creates a real order in this local store (admin → Sales → Orders). Use test data only.
