<?php

namespace App\Http\Integrations\FatSecret;

final class FatSecretNormalizer
{
    /**
     * @return array{results: list<array<string, mixed>>, pagination: array<string, mixed>, effective_query: string}
     */
    public function emptySearchPage(int $page, int $perPage): array
    {
        $page = max(0, $page);
        $perPage = max(1, min(50, $perPage));

        return [
            'results' => [],
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => 0,
                'total_pages' => 0,
                'from' => 0,
                'to' => 0,
                'has_previous' => false,
                'has_next' => false,
            ],
            'effective_query' => '',
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{results: list<array<string, mixed>>, pagination: array<string, mixed>, effective_query: string}
     */
    public function searchPage(array $payload): array
    {
        $foodsPayload = is_array($payload['foods_search'] ?? null) ? $payload['foods_search'] : [];
        $results = $this->searchResults($payload);
        $total = max(0, (int) ($foodsPayload['total_results'] ?? count($results)));
        $page = max(0, (int) ($foodsPayload['page_number'] ?? 0));
        $perPage = max(1, (int) ($foodsPayload['max_results'] ?? count($results) ?: 20));
        $from = $total === 0 || $results === [] ? 0 : ($page * $perPage) + 1;
        $to = $total === 0 || $results === [] ? 0 : min($total, $from + count($results) - 1);

        return [
            'results' => $results,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $total === 0 ? 0 : (int) ceil($total / $perPage),
                'from' => $from,
                'to' => $to,
                'has_previous' => $page > 0,
                'has_next' => $to > 0 && $to < $total,
            ],
            'effective_query' => '',
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<string>
     */
    public function suggestions(array $payload, int $maxResults): array
    {
        $suggestions = $this->asList($payload['suggestions']['suggestion'] ?? []);

        $suggestions = array_values(array_filter(array_map(
            static fn (mixed $suggestion): string => trim((string) $suggestion),
            $suggestions,
        )));

        return array_slice($suggestions, 0, $maxResults);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    public function foodDetails(array $payload): ?array
    {
        $food = $payload['food'] ?? null;

        if (! is_array($food)) {
            return null;
        }

        if (isset($food['servings']['serving'])) {
            $food['servings']['serving'] = $this->asList($food['servings']['serving']);
        }

        $food['original_food_name'] = (string) ($payload['food']['food_name'] ?? $food['food_name'] ?? '');
        $food['brand_name'] = $this->nullableString($food['brand_name'] ?? null);
        $food['image_url'] = $this->extractImageUrl($food);
        $food['food_sub_categories'] = $this->subCategories($food['food_sub_categories']['food_sub_category'] ?? []);
        $food['food_attributes'] = $this->foodAttributes($food['food_attributes'] ?? []);

        foreach ($food['servings']['serving'] ?? [] as $index => $serving) {
            if (is_array($serving)) {
                $food['servings']['serving'][$index] = $this->serving($serving);
            }
        }

        return $food;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array<string, mixed>>
     */
    public function categories(array $payload): array
    {
        return array_values(array_filter(
            $this->asList($payload['food_categories']['food_category'] ?? []),
            is_array(...),
        ));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array<string, mixed>>
     */
    private function searchResults(array $payload): array
    {
        $foods = array_values(array_filter(
            $this->asList($payload['foods_search']['results']['food'] ?? []),
            is_array(...),
        ));

        return array_values(array_filter(
            array_map($this->servingFood(...), $foods),
            static fn (array $food): bool => $food['food_id'] !== '' && $food['food_name'] !== '',
        ));
    }

    /**
     * @param  array<string, mixed>  $food
     * @return array<string, mixed>
     */
    private function servingFood(array $food): array
    {
        $serving = $this->chooseServing($food['servings']['serving'] ?? []);

        return [
            'food_id' => (string) ($food['food_id'] ?? ''),
            'food_name' => (string) ($food['food_name'] ?? ''),
            'original_food_name' => (string) ($food['food_name'] ?? ''),
            'brand_name' => $this->nullableString($food['brand_name'] ?? null),
            'food_type' => (string) ($food['food_type'] ?? ''),
            'food_url' => (string) ($food['food_url'] ?? ''),
            'image_url' => $this->extractImageUrl($food),
            'calories' => $this->toInt($serving['calories'] ?? null),
            'protein' => $this->toFloat($serving['protein'] ?? null),
            'carbs' => $this->toFloat($serving['carbohydrate'] ?? null),
            'fat' => $this->toFloat($serving['fat'] ?? null),
            'serving_description' => (string) ($serving['serving_description'] ?? ''),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function chooseServing(mixed $servings): array
    {
        $servings = array_values(array_filter(
            $this->asList($servings),
            is_array(...),
        ));

        foreach ($servings as $serving) {
            $description = strtolower(str_replace(' ', '', (string) ($serving['serving_description'] ?? '')));

            if (in_array($description, ['100g', '100ml'], true)) {
                return $serving;
            }
        }

        foreach ($servings as $serving) {
            if ((string) ($serving['is_default'] ?? '') === '1') {
                return $serving;
            }
        }

        return $servings[0] ?? [];
    }

    /**
     * @param  array<string, mixed>  $serving
     * @return array<string, mixed>
     */
    private function serving(array $serving): array
    {
        foreach ([
            'calories', 'carbohydrate', 'protein', 'fat', 'saturated_fat', 'polyunsaturated_fat',
            'monounsaturated_fat', 'trans_fat', 'cholesterol', 'sodium', 'potassium', 'fiber',
            'sugar', 'vitamin_a', 'vitamin_c', 'calcium', 'iron', 'metric_serving_amount',
            'number_of_units',
        ] as $key) {
            if (array_key_exists($key, $serving)) {
                $serving[$key] = $this->toFloat($serving[$key]);
            }
        }

        return $serving;
    }

    /**
     * @return array{allergens: list<array<string, mixed>>, preferences: list<array<string, mixed>>}
     */
    private function foodAttributes(mixed $attributes): array
    {
        if (! is_array($attributes)) {
            return ['allergens' => [], 'preferences' => []];
        }

        return [
            'allergens' => $this->attributeList($attributes['allergens']['allergen'] ?? []),
            'preferences' => $this->attributeList($attributes['preferences']['preference'] ?? []),
        ];
    }

    /**
     * @return list<array{id: string, name: string, value: int}>
     */
    private function attributeList(mixed $value): array
    {
        return array_values(array_map(static fn (array $item): array => [
            'id' => (string) ($item['id'] ?? ''),
            'name' => (string) ($item['name'] ?? ''),
            'value' => (int) ($item['value'] ?? -1),
        ], array_filter($this->asList($value), is_array(...))));
    }

    /**
     * @return list<string>
     */
    private function subCategories(mixed $value): array
    {
        return array_values(array_filter(array_map(
            static fn (mixed $category): string => trim((string) $category),
            $this->asList($value),
        )));
    }

    /**
     * @param  array<string, mixed>  $food
     */
    private function extractImageUrl(array $food): ?string
    {
        $image = $food['image_url']
            ?? $food['food_image']
            ?? $food['food_images']['food_image']
            ?? null;

        if (is_array($image)) {
            $image = $this->asList($image)[0] ?? null;
        }

        if (is_array($image)) {
            $image = $image['image_url'] ?? $image['food_image_url'] ?? $image['url'] ?? null;
        }

        $image = trim((string) $image);

        return $image === '' ? null : $image;
    }

    /**
     * @return list<mixed>
     */
    private function asList(mixed $value): array
    {
        if (! is_array($value) || $value === []) {
            return [];
        }

        return array_is_list($value) ? $value : [$value];
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function toInt(mixed $value): ?int
    {
        $float = $this->toFloat($value);

        return $float === null ? null : (int) round($float);
    }

    private function toFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) str_replace(',', '.', (string) $value);
    }
}
