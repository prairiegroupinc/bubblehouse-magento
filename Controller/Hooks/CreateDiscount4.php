<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Controller\Hooks;

use BubbleHouse\Integration\Api\Data\DiscountDataInterface;
use BubbleHouse\Integration\Api\Data\DiscountDataInterfaceFactory;
use BubbleHouse\Integration\Controller\Hooks\Abstract\Index;
use BubbleHouse\Integration\Model\Services\Discount\Create;
use BubbleHouse\Integration\Model\Services\Auth\AuthorizeDiscount;
use Exception;
use InvalidArgumentException;
use Magento\Framework\App\Request\Http as Request;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\AuthorizationException;
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
        private readonly Create $discountCreate,
        private readonly AuthorizeDiscount $authorizeDiscount
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

        try {
            $this->authorizeDiscount->execute((string)$this->request->getHeader('Authorization'));
        } catch (AuthenticationException $exception) {
            return $result->setHttpResponseCode(401)->setData(['error' => $exception->getMessage()]);
        } catch (AuthorizationException $exception) {
            return $result->setHttpResponseCode(403)->setData(['error' => $exception->getMessage()]);
        }

        try {
            $couponData = $this->serializer->unserialize($this->request->getContent());
        } catch (InvalidArgumentException $exception) {
            return $result->setHttpResponseCode(400)->setData(['error' => 'Invalid JSON body.']);
        }
        if (!is_array($couponData)) {
            return $result->setHttpResponseCode(400)->setData(['error' => 'Expected a JSON object.']);
        }

        try {
            $discount = $this->discountInterfaceFactory->create();
            $discount->setData($couponData);
            $this->discountCreate->execute($discount);
        } catch (AlreadyExistsException $exception) {
            return $result->setHttpResponseCode(409)->setData(['error' => 'Coupon already exists.']);
        } catch (Exception $exception) {
            $this->logger->error('Bubblehouse coupon creation failed.', ['exception' => $exception]);
            return $result->setHttpResponseCode(503)->setData(['error' => 'Coupon creation failed.']);
        }

        $result->setHttpResponseCode(201);
        $result->setData(['ok' => true]);

        return $result;
    }
}
