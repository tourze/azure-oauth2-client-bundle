<?php

declare(strict_types=1);

namespace Tourze\AzureOAuth2ClientBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\AzureOAuth2ClientBundle\Repository\AzureOAuth2ConfigRepository;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;

#[ORM\Entity(repositoryClass: AzureOAuth2ConfigRepository::class)]
#[ORM\Table(name: 'azure_oauth2_config', options: ['comment' => 'Azure OAuth2应用配置表'])]
#[ORM\UniqueConstraint(name: 'UNIQ_azure_oauth2_config_client_id', columns: ['client_id'])]
class AzureOAuth2Config implements \Stringable
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '主键ID'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => 'Azure应用ID'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $clientId;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => 'Azure应用密钥'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $clientSecret;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => 'Azure租户ID'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $tenantId;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '配置名称'])]
    #[Assert\Length(max: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '授权范围'])]
    #[Assert\Length(max: 65535)]
    private ?string $scope = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '重定向URI'])]
    #[Assert\Length(max: 65535)]
    #[Assert\Url]
    private ?string $redirectUri = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '备注信息'])]
    #[Assert\Length(max: 65535)]
    private ?string $remark = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否有效'])]
    #[Assert\Type(type: 'bool')]
    private bool $isValid = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function setClientId(string $clientId): void
    {
        $this->clientId = $clientId;
    }

    public function getClientSecret(): string
    {
        return $this->clientSecret;
    }

    public function setClientSecret(string $clientSecret): void
    {
        $this->clientSecret = $clientSecret;
    }

    public function getTenantId(): string
    {
        return $this->tenantId;
    }

    public function setTenantId(string $tenantId): void
    {
        $this->tenantId = $tenantId;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getScope(): ?string
    {
        return $this->scope;
    }

    public function setScope(?string $scope): void
    {
        $this->scope = $scope;
    }

    public function getRedirectUri(): ?string
    {
        return $this->redirectUri;
    }

    public function setRedirectUri(?string $redirectUri): void
    {
        $this->redirectUri = $redirectUri;
    }

    public function getRemark(): ?string
    {
        return $this->remark;
    }

    public function setRemark(?string $remark): void
    {
        $this->remark = $remark;
    }

    public function isValid(): bool
    {
        return $this->isValid;
    }

    public function setValid(bool $valid): void
    {
        $this->isValid = $valid;
    }

    public function __toString(): string
    {
        return sprintf('AzureOAuth2Config[%s]:%s', $this->id, $this->clientId);
    }
}
