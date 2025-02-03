<?php
declare(strict_types=1);

namespace BubbleHouse\Integration\Api\Data;

interface DiscountDataInterfaceInterface
{
    /**
     * String constants for property names
     */
    public const CODE = "code";
    public const TITLE = "title";
    public const END_TIME = "end_time";
    public const AMOUNT = "amount";
    public const PERCENTAGE = "percentage";
    public const SUBJECT_SLUGS = "subject_slugs";
    public const SUBJECT_TYPE = "subject_type";
    public const MIN_ORDER_AMOUNT = "min_order_amount";
    public const MAX_USES = "max_uses";
    public const CUSTOMER_ID = "customer_id";
    public const CUSTOMER_EMAIL = "customer_email";
    public const PRODUCT_IDS = "product_ids";
    public const COLLECTION_IDS = "collection_ids";
    public const IS_PER_PRODUCT = "is_per_product";
    public const APPLIES_TO_ONE_TIME_PURCHASES = "applies_to_one_time_purchases";

    /**
     * Getter for Code.
     *
     * @return string|null
     */
    public function getCode(): ?string;

    /**
     * Setter for Code.
     *
     * @param string|null $code
     *
     * @return void
     */
    public function setCode(?string $code): void;

    /**
     * Getter for Title.
     *
     * @return string|null
     */
    public function getTitle(): ?string;

    /**
     * Setter for Title.
     *
     * @param string|null $title
     *
     * @return void
     */
    public function setTitle(?string $title): void;

    /**
     * Getter for EndTime.
     *
     * @return string|null
     */
    public function getEndTime(): ?string;

    /**
     * Setter for EndTime.
     *
     * @param string|null $endTime
     *
     * @return void
     */
    public function setEndTime(?string $endTime): void;

    /**
     * Getter for Amount.
     *
     * @return string|null
     */
    public function getAmount(): ?string;

    /**
     * Setter for Amount.
     *
     * @param string|null $amount
     *
     * @return void
     */
    public function setAmount(?string $amount): void;

    /**
     * Getter for Percentage.
     *
     * @return int|null
     */
    public function getPercentage(): ?int;

    /**
     * Setter for Percentage.
     *
     * @param int|null $percentage
     *
     * @return void
     */
    public function setPercentage(?int $percentage): void;

    /**
     * Getter for MinOrderAmount.
     *
     * @return string|null
     */
    public function getMinOrderAmount(): ?string;

    /**
     * @return string[]
     */
    public function getSubjectSlugs(): array;

    /**
     * @param string[]|null
     * @return void
     */
    public function setSubjectSlugs(?array $subjectSlugs): void;

    /**
     * @return string
     */
    public function getSubjectType(): string;

    /**
     * @param string $subjectType
     * @return void
     */
    public function setSubjectType(string $subjectType): void;

    /**
     * Setter for MinOrderAmount.
     *
     * @param string|null $minOrderAmount
     *
     * @return void
     */
    public function setMinOrderAmount(?string $minOrderAmount): void;

    /**
     * Getter for MaxUses.
     *
     * @return int|null
     */
    public function getMaxUses(): ?int;

    /**
     * Setter for MaxUses.
     *
     * @param int|null $maxUses
     *
     * @return void
     */
    public function setMaxUses(?int $maxUses): void;

    /**
     * Getter for CustomerId.
     *
     * @return string|null
     */
    public function getCustomerId(): ?string;

    /**
     * Setter for CustomerId.
     *
     * @param string|null $customerId
     *
     * @return void
     */
    public function setCustomerId(?string $customerId): void;

    /**
     * Getter for CustomerEmail.
     *
     * @return string|null
     */
    public function getCustomerEmail(): ?string;

    /**
     * Setter for CustomerEmail.
     *
     * @param string|null $customerEmail
     *
     * @return void
     */
    public function setCustomerEmail(?string $customerEmail): void;

    /**
     * Getter for ProductIds.
     *
     * @return string[]|null
     */
    public function getProductIds(): ?array;

    /**
     * Setter for ProductIds.
     *
     * @param string[]|null $productIds
     *
     * @return void
     */
    public function setProductIds(?array $productIds): void;

    /**
     * Getter for CollectionIds.
     *
     * @return string[]|null
     */
    public function getCollectionIds(): ?array;

    /**
     * Setter for CollectionIds.
     *
     * @param string[]|null $collectionIds
     *
     * @return void
     */
    public function setCollectionIds(?array $collectionIds): void;

    /**
     * Getter for IsPerProduct.
     *
     * @return bool|null
     */
    public function getIsPerProduct(): ?bool;

    /**
     * Setter for IsPerProduct.
     *
     * @param bool|null $isPerProduct
     *
     * @return void
     */
    public function setIsPerProduct(?bool $isPerProduct): void;

    /**
     * Getter for AppliesToOneTimePurchases.
     *
     * @return bool|null
     */
    public function getAppliesToOneTimePurchases(): ?bool;

    /**
     * Setter for AppliesToOneTimePurchases.
     *
     * @param bool|null $appliesToOneTimePurchases
     *
     * @return void
     */
    public function setAppliesToOneTimePurchases(?bool $appliesToOneTimePurchases): void;
}
