<?php

declare(strict_types=1);

namespace Abderrahim\SyliusUpsellPlugin\EventListener;

use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: 'sylius.menu.admin.main', method: 'addAdminMenuItems')]
final class AdminMenuListener
{
    public function addAdminMenuItems(MenuBuilderEvent $event): void
    {
        $menu = $event->getMenu();

        $configurationMenu = $menu->getChild('configuration');

        if (null !== $configurationMenu) {
            $configurationMenu
                ->addChild('upsell_settings', [
                    'route' => 'abderrahim_sylius_upsell_admin_configuration',
                ])
                ->setLabel('upsell.ui.upsell_settings')
                ->setLabelAttribute('icon', 'arrow up')
            ;
        }
    }
}
