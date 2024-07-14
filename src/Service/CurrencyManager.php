<?php

namespace App\Service;

use Symfony\Component\Filesystem\Filesystem;

class CurrencyManager
{

    private const array API_URLS = [
        'coinpaprika' => 'https://api.coinpaprika.com/v1/exchanges/coinbase/markets?quotes=USD',
        'floatrates' => 'https://www.floatrates.com/daily/usd.json'
    ];

    private const string FILE_PATH = '/var/currencies.json';

    private string $projectDir;
    public function __construct(string $projectDir)
    {
        $this->projectDir = $projectDir;
    }

    public function uploadCurrencies()
    {
        $filesystem = new Filesystem();
        $fullPath = $this->projectDir . self::FILE_PATH;
        if (!$filesystem->exists($fullPath)) {
            $filesystem->mkdir(dirname($fullPath), 0700);
            $filesystem->dumpFile($fullPath, '');
            $filesystem->chmod($fullPath, 0600);
        }
        $result = [];//[currency_key => ['code' => string, 'name' => string, 'rate' => float, 'from' => string, 'origin_data' => array]], ...]
        foreach (self::API_URLS as $type => $url) {
            //с библиотеками удобнее обработать различные ошибки, но в связи с тем, что это тестовое задание, нет смысла заморачиваться
            $data = json_decode(file_get_contents($url), true);

            //сделано методами, хотя по хорошему надо вытащить в отдельные классы Стратегии с вызовом из Фабрики стратегий
            $methodName = 'parse' . ucfirst($type);

            //будем считать что приоритет задан массивом self::API_URLS
            //сами методы отдают данные уже в требуемом формате
            $result += $this->$methodName($data);
        }
        $filesystem->dumpFile($fullPath, json_encode($result));
    }

    /**
     * @param array $data
     * @return array [currency_key => ['code' => string, 'name' => string, 'rate' => float, 'from' => string, 'origin_data' => array]], ...]
     */
    private function parseCoinpaprika(array $data): array
    {
        $result = [];
        foreach ($data as $d) {
            if ($d['quote_currency_name'] != 'US Dollars') {
                //будем рассматривать только относительно USD
                continue;
            }
            $code = explode('/', $d['pair'])[0];
            $result[strtolower($code)] = [
                'code' => $code,
                'name' => $d['base_currency_name'],
                'rate' => $d['quotes']['USD']['price'],
                'from' => 'coinpaprika',
                'origin_data' => $d
            ];
        }
        return $result;
    }


    /**
     * @param array $data
     * @return array [currency_key => ['code' => string, 'name' => string, 'rate' => float, 'from' => string, 'origin_data' => array]], ...]
     */
    private function parseFloatrates(array $data): array
    {
        $result = [
            'usd' => [
                'code' => 'USD',
                'name' => 'US Dollars',
                'rate' => 1,
                'from' => 'floatrates',
                'origin_data' => []
            ]
        ];
        foreach ($data as $d) {
            $result[strtolower($d['code'])] = [
                'code' => $d['code'],
                'name' => $d['name'],
                'rate' => $d['rate'],
                'from' => 'floatrates',
                'origin_data' => $d
            ];
        }
        return $result;
    }

    public function getCurrencies(string $base = 'USD'): array
    {
        $base = strtolower($base);
        $filesystem = new Filesystem();
        $fullPath = $this->projectDir . self::FILE_PATH;
        $data = json_decode($filesystem->readFile($fullPath), true);
        if ($base == 'usd') {
            return $data;
        } else {
            if (!isset($data[$base])) {
                throw new \Exception('Unknown currency: ' . $base);
            }
            $result = [];
            $main_cur = $data[$base];
            foreach ($data as $k => $d) {
                $result[$k] = [
                    'rate' => $d['rate']/$main_cur['rate']
                ] + $d;
            }
            return $result;
        }
    }
}
