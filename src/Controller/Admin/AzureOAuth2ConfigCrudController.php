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
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Tourze\AzureOAuth2ClientBundle\Entity\AzureOAuth2Config;

/**
 * Azure OAuth2配置CRUD控制器
 *
 * @extends AbstractCrudController<AzureOAuth2Config>
 */
#[AdminCrud(routePath: '/azure-oauth2/config', routeName: 'azure_oauth2_config')]
final class AzureOAuth2ConfigCrudController extends AbstractCrudController
{
    public function __construct()
    {
    }

    public static function getEntityFqcn(): string
    {
        return AzureOAuth2Config::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Azure OAuth2配置')
            ->setEntityLabelInPlural('Azure OAuth2配置列表')
            ->setPageTitle('index', 'Azure OAuth2配置管理')
            ->setPageTitle('new', '新增Azure OAuth2配置')
            ->setPageTitle('edit', fn (AzureOAuth2Config $config) => sprintf('编辑配置 <strong>%s</strong>', $config->getName() ?? $config->getClientId()))
            ->setPageTitle('detail', fn (AzureOAuth2Config $config) => sprintf('配置 <strong>%s</strong> 详情', $config->getName() ?? $config->getClientId()))
            ->setDefaultSort(['updateTime' => 'DESC'])
            ->setSearchFields(['name', 'clientId', 'tenantId', 'remark'])
            ->setHelp('index', 'Azure OAuth2配置管理，用于配置Azure应用的OAuth2认证参数')
            ->setPaginatorPageSize(30)
            ->showEntityActionsInlined()
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->setMaxLength(9999)
            ->hideOnForm()
        ;

        yield TextField::new('name', '配置名称')
            ->setHelp('用于标识此配置的名称，便于管理')
            ->setRequired(false)
        ;

        yield TextField::new('clientId', '应用ID')
            ->setHelp('Azure应用的Client ID')
            ->setRequired(true)
        ;

        yield TextField::new('clientSecret', '应用密钥')
            ->setHelp('Azure应用的Client Secret')
            ->setRequired(true)
            ->hideOnIndex()
            ->setFormTypeOption('attr', ['type' => 'password'])
            ->formatValue(function ($value) {
                return $value ? str_repeat('*', 12) : '';
            })
        ;

        yield TextField::new('tenantId', '租户ID')
            ->setHelp('Azure租户的Tenant ID')
            ->setRequired(true)
        ;

        yield TextareaField::new('scope', '授权范围')
            ->setHelp('OAuth2授权范围，多个范围用空格分隔')
            ->setRequired(false)
            ->hideOnIndex()
            ->setNumOfRows(3)
        ;

        yield TextField::new('redirectUri', '重定向URI')
            ->setHelp('OAuth2授权成功后的重定向地址')
            ->setRequired(false)
            ->hideOnIndex()
        ;

        yield TextareaField::new('remark', '备注信息')
            ->setHelp('配置的备注和说明信息')
            ->setRequired(false)
            ->hideOnIndex()
            ->setNumOfRows(3)
        ;

        yield BooleanField::new('valid', '是否有效')
            ->setHelp('标识此配置是否可用')
            ->renderAsSwitch(false)
        ;

        yield DateTimeField::new('createTime', '创建时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->hideOnForm()
        ;

        yield DateTimeField::new('updateTime', '更新时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->hideOnForm()
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('name', '配置名称'))
            ->add(TextFilter::new('clientId', '应用ID'))
            ->add(TextFilter::new('tenantId', '租户ID'))
            ->add(BooleanFilter::new('valid', '是否有效'))
            ->add(TextFilter::new('remark', '备注信息'))
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->setPermission(Action::NEW, 'ROLE_ADMIN')
            ->setPermission(Action::EDIT, 'ROLE_ADMIN')
            ->setPermission(Action::DELETE, 'ROLE_SUPER_ADMIN')
            ->setPermission(Action::DETAIL, 'ROLE_USER')
        ;
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $queryBuilder = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        return $queryBuilder
            ->orderBy('entity.updateTime', 'DESC')
        ;
    }
}
