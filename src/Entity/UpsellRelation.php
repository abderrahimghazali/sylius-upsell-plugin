<?php

declare(strict_types=1);

namespace Abderrahim\SyliusUpsellPlugin\Entity;

use Sylius\Component\Core\Model\ProductInterface;

class UpsellRelation implements UpsellRelationInterface
{
    protected ?int $id = null;

    protected ?ProductInterface $sourceProduct = null;

    protected ?ProductInterface $relatedProduct = null;

    protected int $position = 0;

    protected ?int $discount = null;

    protected ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSourceProduct(): ?ProductInterface
    {
        return $this->sourceProduct;
    }

    public function setSourceProduct(?ProductInterface $product): void
    {
        $this->sourceProduct = $product;
    }

    public function getRelatedProduct(): ?ProductInterface
    {
        return $this->relatedProduct;
    }

    public function setRelatedProduct(?ProductInterface $product): void
    {
        $this->relatedProduct = $product;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): void
    {
        $this->position = $position;
    }

    public function getDiscount(): ?int
    {
        return $this->discount;
    }

    public function setDiscount(?int $discount): void
    {
        $this->discount = $discount;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }
}
