<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Sterclicks extends BaseConfig
{
    public string $apiToken;
    public string $baseUrl = 'https://v2.sterclicks.host/public/index.php/api/piezas';

    public function __construct()
    {
        parent::__construct();

        $this->apiToken = (string) env('sterclicks.stockApiToken', '');
        $this->baseUrl  = (string) env('sterclicks.baseUrl', $this->baseUrl);
    }
}
