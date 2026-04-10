<?php

declare(strict_types=1);

namespace Abderrahim\SyliusUpsellPlugin\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class UpsellExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('upsell_frequently_bought_together', [UpsellRuntime::class, 'getFrequentlyBoughtTogether']),
            new TwigFunction('upsell_configuration', [UpsellRuntime::class, 'getConfiguration']),
        ];
    }
}
