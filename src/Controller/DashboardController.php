<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_dashboard')]
    public function index(): Response
    {
        // For now, redirect to the current week
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

        return $this->render('dashboard/index.html.twig', [
            'weekDates' => $weekDates,
            'currentDate' => $currentDate,
            'today' => new \DateTime(),
        ]);
    }
}
