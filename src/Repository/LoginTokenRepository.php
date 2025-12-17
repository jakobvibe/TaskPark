<?php

namespace App\Repository;

use App\Entity\LoginToken;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LoginToken>
 */
class LoginTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LoginToken::class);
    }

    public function save(LoginToken $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findValidByTokenHash(string $tokenHash): ?LoginToken
    {
        $token = $this->findOneBy(['tokenHash' => $tokenHash]);

        if ($token && $token->isValid()) {
            return $token;
        }

        return null;
    }

    /**
     * Clean up expired tokens older than 24 hours
     */
    public function removeExpiredTokens(): int
    {
        $qb = $this->createQueryBuilder('t')
            ->delete()
            ->where('t.expiresAt < :cutoff')
            ->setParameter('cutoff', new \DateTime('-24 hours'));

        return $qb->getQuery()->execute();
    }

    /**
     * Invalidate all unused tokens for a user
     */
    public function invalidateUserTokens(User $user): void
    {
        $qb = $this->createQueryBuilder('t')
            ->update()
            ->set('t.usedAt', ':now')
            ->where('t.user = :user')
            ->andWhere('t.usedAt IS NULL')
            ->setParameter('user', $user)
            ->setParameter('now', new \DateTime());

        $qb->getQuery()->execute();
    }
}
