<?php

namespace App\Controller;

use App\Entity\Task;
use App\Entity\User;
use App\Form\UserType;
use App\Form\ViewerType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\UserRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class UserController extends AbstractController
{
     /**
     * $manager construct
     *
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/users/list", name="user_list")
     * @IsGranted("ROLE_ADMIN")
     */
    public function listAction(UserRepository $UserRepository)
    {
        return $this->render('user/list.html.twig', [ 'users' => $UserRepository->findAll()]);
    }

    /**
     * @Route("/users/create", name="user_create")
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function createAction(Request $request,UserPasswordHasherInterface $encoder)
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        

        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) { 
            $hashedPassword = $encoder->hashPassword(
                $user,
                $user->getPassword()
            );
            $user->setPassword($hashedPassword);
            $user->setCreatedAt(new \DateTimeImmutable());
    
            $user->setPassword($hashedPassword);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        
            $this->addFlash('success', "L'utilisateur a bien été ajouté.");
            return $this->redirectToRoute('login');
            
        }

        return $this->render('user/create.html.twig', ['form' => $form->createView()]);
    }

        /**
     * @Route("/users/viewer/{id}/create", name="viewer_create")
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function createAction2(Task $task,Request $request,UserPasswordHasherInterface $encoder,MailerInterface $mailer)
    {
        $user = new User();
        $form = $this->createForm(ViewerType::class, $user);
        

        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) { 
            $hashedPassword = $encoder->hashPassword(
                $user,
                $user->getPassword()
            );
            $user->setPassword($hashedPassword);
            $user->setCreatedAt(new \DateTimeImmutable());
    
            $user->setPassword($hashedPassword);
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $email = (new Email())
            ->from('noreply@gmail.com')
            ->to($user->getEmail())
            ->subject('Une nouvelle tâche vous a été partagée!')
            ->html('<p>J\'espère que ce courriel vous trouve bien. Je tenais à vous informer qu\'une tâche importante vient d\'être partagée avec vous. Cette tâche nécessite votre attention et votre contribution.</p>
        
            <p>Pour accéder à la tâche partagée, veuillez suivre les instructions ci-dessous:</p>
            
            <ol>
                <li>Connectez-vous à votre compte en utilisant l\'adresse e-mail associée : <strong>votre_nom@gmail.com</strong>.</li>
                <li>Lors de votre première connexion, veuillez utiliser le mot de passe temporaire suivant : <strong>newviewer</strong>. Vous serez ensuite invité(e) à créer un nouveau mot de passe plus sécurisé.</li>
            </ol>
            
            <p>Une fois connecté(e) à votre compte, vous pourrez consulter la tâche partagée et prendre les mesures appropriées en conséquence. Assurez-vous de lire attentivement toutes les informations fournies et de respecter les délais éventuels associés à cette tâche.</p>
            
            <p>Si vous avez des questions ou des préoccupations concernant la tâche ou si vous rencontrez des problèmes lors de la connexion, n\'hésitez pas à me contacter. Je suis là pour vous aider à faciliter ce processus et à assurer une exécution fluide de la tâche.</p>
            
            <p>Je vous remercie par avance pour votre collaboration et votre diligence. Votre contribution est essentielle pour atteindre nos objectifs communs. J\'attends avec impatience votre implication dans cette tâche.</p>
            
            <p>Cordialement,</p>');
        

            $mailer->send($email);
        
            $this->addFlash('success', "Le Viewer a bien été ajouté et un mail de confirmation lui a etait envoyer!.");
            return $this->redirectToRoute('task_choose_viewer', ['id' => $task->getId()]);

        }

        return $this->render('viewer/create.html.twig', ['form' => $form->createView(),'task' => $task]);
    }


    /**
     * @Route("/users/edit/{id}/{role_edit}", name="user_edit")
     * @param int $id
     * @param string $role_edit
     * @param Request $request
     * @return Response
     * @IsGranted("ROLE_USER")
     */
    public function editAction(User $user, Request $request, UserPasswordHasherInterface $encoder)
    {
       // $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $form = $this->createForm(UserType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $hash = $encoder->hashPassword($user, $user->getPassword());
            $user->setPassword($hash);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        
            $this->addFlash('success', "L'utilisateur a bien été modifié");

            return $this->redirectToRoute('user_list');
        }

        return $this->render('user/edit.html.twig', [
            'form' => $form->createView(), 
            'user' => $user
        ]);
    }

    
    
}
