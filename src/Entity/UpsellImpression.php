<?php

declare(strict_types=1);

namespace Abderrahim\SyliusUpsellPlugin\Entity;

class UpsellImpression
{
    public const TYPE_FBT = 'fbt';
    public const TYPE_POST_PURCHASE = 'post_purchase';

    public const ACTION_SHOWN = 'shown';
    public const ACTION_ACCEPTED = 'accepted';
    public const ACTION_DECLINED = 'declined';

    protected ?int $id = null;

    protected ?UpsellOffer $upsellOffer = null;

    protected string $type = self::TYPE_FBT;

    protected ?string $orderToken = null;

    protected string $productCode = '';

    protected string $action = self::ACTION_SHOWN;

    protected int $revenue = 0;

    protected string $channel = '';

    protected \DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUpsellOffer(): ?UpsellOffer
    {
        return $this->upsellOffer;
    }

    public function setUpsellOffer(?UpsellOffer $offer): void
    {
        $this->upsellOffer = $offer;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getOrderToken(): ?string
    {
        return $this->orderToken;
    }

    public function setOrderToken(?string $token): void
    {
        $this->orderToken = $token;
    }

    public function getProductCode(): string
    {
        return $this->productCode;
    }

    public function setProductCode(string $code): void
    {
        $this->productCode = $code;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function setAction(string $action): void
    {
        $this->action = $action;
    }

    public function getRevenue(): int
    {
        return $this->revenue;
    }

    public function setRevenue(int $revenue): void
    {
        $this->revenue = $revenue;
    }

    public function getChannel(): string
    {
        return $this->channel;
    }

    public function setChannel(string $channel): void
    {
        $this->channel = $channel;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }
}
