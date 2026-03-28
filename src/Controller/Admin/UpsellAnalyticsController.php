<?php

declare(strict_types=1);

namespace Abderrahim\SyliusUpsellPlugin\Controller\Admin;

use Abderrahim\SyliusUpsellPlugin\Service\UpsellAnalyticsService;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class UpsellAnalyticsController extends AbstractController
{
    public function __construct(
        private readonly UpsellAnalyticsService $analyticsService,
        private readonly ChannelContextInterface $channelContext,
    ) {
    }

    public function indexAction(Request $request): Response
    {
        $channelCode = $this->channelContext->getChannel()->getCode() ?? '';

        $from = new \DateTime($request->query->get('from', '-30 days'));
        $to = new \DateTime($request->query->get('to', 'now'));

        $summary = $this->analyticsService->getSummary($from, $to, $channelCode);
        $breakdown = $this->analyticsService->getBreakdownByOffer($from, $to, $channelCode);
        $dailyRevenue = $this->analyticsService->getDailyRevenue($from, $to, $channelCode);

        return $this->render('@SyliusUpsellPlugin/Admin/analytics.html.twig', [
            'summary' => $summary,
            'breakdown' => $breakdown,
            'dailyRevenue' => $dailyRevenue,
            'dateFrom' => $from->format('Y-m-d'),
            'dateTo' => $to->format('Y-m-d'),
        ]);
    }
}
