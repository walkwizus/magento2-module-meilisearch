<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchAi\Model;

use Magento\Framework\Encryption\EncryptorInterface;

class ApiKeyEncryption
{
    private const ENCRYPTED_PATTERN = '/^\d+:\d+:\S+$/';

    /**
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        private readonly EncryptorInterface $encryptor
    ) {
    }

    /**
     * @param string|null $apiKey
     * @return string|null
     */
    public function encrypt(?string $apiKey): ?string
    {
        if ($apiKey === null || $apiKey === '' || $this->isEncrypted($apiKey)) {
            return $apiKey;
        }

        return $this->encryptor->encrypt($apiKey);
    }

    /**
     * @param string $apiKey
     * @return string
     */
    public function decrypt(string $apiKey): string
    {
        return $this->isEncrypted($apiKey) ? $this->encryptor->decrypt($apiKey) : $apiKey;
    }

    /**
     * @param string $value
     * @return bool
     */
    public function isEncrypted(string $value): bool
    {
        return (bool) preg_match(self::ENCRYPTED_PATTERN, $value);
    }
}
