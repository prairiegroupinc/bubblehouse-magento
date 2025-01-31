<?php

namespace BubbleHouse\Integration\Model\EportData\Order;

use Magento\Framework\Exception\NoSuchEntityException;

class OrderStatusMapper
{
    public const UNCONFIRMED = 'unconfirmed';
    public const CONFIRMED = 'confirmed';
    public const CANCELED = 'canceled';
    public const COMPLETED = 'completed';
    public const DELETED = 'deleted';
    public const UNCONFIRMED_VALUES = [
        'closed',
        'fraud',
        'pending_payment',
        'pending',
        'payment_review'
    ];
    public const CONFIRMED_VALUES = [
        'processing',
        'holded'
    ];
    public const COMPLETE_VALUES = [
        'complete'
    ];
    public const CANCELED_VALUES = [
        'canceled'
    ];

    public function mapStatus(string $orderStatus): string
    {
        $mappedValue = '';

        switch ($orderStatus) {
            case in_array($orderStatus, self::COMPLETE_VALUES):
                $mappedValue = self::COMPLETED;
                break;
            case in_array($orderStatus, self::CANCELED_VALUES):
                $mappedValue = self::CANCELED;
                break;
            case in_array($orderStatus, self::CONFIRMED_VALUES):
                $mappedValue = self::CONFIRMED;
                break;
            case in_array($orderStatus, self::UNCONFIRMED_VALUES):
                $mappedValue = self::UNCONFIRMED;
                break;
            case $orderStatus === 'deleted':
                $mappedValue = self::DELETED;
                break;
            default:
                throw new NoSuchEntityException(__('Unknown Order Status - need customisations'));
        }

        return $mappedValue;
    }
}
