// The same operations as docs/graphql/*.graphql (Phase 4), used by the React checkout.

// A fragment is a reusable list of fields. Every mutation below returns the cart with these fields,
// so the screen always shows the latest state after each step.
const CART_FIELDS = `
    fragment CartFields on Cart {
        id
        email
        total_quantity
        items {
            uid
            quantity
            product { sku name thumbnail { url } }
            prices { row_total { value currency } }
        }
        shipping_addresses {
            firstname
            lastname
            street
            city
            region { label }
            telephone
            available_shipping_methods {
                carrier_code
                method_code
                carrier_title
                method_title
                amount { value currency }
            }
            selected_shipping_method { carrier_code method_code amount { value currency } }
        }
        available_payment_methods { code title }
        selected_payment_method { code }
        prices {
            subtotal_excluding_tax { value currency }
            grand_total { value currency }
        }
    }
`;

export const GET_CART = `
    query GetCart($cartId: String!) {
        cart(cart_id: $cartId) { ...CartFields }
    }
    ${CART_FIELDS}
`;

export const SET_GUEST_EMAIL = `
    mutation SetGuestEmail($cartId: String!, $email: String!) {
        setGuestEmailOnCart(input: { cart_id: $cartId, email: $email }) {
            cart { ...CartFields }
        }
    }
    ${CART_FIELDS}
`;

export const SET_SHIPPING_ADDRESS = `
    mutation SetShippingAddress($cartId: String!, $address: CartAddressInput!) {
        setShippingAddressesOnCart(input: { cart_id: $cartId, shipping_addresses: [{ address: $address }] }) {
            cart { ...CartFields }
        }
    }
    ${CART_FIELDS}
`;

export const SET_BILLING_SAME_AS_SHIPPING = `
    mutation SetBillingAddress($cartId: String!) {
        setBillingAddressOnCart(input: { cart_id: $cartId, billing_address: { same_as_shipping: true } }) {
            cart { ...CartFields }
        }
    }
    ${CART_FIELDS}
`;

export const SET_SHIPPING_METHOD = `
    mutation SetShippingMethod($cartId: String!, $carrier: String!, $method: String!) {
        setShippingMethodsOnCart(input: { cart_id: $cartId, shipping_methods: [{ carrier_code: $carrier, method_code: $method }] }) {
            cart { ...CartFields }
        }
    }
    ${CART_FIELDS}
`;

export const SET_PAYMENT_METHOD = `
    mutation SetPaymentMethod($cartId: String!, $code: String!) {
        setPaymentMethodOnCart(input: { cart_id: $cartId, payment_method: { code: $code } }) {
            cart { ...CartFields }
        }
    }
    ${CART_FIELDS}
`;

export const PLACE_ORDER = `
    mutation PlaceOrder($cartId: String!) {
        placeOrder(input: { cart_id: $cartId }) {
            orderV2 { number }
            errors { message }
        }
    }
`;
