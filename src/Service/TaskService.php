<?php

namespace App\Service;

use App\Entity\Task;
use App\Entity\User;
use App\Repository\TaskRepository;

class TaskService
{
    public function __construct(
        private TaskRepository $taskRepository,
    ) {
    }

    /**
     * Create a new task for a user
     */
    public function createTask(User $user, string $title, \DateTimeInterface $scheduledDate): Task
    {
        $task = new Task();
        $task->setUser($user);
        $task->setTitle(trim($title));
        $task->setScheduledDate($scheduledDate);
        $task->setPosition($this->taskRepository->getNextPosition($user, $scheduledDate));

        $this->taskRepository->save($task, true);

        return $task;
    }

    /**
     * Update a task's title
     */
    public function updateTitle(Task $task, string $title): Task
    {
        $task->setTitle(trim($title));
        $this->taskRepository->save($task, true);

        return $task;
    }

    /**
     * Toggle task completion status
     */
    public function toggleComplete(Task $task): Task
    {
        $task->setIsCompleted(!$task->isCompleted());
        $this->taskRepository->save($task, true);

        return $task;
    }

    /**
     * Move a task to a different date
     */
    public function moveToDate(Task $task, \DateTimeInterface $newDate): Task
    {
        $oldDate = $task->getScheduledDate();
        $user = $task->getUser();

        // Set new date and get new position
        $task->setScheduledDate($newDate);
        $task->setPosition($this->taskRepository->getNextPosition($user, $newDate));

        $this->taskRepository->save($task, true);

        // Reorder tasks on the old date
        $this->taskRepository->reorderTasks($user, $oldDate);

        return $task;
    }

    /**
     * Move a task to a new position within the same date
     */
    public function reorderTask(Task $task, int $newPosition): Task
    {
        $user = $task->getUser();
        $date = $task->getScheduledDate();
        $oldPosition = $task->getPosition();

        if ($newPosition === $oldPosition) {
            return $task;
        }

        // Get all tasks for this date
        $tasks = $this->taskRepository->findByUserAndDate($user, $date);

        // Remove the task from its current position
        $tasksArray = array_values(array_filter($tasks, fn($t) => $t->getId() !== $task->getId()));

        // Insert at new position
        array_splice($tasksArray, $newPosition, 0, [$task]);

        // Update positions
        foreach ($tasksArray as $index => $t) {
            $t->setPosition($index);
        }

        $this->taskRepository->save($task, true);

        return $task;
    }

    /**
     * Delete a task
     */
    public function deleteTask(Task $task): void
    {
        $user = $task->getUser();
        $date = $task->getScheduledDate();

        $this->taskRepository->remove($task, true);

        // Reorder remaining tasks
        $this->taskRepository->reorderTasks($user, $date);
    }

    /**
     * Get tasks for a week view
     *
     * @return array<string, Task[]>
     */
    public function getTasksForWeek(User $user, \DateTimeInterface $weekStart): array
    {
        $weekEnd = clone $weekStart;
        $weekEnd->modify('+6 days');

        return $this->taskRepository->findByUserAndDateRange($user, $weekStart, $weekEnd);
    }

    /**
     * Get tasks for a specific day
     *
     * @return Task[]
     */
    public function getTasksForDay(User $user, \DateTimeInterface $date): array
    {
        return $this->taskRepository->findByUserAndDate($user, $date);
    }
}
