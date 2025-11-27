<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Api\Data;

interface QuoteDiscountDataInterface
{
    public const AMOUNT = 'amount';
    public const DESCRIPTION = 'description';
    public const CODE = 'code';
    public const QUOTE_HASH = 'quote_hash';

    public function getAmount(): string;
    public function setAmount(string $amount): self;
    public function getDescription(): string;
    public function setDescription(string $description): self;
    public function getCode(): string;
    public function setCode(string $code): self;
    public function getQuoteHash(): string;
    public function setQuoteHash(string $quoteHash): self;
}
