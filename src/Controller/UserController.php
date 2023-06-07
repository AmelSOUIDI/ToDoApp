<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\UserRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordHasher;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

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
            //return $this->redirectToRoute('user_list');
        }

        return $this->render('user/create.html.twig', ['form' => $form->createView()]);
    }


    /**
     * @Route("/users/edit/{id}/{role_edit}", name="user_edit")
     * @param int $id
     * @param string $role_edit
     * @param Request $request
     * @return Response
     * @IsGranted("ROLE_USER")
     */
    public function editAction(User $user, Request $request, UserPasswordEncoderInterface $encoder)
    {
       // $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $form = $this->createForm(UserType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $hash = $encoder->encodePassword($user, $user->getPassword());
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
