<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Test\Unit\Controller\Hooks;

use BubbleHouse\Integration\Api\Data\DiscountDataInterfaceFactory;
use BubbleHouse\Integration\Controller\Hooks\CreateDiscount4;
use BubbleHouse\Integration\Model\Data\DiscountData;
use BubbleHouse\Integration\Model\Services\Auth\AuthorizeDiscount;
use BubbleHouse\Integration\Model\Services\Discount\Create;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\AuthorizationException;
use Magento\Framework\Oauth\TokenProviderInterface;
use Magento\Framework\Phrase;
use Magento\Framework\Serialize\SerializerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CreateDiscount4Test extends TestCase
{
    private $request;
    private $serializer;
    private $factory;
    private $create;
    private $authorization;
    private $result;
    private CreateDiscount4 $controller;

    protected function setUp(): void
    {
        $this->request = $this->createMock(Http::class);
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->factory = $this->createMock(DiscountDataInterfaceFactory::class);
        $this->create = $this->createMock(Create::class);
        $this->authorization = $this->createMock(AuthorizeDiscount::class);
        $this->result = $this->createMock(Json::class);
        $jsonFactory = $this->createMock(JsonFactory::class);
        $jsonFactory->method('create')->willReturn($this->result);
        $this->controller = new CreateDiscount4(
            $this->createMock(TokenProviderInterface::class),
            $this->request,
            $jsonFactory,
            $this->serializer,
            $this->createMock(LoggerInterface::class),
            $this->factory,
            $this->create,
            $this->authorization
        );
    }

    /** @dataProvider denials */
    public function testDenialStopsBeforeReadingBody(string $exceptionClass, int $status): void
    {
        $this->request->method('getHeader')->with('Authorization')->willReturn(false);
        $this->authorization->expects(self::once())->method('execute')->with('')
            ->willThrowException(new $exceptionClass(new Phrase('Denied')));
        $this->request->expects(self::never())->method('getContent');
        $this->serializer->expects(self::never())->method('unserialize');
        $this->factory->expects(self::never())->method('create');
        $this->create->expects(self::never())->method('execute');
        $this->response($status, ['error' => 'Denied']);
        self::assertSame($this->result, $this->controller->execute());
    }

    public static function denials(): array
    {
        return [[AuthenticationException::class, 401], [AuthorizationException::class, 403]];
    }

    /** @dataProvider outcomes */
    public function testAuthorizedCreationPreservesOutcome(?string $exceptionClass, int $status, array $body): void
    {
        $this->request->method('getHeader')->willReturn('Bearer authorized');
        $this->authorization->expects(self::once())->method('execute')->with('Bearer authorized');
        $data = ['code' => 'AUTH-TEST', 'percentage' => 100, 'max_uses' => 1];
        $this->serializer->method('unserialize')->willReturn($data);
        $discount = new DiscountData();
        $this->factory->method('create')->willReturn($discount);
        $this->create->expects(self::once())->method('execute')->with($discount)
            ->willReturnCallback(static function (DiscountData $discount) use ($data, $exceptionClass): void {
                self::assertSame($data, $discount->getData());
                if ($exceptionClass) {
                    throw $exceptionClass === AlreadyExistsException::class
                        ? new AlreadyExistsException(new Phrase('Internal details'))
                        : new \Exception('Internal details');
                }
            });
        $this->response($status, $body);
        $this->controller->execute();
    }

    public static function outcomes(): array
    {
        return [
            [null, 201, ['ok' => true]],
            [AlreadyExistsException::class, 409, ['error' => 'Coupon already exists.']],
            [\Exception::class, 503, ['error' => 'Coupon creation failed.']],
        ];
    }

    public function testInvalidJsonDoesNotCreateCoupon(): void
    {
        $this->serializer->method('unserialize')->willThrowException(new \InvalidArgumentException('Invalid JSON'));
        $this->create->expects(self::never())->method('execute');
        $this->response(400, ['error' => 'Invalid JSON body.']);
        $this->controller->execute();
    }

    private function response(int $status, array $body): void
    {
        $this->result->expects(self::once())->method('setHttpResponseCode')->with($status)->willReturnSelf();
        $this->result->expects(self::once())->method('setData')->with($body)->willReturnSelf();
    }
}
