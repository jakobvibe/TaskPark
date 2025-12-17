<?php

namespace App\Controller;

use App\Service\MessageService;
use App\Service\TaskService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    public function __construct(
        private TaskService $taskService,
        private MessageService $messageService,
    ) {
    }

    #[Route('/', name: 'app_dashboard')]
    public function index(): Response
    {
        // Redirect to the current week
        $today = new \DateTime();
        return $this->redirectToRoute('app_week', [
            'date' => $today->format('Y-m-d'),
        ]);
    }

    #[Route('/week/{date}', name: 'app_week', defaults: ['date' => null])]
    public function week(?string $date): Response
    {
        $currentDate = $date ? new \DateTime($date) : new \DateTime();

        // Get the Monday of the current week
        $monday = clone $currentDate;
        $monday->modify('monday this week');

        // Generate the week dates
        $weekDates = [];
        for ($i = 0; $i < 7; $i++) {
            $day = clone $monday;
            $day->modify("+{$i} days");
            $weekDates[] = $day;
        }

        // Get tasks for the week
        $user = $this->getUser();
        $tasksByDate = $this->taskService->getTasksForWeek($user, $monday);

        // Count total pending tasks for contextual messaging
        $totalTasks = 0;
        foreach ($tasksByDate as $tasks) {
            foreach ($tasks as $task) {
                if (!$task->isCompleted()) {
                    $totalTasks++;
                }
            }
        }

        // Get supportive message
        $supportiveMessage = $this->messageService->getContextualMessage($totalTasks);

        return $this->render('dashboard/index.html.twig', [
            'weekDates' => $weekDates,
            'tasksByDate' => $tasksByDate,
            'currentDate' => $currentDate,
            'today' => new \DateTime(),
            'supportiveMessage' => $supportiveMessage,
        ]);
    }
}
