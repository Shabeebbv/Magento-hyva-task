import { useEffect, useState } from 'react';
import { graphql } from './graphql/client.js';
import {
    GET_CART,
    PLACE_ORDER,
    SET_BILLING_SAME_AS_SHIPPING,
    SET_GUEST_EMAIL,
    SET_PAYMENT_METHOD,
    SET_SHIPPING_ADDRESS,
    SET_SHIPPING_METHOD
} from './graphql/operations.js';
import AddressForm from './components/AddressForm.jsx';
import ShippingMethods from './components/ShippingMethods.jsx';
import PaymentMethods from './components/PaymentMethods.jsx';
import OrderSummary from './components/OrderSummary.jsx';
import Success from './components/Success.jsx';

// Steps of the checkout, in order
const STEPS = ['address', 'shipping', 'payment'];

export default function App({ config }) {
    const [cart, setCart] = useState(null);         // latest cart from Magento
    const [step, setStep] = useState('address');    // which section is open
    const [loading, setLoading] = useState(true);   // first load
    const [busy, setBusy] = useState(false);        // a mutation is running
    const [error, setError] = useState('');         // message shown above the form
    const [orderNumber, setOrderNumber] = useState('');

    // Load the cart once when the page opens
    useEffect(() => {
        if (!config.cartId || config.isLoggedIn) {
            setLoading(false);
            return;
        }
        graphql(config, GET_CART, { cartId: config.cartId })
            .then((data) => setCart(data.cart))
            .catch((e) => setError(e.message))
            .finally(() => setLoading(false));
    }, [config]);

    // Runs one or more GraphQL calls; shows a spinner state and any error message
    async function run(action) {
        setBusy(true);
        setError('');
        try {
            await action();
        } catch (e) {
            setError(e.message);
        } finally {
            setBusy(false);
        }
    }

    function saveAddress(email, address) {
        return run(async () => {
            const cartId = config.cartId;
            await graphql(config, SET_GUEST_EMAIL, { cartId, email });
            await graphql(config, SET_SHIPPING_ADDRESS, { cartId, address });
            const data = await graphql(config, SET_BILLING_SAME_AS_SHIPPING, { cartId });
            setCart(data.setBillingAddressOnCart.cart);
            setStep('shipping');
        });
    }

    function saveShippingMethod(carrier, method) {
        return run(async () => {
            const data = await graphql(config, SET_SHIPPING_METHOD, { cartId: config.cartId, carrier, method });
            setCart(data.setShippingMethodsOnCart.cart);
            setStep('payment');
        });
    }

    function savePaymentMethod(code) {
        return run(async () => {
            const data = await graphql(config, SET_PAYMENT_METHOD, { cartId: config.cartId, code });
            setCart(data.setPaymentMethodOnCart.cart);
        });
    }

    function placeOrder() {
        return run(async () => {
            const data = await graphql(config, PLACE_ORDER, { cartId: config.cartId });
            const result = data.placeOrder;
            if (result.errors && result.errors.length) {
                throw new Error(result.errors[0].message);
            }
            setOrderNumber(result.orderV2.number);
            // Tell Hyvä to reload the mini-cart etc. (the cart is now empty)
            window.dispatchEvent(new CustomEvent('reload-customer-section-data'));
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    if (loading) {
        return <p className="text-fg-secondary">Loading checkout…</p>;
    }
    if (orderNumber) {
        return <Success orderNumber={orderNumber} email={cart.email} homeUrl={config.homeUrl} />;
    }
    if (config.isLoggedIn) {
        return (
            <Notice>
                This checkout is for guests only for now. Please <a className="underline" href={config.homeUrl + 'customer/account/logout'}>sign out</a> and
                check out as a guest.
            </Notice>
        );
    }
    if (!cart || cart.total_quantity === 0) {
        return (
            <Notice>
                {error || 'Your cart is empty.'} <a className="underline" href={config.homeUrl}>Continue shopping</a>
            </Notice>
        );
    }

    const address = cart.shipping_addresses[0];
    const stepIndex = STEPS.indexOf(step);

    return (
        <div className="grid gap-8 lg:grid-cols-3">
            <div className="space-y-6 lg:col-span-2">
                {error && (
                    <p role="alert" className="rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-red-800">
                        {error}
                    </p>
                )}

                <Section number={1} title="Contact & delivery address" done={stepIndex > 0} onEdit={() => setStep('address')}>
                    {step === 'address' ? (
                        <AddressForm cart={cart} busy={busy} onSubmit={saveAddress} />
                    ) : (
                        <p className="text-sm text-fg-secondary">
                            {cart.email}<br />
                            {address.firstname} {address.lastname}, {address.street.join(', ')}, {address.city}, {address.telephone}
                        </p>
                    )}
                </Section>

                <Section number={2} title="Delivery method" done={stepIndex > 1} onEdit={() => setStep('shipping')} disabled={stepIndex < 1}>
                    {step === 'shipping' && (
                        <ShippingMethods address={address} busy={busy} onSelect={saveShippingMethod} />
                    )}
                    {stepIndex > 1 && address.selected_shipping_method && (
                        <p className="text-sm text-fg-secondary">
                            {address.available_shipping_methods.find(
                                (m) => m.method_code === address.selected_shipping_method.method_code
                            )?.carrier_title}
                        </p>
                    )}
                </Section>

                <Section number={3} title="Payment" disabled={stepIndex < 2}>
                    {step === 'payment' && (
                        <PaymentMethods cart={cart} busy={busy} onSelect={savePaymentMethod} onPlaceOrder={placeOrder} />
                    )}
                </Section>
            </div>

            <OrderSummary cart={cart} cartUrl={config.cartUrl} />
        </div>
    );
}

function Section({ number, title, done, disabled, onEdit, children }) {
    return (
        <section className={`rounded-xl bg-surface p-6 shadow-sm ring-1 ring-gray-200 ${disabled ? 'opacity-50' : ''}`}>
            <div className="mb-4 flex items-center justify-between">
                <h2 className="flex items-center gap-3 text-lg font-semibold">
                    <span className="flex h-7 w-7 items-center justify-center rounded-full bg-primary text-sm text-on-primary">
                        {done ? '✓' : number}
                    </span>
                    {title}
                </h2>
                {done && onEdit && (
                    <button type="button" className="text-sm font-semibold text-primary hover:underline" onClick={onEdit}>
                        Edit
                    </button>
                )}
            </div>
            {children}
        </section>
    );
}

function Notice({ children }) {
    return <p className="rounded-xl bg-surface p-6 shadow-sm ring-1 ring-gray-200">{children}</p>;
}
