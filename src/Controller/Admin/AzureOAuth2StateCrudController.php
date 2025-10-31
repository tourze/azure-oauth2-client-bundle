<?php

declare(strict_types=1);

namespace Tourze\AzureOAuth2ClientBundle\Controller\Admin;

use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Tourze\AzureOAuth2ClientBundle\Entity\AzureOAuth2Config;
use Tourze\AzureOAuth2ClientBundle\Entity\AzureOAuth2State;

/**
 * OAuth2状态CRUD控制器
 *
 * @extends AbstractCrudController<AzureOAuth2State>
 */
#[AdminCrud(routePath: '/azure-oauth2/state', routeName: 'azure_oauth2_state')]
final class AzureOAuth2StateCrudController extends AbstractCrudController
{
    public function __construct()
    {
    }

    public static function getEntityFqcn(): string
    {
        return AzureOAuth2State::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('OAuth2状态')
            ->setEntityLabelInPlural('OAuth2状态列表')
            ->setPageTitle('index', 'OAuth2状态管理')
            ->setPageTitle('detail', fn (AzureOAuth2State $state) => sprintf('状态 <strong>%s</strong> 详情', $state->getState()))
            ->setDefaultSort(['createTime' => 'DESC'])
            ->setSearchFields(['id', 'state', 'sessionId', 'codeChallenge'])
            ->setHelp('index', 'OAuth2状态记录了Azure认证过程中的状态信息，包括PKCE参数和过期时间')
            ->setPaginatorPageSize(50)
            ->showEntityActionsInlined()
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->setMaxLength(9999)
        ;

        yield AssociationField::new('config', 'OAuth2配置')
            ->formatValue(function ($value) {
                if (!$value instanceof AzureOAuth2Config) {
                    return '';
                }

                return sprintf('%s (%s)', $value->getName() ?? '未命名', $value->getClientId());
            })
        ;

        yield TextField::new('state', '状态值')
            ->setMaxLength(50)
        ;

        yield TextField::new('sessionId', '会话ID')
            ->hideOnIndex()
            ->setMaxLength(50)
        ;

        yield TextField::new('codeChallenge', 'PKCE代码挑战')
            ->hideOnIndex()
            ->setMaxLength(50)
        ;

        yield TextField::new('codeChallengeMethod', 'PKCE方法')
            ->hideOnIndex()
        ;

        yield BooleanField::new('isUsed', '是否已使用')
            ->renderAsSwitch()
            ->formatValue(function ($value) {
                return $value
                    ? '<span class="badge bg-warning">已使用</span>'
                    : '<span class="badge bg-success">未使用</span>';
            })
        ;

        yield DateTimeField::new('expiresTime', '过期时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->formatValue(function ($value) {
                if (!$value instanceof \DateTimeImmutable) {
                    return '';
                }

                $now = new \DateTimeImmutable();
                $isExpired = $value < $now;
                $formatted = $value->format('Y-m-d H:i:s');

                return $isExpired
                    ? sprintf('<span class="text-danger">%s (已过期)</span>', $formatted)
                    : sprintf('<span class="text-success">%s</span>', $formatted);
            })
        ;

        yield DateTimeField::new('createTime', '创建时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->hideOnForm()
        ;

        yield DateTimeField::new('updateTime', '更新时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->hideOnForm()
            ->hideOnIndex()
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('config', 'OAuth2配置'))
            ->add(TextFilter::new('state', '状态值'))
            ->add(TextFilter::new('sessionId', '会话ID'))
            ->add(BooleanFilter::new('isUsed', '是否已使用'))
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::EDIT, Action::DELETE)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->setPermission(Action::DELETE, 'ROLE_SUPER_ADMIN')
        ;
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $queryBuilder = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        return $queryBuilder
            ->select('entity, config')
            ->leftJoin('entity.config', 'config')
            ->orderBy('entity.createTime', 'DESC')
        ;
    }
}
