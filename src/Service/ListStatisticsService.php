<?php

namespace App\Service;

use App\Repository\TaskRepository;

class ListStatisticsService
{
    private $taskRepository;

    public function __construct(TaskRepository $taskRepository)
    {
        $this->taskRepository = $taskRepository;
    }

    public function getTaskCountByOwner(string $owner): int
    {
        return $this->taskRepository->count(['user' => $owner]);
    }

    // Ajoute d'autres méthodes de calcul de statistiques ici en fonction de tes besoins

    // Par exemple, une méthode pour calculer la moyenne des tâches terminées par owner
    public function getAverageCompletedTasksByOwner(string $owner): float
    {
        $completedTasksCount = $this->taskRepository->count(['user' => $owner, 'isDone' => true]);
        $totalTasksCount = $this->taskRepository->count(['user' => $owner]);

        if ($totalTasksCount > 0) {
            return $completedTasksCount / $totalTasksCount;
        }

        return 0;
    }
}
