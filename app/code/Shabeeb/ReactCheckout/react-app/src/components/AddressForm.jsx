import { useState } from 'react';

const EMIRATES = ['Abu Dhabi', 'Dubai', 'Sharjah', 'Ajman', 'Umm Al Quwain', 'Ras Al Khaimah', 'Fujairah'];

// Email + delivery address (UAE only). Pre-fills from the cart if the shopper already saved it.
export default function AddressForm({ cart, busy, onSubmit }) {
    const saved = cart.shipping_addresses[0];
    const [form, setForm] = useState({
        email: cart.email || '',
        firstname: saved?.firstname || '',
        lastname: saved?.lastname || '',
        street: saved?.street?.[0] || '',
        city: saved?.city || '',
        region: saved?.region?.label || 'Dubai',
        telephone: saved?.telephone || ''
    });

    // One handler for every input: uses the input's "name" to update that field
    function update(event) {
        setForm({ ...form, [event.target.name]: event.target.value });
    }

    function submit(event) {
        event.preventDefault(); // stop the browser from reloading the page
        const { email, ...address } = form;
        onSubmit(email, {
            ...address,
            street: [address.street],
            country_code: 'AE',
            save_in_address_book: false
        });
    }

    return (
        <form onSubmit={submit} className="grid gap-4 sm:grid-cols-2">
            <Field label="Email" name="email" type="email" value={form.email} onChange={update} className="sm:col-span-2" />
            <Field label="First name" name="firstname" value={form.firstname} onChange={update} />
            <Field label="Last name" name="lastname" value={form.lastname} onChange={update} />
            <Field label="Street address" name="street" value={form.street} onChange={update} className="sm:col-span-2" />
            <Field label="City / area" name="city" value={form.city} onChange={update} />
            <label className="block">
                <span className="mb-1 block text-sm font-medium">Emirate</span>
                <select name="region" value={form.region} onChange={update} className="form-select w-full">
                    {EMIRATES.map((emirate) => (
                        <option key={emirate} value={emirate}>{emirate}</option>
                    ))}
                </select>
            </label>
            <Field label="Mobile number" name="telephone" type="tel" value={form.telephone} onChange={update} />
            <p className="self-end text-sm text-fg-secondary">Country: United Arab Emirates</p>
            <div className="sm:col-span-2">
                <button type="submit" className="btn btn-primary" disabled={busy}>
                    {busy ? 'Saving…' : 'Continue to delivery'}
                </button>
            </div>
        </form>
    );
}

function Field({ label, className = '', ...input }) {
    return (
        <label className={`block ${className}`}>
            <span className="mb-1 block text-sm font-medium">{label}</span>
            <input required className="form-input w-full" {...input} />
        </label>
    );
}
