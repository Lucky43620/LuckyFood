<?php

namespace App\Http\Integrations\FatSecret;

use App\Http\Integrations\FatSecret\Data\FatSecretApiError;

final class FatSecretErrorPresenter
{
    /**
     * @return array{code: int|null, message: string, ip: string|null}|null
     */
    public function search(?FatSecretApiError $error): ?array
    {
        if ($error === null) {
            return null;
        }

        $code = (int) ($error->code ?? 0);

        return [
            'code' => $code ?: null,
            'message' => match ($code) {
                5 => 'FatSecret refuse la Consumer Key OAuth1. Verifiez les identifiants OAuth1.',
                8 => 'FatSecret refuse la signature OAuth1. Verifiez le Consumer Secret OAuth1.',
                21 => $error->ip
                    ? "FatSecret bloque l'adresse IP actuelle ({$error->ip}). Ajoutez cette IP dans les parametres FatSecret, puis relancez la recherche."
                    : "FatSecret bloque l'adresse IP actuelle. Ajoutez l'IP du serveur dans les parametres FatSecret, puis relancez la recherche.",
                14 => "FatSecret refuse le niveau d'acces API actuel. Verifiez les droits du compte FatSecret.",
                default => 'La recherche FatSecret est temporairement indisponible.',
            },
            'ip' => $error->ip,
        ];
    }

    /**
     * @return array{code: int|null, message: string}|null
     */
    public function ingredient(?FatSecretApiError $error): ?array
    {
        if ($error === null) {
            return null;
        }

        $code = (int) ($error->code ?? 0);

        return [
            'code' => $code ?: null,
            'message' => match ($code) {
                5, 8 => 'Les identifiants FatSecret sont invalides.',
                21 => "FatSecret bloque l'adresse IP actuelle.",
                14 => "Le compte FatSecret n'a pas le niveau d'acces requis.",
                default => 'La recherche FatSecret est temporairement indisponible.',
            },
        ];
    }
}
