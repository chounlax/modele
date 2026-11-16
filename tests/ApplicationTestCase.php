<?php

namespace App\Tests;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Outils communs à tous les tests fonctionnels.
 */
abstract class ApplicationTestCase extends WebTestCase
{
    protected KernelBrowser $client;

    protected function setUp(): void
    {
        // faux navigateur qui envoie de vraies requêtes HTTP à l'application
        $this->client = static::createClient();
    }

    /** Relit l'utilisateur directement en base de données. */
    protected function utilisateur(string $email): ?User
    {
        return static::getContainer()->get(UserRepository::class)->findOneBy(['email' => $email]);
    }

    /** Connexion en remplissant le vrai formulaire de /login. */
    protected function connecter(string $email, string $motDePasse): void
    {
        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->filter('form')->form(['email' => $email, 'password' => $motDePasse]);
        $this->client->submit($form);
    }

    /** Connexion directe, sans formulaire (quand ce n'est pas la connexion qu'on teste). */
    protected function connecterEn(string $email): void
    {
        $this->client->loginUser($this->utilisateur($email));
    }

    protected function deconnecter(): void
    {
        $this->client->request('GET', '/logout');
    }

    protected function assertConnecte(): void
    {
        $this->client->request('GET', '/private/profil');
        $this->assertResponseIsSuccessful();
    }

    protected function assertNonConnecte(): void
    {
        $this->client->request('GET', '/private/profil');
        $this->assertResponseRedirects('/login');
    }

    /**
     * Ouvre la page, récupère le formulaire (avec son jeton CSRF),
     * remplace certaines valeurs puis l'envoie en POST.
     */
    protected function envoyerFormulaire(string $url, string $nomFormulaire, array $donnees): void
    {
        $crawler = $this->client->request('GET', $url);
        $form = $crawler->filter(sprintf('form[name="%s"]', $nomFormulaire))->form();
        $valeurs = $form->getPhpValues();
        $valeurs[$nomFormulaire] = array_replace_recursive($valeurs[$nomFormulaire] ?? [], $donnees);
        $this->client->request('POST', $form->getUri(), $valeurs);
    }
}