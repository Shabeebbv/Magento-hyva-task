// 349 + "AED" -> "AED 349.00"
export function formatPrice(money) {
    if (!money) {
        return '';
    }
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: money.currency }).format(money.value);
}
