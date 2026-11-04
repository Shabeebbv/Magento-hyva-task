---
name: graphql-test
description: Run a Magento GraphQL query or mutation against shabeeb-hyva.test and explain the request and response. Use when learning or debugging GraphQL (products, cart, checkout steps, orders).
---

# Run and explain a GraphQL call

Endpoint: `POST http://shabeeb-hyva.test/graphql`, header `Content-Type: application/json`, body `{"query": "...", "variables": {...}}`.

1. Write the operation. Prefer variables over values pasted into the query:
   ```graphql
   query Product($sku: String!) {
     products(filter: { sku: { eq: $sku } }) {
       items { name sku price_range { minimum_price { final_price { value currency } } } }
     }
   }
   ```
2. Send it with curl and pretty-print:
   ```bash
   curl -s -X POST http://shabeeb-hyva.test/graphql -H 'Content-Type: application/json' \
     -d '{"query":"<operation>","variables":{...}}' | php -r 'echo json_encode(json_decode(stream_get_contents(STDIN)), JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES), "\n";'
   ```
3. For a checkout flow, keep the cart ID from `createGuestCart` and reuse it in each next mutation. Order:
   `createGuestCart` → `addProductsToCart` (sku `JER-RMA-H2627`) → `setGuestEmailOnCart` → `setShippingAddressesOnCart` → `setBillingAddressOnCart` → `setShippingMethodsOnCart` (`carrier_code: "flatrate", method_code: "flatrate"`) → `setPaymentMethodOnCart` (`code: "checkmo"`) → `placeOrder`.
   Use obviously fake test data (e.g. `test@example.com`, "Test Street"). Placing an order creates a real order in this local store — say so before running `placeOrder`.
4. Explain to the user: query vs mutation, what each requested field is, what came back, and any `errors` entry in plain words.
5. The schema can be explored with an introspection query or in the Altair GraphQL Client browser extension pointed at the same endpoint.
