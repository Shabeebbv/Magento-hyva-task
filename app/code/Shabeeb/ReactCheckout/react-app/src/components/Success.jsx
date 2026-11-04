// Shown after placeOrder succeeds.
export default function Success({ orderNumber, email, homeUrl }) {
    return (
        <div className="mx-auto max-w-xl rounded-xl bg-surface p-8 text-center shadow-sm ring-1 ring-gray-200">
            <p className="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-green-100 text-2xl text-green-700">✓</p>
            <h2 className="text-2xl font-bold">Thank you for your order!</h2>
            <p className="mt-3 text-fg-secondary">
                Your order number is <strong className="text-fg">#{orderNumber}</strong>.
                We will send the confirmation to {email}.
            </p>
            <a href={homeUrl} className="btn btn-primary mt-6">Continue shopping</a>
        </div>
    );
}
