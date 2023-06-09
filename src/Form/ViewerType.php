<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
class ViewerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username' , TextType::class, ['label' => "Nom du Viewer",'attr'=>['class'=>'form-control m-3']])
            ->add('roles', ChoiceType::class, [
                'choices' => [
                    'Viewer' => 'ROLE_VIEWER',

                ],'attr'=>['class'=>'form-control']
            ])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'Les deux mots de passe doivent correspondre.',
                'required' => true,
                'first_options'  => ['label' => 'Mot de passe','attr'=>['class'=>'form-control']],
                'second_options' => ['label' => 'Tapez le mot de passe à nouveau','attr'=>['class'=>'form-control']],
            ])
            ->add('email', EmailType::class, ['label' => 'Adresse email','attr'=>['class'=>'form-control']])
        ;

        $builder->get('roles')
            ->addModelTransformer(new CallbackTransformer(
                function ($rolesArray) {
                    // transform the array to a string
                    return implode(', ', $rolesArray);
                },
                function ($rolesString) {
                    // transform the string back to an array
                    return explode(', ', $rolesString);
                }
            ))
        ;
    }
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
