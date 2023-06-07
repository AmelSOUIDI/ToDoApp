<?php

namespace App\Controller;

use App\Entity\Task;
use App\Form\TaskType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\TaskRepository;

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
    public function editAction(Task $task, Request $request)
    {
        $form = $this->createForm(TaskType::class, $task);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

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
    public function toggleTaskAction(Task $task)
    {
        $task->toggle(!$task->isDone());
        $this->getDoctrine()->getManager()->flush();
        $this->addFlash('success', sprintf('La tâche %s a bien été marquée comme faite.', $task->getTitle()));

        return $this->redirectToRoute('task_list');
    }

    /**
     * @Route("/tasks/{id}/delete", name="task_delete")
     * @IsGranted("ROLE_USER")
     */
    public function deleteTaskAction(Task $task)
    {
        if ($task->getUser()->getUsername() === 'anonyme' && $this->getUser()){
            $roles = $this->getUser()->getRoles();
            if (in_array("ROLE_ADMIN",  $roles)){
                $em = $this->getDoctrine()->getManager();
                $em->remove($task);
                $em->flush();
                $this->addFlash('success', 'La tâche a bien été supprimée.');

                return $this->redirectToRoute('task_list');
            }

            $this->addFlash('error', 'Vous ne pouvez pas supprimer cette tâche.');

            return $this->redirectToRoute('task_list');
        }

        if ($task->getUser() === $this->getUser()){
            $em = $this->getDoctrine()->getManager();
            $em->remove($task);
            $em->flush();
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
    public function chooseViewerAction(Task $task)
    {
        $users = $this->getDoctrine()->getRepository('App:User')->getUsersByRoleViewer();
    
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
    public function shareTaskAction(Task $task, Request $request)
    {
        $viewerId = $request->request->get('viewer');
        $user = $this->getDoctrine()->getRepository('App:User')->find($viewerId);
        $task->addParatgerAvec($user);
        $this->getDoctrine()->getManager()->flush();

        // Récupérer l'utilisateur destinataire en fonction de l'ID sélectionné

        // Code pour effectuer le partage de la tâche avec le destinataire

        $this->addFlash('success', 'La tâche a été partagée avec succès.');

        return $this->redirectToRoute('task_list');
    }





}
