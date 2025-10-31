<?php

declare(strict_types=1);

namespace Tourze\AzureOAuth2ClientBundle\Service;

use Knp\Menu\ItemInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\AzureOAuth2ClientBundle\Entity\AzureOAuth2Config;
use Tourze\AzureOAuth2ClientBundle\Entity\AzureOAuth2State;
use Tourze\AzureOAuth2ClientBundle\Entity\AzureOAuth2User;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface;

#[Autoconfigure(public: true)]
readonly class AdminMenu implements MenuProviderInterface
{
    public function __construct(private LinkGeneratorInterface $linkGenerator)
    {
    }

    public function __invoke(ItemInterface $item): void
    {
        if (null === $item->getChild('Azure OAuth2')) {
            $item->addChild('Azure OAuth2');
        }

        $azureMenu = $item->getChild('Azure OAuth2');
        if (null !== $azureMenu) {
            $azureMenu
                ->addChild('OAuth2配置')
                ->setUri($this->linkGenerator->getCurdListPage(AzureOAuth2Config::class))
                ->setAttribute('icon', 'fas fa-cog')
            ;

            $azureMenu
                ->addChild('OAuth2状态')
                ->setUri($this->linkGenerator->getCurdListPage(AzureOAuth2State::class))
                ->setAttribute('icon', 'fas fa-info-circle')
            ;

            $azureMenu
                ->addChild('OAuth2用户')
                ->setUri($this->linkGenerator->getCurdListPage(AzureOAuth2User::class))
                ->setAttribute('icon', 'fas fa-users')
            ;
        }
    }
}
