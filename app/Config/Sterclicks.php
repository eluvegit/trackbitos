<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Sterclicks extends BaseConfig
{
    public string $apiToken;
    // v2.sterclicks.host ya no existe; la web vive en sterclicks.es
    public string $baseUrl = 'https://sterclicks.es/api/piezas';

    public function __construct()
    {
        parent::__construct();

        $this->apiToken = (string) env('sterclicks.stockApiToken', '');
        $this->baseUrl  = (string) env('sterclicks.baseUrl', $this->baseUrl);
    }
}
