<?php

namespace App\Http\Controllers;

use App\Http\Integrations\FatSecret\FatSecretErrorPresenter;
use App\Http\Requests\AutocompleteFoodRequest;
use App\Http\Requests\BarcodeFoodRequest;
use App\Http\Requests\SearchFoodRequest;
use App\Http\Requests\ShowFoodRequest;
use App\Services\FatSecretService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class FoodSearchController extends Controller
{
    public function __construct(
        private readonly FatSecretService $fatSecret,
        private readonly FatSecretErrorPresenter $errors,
    ) {}

    public function index(SearchFoodRequest $request): Response
    {
        $query = $request->queryText();
        $meal = $request->meal();
        $pageNumber = $request->pageNumber();
        $categoryId = $request->categoryId();
        $results = [];
        $pagination = $this->emptyPagination($pageNumber);
        $searchError = null;
        $region = $request->user()?->fatsecret_region ?? 'FR';
        $language = $request->user()?->fatsecret_language ?? 'fr';

        if (strlen(trim($query)) >= 2) {
            $search = $this->fatSecret->searchFoodsPageResult($query, $pageNumber, 20, $region, $language, $categoryId);
            $results = $search->data()['results'];
            $pagination = $search->data()['pagination'];
            $searchError = $this->errors->search($search->error());
        }

        $categories = $this->fatSecret->getFoodCategoriesResult($region, $language);

        return Inertia::render('Search', [
            'query' => $query,
            'results' => $results,
            'meal' => $meal,
            'pagination' => $pagination,
            'searchError' => $searchError,
            'categories' => $categories->data(),
            'categoryId' => $categoryId,
        ]);
    }

    public function show(ShowFoodRequest $request, string $foodId): Response
    {
        $food = $this->fatSecret->getFoodResult(
            $foodId,
            region: $request->user()?->fatsecret_region ?? 'FR',
            language: $request->user()?->fatsecret_language ?? 'fr',
        );

        return Inertia::render('Search/Show', [
            'food' => $food->data(),
            'meal' => $request->meal(),
            'query' => $request->queryText(),
            'from' => $request->from(),
            'searchError' => $this->errors->search($food->error()),
        ]);
    }

    public function autocomplete(AutocompleteFoodRequest $request): JsonResponse
    {
        $query = $request->queryText();
        $suggestions = strlen($query) >= 2
            ? $this->fatSecret->autocompleteResult(
                $query,
                region: $request->user()?->fatsecret_region ?? 'FR',
                language: $request->user()?->fatsecret_language ?? 'fr',
            )
            : null;

        return response()->json($suggestions?->data() ?? []);
    }

    public function barcode(BarcodeFoodRequest $request): Response
    {
        $barcode = $request->barcode();
        $food = null;
        $searchError = null;

        if ($barcode !== '') {
            $food = $this->fatSecret->searchByBarcodeResult(
                $barcode,
                region: $request->user()?->fatsecret_region ?? 'FR',
                language: $request->user()?->fatsecret_language ?? 'fr',
            );
            $searchError = $this->errors->search($food->error());
        }

        return Inertia::render('Search/Show', [
            'food' => $food?->data(),
            'meal' => $request->meal(),
            'query' => '',
            'searchError' => $searchError,
        ]);
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
