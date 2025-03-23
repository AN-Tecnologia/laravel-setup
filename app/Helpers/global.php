<?php

if (!function_exists('removeSpecialCharacters')) {
    function removeSpecialCharacters(string $string): string
    {
        return preg_replace('/[^a-zA-Z0-9]/', '', $string);
    }
}

if (!function_exists('decodeLlmJsonResponse')) {
    function decodeLlmJsonResponse(string $string): array
    {
        // Remove a marcação do código JSON
        if (preg_match('/```json\s*(.*?)\s*```/s', $string, $matches)) {
            $string = $matches[1];
        }

        // Limpa quebras de linha, espaços em excesso e outros caracteres
        $string = preg_replace('/\n|\r|\s+/', ' ', $string); // Remove quebras de linha e espaços extras

        // Decodifica o JSON
        $decoded = json_decode(trim($string), true);

        // Retorna o array decodificado ou um array vazio se falhar
        return is_array($decoded) ? $decoded : [];
    }
}
