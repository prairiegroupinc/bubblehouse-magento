<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Model\Services\Connector;

use BubbleHouse\Integration\Model\ConfigProvider;
use BubbleHouse\Integration\Model\Services\Auth\TokenAuthCreate;
use GuzzleHttp\Client;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;
use Psr\Log\LoggerInterface;

class BubbleHouseRequest
{
    private const URL = 'https://app.bubblehouse.com/api/v2023061/';
    private const ORDER_UPDATE_PATH = 'UpdateOrders4';
    private const CUSTOMER_UPDATE_PATH = 'UpdateCustomers3';
    public const ORDER_EXPORT_TYPE = 1;
    public const CUSTOMER_EXPORT_TYPE = 2;
    private const EXPORT_TYPES = [
        1 => self::ORDER_UPDATE_PATH,
        2 => self::CUSTOMER_UPDATE_PATH
    ];

    public function __construct(
        private readonly ConfigProvider $configProvider,
        private readonly TokenAuthCreate $tokenAuthCreate,
        private readonly SerializerInterface $serializer,
        private readonly Client $client,
        private readonly LoggerInterface $logger
    ) {
    }

    public function exportData(
        int $exportType,
        array $payload,
        $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
        $scopeCode = null
    ): bool {

        try {
            $shopToken = $this->tokenAuthCreate->createShopToken(
                $scopeType,
                $scopeCode
            );
            $uri = $this->getExportUrl($exportType, $scopeType, $scopeCode);
            $payloadOffset = $exportType === self::ORDER_EXPORT_TYPE ? 'orders' : 'customers';
            $payload = [
                $payloadOffset => [$payload]
            ];
            $requestOptions = $this->prepareOptions($shopToken, $payload, $scopeType, $scopeCode);
            $response = $this->client->post($uri, $requestOptions);
            $result = $this->serializer->unserialize($response->getBody());
        } catch (\Exception $exception) {
            $this->logger->critical(
                __('Could not sync BubbleHouse Entity %1: ',
                    self::EXPORT_TYPES[$exportType],
                    $exception->getMessage()
                )
            );
        }

        return $result['ok'] ?? false;
    }

    private function getExportUrl(
        int $exportType,
        $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
        $scopeCode = null
    ): string {
        $shopSlug = $this->configProvider->getShopSlug($scopeType, $scopeCode);

        if (empty($shopSlug)) {
            throw new LocalizedException(__('Please Add Shop Slug in Admin'));
        }

        return self::URL . $shopSlug . '/' . self::EXPORT_TYPES[$exportType];
    }

    private function prepareOptions(
        string $shopToken,
        array $payload,
        $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
        $scopeCode = null
    ): array {
        $debug = $this->configProvider->isDebug(
            $scopeType,
            $scopeCode
        );

        if ($debug) {

            $payload['debug'] = true;
        }

        return [
            'headers' => [
                'Authorization' => 'Bearer ' . $shopToken,
                'Content-Type' => 'application/json'
            ],
            'body' => $this->serializer->serialize($payload)
        ];
    }
}
