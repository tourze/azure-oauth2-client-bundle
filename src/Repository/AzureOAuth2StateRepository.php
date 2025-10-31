<?php

declare(strict_types=1);

namespace Tourze\AzureOAuth2ClientBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\AzureOAuth2ClientBundle\Entity\AzureOAuth2State;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<AzureOAuth2State>
 */
#[AsRepository(entityClass: AzureOAuth2State::class)]
class AzureOAuth2StateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AzureOAuth2State::class);
    }

    public function findValidState(string $state): ?AzureOAuth2State
    {
        /** @var AzureOAuth2State|null */
        return $this->createQueryBuilder('s')
            ->where('s.state = :state')
            ->andWhere('s.isUsed = :used')
            ->andWhere('s.expiresTime > :now')
            ->setParameter('state', $state)
            ->setParameter('used', false)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function cleanupExpiredStates(): int
    {
        /** @var int */
        return $this->createQueryBuilder('s')
            ->delete()
            ->where('s.expiresTime < :now OR s.isUsed = :used')
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('used', true)
            ->getQuery()
            ->execute()
        ;
    }

    public function save(AzureOAuth2State $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(AzureOAuth2State $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
