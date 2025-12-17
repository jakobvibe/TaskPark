<?php

namespace App\Repository;

use App\Entity\SupportiveMessage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SupportiveMessage>
 */
class SupportiveMessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SupportiveMessage::class);
    }

    public function save(SupportiveMessage $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Get a random active message by category
     */
    public function findRandomByCategory(string $category): ?SupportiveMessage
    {
        $messages = $this->createQueryBuilder('m')
            ->andWhere('m.category = :category')
            ->andWhere('m.isActive = :active')
            ->setParameter('category', $category)
            ->setParameter('active', true)
            ->getQuery()
            ->getResult();

        if (empty($messages)) {
            return null;
        }

        return $messages[array_rand($messages)];
    }

    /**
     * Get all active messages by category
     *
     * @return SupportiveMessage[]
     */
    public function findActiveByCategory(string $category): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.category = :category')
            ->andWhere('m.isActive = :active')
            ->setParameter('category', $category)
            ->setParameter('active', true)
            ->getQuery()
            ->getResult();
    }
}
