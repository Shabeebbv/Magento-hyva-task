<?php
declare(strict_types=1);

namespace Shabeeb\Storefront\ViewModel;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Pricing\Price\FinalPrice;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 * Illustrative "pay in N payments" examples for the product page: the product's current final price split
 * into equal payments. No payment provider is called; nothing here is an approved or available plan.
 *
 * Amounts are worked out in minor units (fils) so they always add up to the price exactly: every payment is
 * the price divided by N rounded down, and the last payment carries the remainder (at most N-1 fils).
 */
class InstallmentExamples implements ArgumentInterface
{
    public function __construct(
        private readonly PriceCurrencyInterface $priceCurrency
    ) {
    }

    /**
     * Current final price of the product in the store's display currency.
     */
    public function getPrice(Product $product): float
    {
        $amount = (float)$product->getPriceInfo()->getPrice(FinalPrice::PRICE_CODE)->getAmount()->getValue();

        return (float)$this->priceCurrency->convertAndRound($amount);
    }

    /**
     * @param int[]|string $counts payment counts, as an array or a comma-separated string from layout XML
     * @return array<int, array{count: int, payment: float, last: float}> sorted by count, smallest first
     */
    public function getPlans(float $price, array|string $counts): array
    {
        $cents = (int)round($price * 100);
        if ($cents <= 0) {
            return [];
        }

        $plans = [];
        foreach ($this->parseCounts($counts) as $count) {
            $payment = intdiv($cents, $count);
            $plans[] = [
                'count' => $count,
                'payment' => $payment / 100,
                'last' => ($cents - $payment * ($count - 1)) / 100,
            ];
        }

        return $plans;
    }

    public function format(float $amount): string
    {
        return $this->priceCurrency->format($amount, false);
    }

    /**
     * @param int[]|string $counts
     * @return int[] unique counts of 2 or more, ascending
     */
    private function parseCounts(array|string $counts): array
    {
        if (is_string($counts)) {
            $counts = explode(',', $counts);
        }
        $counts = array_unique(array_filter(array_map('intval', $counts), static fn (int $count): bool => $count > 1));
        sort($counts);

        return $counts;
    }
}
