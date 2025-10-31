<?php

declare(strict_types=1);

namespace Tourze\AzureOAuth2ClientBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\AzureOAuth2ClientBundle\Entity\AzureOAuth2Config;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<AzureOAuth2Config>
 */
#[AsRepository(entityClass: AzureOAuth2Config::class)]
class AzureOAuth2ConfigRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AzureOAuth2Config::class);
    }

    public function findValidConfig(): ?AzureOAuth2Config
    {
        /** @var AzureOAuth2Config|null */
        return $this->createQueryBuilder('c')
            ->where('c.isValid = :valid')
            ->setParameter('valid', true)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findByClientId(string $clientId): ?AzureOAuth2Config
    {
        return $this->findOneBy(['clientId' => $clientId]);
    }

    public function findByTenantId(string $tenantId): ?AzureOAuth2Config
    {
        return $this->findOneBy(['tenantId' => $tenantId, 'isValid' => true]);
    }

    /**
     * @return AzureOAuth2Config[]
     */
    public function findAllValid(): array
    {
        return $this->findBy(['isValid' => true], ['id' => 'ASC']);
    }

    public function save(AzureOAuth2Config $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(AzureOAuth2Config $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
