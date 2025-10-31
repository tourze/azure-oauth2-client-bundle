<?php

declare(strict_types=1);

namespace Tourze\AzureOAuth2ClientBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\AzureOAuth2ClientBundle\Repository\AzureOAuth2StateRepository;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;

#[ORM\Entity(repositoryClass: AzureOAuth2StateRepository::class)]
#[ORM\Table(name: 'azure_oauth2_state', options: ['comment' => 'Azure OAuth2状态表'])]
#[ORM\UniqueConstraint(name: 'UNIQ_azure_oauth2_state_state', columns: ['state'])]
class AzureOAuth2State implements \Stringable
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '主键ID'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: AzureOAuth2Config::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private AzureOAuth2Config $config;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '状态值'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $state;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '会话ID'])]
    #[Assert\Length(max: 255)]
    private ?string $sessionId = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => 'PKCE代码挑战'])]
    #[Assert\Length(max: 255)]
    private ?string $codeChallenge = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true, options: ['comment' => 'PKCE方法'])]
    #[Assert\Length(max: 50)]
    private ?string $codeChallengeMethod = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否已使用'])]
    #[Assert\Type(type: 'bool')]
    private bool $isUsed = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'expires_time', options: ['comment' => '过期时间'])]
    #[Assert\NotNull]
    #[Assert\Type(type: '\DateTimeImmutable')]
    private \DateTimeImmutable $expiresTime;

    public function __construct()
    {
        $this->expiresTime = (new \DateTimeImmutable())->modify('+10 minutes');
    }

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

    public function getState(): string
    {
        return $this->state;
    }

    public function setState(string $state): void
    {
        $this->state = $state;
    }

    public function getSessionId(): ?string
    {
        return $this->sessionId;
    }

    public function setSessionId(?string $sessionId): void
    {
        $this->sessionId = $sessionId;
    }

    public function getCodeChallenge(): ?string
    {
        return $this->codeChallenge;
    }

    public function setCodeChallenge(?string $codeChallenge): void
    {
        $this->codeChallenge = $codeChallenge;
    }

    public function getCodeChallengeMethod(): ?string
    {
        return $this->codeChallengeMethod;
    }

    public function setCodeChallengeMethod(?string $codeChallengeMethod): void
    {
        $this->codeChallengeMethod = $codeChallengeMethod;
    }

    public function isUsed(): bool
    {
        return $this->isUsed;
    }

    public function setUsed(bool $isUsed): void
    {
        $this->isUsed = $isUsed;
    }

    public function getExpiresTime(): \DateTimeImmutable
    {
        return $this->expiresTime;
    }

    public function setExpiresTime(\DateTimeImmutable $expiresTime): void
    {
        $this->expiresTime = $expiresTime;
    }

    public function isValid(): bool
    {
        return !$this->isUsed && $this->expiresTime > new \DateTimeImmutable();
    }

    public function __toString(): string
    {
        return sprintf('AzureOAuth2State[%s]:%s', $this->id, $this->state);
    }
}
