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
use EasyCorp\Bundle\EasyAdminBundle\Field\CodeEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Tourze\AzureOAuth2ClientBundle\Entity\AzureOAuth2Config;
use Tourze\AzureOAuth2ClientBundle\Entity\AzureOAuth2User;

/**
 * Azure OAuth2用户CRUD控制器
 *
 * @extends AbstractCrudController<AzureOAuth2User>
 */
#[AdminCrud(routePath: '/azure-oauth2/user', routeName: 'azure_oauth2_user')]
final class AzureOAuth2UserCrudController extends AbstractCrudController
{
    public function __construct()
    {
    }

    public static function getEntityFqcn(): string
    {
        return AzureOAuth2User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('OAuth2用户')
            ->setEntityLabelInPlural('OAuth2用户列表')
            ->setPageTitle('index', 'OAuth2用户管理')
            ->setPageTitle('detail', fn (AzureOAuth2User $user) => sprintf('用户 <strong>%s</strong> 详情', null !== $user->getDisplayName() ? $user->getDisplayName() : $user->getObjectId()))
            ->setDefaultSort(['createTime' => 'DESC'])
            ->setSearchFields(['objectId', 'userPrincipalName', 'displayName', 'mail'])
            ->setHelp('index', 'OAuth2用户记录了通过Azure AD认证的用户信息和令牌')
            ->setPaginatorPageSize(50)
            ->showEntityActionsInlined()
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->setMaxLength(9999)
        ;

        yield AssociationField::new('config', '配置')
            ->setColumns('col-sm-6 col-lg-5 col-xxl-4')
            ->formatValue(function ($value) {
                if (!$value instanceof AzureOAuth2Config) {
                    return '';
                }

                return null !== $value->getName() ? $value->getName() : $value->getClientId();
            })
        ;

        yield TextField::new('objectId', '用户对象ID')
            ->setColumns('col-sm-6 col-lg-5 col-xxl-4')
        ;

        yield TextField::new('userPrincipalName', '用户主名称')
            ->setColumns('col-sm-6 col-lg-5 col-xxl-4')
        ;

        yield TextField::new('displayName', '显示名称')
            ->setColumns('col-sm-6 col-lg-5 col-xxl-4')
        ;

        yield TextField::new('givenName', '名字')
            ->hideOnIndex()
            ->setColumns('col-sm-6 col-lg-5 col-xxl-4')
        ;

        yield TextField::new('surname', '姓氏')
            ->hideOnIndex()
            ->setColumns('col-sm-6 col-lg-5 col-xxl-4')
        ;

        yield EmailField::new('mail', '邮箱')
            ->setColumns('col-sm-6 col-lg-5 col-xxl-4')
        ;

        yield TextField::new('mobilePhone', '手机号码')
            ->hideOnIndex()
            ->setColumns('col-sm-6 col-lg-5 col-xxl-4')
        ;

        yield TextField::new('officeLocation', '办公地点')
            ->hideOnIndex()
            ->setColumns('col-sm-6 col-lg-5 col-xxl-4')
        ;

        yield TextField::new('preferredLanguage', '首选语言')
            ->hideOnIndex()
            ->setColumns('col-sm-6 col-lg-5 col-xxl-4')
        ;

        yield TextField::new('jobTitle', '职位')
            ->hideOnIndex()
            ->setColumns('col-sm-6 col-lg-5 col-xxl-4')
        ;

        yield TextareaField::new('accessToken', '访问令牌')
            ->hideOnIndex()
            ->setMaxLength(100)
            ->formatValue(function ($value) {
                if (!is_string($value) || '' === $value) {
                    return '';
                }

                return substr($value, 0, 50) . '...';
            })
            ->setHelp('为安全考虑，仅显示前50个字符')
        ;

        yield TextareaField::new('refreshToken', '刷新令牌')
            ->hideOnIndex()
            ->setMaxLength(100)
            ->formatValue(function ($value) {
                if (!is_string($value) || '' === $value) {
                    return '';
                }

                return substr($value, 0, 50) . '...';
            })
            ->setHelp('为安全考虑，仅显示前50个字符')
        ;

        yield TextareaField::new('idToken', 'ID令牌')
            ->hideOnIndex()
            ->setMaxLength(100)
            ->formatValue(function ($value) {
                if (!is_string($value) || '' === $value) {
                    return '';
                }

                return substr($value, 0, 50) . '...';
            })
            ->setHelp('为安全考虑，仅显示前50个字符')
        ;

        yield IntegerField::new('expiresIn', '令牌有效期(秒)')
            ->hideOnIndex()
            ->formatValue(function ($value) {
                if (!is_numeric($value) || 0 === (int) $value) {
                    return '';
                }

                return number_format((float) $value) . ' 秒';
            })
        ;

        yield DateTimeField::new('tokenExpiresTime', '令牌过期时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->setColumns('col-sm-6 col-lg-5 col-xxl-4')
        ;

        yield BooleanField::new('isTokenExpired', '令牌已过期')
            ->onlyOnDetail()
            ->renderAsSwitch(false)
            ->formatValue(function ($value, AzureOAuth2User $entity) {
                return $entity->isTokenExpired();
            })
        ;

        yield TextField::new('scope', '授权范围')
            ->hideOnIndex()
            ->setColumns('col-sm-6 col-lg-5 col-xxl-4')
        ;

        yield CodeEditorField::new('rawData', '原始数据')
            ->hideOnIndex()
            ->setLanguage('javascript')
            ->setNumOfRows(20)
            ->formatValue(function ($value) {
                if (!$value) {
                    return '';
                }

                return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            })
        ;

        yield DateTimeField::new('createTime', '创建时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->setColumns('col-sm-6 col-lg-5 col-xxl-4')
        ;

        yield DateTimeField::new('updateTime', '更新时间')
            ->hideOnIndex()
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->setColumns('col-sm-6 col-lg-5 col-xxl-4')
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('config', '配置'))
            ->add(TextFilter::new('objectId', '用户对象ID'))
            ->add(TextFilter::new('userPrincipalName', '用户主名称'))
            ->add(TextFilter::new('displayName', '显示名称'))
            ->add(TextFilter::new('mail', '邮箱'))
            ->add(TextFilter::new('jobTitle', '职位'))
            ->add(DateTimeFilter::new('tokenExpiresTime', '令牌过期时间'))
            ->add(BooleanFilter::new('isTokenExpired', '令牌已过期')
                ->setFormTypeOption('mapped', false))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
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
