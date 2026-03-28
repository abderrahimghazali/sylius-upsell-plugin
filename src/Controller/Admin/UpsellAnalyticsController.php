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
        $this->denyAccessUnlessGranted('ROLE_ADMINISTRATION_ACCESS');

        $channelCode = $this->channelContext->getChannel()->getCode() ?? '';

        try {
            $from = new \DateTime($request->query->getString('from', '-30 days'));
        } catch (\Exception) {
            $from = new \DateTime('-30 days');
        }

        try {
            $to = new \DateTime($request->query->getString('to', 'now'));
        } catch (\Exception) {
            $to = new \DateTime('now');
        }

        $summary = $this->analyticsService->getSummary($from, $to, $channelCode);
        $breakdown = $this->analyticsService->getBreakdownByOffer($from, $to, $channelCode);
        $dailyRevenue = $this->analyticsService->getDailyRevenue($from, $to, $channelCode);

        $currencyCode = $this->channelContext->getChannel()->getBaseCurrency()?->getCode() ?? 'USD';

        return $this->render('@SyliusUpsellPlugin/Admin/analytics.html.twig', [
            'summary' => $summary,
            'breakdown' => $breakdown,
            'dailyRevenue' => $dailyRevenue,
            'dateFrom' => $from->format('Y-m-d'),
            'dateTo' => $to->format('Y-m-d'),
            'currencyCode' => $currencyCode,
        ]);
    }
}
