<?php

declare(strict_types=1);

namespace Abderrahim\SyliusUpsellPlugin\Controller\Shop;

use Abderrahim\SyliusUpsellPlugin\Entity\UpsellImpression;
use Abderrahim\SyliusUpsellPlugin\Repository\UpsellOfferRepository;
use Abderrahim\SyliusUpsellPlugin\Service\UpsellAnalyticsService;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ImpressionController extends AbstractController
{
    private const ALLOWED_TYPES = [UpsellImpression::TYPE_FBT, UpsellImpression::TYPE_POST_PURCHASE];
    private const MAX_IMPRESSIONS_PER_SESSION = 20;

    public function __construct(
        private readonly UpsellAnalyticsService $analyticsService,
        private readonly UpsellOfferRepository $offerRepository,
        private readonly ChannelContextInterface $channelContext,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public function recordAction(Request $request): JsonResponse
    {
        // Validate CSRF token
        $token = $request->headers->get('X-CSRF-Token', '');
        if (!$this->isCsrfTokenValid('upsell_impression', $token)) {
            return new JsonResponse(['error' => 'Invalid CSRF token'], Response::HTTP_FORBIDDEN);
        }

        // Rate limit: max impressions per session
        $session = $request->getSession();
        $count = (int) $session->get('upsell_impression_count', 0);
        if ($count >= self::MAX_IMPRESSIONS_PER_SESSION) {
            return new JsonResponse(['error' => 'Rate limit exceeded'], Response::HTTP_TOO_MANY_REQUESTS);
        }

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

        $impression = $this->analyticsService->recordImpression(
            $type,
            $orderToken,
            $productCode,
            $channelCode,
            $offer,
        );

        $this->entityManager->flush();

        // Increment session counter
        $session->set('upsell_impression_count', $count + 1);

        // Store impression ID in session for FBT accept tracking
        $fbtImpressionIds = $session->get('upsell_fbt_impression_ids', []);
        $fbtImpressionIds[] = $impression->getId();
        $session->set('upsell_fbt_impression_ids', $fbtImpressionIds);

        return new JsonResponse(['impressionId' => $impression->getId()]);
    }
}
