<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Test\Unit\Model\Services\Auth;

use BubbleHouse\Integration\Model\Services\Auth\AuthorizeDiscount;
use Magento\Authorization\Model\ResourceModel\Role\Collection;
use Magento\Authorization\Model\ResourceModel\Role\CollectionFactory;
use Magento\Authorization\Model\Role;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\Authorization\PolicyInterface;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\AuthorizationException;
use Magento\Framework\Phrase;
use Magento\Integration\Api\Data\UserToken;
use Magento\Integration\Api\Data\UserTokenDataInterface;
use Magento\Integration\Api\Exception\UserTokenException;
use Magento\Integration\Api\IntegrationServiceInterface;
use Magento\Integration\Api\UserTokenReaderInterface;
use Magento\Integration\Api\UserTokenValidatorInterface;
use Magento\Integration\Model\Integration;
use PHPUnit\Framework\TestCase;

class AuthorizeDiscountTest extends TestCase
{
    private $reader;
    private $validator;
    private $roles;
    private $policy;
    private $integrations;
    private AuthorizeDiscount $authorization;

    protected function setUp(): void
    {
        $this->reader = $this->createMock(UserTokenReaderInterface::class);
        $this->validator = $this->createMock(UserTokenValidatorInterface::class);
        $this->roles = $this->createMock(CollectionFactory::class);
        $this->policy = $this->createMock(PolicyInterface::class);
        $this->integrations = $this->createMock(IntegrationServiceInterface::class);
        $this->authorization = new AuthorizeDiscount(
            $this->reader, $this->validator, $this->roles, $this->policy, $this->integrations
        );
    }

    /** @dataProvider invalidHeaders */
    public function testRejectsMissingOrMalformedHeader(string $header): void
    {
        $this->reader->expects(self::never())->method('read');
        $this->roles->expects(self::never())->method('create');
        $this->expectException(AuthenticationException::class);
        $this->authorization->execute($header);
    }

    public static function invalidHeaders(): array
    {
        return [[''], ['Bearer'], ['Bearer '], ['Basic token'], ['token'], ['Bearer a b'], ['Bearer a,Bearer b']];
    }

    public function testRejectsInvalidOrRevokedToken(): void
    {
        $this->reader->method('read')->willThrowException(new UserTokenException('Invalid token'));
        $this->validator->expects(self::never())->method('validate');
        $this->roles->expects(self::never())->method('create');
        $this->expectException(AuthenticationException::class);
        $this->authorization->execute('Bearer invalid');
    }

    public function testRejectsExpiredToken(): void
    {
        $this->token(UserContextInterface::USER_TYPE_INTEGRATION);
        $this->validator->method('validate')->willThrowException(new AuthorizationException(new Phrase('Expired')));
        $this->roles->expects(self::never())->method('create');
        $this->expectException(AuthenticationException::class);
        $this->authorization->execute('Bearer expired');
    }

    public function testRejectsCustomerToken(): void
    {
        $this->token(UserContextInterface::USER_TYPE_CUSTOMER);
        $this->roles->expects(self::never())->method('create');
        $this->expectException(AuthorizationException::class);
        $this->authorization->execute('Bearer customer-token');
    }

    public function testRejectsInactiveIntegration(): void
    {
        $this->token(UserContextInterface::USER_TYPE_INTEGRATION);
        $integration = $this->getMockBuilder(Integration::class)->disableOriginalConstructor()
            ->onlyMethods([])->getMock();
        $integration->setData('status', Integration::STATUS_INACTIVE);
        $this->integrations->method('get')->with(42)->willReturn($integration);
        $this->roles->expects(self::never())->method('create');
        $this->expectException(AuthorizationException::class);
        $this->authorization->execute('Bearer inactive-integration');
    }

    /** @dataProvider permissions */
    public function testChecksTokenOwnersRole(int $type, ?string $roleId, bool $allowed): void
    {
        $token = $this->token($type);
        $integration = $this->getMockBuilder(Integration::class)->disableOriginalConstructor()
            ->onlyMethods([])->getMock();
        $integration->setData('status', Integration::STATUS_ACTIVE);
        $this->integrations->method('get')->with(42)->willReturn($integration);
        $this->validator->expects(self::once())->method('validate')->with($token);
        $collection = $this->createMock(Collection::class);
        $role = $this->createMock(Role::class);
        $role->method('getId')->willReturn($roleId);
        $this->roles->method('create')->willReturn($collection);
        $collection->expects(self::once())->method('setUserFilter')->with(42, $type)->willReturnSelf();
        $collection->method('getFirstItem')->willReturn($role);
        $this->policy->expects($roleId ? self::once() : self::never())->method('isAllowed')
            ->with($roleId, AuthorizeDiscount::RESOURCE)->willReturn($allowed);
        if (!$allowed) {
            $this->expectException(AuthorizationException::class);
        }
        $this->authorization->execute('bearer valid-token');
    }

    public static function permissions(): array
    {
        return [
            'allowed integration' => [UserContextInterface::USER_TYPE_INTEGRATION, '7', true],
            'allowed admin' => [UserContextInterface::USER_TYPE_ADMIN, '8', true],
            'denied integration' => [UserContextInterface::USER_TYPE_INTEGRATION, '7', false],
            'missing role' => [UserContextInterface::USER_TYPE_INTEGRATION, null, false],
        ];
    }

    private function token(int $type): UserToken
    {
        $context = $this->createMock(UserContextInterface::class);
        $context->method('getUserId')->willReturn(42);
        $context->method('getUserType')->willReturn($type);
        $token = new UserToken($context, $this->createMock(UserTokenDataInterface::class));
        $this->reader->method('read')->willReturn($token);
        return $token;
    }
}
