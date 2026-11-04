import { formatPrice } from '../price.js';

// Right-hand column: items, delivery cost and totals. Re-renders whenever the cart changes.
export default function OrderSummary({ cart, cartUrl }) {
    const shipping = cart.shipping_addresses[0]?.selected_shipping_method;

    return (
        <aside className="h-fit rounded-xl bg-surface p-6 shadow-sm ring-1 ring-gray-200">
            <div className="mb-4 flex items-center justify-between">
                <h2 className="text-lg font-semibold">Order summary</h2>
                <a href={cartUrl} className="text-sm text-primary hover:underline">Edit cart</a>
            </div>

            <ul className="divide-y divide-gray-200">
                {cart.items.map((item) => (
                    <li key={item.uid} className="flex items-center gap-4 py-3">
                        <img src={item.product.thumbnail.url} alt="" className="h-16 w-16 rounded-md bg-white object-contain ring-1 ring-gray-200" />
                        <div className="flex-1 text-sm">
                            <p className="font-medium">{item.product.name}</p>
                            <p className="text-fg-secondary">Qty {item.quantity}</p>
                        </div>
                        <p className="text-sm font-semibold">{formatPrice(item.prices.row_total)}</p>
                    </li>
                ))}
            </ul>

            <dl className="mt-4 space-y-2 border-t border-gray-200 pt-4 text-sm">
                <Row label="Subtotal" value={formatPrice(cart.prices.subtotal_excluding_tax)} />
                <Row label="Delivery" value={shipping ? formatPrice(shipping.amount) : 'Chosen in step 2'} />
                <div className="flex justify-between border-t border-gray-200 pt-2 text-base font-bold">
                    <dt>Total</dt>
                    <dd>{formatPrice(cart.prices.grand_total)}</dd>
                </div>
            </dl>
        </aside>
    );
}

function Row({ label, value }) {
    return (
        <div className="flex justify-between">
            <dt className="text-fg-secondary">{label}</dt>
            <dd>{value}</dd>
        </div>
    );
}
