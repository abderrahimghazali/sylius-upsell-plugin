<?php

declare(strict_types=1);

namespace Abderrahim\SyliusUpsellPlugin\Controller\Admin;

use Abderrahim\SyliusUpsellPlugin\Form\Type\UpsellConfigurationType;
use Abderrahim\SyliusUpsellPlugin\Service\UpsellConfigurationProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class UpsellConfigurationController extends AbstractController
{
    public function __construct(
        private readonly UpsellConfigurationProvider $configurationProvider,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public function editAction(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMINISTRATION_ACCESS');

        $configuration = $this->configurationProvider->getConfiguration();

        $form = $this->createForm(UpsellConfigurationType::class, $configuration);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'upsell.flash.configuration_saved');

            return $this->redirectToRoute('abderrahim_sylius_upsell_admin_configuration');
        }

        return $this->render('@SyliusUpsellPlugin/Admin/configuration.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
