<?php

declare(strict_types=1);

namespace Abderrahim\SyliusUpsellPlugin\Entity;

use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Resource\Model\ResourceInterface;

interface UpsellOfferInterface extends ResourceInterface
{
    public function getName(): ?string;

    public function setName(?string $name): void;

    public function isEnabled(): bool;

    public function setEnabled(bool $enabled): void;

    public function getTriggerProduct(): ?ProductInterface;

    public function setTriggerProduct(?ProductInterface $product): void;

    public function getOfferProduct(): ?ProductInterface;

    public function setOfferProduct(?ProductInterface $product): void;

    public function getOfferVariant(): ?ProductVariantInterface;

    public function setOfferVariant(?ProductVariantInterface $variant): void;

    public function getDiscountPercent(): int;

    public function setDiscountPercent(int $percent): void;

    public function getHeadline(): ?string;

    public function setHeadline(?string $headline): void;

    public function getBody(): ?string;

    public function setBody(?string $body): void;

    public function getCtaLabel(): ?string;

    public function setCtaLabel(?string $label): void;

    public function getDeclineLabel(): ?string;

    public function setDeclineLabel(?string $label): void;

    public function getPriority(): int;

    public function setPriority(int $priority): void;

    public function getStartsAt(): ?\DateTimeInterface;

    public function setStartsAt(?\DateTimeInterface $startsAt): void;

    public function getEndsAt(): ?\DateTimeInterface;

    public function setEndsAt(?\DateTimeInterface $endsAt): void;
}
