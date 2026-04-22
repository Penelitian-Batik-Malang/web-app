<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ColorSearchMockController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,jpg,png,webp|max:10240',
            'selected_palettes' => 'sometimes|array',
            'selected_palettes.*' => 'string',
        ]);

        $palettes = [
            ['name' => 'Terracotta Tua', 'hex' => '#8C2F1D'],
            ['name' => 'Salem Muda', 'hex' => '#E78F7D'],
            ['name' => 'Coral', 'hex' => '#F15A38'],
            ['name' => 'Coklat Kayu', 'hex' => '#7A4E3F'],
            ['name' => 'Bata', 'hex' => '#C14F2D'],
        ];

        $availableHex = array_column($palettes, 'hex');
        $selected = array_map('strtoupper', (array) $request->input('selected_palettes', []));
        $selected = array_values(array_intersect($availableHex, $selected));

        if (empty($selected)) {
            $selected = $availableHex;
        }

        $recommendations = [
            [
                'id' => 1,
                'name' => 'Mega Mendung',
                'image_url' => 'https://placehold.co/240x180/2C8CD6/FFFFFF?text=Mega+Mendung',
                'palette_tags' => ['#8C2F1D', '#E78F7D'],
            ],
            [
                'id' => 2,
                'name' => 'Parijoto',
                'image_url' => 'https://placehold.co/240x180/1F7ABF/FFFFFF?text=Parijoto',
                'palette_tags' => ['#F15A38', '#C14F2D'],
            ],
            [
                'id' => 3,
                'name' => 'Tugu Malang',
                'image_url' => 'https://placehold.co/240x180/14679E/FFFFFF?text=Tugu+Malang',
                'palette_tags' => ['#7A4E3F', '#E78F7D'],
            ],
            [
                'id' => 4,
                'name' => 'Sekar Jagad',
                'image_url' => 'https://placehold.co/240x180/0E4E75/FFFFFF?text=Sekar+Jagad',
                'palette_tags' => ['#8C2F1D', '#F15A38'],
            ],
            [
                'id' => 5,
                'name' => 'Sido Mukti',
                'image_url' => 'https://placehold.co/240x180/0C3E5C/FFFFFF?text=Sido+Mukti',
                'palette_tags' => ['#C14F2D', '#7A4E3F'],
            ],
        ];

        $filtered = array_values(array_filter($recommendations, function (array $item) use ($selected): bool {
            return !empty(array_intersect($item['palette_tags'], $selected));
        }));

        return response()->json([
            'success' => true,
            'result' => [
                'palettes' => $palettes,
                'selected_palettes' => $selected,
                'recommendations' => array_map(function (array $item): array {
                    return [
                        'id' => $item['id'],
                        'name' => $item['name'],
                        'image_url' => $item['image_url'],
                    ];
                }, $filtered),
            ],
        ]);
    }
}
