<?php

namespace App\Controller;

use App\Entity\Task;
use App\Entity\User;
use App\Form\TaskType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\UserRepository;
use App\Repository\TaskRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Service\ListStatisticsService;


class TaskController extends DefaultController

{
    /**
     * @Route("/tasks", name="task_list")
     * @IsGranted("ROLE_USER")
     */
    public function listAction()
    {
        if(in_array('ROLE_VIEWER', $this->getUser()->getRoles())){
            $tasks =$this->getUser()->getTaskPartagerAvec();
        }else{
            $tasks =$this->getUser()->getTasks();
        }
        return $this->render('task/list.html.twig', ['tasks' => $tasks]);
    }

    /**
     * @Route("/task/statistics", name="task_statistics")
     * @IsGranted("ROLE_USER")
     */
    public function statisticsAction(TaskRepository $taskRepository)
    {
        $owner = $this->getUser();
        $taskCount = $taskRepository->countTasksByOwner($owner);
        $averageCompletedTasks = $taskRepository->getAverageCompletedTasksByOwner($owner);

        return $this->render('task/statistics.html.twig', [
            'taskCount' => $taskCount,
            'averageCompletedTasks' => $averageCompletedTasks,
        ]);
    }

    /**
     * @Route("/tasks/create", name="task_create")
     * @param Request $request
     * @return RedirectResponse|Response
     * @IsGranted("ROLE_USER")
     */
    public function createAction(Request $request,EntityManagerInterface $entityManager)
    {
        
        $task = new Task();
        $form = $this->createForm(TaskType::class, $task);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $task->setUser($this->getUser());
            $task->setIsDone(0);
            $task->setCreatedAt(new \DateTimeImmutable());
            $entityManager->persist($task);
            $entityManager->flush();

            $this->addFlash('success', 'La tâche a été bien été ajoutée.');

            return $this->redirectToRoute('task_list');
        }

        return $this->render('task/create.html.twig', ['form' => $form->createView()]);
    }


    /**
     * @Route("/tasks/{id}/edit", name="task_edit")
     * @param Task $task
     * @param Request $request
     * @return RedirectResponse|Response
     * @IsGranted("ROLE_USER")
     */
    public function editAction(Task $task, Request $request,EntityManagerInterface $entityManager)
    {
        $form = $this->createForm(TaskType::class, $task);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $entityManager->persist($task);
            $entityManager->flush();

            $this->addFlash('success', 'La tâche a bien été modifiée.');

            return $this->redirectToRoute('task_list');
        }

        return $this->render('task/edit.html.twig', [
            'form' => $form->createView(),
            'task' => $task,
        ]);
    }

    /**
     * @Route("/tasks/{id}/toggle", name="task_toggle")
     * @IsGranted("ROLE_USER")
     */
    public function toggleTaskAction(Task $task,EntityManagerInterface $entityManager)
    {
        $task->toggle(!$task->IsisDone());
        #$this->getDoctrine()->getManager()->flush();
        $entityManager->persist($task);
        $entityManager->flush();

        $this->addFlash('success', sprintf('La tâche %s a bien été marquée comme faite.', $task->getTitle()));

        return $this->redirectToRoute('task_list');
    }

    /**
     * @Route("/tasks/{id}/delete", name="task_delete")
     * @IsGranted("ROLE_USER")
     */
    public function deleteTaskAction(Task $task,EntityManagerInterface $entityManager)
    {
        if ($task->getUser()->getUsername() === 'anonyme' && $this->getUser()){
            $roles = $this->getUser()->getRoles();
            if (in_array("ROLE_ADMIN",  $roles)){
                $entityManager->persist($task);
                
                $entityManager->remove($task);
              
                $entityManager->flush();
                $this->addFlash('success', 'La tâche a bien été supprimée.');

                return $this->redirectToRoute('task_list');
            }

            $this->addFlash('error', 'Vous ne pouvez pas supprimer cette tâche.');

            return $this->redirectToRoute('task_list');
        }

        if ($task->getUser() === $this->getUser()){

            $em=$entityManager->persist($task);
                
            $entityManager->remove($task);

            $entityManager->flush();
            $this->addFlash('success', 'La tâche a bien été supprimée.');

            return $this->redirectToRoute('task_list');
        }

        $this->addFlash('error', 'Vous n\'êtes pas à l\'origine de cettte tâche, vous ne pouvez pas la supprimer.');

        return $this->redirectToRoute('task_list');
    }


    /**
     * @Route("/tasks/{id}/partager", name="task_partager_single")
     * @param Request $request
     * @return RedirectResponse|Response
     * @IsGranted("ROLE_USER")
     */
    public function partagerTaskAction(Task $task)
    {
        
        return $this->redirectToRoute('task_choose_viewer', ['id' => $task->getId()]);
    }

        /**
     * @Route("/tasks/{id}/choose-viewer", name="task_choose_viewer")
     * @param Task $task
     * @return Response
     * @IsGranted("ROLE_USER")
     */
    public function chooseViewerAction(Task $task,UserRepository $UserRepository)
    {
        $users = $UserRepository->getUsersByRoleViewer();

        // Code pour afficher la page de choix du destinataire
        return $this->render('task/choose_viewer.html.twig', ['task' => $task, 'users' => $users]);
    }
    
    

        /**
     * @Route("/tasks/{id}/share", name="task_share", methods={"POST"})
     * @param Task $task
     * @param Request $request
     * @return RedirectResponse
     * @IsGranted("ROLE_USER")
     */


    public function shareTaskAction(Task $task, Request $request, EntityManagerInterface $entityManager)
    {
        $viewerId = $request->request->get('viewer');
        $userRepository = $entityManager->getRepository(User::class);
        $user = $userRepository->find($viewerId);
        
        if ($user) {
            $task->addPartagerAvec($user);
            $entityManager->flush();
        
            $this->addFlash('success', 'La tâche a été partagée avec succès.');
        } else {
            $this->addFlash('error', 'Impossible de trouver l\'utilisateur sélectionné.');
        }

        return $this->redirectToRoute('task_list');
    }

    
    /**
     * @Route("/tasks/json", name="task_list_json", methods={"GET"})
     * @IsGranted("ROLE_USER")
     */
    public function indexJson(TaskRepository $taskRepository): JsonResponse
    {
        $tasks = $taskRepository->findAll();
        $responseData = [];
    
        foreach ($tasks as $task) {
            $responseData[] = [
                'id' => $task->getId(),
                'owner' => $task->getUser()->getUsername(),
                'title' => $task->getTitle(),
                'completed' => $task->isIsDone(),
                'content' => $task->getContent(),
            ];
        }
    
        $jsonResponse = new JsonResponse($responseData);
        $jsonResponse->setEncodingOptions(JSON_PRETTY_PRINT);
    
        return $jsonResponse;
    }
    

}
