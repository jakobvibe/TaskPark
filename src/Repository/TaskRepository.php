<?php

namespace App\Repository;

use App\Entity\Task;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Task>
 */
class TaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Task::class);
    }

    public function save(Task $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Task $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find tasks for a user within a date range, organized by date
     *
     * @return array<string, Task[]> Keyed by date string (Y-m-d)
     */
    public function findByUserAndDateRange(User $user, \DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        $tasks = $this->createQueryBuilder('t')
            ->andWhere('t.user = :user')
            ->andWhere('t.scheduledDate >= :startDate')
            ->andWhere('t.scheduledDate <= :endDate')
            ->setParameter('user', $user)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('t.scheduledDate', 'ASC')
            ->addOrderBy('t.isCompleted', 'ASC')  // Pending tasks first
            ->addOrderBy('t.position', 'ASC')
            ->getQuery()
            ->getResult();

        // Organize by date
        $result = [];
        foreach ($tasks as $task) {
            $dateKey = $task->getScheduledDate()->format('Y-m-d');
            if (!isset($result[$dateKey])) {
                $result[$dateKey] = [];
            }
            $result[$dateKey][] = $task;
        }

        return $result;
    }

    /**
     * Find tasks for a specific user and date
     *
     * @return Task[]
     */
    public function findByUserAndDate(User $user, \DateTimeInterface $date): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.user = :user')
            ->andWhere('t.scheduledDate = :date')
            ->setParameter('user', $user)
            ->setParameter('date', $date->format('Y-m-d'))
            ->orderBy('t.isCompleted', 'ASC')
            ->addOrderBy('t.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get the next position for a new task on a given date
     */
    public function getNextPosition(User $user, \DateTimeInterface $date): int
    {
        $result = $this->createQueryBuilder('t')
            ->select('MAX(t.position)')
            ->andWhere('t.user = :user')
            ->andWhere('t.scheduledDate = :date')
            ->setParameter('user', $user)
            ->setParameter('date', $date->format('Y-m-d'))
            ->getQuery()
            ->getSingleScalarResult();

        return ($result ?? -1) + 1;
    }

    /**
     * Reorder tasks after a position change
     */
    public function reorderTasks(User $user, \DateTimeInterface $date): void
    {
        $tasks = $this->findByUserAndDate($user, $date);

        $position = 0;
        foreach ($tasks as $task) {
            if ($task->getPosition() !== $position) {
                $task->setPosition($position);
            }
            $position++;
        }

        $this->getEntityManager()->flush();
    }

    /**
     * Find tasks completed more than X days ago (for archiving)
     *
     * @return Task[]
     */
    public function findOldCompletedTasks(int $daysOld = 30): array
    {
        $cutoffDate = new \DateTime("-{$daysOld} days");

        return $this->createQueryBuilder('t')
            ->andWhere('t.isCompleted = :completed')
            ->andWhere('t.completedAt < :cutoffDate')
            ->setParameter('completed', true)
            ->setParameter('cutoffDate', $cutoffDate)
            ->getQuery()
            ->getResult();
    }
}
