<?php
declare(strict_types=1);

namespace BubbleHouse\Integration\Model\Data;

use BubbleHouse\Integration\Api\Data\DiscountDataInterface;
use Magento\Framework\DataObject;

class DiscountData extends DataObject implements DiscountDataInterface
{
    /**
     * Getter for Code.
     *
     * @return string|null
     */
    public function getCode(): ?string
    {
        return $this->getData(self::CODE);
    }

    /**
     * Setter for Code.
     *
     * @param string|null $code
     *
     * @return void
     */
    public function setCode(?string $code): void
    {
        $this->setData(self::CODE, $code);
    }

    /**
     * Getter for Title.
     *
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->getData(self::TITLE);
    }

    /**
     * Setter for Title.
     *
     * @param string|null $title
     *
     * @return void
     */
    public function setTitle(?string $title): void
    {
        $this->setData(self::TITLE, $title);
    }

    /**
     * Getter for EndTime.
     *
     * @return string|null
     */
    public function getEndTime(): ?string
    {
        return $this->getData(self::END_TIME);
    }

    /**
     * Setter for EndTime.
     *
     * @param string|null $endTime
     *
     * @return void
     */
    public function setEndTime(?string $endTime): void
    {
        $this->setData(self::END_TIME, $endTime);
    }

    /**
     * Getter for Amount.
     *
     * @return string|null
     */
    public function getAmount(): ?string
    {
        return $this->getData(self::AMOUNT);
    }

    /**
     * Setter for Amount.
     *
     * @param string|null $amount
     *
     * @return void
     */
    public function setAmount(?string $amount): void
    {
        $this->setData(self::AMOUNT, $amount);
    }

    /**
     * Getter for Percentage.
     *
     * @return int|null
     */
    public function getPercentage(): ?int
    {
        return $this->getData(self::PERCENTAGE) === null ? null
            : (int)$this->getData(self::PERCENTAGE);
    }

    /**
     * Setter for Percentage.
     *
     * @param int|null $percentage
     *
     * @return void
     */
    public function setPercentage(?int $percentage): void
    {
        $this->setData(self::PERCENTAGE, $percentage);
    }

    /**
     * Getter for MinOrderAmount.
     *
     * @return string|null
     */
    public function getMinOrderAmount(): ?string
    {
        return $this->getData(self::MIN_ORDER_AMOUNT);
    }

    /**
     * Setter for MinOrderAmount.
     *
     * @param string|null $minOrderAmount
     *
     * @return void
     */
    public function setMinOrderAmount(?string $minOrderAmount): void
    {
        $this->setData(self::MIN_ORDER_AMOUNT, $minOrderAmount);
    }

    /**
     * Getter for MaxUses.
     *
     * @return int|null
     */
    public function getMaxUses(): ?int
    {
        return $this->getData(self::MAX_USES) === null ? null
            : (int)$this->getData(self::MAX_USES);
    }

    /**
     * Setter for MaxUses.
     *
     * @param int|null $maxUses
     *
     * @return void
     */
    public function setMaxUses(?int $maxUses): void
    {
        $this->setData(self::MAX_USES, $maxUses);
    }

    /**
     * Getter for CustomerId.
     *
     * @return string|null
     */
    public function getCustomerId(): ?string
    {
        return $this->getData(self::CUSTOMER_ID);
    }

    /**
     * Setter for CustomerId.
     *
     * @param string|null $customerId
     *
     * @return void
     */
    public function setCustomerId(?string $customerId): void
    {
        $this->setData(self::CUSTOMER_ID, $customerId);
    }

    /**
     * Getter for CustomerEmail.
     *
     * @return string|null
     */
    public function getCustomerEmail(): ?string
    {
        return $this->getData(self::CUSTOMER_EMAIL);
    }

    /**
     * Setter for CustomerEmail.
     *
     * @param string|null $customerEmail
     *
     * @return void
     */
    public function setCustomerEmail(?string $customerEmail): void
    {
        $this->setData(self::CUSTOMER_EMAIL, $customerEmail);
    }

    /**
     * Getter for ProductIds.
     *
     * @return string[]|null
     */
    public function getProductIds(): ?array
    {
        return $this->getData(self::PRODUCT_IDS);
    }

    /**
     * Setter for ProductIds.
     *
     * @param string[]|null $productIds
     *
     * @return void
     */
    public function setProductIds(?array $productIds): void
    {
        $this->setData(self::PRODUCT_IDS, $productIds);
    }

    /**
     * Getter for CollectionIds.
     *
     * @return string[]|null
     */
    public function getCollectionIds(): ?array
    {
        return $this->getData(self::COLLECTION_IDS);
    }

    /**
     * Setter for CollectionIds.
     *
     * @param string[]|null $collectionIds
     *
     * @return void
     */
    public function setCollectionIds(?array $collectionIds): void
    {
        $this->setData(self::COLLECTION_IDS, $collectionIds);
    }

    /**
     * Getter for IsPerProduct.
     *
     * @return bool|null
     */
    public function getIsPerProduct(): ?bool
    {
        return $this->getData(self::IS_PER_PRODUCT) === null ? null
            : (bool)$this->getData(self::IS_PER_PRODUCT);
    }

    /**
     * Setter for IsPerProduct.
     *
     * @param bool|null $isPerProduct
     *
     * @return void
     */
    public function setIsPerProduct(?bool $isPerProduct): void
    {
        $this->setData(self::IS_PER_PRODUCT, $isPerProduct);
    }

    /**
     * Getter for AppliesToOneTimePurchases.
     *
     * @return bool|null
     */
    public function getAppliesToOneTimePurchases(): ?bool
    {
        return $this->getData(self::APPLIES_TO_ONE_TIME_PURCHASES) === null ? null
            : (bool)$this->getData(self::APPLIES_TO_ONE_TIME_PURCHASES);
    }

    /**
     * Setter for AppliesToOneTimePurchases.
     *
     * @param bool|null $appliesToOneTimePurchases
     *
     * @return void
     */
    public function setAppliesToOneTimePurchases(?bool $appliesToOneTimePurchases): void
    {
        $this->setData(self::APPLIES_TO_ONE_TIME_PURCHASES, $appliesToOneTimePurchases);
    }

    public function getSubjectSlugs(): array
    {
        return $this->getData(self::SUBJECT_SLUGS) ?? [];
    }

    public function setSubjectSlugs(?array $subjectSlugs): void
    {
        $this->setData(self::SUBJECT_SLUGS, $subjectSlugs);
    }

    public function getSubjectType(): string
    {
        return $this->getData(self::SUBJECT_TYPE);
    }

    public function setSubjectType(string $subjectType): void
    {
        $this->setData(self::SUBJECT_TYPE, $subjectType);
    }

    /**
     * Getter for Extras.
     *
     * @return array|null
     */
    public function getExtras(): ?array
    {
        return $this->getData(self::EXTRAS);
    }

    /**
     * Setter for Extras.
     *
     * @param array|null $extras
     *
     * @return void
     */
    public function setExtras(?array $extras): void
    {
        $this->setData(self::EXTRAS, $extras);
    }
}
