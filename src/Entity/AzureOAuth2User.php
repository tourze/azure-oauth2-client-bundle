<?php

declare(strict_types=1);

namespace Tourze\AzureOAuth2ClientBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\AzureOAuth2ClientBundle\Repository\AzureOAuth2UserRepository;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;

#[ORM\Entity(repositoryClass: AzureOAuth2UserRepository::class)]
#[ORM\Table(name: 'azure_oauth2_user', options: ['comment' => 'Azure OAuth2用户信息表'])]
#[ORM\UniqueConstraint(name: 'UNIQ_azure_oauth2_user_object_id', columns: ['object_id'])]
class AzureOAuth2User implements \Stringable
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '主键ID'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: AzureOAuth2Config::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private AzureOAuth2Config $config;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => 'Azure用户对象ID'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $objectId;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '用户主名称'])]
    #[IndexColumn]
    #[Assert\Length(max: 255)]
    private ?string $userPrincipalName = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '显示名称'])]
    #[Assert\Length(max: 255)]
    private ?string $displayName = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '给定名称'])]
    #[Assert\Length(max: 255)]
    private ?string $givenName = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '姓氏'])]
    #[Assert\Length(max: 255)]
    private ?string $surname = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '邮箱地址'])]
    #[Assert\Length(max: 255)]
    #[Assert\Email]
    private ?string $mail = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '手机号码'])]
    #[Assert\Length(max: 255)]
    private ?string $mobilePhone = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '办公地点'])]
    #[Assert\Length(max: 255)]
    private ?string $officeLocation = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '首选语言'])]
    #[Assert\Length(max: 255)]
    private ?string $preferredLanguage = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '工作职位'])]
    #[Assert\Length(max: 255)]
    private ?string $jobTitle = null;

    #[ORM\Column(type: Types::TEXT, options: ['comment' => '访问令牌'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 65535)]
    private string $accessToken;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '刷新令牌'])]
    #[Assert\Length(max: 65535)]
    private ?string $refreshToken = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => 'ID令牌'])]
    #[Assert\Length(max: 65535)]
    private ?string $idToken = null;

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '令牌过期时间(秒)'])]
    #[Assert\Type(type: 'int')]
    #[Assert\PositiveOrZero]
    private int $expiresIn;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'token_expires_time', options: ['comment' => '令牌过期时间'])]
    #[Assert\NotNull]
    #[Assert\Type(type: '\DateTimeImmutable')]
    private \DateTimeImmutable $tokenExpiresTime;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '授权范围'])]
    #[Assert\Length(max: 65535)]
    private ?string $scope = null;

    /**
     * @var array<string, mixed>|null
     * @phpstan-ignore-next-line missingType.iterableValue
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '原始数据'])]
    #[Assert\Type(type: 'array')]
    private ?array $rawData = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getConfig(): AzureOAuth2Config
    {
        return $this->config;
    }

    public function setConfig(AzureOAuth2Config $config): void
    {
        $this->config = $config;
    }

    public function getObjectId(): string
    {
        return $this->objectId;
    }

    public function setObjectId(string $objectId): void
    {
        $this->objectId = $objectId;
    }

    public function getUserPrincipalName(): ?string
    {
        return $this->userPrincipalName;
    }

    public function setUserPrincipalName(?string $userPrincipalName): void
    {
        $this->userPrincipalName = $userPrincipalName;
    }

    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    public function setDisplayName(?string $displayName): void
    {
        $this->displayName = $displayName;
    }

    public function getGivenName(): ?string
    {
        return $this->givenName;
    }

    public function setGivenName(?string $givenName): void
    {
        $this->givenName = $givenName;
    }

    public function getSurname(): ?string
    {
        return $this->surname;
    }

    public function setSurname(?string $surname): void
    {
        $this->surname = $surname;
    }

    public function getMail(): ?string
    {
        return $this->mail;
    }

    public function setMail(?string $mail): void
    {
        $this->mail = $mail;
    }

    public function getMobilePhone(): ?string
    {
        return $this->mobilePhone;
    }

    public function setMobilePhone(?string $mobilePhone): void
    {
        $this->mobilePhone = $mobilePhone;
    }

    public function getOfficeLocation(): ?string
    {
        return $this->officeLocation;
    }

    public function setOfficeLocation(?string $officeLocation): void
    {
        $this->officeLocation = $officeLocation;
    }

    public function getPreferredLanguage(): ?string
    {
        return $this->preferredLanguage;
    }

    public function setPreferredLanguage(?string $preferredLanguage): void
    {
        $this->preferredLanguage = $preferredLanguage;
    }

    public function getJobTitle(): ?string
    {
        return $this->jobTitle;
    }

    public function setJobTitle(?string $jobTitle): void
    {
        $this->jobTitle = $jobTitle;
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function setAccessToken(string $accessToken): void
    {
        $this->accessToken = $accessToken;
    }

    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    public function setRefreshToken(?string $refreshToken): void
    {
        $this->refreshToken = $refreshToken;
    }

    public function getIdToken(): ?string
    {
        return $this->idToken;
    }

    public function setIdToken(?string $idToken): void
    {
        $this->idToken = $idToken;
    }

    public function getExpiresIn(): int
    {
        return $this->expiresIn;
    }

    public function setExpiresIn(int $expiresIn): void
    {
        $this->expiresIn = $expiresIn;
        $this->tokenExpiresTime = (new \DateTimeImmutable())->modify("+{$expiresIn} seconds");
    }

    public function getTokenExpiresTime(): \DateTimeImmutable
    {
        return $this->tokenExpiresTime;
    }

    public function getScope(): ?string
    {
        return $this->scope;
    }

    public function setScope(?string $scope): void
    {
        $this->scope = $scope;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getRawData(): ?array
    {
        /** @var array<string, mixed>|null */
        return $this->rawData;
    }

    /**
     * @param array<string, mixed>|null $rawData
     */
    public function setRawData(?array $rawData): void
    {
        $this->rawData = $rawData;
    }

    public function isTokenExpired(): bool
    {
        return $this->tokenExpiresTime < new \DateTimeImmutable();
    }

    public function __toString(): string
    {
        return sprintf('AzureOAuth2User[%s]:%s', $this->id, $this->objectId);
    }
}
