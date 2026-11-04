// Payment options from Magento as radio buttons, then the "Place order" button.
export default function PaymentMethods({ cart, busy, onSelect, onPlaceOrder }) {
    const selected = cart.selected_payment_method?.code || '';

    return (
        <div className="space-y-3">
            {cart.available_payment_methods.map((method) => (
                <label
                    key={method.code}
                    className="flex cursor-pointer items-center gap-3 rounded-lg border border-gray-300 px-4 py-3 hover:border-primary"
                >
                    <input
                        type="radio"
                        name="payment"
                        value={method.code}
                        checked={selected === method.code}
                        disabled={busy}
                        onChange={() => onSelect(method.code)}
                    />
                    <span className="font-medium">{method.title}</span>
                </label>
            ))}

            <button
                type="button"
                className="btn btn-primary mt-4 w-full py-3 text-base"
                disabled={busy || !selected}
                onClick={onPlaceOrder}
            >
                {busy ? 'Please wait…' : 'Place order'}
            </button>
        </div>
    );
}
