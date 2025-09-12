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
        $result = [
            'name' => ucfirst($data['name'] ?? ''),
            'sprite' => $data['sprites']['front_default'] ?? null,
            'types' => array_map(
                fn($t) => ucfirst($t['type']['name'] ?? ''),
                $data['types'] ?? []
            )
        ];

        // 6. risposta al client
        return response()->json($result);
    }
}
