<?php

namespace App\Http\Controllers;

use App\Services\FatSecretService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FoodSearchController extends Controller
{
    public function __construct(private FatSecretService $fatSecret) {}

    public function index(Request $request): Response
    {
        $query   = $request->input('q', '');
        $meal    = $request->input('meal', 'breakfast');
        $pageNumber = max(0, (int) $request->input('page', 0));
        $results = [];
        $pagination = $this->emptyPagination($pageNumber);
        $effectiveQuery = '';
        $searchError = null;
        $region  = $request->user()?->fatsecret_region;
        $language = $request->user()?->fatsecret_language;

        if (strlen(trim($query)) >= 2) {
            $search = $this->fatSecret->searchFoodsPage($query, $pageNumber, 20, $region, $language);
            $results = $search['results'];
            $pagination = $search['pagination'];
            $effectiveQuery = $search['effective_query'];
            $searchError = $this->formatSearchError($this->fatSecret->lastError());
        }

        return Inertia::render('Search', [
            'query' => $query,
            'results' => $results,
            'meal' => $meal,
            'pagination' => $pagination,
            'effectiveQuery' => $effectiveQuery,
            'searchError' => $searchError,
        ]);
    }

    public function show(Request $request, string $foodId): Response
    {
        $food = $this->fatSecret->getFood(
            $foodId,
            region: $request->user()?->fatsecret_region,
            language: $request->user()?->fatsecret_language,
        );

        return Inertia::render('Search/Show', [
            'food' => $food,
            'meal' => $request->input('meal', 'breakfast'),
            'query' => $request->input('q', ''),
            'searchError' => $this->formatSearchError($this->fatSecret->lastError()),
        ]);
    }

    public function autocomplete(Request $request): JsonResponse
    {
        $query       = $request->input('q', '');
        $suggestions = strlen(trim($query)) >= 2
            ? $this->fatSecret->autocomplete(
                $query,
                region: $request->user()?->fatsecret_region,
                language: $request->user()?->fatsecret_language,
            )
            : [];

        return response()->json($suggestions);
    }

    private function formatSearchError(?array $error): ?array
    {
        if ($error === null) {
            return null;
        }

        $code = (int) ($error['code'] ?? 0);
        $ip = $error['ip'] ?? null;

        $message = match ($code) {
            5 => "FatSecret refuse la Consumer Key OAuth1. Verifiez les identifiants OAuth1.",
            8 => "FatSecret refuse la signature OAuth1. Verifiez le Consumer Secret OAuth1.",
            21 => $ip
                ? "FatSecret bloque l'adresse IP actuelle ($ip). Ajoutez cette IP dans les parametres FatSecret, puis relancez la recherche."
                : "FatSecret bloque l'adresse IP actuelle. Ajoutez l'IP du serveur dans les parametres FatSecret, puis relancez la recherche.",
            14 => "FatSecret refuse le niveau d'acces API actuel. Verifiez les droits du compte FatSecret.",
            default => 'La recherche FatSecret est temporairement indisponible.',
        };

        return [
            'code' => $code ?: null,
            'message' => $message,
            'ip' => $ip,
        ];
    }

    private function emptyPagination(int $page): array
    {
        return [
            'page' => $page,
            'per_page' => 20,
            'total' => 0,
            'total_pages' => 0,
            'from' => 0,
            'to' => 0,
            'has_previous' => false,
            'has_next' => false,
        ];
    }
}
