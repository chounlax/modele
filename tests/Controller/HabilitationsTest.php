<?php

namespace App\Tests\Controller;

use App\Tests\ApplicationTestCase;

class HabilitationsTest extends ApplicationTestCase
{
    // Test 5 – Accès à l'administration
    public function testAdministrateurAccedeAAdmin(): void
    {
        $this->connecterEn('admin@test.fr');
        $this->client->request('GET', '/admin/utilisateurs');
        $this->assertResponseIsSuccessful();
    }

    public function testAliceRecoitUne403SurAdmin(): void
    {
        $this->connecterEn('alice@test.fr');
        $this->client->request('GET', '/admin/utilisateurs');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testVisiteurRedirigeSurAdmin(): void
    {
        $this->client->request('GET', '/admin/utilisateurs');
        $this->assertResponseRedirects('/login');
    }

    // Test 6 – Fonctionnalités d'administration
    public function testAdministrateurSuspendBob(): void
    {
        $bobId = $this->utilisateur('bob@test.fr')->getId();
        $this->connecterEn('admin@test.fr');

        // on clique sur le vrai bouton « Suspendre » (avec son jeton CSRF)
        $crawler = $this->client->request('GET', '/admin/utilisateurs');
        $form = $crawler->filter(sprintf('form[action$="/%d/suspendre"]', $bobId))->form();
        $this->client->submit($form);

        $this->assertFalse($this->utilisateur('bob@test.fr')->isActive());
    }

    public function testAliceNePeutNiSuspendreNiSupprimerBob(): void
    {
        $bobId = $this->utilisateur('bob@test.fr')->getId();
        $this->connecterEn('alice@test.fr');

        // requêtes envoyées directement, sans bouton : le serveur doit refuser
        $this->client->request('POST', "/admin/utilisateurs/$bobId/suspendre");
        $this->assertResponseStatusCodeSame(403);
        $this->client->request('POST', "/admin/utilisateurs/$bobId/supprimer");
        $this->assertResponseStatusCodeSame(403);

        $bob = $this->utilisateur('bob@test.fr');
        $this->assertNotNull($bob);
        $this->assertTrue($bob->isActive());
    }

    // Test 7 – IDOR / BOLA
    public function testAliceModifieSonPropreProfil(): void
    {
        $this->connecterEn('alice@test.fr');
        $this->envoyerFormulaire('/private/profil/modifier', 'profile_form', ['prenom' => 'Alicia']);

        $this->assertResponseRedirects('/private/profil');
        $this->assertSame('Alicia', $this->utilisateur('alice@test.fr')->getPrenom());
    }

    public function testAliceNePeutPasModifierLeProfilDeBob(): void
    {
        $bobId = $this->utilisateur('bob@test.fr')->getId();
        $this->connecterEn('alice@test.fr');

        // elle met l'id de Bob dans l'URL de modification
        $this->client->request('GET', "/admin/utilisateurs/$bobId/modifier");
        $this->assertResponseStatusCodeSame(403);

        // elle envoie directement une requête HTTP avec de nouvelles données
        $this->client->request('POST', "/admin/utilisateurs/$bobId/modifier", [
            'admin_user' => ['prenom' => 'Pirate'],
        ]);
        $this->assertResponseStatusCodeSame(403);

        // elle ajoute ?id=Bob à sa propre page : c'est quand même son profil qui change
        $this->envoyerFormulaire("/private/profil/modifier?id=$bobId", 'profile_form', ['prenom' => 'Pirate']);

        $this->assertSame('Bob', $this->utilisateur('bob@test.fr')->getPrenom());
    }

    public function testAdministrateurModifieLeProfilDeBob(): void
    {
        $bobId = $this->utilisateur('bob@test.fr')->getId();
        $this->connecterEn('admin@test.fr');
        $this->envoyerFormulaire("/admin/utilisateurs/$bobId/modifier", 'admin_user', ['prenom' => 'Robert']);

        $this->assertSame('Robert', $this->utilisateur('bob@test.fr')->getPrenom());
    }

    // Test 8 – Modification des rôles
    public function testAliceNePeutPasSAttribuerAdmin(): void
    {
        $aliceId = $this->utilisateur('alice@test.fr')->getId();
        $this->connecterEn('alice@test.fr');

        // paramètre roles ajouté à la main dans la requête de son profil
        $this->envoyerFormulaire('/private/profil/modifier', 'profile_form', ['roles' => ['ROLE_ADMIN']]);
        $this->assertResponseStatusCodeSame(422); // formulaire refusé (champ en trop)

        // tentative via la page d'administration
        $this->client->request('POST', "/admin/utilisateurs/$aliceId/modifier", [
            'admin_user' => ['roles' => ['ROLE_ADMIN']],
        ]);
        $this->assertResponseStatusCodeSame(403);

        $this->assertNotContains('ROLE_ADMIN', $this->utilisateur('alice@test.fr')->getRoles());
    }

    public function testAliceNePeutPasModifierLesRolesDeBob(): void
    {
        $bobId = $this->utilisateur('bob@test.fr')->getId();
        $this->connecterEn('alice@test.fr');
        $this->client->request('POST', "/admin/utilisateurs/$bobId/modifier", [
            'admin_user' => ['roles' => ['ROLE_ADMIN']],
        ]);

        $this->assertResponseStatusCodeSame(403);
        $this->assertNotContains('ROLE_ADMIN', $this->utilisateur('bob@test.fr')->getRoles());
    }

    public function testAdministrateurAttribueUnRoleABob(): void
    {
        $bobId = $this->utilisateur('bob@test.fr')->getId();
        $this->connecterEn('admin@test.fr');
        $this->envoyerFormulaire("/admin/utilisateurs/$bobId/modifier", 'admin_user', [
            'roles' => ['ROLE_USER', 'ROLE_ADMIN'],
        ]);

        $this->assertContains('ROLE_ADMIN', $this->utilisateur('bob@test.fr')->getRoles());
    }
}