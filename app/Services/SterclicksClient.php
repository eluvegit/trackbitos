<?php

namespace App\Services;

use Config\Sterclicks;

/**
 * Lee en vivo el stock de sterclicks (App\Controllers\Api\PiezaStockApi de
 * allá, filtro trackbitosInbound). Solo lectura, sin caché local: es una
 * llamada barata y así nunca hay desfase con lo que sterclicks tiene de
 * verdad. Si sterclicks no responde, devuelve [] en vez de reventar la
 * página — el stock es un dato extra, no algo sin lo que la galería deba
 * dejar de funcionar.
 */
class SterclicksClient
{
    private string $apiToken;
    private string $baseUrl;

    /** Por qué falló la última llamada (para enseñarlo en el TPV), o null. */
    public ?string $ultimoError = null;

    public function __construct(?Sterclicks $config = null)
    {
        $config = $config ?? config(Sterclicks::class);

        $this->apiToken = $config->apiToken;
        $this->baseUrl  = rtrim($config->baseUrl, '/');
    }

    /** @return array<string, array{stock_actual:int, stock_minimo:int}> indexado por sku */
    public function stockPorSku(): array
    {
        if ($this->apiToken === '') {
            return [];
        }

        $ch = curl_init($this->baseUrl . '/stock');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $this->apiToken],
            CURLOPT_TIMEOUT        => 5,
        ]);

        $raw = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false || $status < 200 || $status >= 300) {
            return [];
        }

        $decoded = json_decode($raw, true);
        $filas = $decoded['stock'] ?? [];

        $porSku = [];
        foreach ($filas as $fila) {
            $porSku[$fila['sku']] = [
                'stock_actual' => (int) $fila['stock_actual'],
                'stock_minimo' => (int) $fila['stock_minimo'],
            ];
        }

        return $porSku;
    }

    /**
     * Carta de colores del taller de sterclicks (App\Controllers\Api\TallerColoresApi
     * de allá): se edita solo allí, aquí se lee en vivo. La última respuesta
     * buena se guarda en caché para que el TPV la siga enseñando si sterclicks
     * no responde ('desfasada' => true); null si no hay ni respuesta ni caché.
     *
     * @return array{colores: list<array{id:int, hex:string, nombre:string, alias:string, foto:?string}>, generado_en:?string, desfasada:bool}|null
     */
    public function cartaColores(): ?array
    {
        $claveCache = 'sterclicks_carta_colores';
        $raw = $this->get('/colores', $status);
        $decoded = $raw !== null && $status >= 200 && $status < 300 ? json_decode($raw, true) : null;

        if (is_array($decoded) && isset($decoded['colores']) && is_array($decoded['colores'])) {
            $carta = ['colores' => $decoded['colores'], 'generado_en' => $decoded['generado_en'] ?? null];
            cache()->save($claveCache, $carta, 60 * 60 * 24 * 365);

            return $carta + ['desfasada' => false];
        }

        $guardada = cache($claveCache);

        return is_array($guardada) ? $guardada + ['desfasada' => true] : null;
    }

    /** @return array{body:string, mime:string}|null foto de ejemplo de un color de la carta */
    public function fotoColor(int $id): ?array
    {
        $raw = $this->get('/colores/' . $id . '/foto', $status, $mime, 10);
        if ($raw === null || $status < 200 || $status >= 300 || !str_starts_with((string) $mime, 'image/')) {
            return null;
        }

        return ['body' => $raw, 'mime' => (string) $mime];
    }

    /** GET con el token; null si no hay token o falla la conexión. */
    private function get(string $ruta, ?int &$status = null, ?string &$mime = null, int $timeout = 5): ?string
    {
        $status = 0;
        if ($this->apiToken === '') {
            $this->ultimoError = 'falta sterclicks.stockApiToken en el .env de trackbitos';

            return null;
        }

        $ch = curl_init($this->baseUrl . $ruta);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $this->apiToken],
            CURLOPT_TIMEOUT        => $timeout,
        ]);

        $raw = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $mime = curl_getinfo($ch, CURLINFO_CONTENT_TYPE) ?: null;
        $errorCurl = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            $this->ultimoError = 'no se pudo conectar con ' . $this->baseUrl . ' (' . $errorCurl . ')';

            return null;
        }
        if ($status < 200 || $status >= 300) {
            $this->ultimoError = match ($status) {
                401     => 'sterclicks rechaza el token (401): sterclicks.stockApiToken de trackbitos no coincide con STERCLICKS_INBOUND_TOKEN de sterclicks',
                404     => 'sterclicks responde 404 en ' . $this->baseUrl . $ruta . ' (¿sterclicks.baseUrl mal o falta subir la ruta?)',
                default => 'sterclicks responde HTTP ' . $status . ' en ' . $this->baseUrl . $ruta,
            };
        }

        return $raw;
    }

    /** Avisa a sterclicks de que resincronice su catálogo ya, sin esperar al cron. */
    public function sincronizarCatalogo(): bool
    {
        if ($this->apiToken === '') {
            return false;
        }

        $ch = curl_init($this->baseUrl . '/sincronizar');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $this->apiToken],
            CURLOPT_TIMEOUT        => 5,
        ]);

        $raw = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $raw !== false && $status >= 200 && $status < 300;
    }
}
