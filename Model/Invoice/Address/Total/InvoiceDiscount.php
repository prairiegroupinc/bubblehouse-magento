<?php

namespace BubbleHouse\Integration\Model\Invoice\Address\Total;

use Psr\Log\LoggerInterface;

class InvoiceDiscount extends \Magento\Sales\Model\Order\Invoice\Total\AbstractTotal
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {}

    public function collect(\Magento\Sales\Model\Order\Invoice $invoice)
    {
        $order = $invoice->getOrder();
        $discount = $order->getDiscountAmount();

        if ($discount) {
            $invoice->setDiscountAmount($discount);
            $invoice->setBaseDiscountAmount($discount);

            $invoice->setGrandTotal($order->getGrandTotal());
            $invoice->setBaseGrandTotal($order->getBaseGrandTotal());
        }

        return $this;
    }
}
