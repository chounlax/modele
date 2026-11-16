<?php

namespace App\Tests\Mock;

use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Mock : remplace l'API Have I Been Pwned pendant les tests.
 * Un seul mot de passe est considéré comme « apparu dans une fuite ».
 */
final class PwnedPasswordsMock
{
    public const MOT_DE_PASSE_COMPROMIS = 'Fuite#Donnees2024';

    public function __invoke(string $method, string $url, array $options = []): ResponseInterface
    {
        // l'API reçoit les 5 premiers caractères du SHA-1 et renvoie la fin des empreintes connues
        $empreinte = strtoupper(sha1(self::MOT_DE_PASSE_COMPROMIS));
        $corps = str_ends_with($url, '/range/' . substr($empreinte, 0, 5))
            ? substr($empreinte, 5) . ':12345'
            : '';

        return new MockResponse($corps);
    }
}