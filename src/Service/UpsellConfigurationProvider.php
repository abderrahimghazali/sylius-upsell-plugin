<?php

declare(strict_types=1);

namespace Abderrahim\SyliusUpsellPlugin\Service;

use Abderrahim\SyliusUpsellPlugin\Entity\UpsellConfiguration;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;

class UpsellConfigurationProvider
{
    private ?UpsellConfiguration $cached = null;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function getConfiguration(): UpsellConfiguration
    {
        // Verify cached entity is still managed (not detached by a clear())
        if (null !== $this->cached && $this->entityManager->contains($this->cached)) {
            return $this->cached;
        }

        $this->cached = null;

        $repository = $this->entityManager->getRepository(UpsellConfiguration::class);
        $config = $repository->findOneBy([]);

        if (null === $config) {
            $config = new UpsellConfiguration();
            $this->entityManager->persist($config);

            try {
                $this->entityManager->flush();
            } catch (UniqueConstraintViolationException) {
                $this->entityManager->clear();
                $config = $repository->findOneBy([]) ?? new UpsellConfiguration();
            }
        }

        $this->cached = $config;

        return $config;
    }
}
