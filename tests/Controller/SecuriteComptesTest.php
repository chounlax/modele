<?php

namespace App\Tests\Controller;

use App\DataFixtures\AppFixtures;
use App\Tests\ApplicationTestCase;

class SecuriteComptesTest extends ApplicationTestCase
{
    private function basculerSuspension(string $email): void
    {
        $id = $this->utilisateur($email)->getId();
        $this->connecterEn('admin@test.fr');
        $crawler = $this->client->request('GET', '/admin/utilisateurs');
        $this->client->submit($crawler->filter(sprintf('form[action$="/%d/suspendre"]', $id))->form());
        $this->deconnecter();
    }

    private function changerMotDePasse(string $actuel, string $nouveau): void
    {
        $this->envoyerFormulaire('/private/mot-de-passe', 'change_password_form', [
            'currentPassword' => $actuel,
            'newPassword' => ['first' => $nouveau, 'second' => $nouveau],
        ]);
    }

    // Test 13 – Suspension
    public function testCompteSuspenduNePeutPasSeConnecter(): void
    {
        $this->connecter('alice@test.fr', AppFixtures::MDP_ALICE);
        $this->assertConnecte();
        $this->deconnecter();

        $this->basculerSuspension('alice@test.fr');
        $this->assertFalse($this->utilisateur('alice@test.fr')->isActive());

        $this->connecter('alice@test.fr', AppFixtures::MDP_ALICE);
        $this->assertNonConnecte();

        $this->basculerSuspension('alice@test.fr');
        $this->assertTrue($this->utilisateur('alice@test.fr')->isActive());

        $this->connecter('alice@test.fr', AppFixtures::MDP_ALICE);
        $this->assertConnecte();
    }

    public function testCompteDejaSuspenduDansLesFixtures(): void
    {
        $this->connecter('carole@test.fr', AppFixtures::MDP_CAROLE);
        $this->assertNonConnecte();
    }

    // Test 14 – Réutilisation d'un ancien mot de passe
    public function testAncienMotDePasseNonReutilisable(): void
    {
        $a = AppFixtures::MDP_ALICE;
        $b = 'Deuxieme#Secret2026';
        $c = 'Troisieme#Secret2026';
        $this->connecter('alice@test.fr', $a);
        $this->assertConnecte();

        $this->changerMotDePasse($a, $b);
        $this->assertResponseRedirects('/private/profil');
        $this->changerMotDePasse($b, $c);
        $this->assertResponseRedirects('/private/profil');

        $this->changerMotDePasse($c, $a);
        $this->assertResponseStatusCodeSame(422);
        $this->changerMotDePasse($c, $b);
        $this->assertResponseStatusCodeSame(422);
    }

    // Test 15 – Modification du mot de passe
    public function testModificationDuMotDePasse(): void
    {
        $nouveau = 'Nouveau#Secret2026';
        $this->connecterEn('alice@test.fr');

        $this->changerMotDePasse('MauvaisActuel#2026', $nouveau);
        $this->assertResponseStatusCodeSame(422); // mot de passe actuel faux

        $this->changerMotDePasse(AppFixtures::MDP_ALICE, 'court');
        $this->assertResponseStatusCodeSame(422); // nouveau mot de passe non conforme

        $this->changerMotDePasse(AppFixtures::MDP_ALICE, $nouveau);
        $this->assertResponseRedirects('/private/profil');
        $this->deconnecter();

        $this->connecter('alice@test.fr', $nouveau);
        $this->assertConnecte();
        $this->deconnecter();

        $this->connecter('alice@test.fr', AppFixtures::MDP_ALICE);
        $this->assertNonConnecte();
    }
}