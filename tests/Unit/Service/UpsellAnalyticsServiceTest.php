<?php

declare(strict_types=1);

namespace Tests\Abderrahim\SyliusUpsellPlugin\Unit\Service;

use Abderrahim\SyliusUpsellPlugin\Entity\UpsellImpression;
use Abderrahim\SyliusUpsellPlugin\Repository\UpsellImpressionRepository;
use Abderrahim\SyliusUpsellPlugin\Service\UpsellAnalyticsService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class UpsellAnalyticsServiceTest extends TestCase
{
    private UpsellImpressionRepository&MockObject $impressionRepository;
    private EntityManagerInterface&MockObject $entityManager;
    private UpsellAnalyticsService $service;

    protected function setUp(): void
    {
        $this->impressionRepository = $this->createMock(UpsellImpressionRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->service = new UpsellAnalyticsService($this->impressionRepository, $this->entityManager);
    }

    public function testRecordImpressionPersistsAndFlushes(): void
    {
        $this->entityManager->expects(self::once())->method('persist')->with(self::isInstanceOf(UpsellImpression::class));
        $this->entityManager->expects(self::never())->method('flush');

        $impression = $this->service->recordImpression('fbt', null, 'PRODUCT_CODE', 'WEB');

        self::assertSame('fbt', $impression->getType());
        self::assertSame('PRODUCT_CODE', $impression->getProductCode());
        self::assertSame('WEB', $impression->getChannel());
        self::assertSame(UpsellImpression::ACTION_SHOWN, $impression->getAction());
    }

    public function testGetSummaryIncludesAcceptanceRate(): void
    {
        $from = new \DateTime('2026-03-01');
        $to = new \DateTime('2026-03-28');

        $this->impressionRepository
            ->method('getSummary')
            ->with($from, $to, 'WEB')
            ->willReturn([
                'impressions' => 100,
                'accepted' => 25,
                'declined' => 50,
                'revenue' => 50000,
            ]);

        $summary = $this->service->getSummary($from, $to, 'WEB');

        self::assertSame(100, $summary['impressions']);
        self::assertSame(25, $summary['accepted']);
        self::assertSame(25.0, $summary['rate']);
        self::assertSame(50000, $summary['revenue']);
    }

    public function testGetSummaryZeroImpressionsReturnsZeroRate(): void
    {
        $from = new \DateTime('2026-03-01');
        $to = new \DateTime('2026-03-28');

        $this->impressionRepository
            ->method('getSummary')
            ->willReturn([
                'impressions' => 0,
                'accepted' => 0,
                'declined' => 0,
                'revenue' => 0,
            ]);

        $summary = $this->service->getSummary($from, $to, 'WEB');

        self::assertSame(0.0, $summary['rate']);
    }

    public function testRecordAcceptedUpdatesImpressionAction(): void
    {
        $impression = new UpsellImpression();

        $this->impressionRepository
            ->method('find')
            ->with(1)
            ->willReturn($impression);

        $this->entityManager->expects(self::never())->method('flush');

        $this->service->recordAccepted(1, 3500);

        self::assertSame(UpsellImpression::ACTION_ACCEPTED, $impression->getAction());
        self::assertSame(3500, $impression->getRevenue());
    }

    public function testRecordDeclinedUpdatesImpressionAction(): void
    {
        $impression = new UpsellImpression();

        $this->impressionRepository
            ->method('find')
            ->with(2)
            ->willReturn($impression);

        $this->entityManager->expects(self::never())->method('flush');

        $this->service->recordDeclined(2);

        self::assertSame(UpsellImpression::ACTION_DECLINED, $impression->getAction());
    }
}
