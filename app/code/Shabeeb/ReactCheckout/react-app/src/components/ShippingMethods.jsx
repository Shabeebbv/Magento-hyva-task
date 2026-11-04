import { formatPrice } from '../price.js';

// Lists the delivery options Magento returned for the saved address; clicking one saves it.
export default function ShippingMethods({ address, busy, onSelect }) {
    const methods = address?.available_shipping_methods || [];
    if (!methods.length) {
        return <p className="text-sm text-red-700">No delivery method is available for this address.</p>;
    }

    return (
        <div className="space-y-3">
            {methods.map((method) => (
                <button
                    key={method.carrier_code + method.method_code}
                    type="button"
                    disabled={busy}
                    onClick={() => onSelect(method.carrier_code, method.method_code)}
                    className="flex w-full items-center justify-between rounded-lg border border-gray-300 px-4 py-3 text-left hover:border-primary"
                >
                    <span>
                        <span className="font-medium">{method.carrier_title}</span>
                        <span className="block text-sm text-fg-secondary">{method.method_title}</span>
                    </span>
                    <span className="font-semibold">{formatPrice(method.amount)}</span>
                </button>
            ))}
        </div>
    );
}
