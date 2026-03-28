<?php

declare(strict_types=1);

namespace Abderrahim\SyliusUpsellPlugin\Entity;

use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Resource\Model\ResourceInterface;

interface UpsellRelationInterface extends ResourceInterface
{
    public function getSourceProduct(): ?ProductInterface;

    public function setSourceProduct(?ProductInterface $product): void;

    public function getRelatedProduct(): ?ProductInterface;

    public function setRelatedProduct(?ProductInterface $product): void;

    public function getPosition(): int;

    public function setPosition(int $position): void;

    public function getDiscount(): ?int;

    public function setDiscount(?int $discount): void;

    public function getCreatedAt(): ?\DateTimeInterface;
}
