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
    private const ALLOWED_ACTIONS = [UpsellImpression::ACTION_SHOWN, UpsellImpression::ACTION_ACCEPTED, UpsellImpression::ACTION_DECLINED];

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
        $action = $data['action'] ?? UpsellImpression::ACTION_SHOWN;

        if (!\in_array($type, self::ALLOWED_TYPES, true) || !\in_array($action, self::ALLOWED_ACTIONS, true)) {
            return new JsonResponse(['error' => 'Invalid type or action'], Response::HTTP_BAD_REQUEST);
        }

        $productCode = substr((string) $data['productCode'], 0, 255);
        $orderToken = isset($data['orderToken']) ? substr((string) $data['orderToken'], 0, 255) : null;
        $channelCode = $this->channelContext->getChannel()->getCode() ?? '';

        $offer = null;
        $offerId = $data['offerId'] ?? null;
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
        if ($impressionId <= 0) {
            return new JsonResponse(['error' => 'Missing impressionId'], Response::HTTP_BAD_REQUEST);
        }

        // Revenue is ignored from client — server computes it in acceptAction
        if ($action === UpsellImpression::ACTION_ACCEPTED) {
            $this->analyticsService->recordAccepted($impressionId, 0);
        } elseif ($action === UpsellImpression::ACTION_DECLINED) {
            $this->analyticsService->recordDeclined($impressionId);
        }

        return new JsonResponse(['success' => true]);
    }
}
