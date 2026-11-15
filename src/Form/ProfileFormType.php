<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Modification de son propre profil : volontairement sans e-mail, rôles ni statut.
 * Symfony refuse tout champ en trop (ex. roles) envoyé par un utilisateur malveillant.
 */
class ProfileFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('prenom', TextType::class, ['label' => 'Prénom'])
            ->add('nom', TextType::class, ['label' => 'Nom'])
            ->add('adresse', TextType::class, ['label' => 'Adresse'])
            ->add('codePostal', TextType::class, ['label' => 'Code postal'])
            ->add('ville', TextType::class, ['label' => 'Ville'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => User::class]);
    }
}