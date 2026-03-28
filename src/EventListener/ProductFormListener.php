<?php

declare(strict_types=1);

namespace Abderrahim\SyliusUpsellPlugin\EventListener;

use Abderrahim\SyliusUpsellPlugin\Entity\UpsellRelation;
use Abderrahim\SyliusUpsellPlugin\Form\Type\UpsellRelationType;
use Abderrahim\SyliusUpsellPlugin\Repository\UpsellRelationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Bundle\ResourceBundle\Event\ResourceControllerEvent;
use Sylius\Component\Core\Model\ProductInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;

#[AsEventListener(event: 'sylius.product.pre_update', method: 'onProductPreUpdate')]
#[AsEventListener(event: 'sylius.product.pre_create', method: 'onProductPreUpdate')]
final class ProductFormListener
{
    public function __construct(
        private readonly UpsellRelationRepository $upsellRelationRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $requestStack,
    ) {}

    public function onProductPreUpdate(ResourceControllerEvent $event): void
    {
        /** @var ProductInterface $product */
        $product = $event->getSubject();
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            return;
        }

        $formData = $request->request->all();
        $upsellData = $formData['sylius_product']['upsellRelations'] ?? [];

        // Remove existing relations
        $existingRelations = $this->upsellRelationRepository->findBySourceProduct($product, 100);
        foreach ($existingRelations as $relation) {
            $this->entityManager->remove($relation);
        }

        // Persist new relations
        $position = 0;
        foreach ($upsellData as $data) {
            if (empty($data['relatedProduct'])) {
                continue;
            }

            $relation = new UpsellRelation();
            $relation->setSourceProduct($product);

            $relatedProduct = $this->entityManager->getRepository(\Sylius\Component\Core\Model\Product::class)
                ->find((int) $data['relatedProduct']);

            if (null === $relatedProduct) {
                continue;
            }

            $relation->setRelatedProduct($relatedProduct);
            $relation->setPosition($position++);
            $discount = !empty($data['discount']) ? max(0, min(100, (int) $data['discount'])) : null;
            $relation->setDiscount($discount);

            $this->entityManager->persist($relation);
        }
    }
}
