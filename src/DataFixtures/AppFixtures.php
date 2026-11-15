<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Security\PasswordManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * Données reproductibles pour les tests : mêmes comptes, mêmes mots de passe à chaque chargement.
 */
class AppFixtures extends Fixture
{
    public const MDP_ADMIN = 'Admin#Secret2026';
    public const MDP_ALICE = 'Alice#Secret2026';
    public const MDP_BOB = 'Bob#Secret2026x';
    public const MDP_CAROLE = 'Carole#Secret2026';

    public function __construct(private readonly PasswordManager $passwordManager)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $this->creer($manager, 'admin@test.fr', 'Admin', 'Test', self::MDP_ADMIN, ['ROLE_ADMIN']);
        $this->creer($manager, 'alice@test.fr', 'Alice', 'Martin', self::MDP_ALICE);
        $this->creer($manager, 'bob@test.fr', 'Bob', 'Durand', self::MDP_BOB);
        // compte déjà suspendu
        $this->creer($manager, 'carole@test.fr', 'Carole', 'Petit', self::MDP_CAROLE, [], false);

        $manager->flush();
    }

    private function creer(ObjectManager $manager, string $email, string $prenom, string $nom, string $mdp, array $roles = [], bool $actif = true): void
    {
        $user = (new User())
            ->setEmail($email)
            ->setPrenom($prenom)
            ->setNom($nom)
            ->setAdresse('1 rue de la Paix')
            ->setCodePostal('80000')
            ->setVille('Amiens')
            ->setRoles($roles)
            ->setIsActive($actif);

        // hache le mot de passe et le met dans l'historique, comme à l'inscription
        $this->passwordManager->setPassword($user, $mdp);
        $manager->persist($user);
    }
}