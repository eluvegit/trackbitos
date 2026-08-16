<?php

namespace App\Services;

/**
 * Búsqueda de metadatos de libros (portada, autor, ISBN, páginas) contra la
 * API pública de Open Library. No requiere API key ni cuota: solo pide un
 * User-Agent identificable (https://openlibrary.org/dev/docs/api/search).
 */
class BookLookupService
{
    private const API_URL = 'https://openlibrary.org/search.json';

    /**
     * @return list<array{title:string,author:?string,cover_url:?string,isbn:?string,total_pages:?int}>
     */
    public function buscar(string $query, int $maxResultados = 8): array
    {
        $query = trim($query);
        if ($query === '') {
            return [];
        }

        try {
            $client   = \Config\Services::curlrequest();
            $response = $client->get(self::API_URL, [
                'query' => [
                    'q'      => $query,
                    'limit'  => $maxResultados,
                    'fields' => 'title,author_name,isbn,number_of_pages_median,cover_i',
                ],
                'headers' => [
                    'User-Agent' => 'Trackbitos/1.0 (personal app; eluvemail@gmail.com)',
                ],
                'timeout' => 8,
                // Este entorno PHP (Windows/ServBay) no trae un CA bundle
                // configurado en php.ini, así que se pasa uno explícito.
                'verify' => is_file(WRITEPATH . 'cacert.pem') ? WRITEPATH . 'cacert.pem' : true,
            ]);

            $data = json_decode($response->getBody(), true);
        } catch (\Throwable $e) {
            log_message('error', 'BookLookupService: fallo al llamar a Open Library: {msg}', ['msg' => $e->getMessage()]);

            return [];
        }

        $resultados = [];
        foreach ($data['docs'] ?? [] as $doc) {
            $titulo = trim((string) ($doc['title'] ?? ''));
            if ($titulo === '') {
                continue;
            }

            $resultados[] = [
                'title'       => $titulo,
                'author'      => !empty($doc['author_name']) ? implode(', ', $doc['author_name']) : null,
                'cover_url'   => !empty($doc['cover_i']) ? "https://covers.openlibrary.org/b/id/{$doc['cover_i']}-M.jpg" : null,
                'isbn'        => !empty($doc['isbn'][0]) ? $doc['isbn'][0] : null,
                'total_pages' => !empty($doc['number_of_pages_median']) ? (int) $doc['number_of_pages_median'] : null,
            ];
        }

        return $resultados;
    }
}
