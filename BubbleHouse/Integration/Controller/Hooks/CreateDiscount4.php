<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Controller\Hooks;

use BubbleHouse\Integration\Api\Data\DiscountDataInterface;
use BubbleHouse\Integration\Api\Data\DiscountDataInterfaceFactory;
use BubbleHouse\Integration\Controller\Hooks\Abstract\Index;
use BubbleHouse\Integration\Model\Services\Discount\Create;
use Exception;
use Magento\Framework\App\Request\Http as Request;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Oauth\TokenProviderInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Psr\Log\LoggerInterface;

class CreateDiscount4 extends Index
{
    public function __construct(
        protected TokenProviderInterface $tokenProvider,
        protected Request $request,
        protected JsonFactory $jsonFactory,
        protected SerializerInterface $serializer,
        protected LoggerInterface $logger,
        private readonly DiscountDataInterfaceFactory $discountInterfaceFactory,
        private readonly Create $discountCreate
    ) {
        parent::__construct(
            $this->tokenProvider,
            $this->request,
            $this->jsonFactory,
            $this->serializer,
            $this->logger
        );
    }

    /**
     * @inheritDoc
     */
    public function execute(): ResultInterface
    {
        $result = $this->jsonFactory->create();
        $this->logger->critical($this->serializer->serialize($this->request->getContent()));
        $this->logger->critical($this->serializer->serialize($this->request->getHeaders()));
// somehow there is no auth in headers
//        if ($this->validateToken()) {
            $couponData = $this->serializer->unserialize($this->request->getContent());
            $discount = $this->discountInterfaceFactory->create();
            $discount->setData($couponData);

            try {
                $this->discountCreate->execute($discount);
            } catch (AlreadyExistsException $exception) {
                $result->setHttpResponseCode(409);
                $result->setData(['error' => $exception->getMessage()]);
            } catch (Exception $exception) {
                $result->setHttpResponseCode(503);
                $result->setData(['error' => $exception->getMessage()]);
            }

            $result->setHttpResponseCode(201);
            $result->setData(['ok' => true]);

//        } else {
//            $result->setHttpResponseCode(403);
//            $result->setData(['error' => 'Not valid access token']);
//        }

        return $result;
    }
}
