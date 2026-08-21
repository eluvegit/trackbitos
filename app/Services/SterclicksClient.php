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
}
