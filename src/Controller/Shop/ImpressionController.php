<?php

declare(strict_types=1);

namespace Abderrahim\SyliusUpsellPlugin\Controller\Shop;

use Abderrahim\SyliusUpsellPlugin\Entity\UpsellImpression;
use Abderrahim\SyliusUpsellPlugin\Repository\UpsellOfferRepository;
use Abderrahim\SyliusUpsellPlugin\Service\UpsellAnalyticsService;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ImpressionController extends AbstractController
{
    public function __construct(
        private readonly UpsellAnalyticsService $analyticsService,
        private readonly UpsellOfferRepository $offerRepository,
        private readonly ChannelContextInterface $channelContext,
    ) {
    }

    public function recordAction(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!\is_array($data) || empty($data['type']) || empty($data['productCode'])) {
            return new JsonResponse(['error' => 'Invalid data'], Response::HTTP_BAD_REQUEST);
        }

        $type = $data['type'];
        $productCode = $data['productCode'];
        $orderToken = $data['orderToken'] ?? null;
        $action = $data['action'] ?? UpsellImpression::ACTION_SHOWN;
        $offerId = $data['offerId'] ?? null;
        $revenue = (int) ($data['revenue'] ?? 0);
        $channelCode = $this->channelContext->getChannel()->getCode() ?? '';

        $offer = null;
        if (null !== $offerId) {
            $offer = $this->offerRepository->find((int) $offerId);
        }

        if ($action === UpsellImpression::ACTION_SHOWN) {
            $impression = $this->analyticsService->recordImpression(
                $type,
                $orderToken,
                $productCode,
                $channelCode,
                $offer,
            );

            return new JsonResponse(['impressionId' => $impression->getId()]);
        }

        $impressionId = (int) ($data['impressionId'] ?? 0);

        if ($action === UpsellImpression::ACTION_ACCEPTED) {
            $this->analyticsService->recordAccepted($impressionId, $revenue);
        } elseif ($action === UpsellImpression::ACTION_DECLINED) {
            $this->analyticsService->recordDeclined($impressionId);
        }

        return new JsonResponse(['success' => true]);
    }
}
