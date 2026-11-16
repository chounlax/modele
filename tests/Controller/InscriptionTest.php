<?php

namespace App\Tests\Controller;

use App\Repository\UserRepository;
use App\Tests\ApplicationTestCase;
use App\Tests\Mock\PwnedPasswordsMock;
use PHPUnit\Framework\Attributes\DataProvider;

class InscriptionTest extends ApplicationTestCase
{
    private const MDP_VALIDE = 'Inscription#Valide2026';

    /** Inscription d'Alice (nouvelle adresse), avec des valeurs valides qu'on peut remplacer. */
    private function inscrire(array $modifications = []): void
    {
        $donnees = array_replace([
            'prenom' => 'Alice',
            'nom' => 'Nouvelle',
            'email' => 'alice.nouvelle@test.fr',
            'adresse' => '12 rue des Lilas',
            'codePostal' => '80000',
            'ville' => 'Amiens',
            'plainPassword' => ['first' => self::MDP_VALIDE, 'second' => self::MDP_VALIDE],
            'agreeTerms' => '1',
        ], $modifications);

        $this->envoyerFormulaire('/register', 'registration_form', $donnees);
    }

    private function nombreDeComptes(): int
    {
        return \count(static::getContainer()->get(UserRepository::class)
            ->findBy(['email' => 'alice.nouvelle@test.fr']));
    }

    // Tests 9, 10 et 12 : chaque ligne = une inscription qui doit être refusée
    public static function donneesRefusees(): array
    {
        return [
            'nom vide' => [['nom' => '']],
            'prénom vide' => [['prenom' => '']],
            'adresse vide' => [['adresse' => '']],
            'code postal vide' => [['codePostal' => '']],
            'mot de passe vide' => [['plainPassword' => ['first' => '', 'second' => '']]],
            'code postal ABCDE' => [['codePostal' => 'ABCDE']],
            'email invalide' => [['email' => 'pas-un-email']],
            'nom trop long' => [['nom' => str_repeat('a', 56)]],
            'mot de passe trop court' => [['plainPassword' => ['first' => 'Ab1!', 'second' => 'Ab1!']]],
            'mot de passe compromis' => [['plainPassword' => [
                'first' => PwnedPasswordsMock::MOT_DE_PASSE_COMPROMIS,
                'second' => PwnedPasswordsMock::MOT_DE_PASSE_COMPROMIS,
            ]]],
        ];
    }

    #[DataProvider('donneesRefusees')]
    public function testInscriptionRefuseeEtAucunCompteCree(array $modification): void
    {
        $this->inscrire($modification);

        $this->assertResponseStatusCodeSame(422); // formulaire réaffiché avec les erreurs
        $this->assertSame(0, $this->nombreDeComptes());
    }

    // Test 11 – Inscription valide
    public function testInscriptionValidePuisConnexion(): void
    {
        $this->inscrire();
        $this->assertResponseRedirects();
        $this->assertSame(1, $this->nombreDeComptes());

        $this->deconnecter();
        $this->connecter('alice.nouvelle@test.fr', self::MDP_VALIDE);
        $this->assertConnecte();
    }

    // Test 12 – Le compte n'est créé qu'une seule fois
    public function testCompteCreeUneSeuleFois(): void
    {
        $this->inscrire(['codePostal' => 'ABCDE']);
        $this->assertSame(0, $this->nombreDeComptes());

        $this->inscrire();
        $this->deconnecter();
        $this->inscrire(); // même adresse e-mail une 2e fois : refusé
        $this->assertSame(1, $this->nombreDeComptes());
    }
}