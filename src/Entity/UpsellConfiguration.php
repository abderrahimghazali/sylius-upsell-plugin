<?php

declare(strict_types=1);

namespace Abderrahim\SyliusUpsellPlugin\Entity;

class UpsellConfiguration
{
    public const FALLBACK_ALGORITHMIC = 'algorithmic';
    public const FALLBACK_MANUAL_ONLY = 'manual_only';
    public const FALLBACK_DISABLED = 'disabled';

    protected ?int $id = null;

    protected bool $enabled = true;

    protected int $minCoPurchaseThreshold = 3;

    protected int $maxProductsShown = 4;

    protected string $sectionTitle = 'Frequently bought together';

    protected bool $showDiscountBadge = true;

    protected string $fallbackStrategy = self::FALLBACK_ALGORITHMIC;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    public function getMinCoPurchaseThreshold(): int
    {
        return $this->minCoPurchaseThreshold;
    }

    public function setMinCoPurchaseThreshold(int $threshold): void
    {
        $this->minCoPurchaseThreshold = $threshold;
    }

    public function getMaxProductsShown(): int
    {
        return $this->maxProductsShown;
    }

    public function setMaxProductsShown(int $max): void
    {
        $this->maxProductsShown = $max;
    }

    public function getSectionTitle(): string
    {
        return $this->sectionTitle;
    }

    public function setSectionTitle(string $title): void
    {
        $this->sectionTitle = $title;
    }

    public function isShowDiscountBadge(): bool
    {
        return $this->showDiscountBadge;
    }

    public function setShowDiscountBadge(bool $show): void
    {
        $this->showDiscountBadge = $show;
    }

    public function getFallbackStrategy(): string
    {
        return $this->fallbackStrategy;
    }

    public function setFallbackStrategy(string $strategy): void
    {
        $this->fallbackStrategy = $strategy;
    }
}
