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
    private const ALLOWED_TYPES = [UpsellImpression::TYPE_FBT, UpsellImpression::TYPE_POST_PURCHASE];

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

        if (!\in_array($type, self::ALLOWED_TYPES, true)) {
            return new JsonResponse(['error' => 'Invalid type'], Response::HTTP_BAD_REQUEST);
        }

        $productCode = substr((string) $data['productCode'], 0, 255);
        $orderToken = isset($data['orderToken']) ? substr((string) $data['orderToken'], 0, 255) : null;
        $channelCode = $this->channelContext->getChannel()->getCode() ?? '';

        $offer = null;
        $offerId = $data['offerId'] ?? null;
        if (null !== $offerId) {
            /** @var \Abderrahim\SyliusUpsellPlugin\Entity\UpsellOffer|null $offer */
            $offer = $this->offerRepository->find((int) $offerId);
        }

        // Only allow recording "shown" impressions from this endpoint.
        // Accepted/declined are handled server-side in PostPurchaseController.
        $impression = $this->analyticsService->recordImpression(
            $type,
            $orderToken,
            $productCode,
            $channelCode,
            $offer,
        );

        // Store impression ID in session for FBT accept tracking
        $session = $request->getSession();
        $fbtImpressionIds = $session->get('upsell_fbt_impression_ids', []);
        $fbtImpressionIds[] = $impression->getId();
        $session->set('upsell_fbt_impression_ids', $fbtImpressionIds);

        return new JsonResponse(['impressionId' => $impression->getId()]);
    }
}
