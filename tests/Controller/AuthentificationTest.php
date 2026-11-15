<?php

namespace App\Tests\Controller;

use App\DataFixtures\AppFixtures;
use App\Tests\ApplicationTestCase;

class AuthentificationTest extends ApplicationTestCase
{
    // Test 1 – Accès à une page protégée
    public function testAliceConnecteeAccedeASonEspace(): void
    {
        $this->connecterEn('alice@test.fr');
        $this->assertConnecte();
    }

    public function testVisiteurNonConnecteEstRedirigeVersLaConnexion(): void
    {
        $this->client->request('GET', '/private/profil');
        $this->assertResponseRedirects('/login');

        $this->client->request('GET', '/private/mot-de-passe');
        $this->assertResponseRedirects('/login');
    }

    // Test 2 – Identifiants valides
    public function testConnexionAvecIdentifiantsValides(): void
    {
        $this->connecter('alice@test.fr', AppFixtures::MDP_ALICE);
        $this->assertResponseRedirects('/');

        $this->assertConnecte();
        // elle reste connectée sur une autre page protégée
        $this->client->request('GET', '/private/mot-de-passe');
        $this->assertResponseIsSuccessful();
    }

    // Test 3 – Mot de passe incorrect
    public function testConnexionRefuseeAvecMauvaisMotDePasse(): void
    {
        $this->connecter('alice@test.fr', 'MauvaisMotDePasse#1');
        $this->assertNonConnecte();
    }

    public function testConnexionRefuseeAvecLeMotDePasseDeBob(): void
    {
        $this->connecter('alice@test.fr', AppFixtures::MDP_BOB);
        $this->assertNonConnecte();
    }

    public function testConnexionRefuseeAvecIdentifiantInexistant(): void
    {
        $this->connecter('inconnu@test.fr', 'NImporteQuoi#123');
        $this->assertNonConnecte();
    }

    // Test 4 – Déconnexion et invalidation de session
    public function testAncienCookieDeSessionInutilisableApresDeconnexion(): void
    {
        $this->connecter('alice@test.fr', AppFixtures::MDP_ALICE);
        $this->assertConnecte();

        // on garde une copie du cookie de session avant de se déconnecter
        $ancienCookie = $this->client->getCookieJar()->get('MOCKSESSID');
        $this->deconnecter();
        $this->assertNonConnecte();

        // on remet l'ancien cookie : la session a été détruite côté serveur
        $this->client->getCookieJar()->set($ancienCookie);
        $this->assertNonConnecte();
    }
}