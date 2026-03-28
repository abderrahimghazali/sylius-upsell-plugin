<?php

declare(strict_types=1);

namespace Abderrahim\SyliusUpsellPlugin\Service;

use Abderrahim\SyliusUpsellPlugin\Entity\UpsellConfiguration;
use Doctrine\ORM\EntityManagerInterface;

final class UpsellConfigurationProvider
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function getConfiguration(): UpsellConfiguration
    {
        $repository = $this->entityManager->getRepository(UpsellConfiguration::class);
        $config = $repository->findOneBy([]);

        if (null === $config) {
            $config = new UpsellConfiguration();
            $this->entityManager->persist($config);
            $this->entityManager->flush();
        }

        return $config;
    }
}
