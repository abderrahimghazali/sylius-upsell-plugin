<?php

declare(strict_types=1);

namespace Abderrahim\SyliusUpsellPlugin\Entity;

use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;

class UpsellOffer implements UpsellOfferInterface
{
    protected ?int $id = null;

    protected string $name = '';

    protected bool $enabled = true;

    protected ?ProductInterface $triggerProduct = null;

    protected ?ProductInterface $offerProduct = null;

    protected ?ProductVariantInterface $offerVariant = null;

    protected int $discountPercent = 0;

    protected ?string $headline = 'Wait! A special offer just for you';

    protected ?string $body = null;

    protected ?string $ctaLabel = 'Yes, add it!';

    protected ?string $declineLabel = 'No thanks';

    protected int $priority = 0;

    protected ?\DateTimeInterface $startsAt = null;

    protected ?\DateTimeInterface $endsAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    public function getTriggerProduct(): ?ProductInterface
    {
        return $this->triggerProduct;
    }

    public function setTriggerProduct(?ProductInterface $product): void
    {
        $this->triggerProduct = $product;
    }

    public function getOfferProduct(): ?ProductInterface
    {
        return $this->offerProduct;
    }

    public function setOfferProduct(?ProductInterface $product): void
    {
        $this->offerProduct = $product;
    }

    public function getOfferVariant(): ?ProductVariantInterface
    {
        return $this->offerVariant;
    }

    public function setOfferVariant(?ProductVariantInterface $variant): void
    {
        $this->offerVariant = $variant;
    }

    public function getDiscountPercent(): int
    {
        return $this->discountPercent;
    }

    public function setDiscountPercent(int $percent): void
    {
        $this->discountPercent = $percent;
    }

    public function getHeadline(): ?string
    {
        return $this->headline;
    }

    public function setHeadline(?string $headline): void
    {
        $this->headline = $headline;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(?string $body): void
    {
        $this->body = $body;
    }

    public function getCtaLabel(): ?string
    {
        return $this->ctaLabel;
    }

    public function setCtaLabel(?string $label): void
    {
        $this->ctaLabel = $label;
    }

    public function getDeclineLabel(): ?string
    {
        return $this->declineLabel;
    }

    public function setDeclineLabel(?string $label): void
    {
        $this->declineLabel = $label;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): void
    {
        $this->priority = $priority;
    }

    public function getStartsAt(): ?\DateTimeInterface
    {
        return $this->startsAt;
    }

    public function setStartsAt(?\DateTimeInterface $startsAt): void
    {
        $this->startsAt = $startsAt;
    }

    public function getEndsAt(): ?\DateTimeInterface
    {
        return $this->endsAt;
    }

    public function setEndsAt(?\DateTimeInterface $endsAt): void
    {
        $this->endsAt = $endsAt;
    }
}
