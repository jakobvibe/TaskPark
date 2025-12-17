<?php

namespace App\Controller;

use App\Entity\Task;
use App\Service\TaskService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/task')]
class TaskController extends AbstractController
{
    public function __construct(
        private TaskService $taskService,
    ) {
    }

    #[Route('', name: 'app_task_create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        $title = trim($request->request->get('title', ''));
        $dateString = $request->request->get('date');

        if (empty($title) || empty($dateString)) {
            if ($request->headers->has('Turbo-Frame')) {
                return $this->render('task/_error.html.twig', [
                    'error' => 'Title and date are required.',
                ]);
            }
            $this->addFlash('error', 'Title and date are required.');
            return $this->redirectToRoute('app_dashboard');
        }

        $date = new \DateTime($dateString);
        $task = $this->taskService->createTask($this->getUser(), $title, $date);

        // Return the day column for Turbo Frame update
        if ($request->headers->has('Turbo-Frame')) {
            $tasks = $this->taskService->getTasksForDay($this->getUser(), $date);
            return $this->render('task/_day_tasks.html.twig', [
                'date' => $date,
                'tasks' => $tasks,
                'today' => new \DateTime(),
            ]);
        }

        return $this->redirectToRoute('app_week', ['date' => $date->format('Y-m-d')]);
    }

    #[Route('/{id}', name: 'app_task_update', methods: ['PATCH', 'POST'])]
    #[IsGranted('edit', subject: 'task')]
    public function update(Task $task, Request $request): Response
    {
        $title = trim($request->request->get('title', ''));

        if (empty($title)) {
            if ($request->headers->has('Turbo-Frame')) {
                return $this->render('task/_task.html.twig', [
                    'task' => $task,
                    'error' => 'Title cannot be empty.',
                ]);
            }
            $this->addFlash('error', 'Title cannot be empty.');
            return $this->redirectToRoute('app_dashboard');
        }

        $this->taskService->updateTitle($task, $title);

        if ($request->headers->has('Turbo-Frame')) {
            return $this->render('task/_task.html.twig', [
                'task' => $task,
            ]);
        }

        return $this->redirectToRoute('app_week', ['date' => $task->getScheduledDate()->format('Y-m-d')]);
    }

    #[Route('/{id}/complete', name: 'app_task_complete', methods: ['PATCH', 'POST'])]
    #[IsGranted('edit', subject: 'task')]
    public function complete(Task $task, Request $request): Response
    {
        $this->taskService->toggleComplete($task);

        if ($request->headers->has('Turbo-Frame')) {
            return $this->render('task/_task.html.twig', [
                'task' => $task,
            ]);
        }

        return $this->redirectToRoute('app_week', ['date' => $task->getScheduledDate()->format('Y-m-d')]);
    }

    #[Route('/{id}/move', name: 'app_task_move', methods: ['PATCH', 'POST'])]
    #[IsGranted('edit', subject: 'task')]
    public function move(Task $task, Request $request): Response
    {
        $newDateString = $request->request->get('date');
        $newPosition = $request->request->get('position');

        if ($newDateString) {
            $newDate = new \DateTime($newDateString);
            $oldDate = $task->getScheduledDate();
            $this->taskService->moveToDate($task, $newDate);

            if ($request->headers->has('Turbo-Frame')) {
                // Return both day columns for update
                return $this->render('task/_move_response.html.twig', [
                    'oldDate' => $oldDate,
                    'newDate' => $newDate,
                    'oldTasks' => $this->taskService->getTasksForDay($this->getUser(), $oldDate),
                    'newTasks' => $this->taskService->getTasksForDay($this->getUser(), $newDate),
                    'today' => new \DateTime(),
                ]);
            }
        } elseif ($newPosition !== null) {
            $this->taskService->reorderTask($task, (int) $newPosition);
        }

        return $this->redirectToRoute('app_week', ['date' => $task->getScheduledDate()->format('Y-m-d')]);
    }

    #[Route('/{id}', name: 'app_task_delete', methods: ['DELETE', 'POST'])]
    #[IsGranted('edit', subject: 'task')]
    public function delete(Task $task, Request $request): Response
    {
        // Check for delete confirmation via _method or dedicated parameter
        if ($request->request->get('_method') !== 'DELETE' && !$request->request->has('confirm_delete')) {
            if (!$request->isMethod('DELETE')) {
                $this->addFlash('error', 'Invalid request.');
                return $this->redirectToRoute('app_dashboard');
            }
        }

        $date = $task->getScheduledDate();
        $this->taskService->deleteTask($task);

        if ($request->headers->has('Turbo-Frame')) {
            $tasks = $this->taskService->getTasksForDay($this->getUser(), $date);
            return $this->render('task/_day_tasks.html.twig', [
                'date' => $date,
                'tasks' => $tasks,
                'today' => new \DateTime(),
            ]);
        }

        return $this->redirectToRoute('app_week', ['date' => $date->format('Y-m-d')]);
    }
}
