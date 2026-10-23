<?php
declare(strict_types=1);

namespace Shabeeb\GuestWishlist\Model;

use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\Cookie\PublicCookieMetadata;
use Magento\Framework\Stdlib\CookieManagerInterface;

/**
 * Anonymous guest identifier. The cookie holds only a random token; wishlist data stays server-side.
 */
class GuestToken
{
    public const COOKIE_NAME = 'shabeeb_gwl';
    private const LIFETIME = 2592000; // 30 days

    public function __construct(
        private readonly CookieManagerInterface $cookieManager,
        private readonly CookieMetadataFactory $cookieMetadataFactory,
        private readonly SessionManagerInterface $sessionManager
    ) {
    }

    public function get(): ?string
    {
        $value = $this->cookieManager->getCookie(self::COOKIE_NAME);

        return is_string($value) && preg_match('/^[a-f0-9]{64}$/', $value) ? $value : null;
    }

    /**
     * Return the current token, creating one if needed, and refresh the cookie lifetime.
     */
    public function getOrCreate(): string
    {
        $token = $this->get() ?? bin2hex(random_bytes(32));
        $metadata = $this->createMetadata()->setDuration(self::LIFETIME);
        $this->cookieManager->setPublicCookie(self::COOKIE_NAME, $token, $metadata);

        return $token;
    }

    public function delete(): void
    {
        $this->cookieManager->deleteCookie(self::COOKIE_NAME, $this->createMetadata());
    }

    private function createMetadata(): PublicCookieMetadata
    {
        return $this->cookieMetadataFactory->createPublicCookieMetadata()
            ->setPath($this->sessionManager->getCookiePath())
            ->setDomain($this->sessionManager->getCookieDomain())
            ->setHttpOnly(true)
            ->setSameSite('Lax');
    }
}
