<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http; // client HTTP di Laravel

class PokemonController extends Controller
{
    public function index(Request $request)
    {
        $limit = (int) $request->query('limit', 12);
        $offset = (int) $request->query('offset', 0);

        // 1. prendo lista base ( nomi + url dettaglio)
        $listRes = Http::timeout(5)->get('https://pokeapi.co/api/v2/pokemon', [
            'limit'  => $limit,
            'offset' => $offset,
        ]);

        if ($listRes->failed()) {
            return response()->json(['error' => 'Impossibile ottenere la lista'], 502);
        }

        $results = $listRes->json('results') ?? [];

        // 2. Preparo richieste per dettagli (sprite + types)
        $items = [];
        foreach ($results as $row) {
            $url = $row['url'] ?? null;
            if (!$url) {
                continue;
            }

            $detail = Http::timeout(5)->get($url);
            if ($detail->failed()) {
                continue;
            }

            $d = $detail->json();

            $items[] = [
                'id'     => $d['id'] ?? null,
                'name'   => ucfirst($d['name'] ?? ''),
                'sprite' => $d['sprites']['front_default'] ?? null,
                'types'  => array_map(fn($t) => ucfirst($t['type']['name'] ?? ''), $d['types'] ?? []),
            ];
        }

        // 3) risposta pulita
        return response()->json([
            'count'  => count($items),
            'limit'  => $limit,
            'offset' => $offset,
            'items'  => $items,
        ]);
    }


    public function show(string $name)
    {
        // 1. normalizzo l'input (lowercase, niente spazi)
        $name = strtolower(trim($name));

        // 2. faccio la chiamata API a pokéApi
        try {
            $response = Http::timeout(5)->get("https://pokeapi.co/api/v2/pokemon/{$name}");
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Servizio esterno non disponibile'], 503);
        }

        // 3. Gestione errore 404
        if ($response->failed()) {
            return response()->json(['error' => "Pokemon {$name} non torvato"], 404);
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
