<?php

declare(strict_types=1);

namespace Abderrahim\SyliusUpsellPlugin\Controller\Admin;

use Abderrahim\SyliusUpsellPlugin\Entity\UpsellOfferInterface;
use Abderrahim\SyliusUpsellPlugin\Form\Type\UpsellOfferType;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class UpsellOfferController extends AbstractController
{
    public function __construct(
        private readonly RepositoryInterface $offerRepository,
        private readonly FactoryInterface $offerFactory,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public function createAction(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMINISTRATION_ACCESS');

        /** @var UpsellOfferInterface $offer */
        $offer = $this->offerFactory->createNew();

        $form = $this->createForm(UpsellOfferType::class, $offer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($offer);
            $this->entityManager->flush();

            $this->addFlash('success', 'upsell.flash.offer_created');

            return $this->redirectToRoute('upsell_admin_offer_index');
        }

        return $this->render('@SyliusUpsellPlugin/Admin/upsell_offer/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    public function updateAction(Request $request, int $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMINISTRATION_ACCESS');

        $offer = $this->offerRepository->find($id);
        if (!$offer instanceof UpsellOfferInterface) {
            throw new NotFoundHttpException('Upsell offer not found.');
        }

        $form = $this->createForm(UpsellOfferType::class, $offer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'upsell.flash.offer_updated');

            return $this->redirectToRoute('upsell_admin_offer_index');
        }

        return $this->render('@SyliusUpsellPlugin/Admin/upsell_offer/update.html.twig', [
            'form' => $form->createView(),
            'offer' => $offer,
        ]);
    }
}
