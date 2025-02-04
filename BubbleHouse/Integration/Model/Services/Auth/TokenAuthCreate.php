<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Model\Services\Auth;

use BubbleHouse\Integration\Model\ConfigProvider;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class TokenAuthCreate
{
    private const ALG = 'HS256';
    private const TYPE = 'JWT';
    private const AUD = 'BH';
    private const ALGORITHM = 'sha256';

    public function __construct(
        private readonly ConfigProvider $configProvider,
        private readonly SerializerInterface $serializer,
        private readonly TimezoneInterface $timezone
    ) {
    }
    public function signBubblehouseToken($subject, $keyId, $keySecretInBase64, $validityInSeconds) {
        $nowUnix = time(); // Current Unix timestamp

        // JWT Header
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256', 'kid' => $keyId]);

        // JWT Payload
        $payload = json_encode([
            'aud' => 'BH',
            'sub' => $subject,
            'iat' => $nowUnix,
            'exp' => $nowUnix + $validityInSeconds
        ]);

        // Base64Url Encoding function (same as JavaScript `base64url`)
        $base64UrlEncode = function ($data) {
            return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
        };

        // Encode Header & Payload
        $headerEncoded = $base64UrlEncode($header);
        $payloadEncoded = $base64UrlEncode($payload);

        // Create the signature
        $raw = "$headerEncoded.$payloadEncoded";
        $key = base64_decode($keySecretInBase64);
        $signature = hash_hmac('sha256', $raw, $key, true);

        // Encode signature in Base64Url
        $signatureEncoded = $base64UrlEncode($signature);

        // Return the final JWT token
        return "$raw.$signatureEncoded";
    }

    public function createShopToken(
        $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
        $scopeCode = null
    ): string {
        return $this->signBubblehouseToken(
            $this->configProvider->getShopSlug($scopeType, $scopeCode),
            $this->configProvider->getKid($scopeType, $scopeCode),
            $this->configProvider->getSharedSecret($scopeType, $scopeCode),
            (int)$this->configProvider->getTokenExpirationTime($scopeType, $scopeCode) * 60
        );
    }
}
