<?php

declare(strict_types=1);

namespace Tourze\AzureOAuth2ClientBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\AzureOAuth2ClientBundle\Entity\AzureOAuth2Config;
use Tourze\AzureOAuth2ClientBundle\Entity\AzureOAuth2User;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<AzureOAuth2User>
 */
#[AsRepository(entityClass: AzureOAuth2User::class)]
class AzureOAuth2UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AzureOAuth2User::class);
    }

    public function findByObjectId(string $objectId): ?AzureOAuth2User
    {
        return $this->findOneBy(['objectId' => $objectId]);
    }

    public function findByUserPrincipalName(string $userPrincipalName): ?AzureOAuth2User
    {
        return $this->findOneBy(['userPrincipalName' => $userPrincipalName]);
    }

    public function findByMail(string $mail): ?AzureOAuth2User
    {
        return $this->findOneBy(['mail' => $mail]);
    }

    /**
     * @return AzureOAuth2User[]
     */
    public function findExpiredTokenUsers(): array
    {
        /** @var AzureOAuth2User[] */
        return $this->createQueryBuilder('u')
            ->where('u.tokenExpiresTime < :now')
            ->andWhere('u.refreshToken IS NOT NULL')
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @param array<string, mixed> $userData
     */
    public function updateOrCreate(array $userData, AzureOAuth2Config $config): AzureOAuth2User
    {
        $user = $this->findOrCreateUser($userData, $config);
        $this->updateUserProfileFields($user, $userData);
        $this->updateOAuth2TokenFields($user, $userData);
        $this->finalizeUserUpdate($user, $userData);

        return $user;
    }

    /**
     * @param array<string, mixed> $userData
     */
    private function findOrCreateUser(array $userData, AzureOAuth2Config $config): AzureOAuth2User
    {
        $objectId = $this->extractObjectId($userData);

        $user = $this->findByObjectId($objectId);
        if (null === $user) {
            $user = new AzureOAuth2User();
            $user->setObjectId($objectId);
            $user->setConfig($config);
        }

        return $user;
    }

    /**
     * @param array<string, mixed> $userData
     */
    private function extractObjectId(array $userData): string
    {
        $objectId = $userData['id'] ?? $userData['oid'] ?? $userData['objectId'] ?? null;
        if (!is_string($objectId) || '' === $objectId) {
            throw new \InvalidArgumentException('Missing required field: objectId');
        }

        return $objectId;
    }

    /**
     * @param array<string, mixed> $userData
     */
    private function updateUserProfileFields(AzureOAuth2User $user, array $userData): void
    {
        $this->setUserPrincipalNameField($user, $userData);
        $this->setDisplayNameField($user, $userData);
        $this->setGivenNameField($user, $userData);
        $this->setSurnameField($user, $userData);
        $this->setMailField($user, $userData);
        $this->setMobilePhoneField($user, $userData);
        $this->setOfficeLocationField($user, $userData);
        $this->setPreferredLanguageField($user, $userData);
        $this->setJobTitleField($user, $userData);
    }

    /**
     * @param array<string, mixed> $userData
     */
    private function setUserPrincipalNameField(AzureOAuth2User $user, array $userData): void
    {
        $this->setNullableStringField($user, $userData, 'userPrincipalName', fn ($value) => $user->setUserPrincipalName($value));
    }

    /**
     * @param array<string, mixed> $userData
     */
    private function setDisplayNameField(AzureOAuth2User $user, array $userData): void
    {
        $this->setNullableStringField($user, $userData, 'displayName', fn ($value) => $user->setDisplayName($value));
    }

    /**
     * @param array<string, mixed> $userData
     */
    private function setGivenNameField(AzureOAuth2User $user, array $userData): void
    {
        $this->setNullableStringField($user, $userData, 'givenName', fn ($value) => $user->setGivenName($value));
    }

    /**
     * @param array<string, mixed> $userData
     */
    private function setSurnameField(AzureOAuth2User $user, array $userData): void
    {
        $this->setNullableStringField($user, $userData, 'surname', fn ($value) => $user->setSurname($value));
    }

    /**
     * @param array<string, mixed> $userData
     */
    private function setMailField(AzureOAuth2User $user, array $userData): void
    {
        $this->setNullableStringField($user, $userData, 'mail', fn ($value) => $user->setMail($value));
    }

    /**
     * @param array<string, mixed> $userData
     */
    private function setMobilePhoneField(AzureOAuth2User $user, array $userData): void
    {
        $this->setNullableStringField($user, $userData, 'mobilePhone', fn ($value) => $user->setMobilePhone($value));
    }

    /**
     * @param array<string, mixed> $userData
     */
    private function setOfficeLocationField(AzureOAuth2User $user, array $userData): void
    {
        $this->setNullableStringField($user, $userData, 'officeLocation', fn ($value) => $user->setOfficeLocation($value));
    }

    /**
     * @param array<string, mixed> $userData
     */
    private function setPreferredLanguageField(AzureOAuth2User $user, array $userData): void
    {
        $this->setNullableStringField($user, $userData, 'preferredLanguage', fn ($value) => $user->setPreferredLanguage($value));
    }

    /**
     * @param array<string, mixed> $userData
     */
    private function setJobTitleField(AzureOAuth2User $user, array $userData): void
    {
        $this->setNullableStringField($user, $userData, 'jobTitle', fn ($value) => $user->setJobTitle($value));
    }

    /**
     * @param array<string, mixed> $userData
     * @param callable(string|null): void $setter
     */
    private function setNullableStringField(AzureOAuth2User $user, array $userData, string $dataKey, callable $setter): void
    {
        if (!array_key_exists($dataKey, $userData)) {
            return;
        }

        $value = $userData[$dataKey];
        if (is_string($value) || is_null($value)) {
            $setter($value);
        }
    }

    /**
     * @param array<string, mixed> $userData
     */
    private function updateOAuth2TokenFields(AzureOAuth2User $user, array $userData): void
    {
        $this->setAccessTokenField($user, $userData);
        $this->setRefreshTokenField($user, $userData);
        $this->setIdTokenField($user, $userData);
        $this->setExpiresInField($user, $userData);
        $this->setScopeField($user, $userData);
    }

    /**
     * @param array<string, mixed> $userData
     */
    private function setAccessTokenField(AzureOAuth2User $user, array $userData): void
    {
        if (isset($userData['access_token']) && is_string($userData['access_token'])) {
            $user->setAccessToken($userData['access_token']);
        }
    }

    /**
     * @param array<string, mixed> $userData
     */
    private function setRefreshTokenField(AzureOAuth2User $user, array $userData): void
    {
        if (!array_key_exists('refresh_token', $userData)) {
            return;
        }

        $value = $userData['refresh_token'];
        if (is_string($value) || is_null($value)) {
            $user->setRefreshToken($value);
        }
    }

    /**
     * @param array<string, mixed> $userData
     */
    private function setIdTokenField(AzureOAuth2User $user, array $userData): void
    {
        if (!array_key_exists('id_token', $userData)) {
            return;
        }

        $value = $userData['id_token'];
        if (is_string($value) || is_null($value)) {
            $user->setIdToken($value);
        }
    }

    /**
     * @param array<string, mixed> $userData
     */
    private function setScopeField(AzureOAuth2User $user, array $userData): void
    {
        if (!array_key_exists('scope', $userData)) {
            return;
        }

        $value = $userData['scope'];
        if (is_string($value) || is_null($value)) {
            $user->setScope($value);
        }
    }

    /**
     * @param array<string, mixed> $userData
     */
    private function setExpiresInField(AzureOAuth2User $user, array $userData): void
    {
        if (isset($userData['expires_in']) && is_numeric($userData['expires_in'])) {
            $user->setExpiresIn((int) $userData['expires_in']);
        }
    }

    /**
     * @param array<string, mixed> $userData
     */
    private function finalizeUserUpdate(AzureOAuth2User $user, array $userData): void
    {
        $user->setRawData($userData);
        $user->setUpdateTime(new \DateTimeImmutable());
    }

    public function save(AzureOAuth2User $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(AzureOAuth2User $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
