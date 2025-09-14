<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http; // client HTTP di Laravel

class PokemonController extends Controller
{
    public function show(string $name)
    {
        // 1. normalizzo l'input (lowercase, niente spazi)
        $name = strtolower(trim($name));

        // 2. faccio la chiamata API a pokéApi
        try {
            $response = Http::get("https://pokeapi.co/api/v2/pokemon/{$name}");
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Servizio esterno non disponibile'], 503);
        }

        // 3. Gestione errore 404
        if ($response->failed()) {
            return response()->json(['error' => `Pokemon {$name} non torvato`], 404);
        }

        // 4. dati grezzi della chiamata Api
        $data = $response->json();

        // 5. output pulito

        $statsMap = [];
        foreach ($data['stats'] ?? [] as $s) {
            $key = $s['stat']['name'] ?? '';
            $base = $s['base_stat'] ?? null;
            if ($key && $base !== null) {
                $statsMap[$key] = $base;
            }
        }

        $result = [
            'name' => ucfirst($data['name'] ?? ''),
            'sprite' => $data['sprites']['front_default'] ?? null,
            'sprite_shiny' => $data['sprites']['front_shiny'] ?? null,
            'height_cm' => isset($data['height']) ? $data['height'] * 10 : null,
            'weight_kg' => isset($data['weight']) ? round($data['weight'] / 10, 1) : null,
            'types' => array_map(
                fn($t) => ucfirst($t['type']['name'] ?? ''),
                $data['types'] ?? []
            ),
            'abilities' => array_map(fn($a) => ucfirst($a['ability']['name'] ?? ''), $data['abilities'] ?? []),
            'stats' => $statsMap,
        ];

        // 6. risposta al client
        return response()->json($result);
    }
}
