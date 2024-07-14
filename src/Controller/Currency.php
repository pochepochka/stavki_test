<?php

namespace App\Controller;

use App\Service\CurrencyManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Annotation\Route;
class Currency extends AbstractController
{

    private const string CURRENCY_REGEXP = '~^[a-zA-Z]{3,}$~';
    /**
     * @param string|null $base
     * @return JsonResponse
     */
    #[Route('/currency/rates', name: 'app_currency_rates', methods: ['GET'])]
    public function rates(
        CurrencyManager $currencyManager,
        #[MapQueryParameter(filter: \FILTER_VALIDATE_REGEXP, options: ['regexp' => self::CURRENCY_REGEXP])] string $base = 'USD'
    ): JsonResponse
    {
        $base = strtolower($base);
        try {
            $currencies = $currencyManager->getCurrencies($base);
            return $this->json(array_values(array_map(function($a){
                return ['rate' => $a['rate'], 'code' => $a['code']];
            }, $currencies)));
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

    }

    /**
     * @param string $from
     * @param string $to
     * @param float $amount
     * @param CurrencyManager $currencyManager
     * @return JsonResponse
     */
    #[Route('/currency/convert', name: 'app_currency_convert', methods: ['GET'])]
    public function convert(
        #[MapQueryParameter(filter: \FILTER_VALIDATE_REGEXP, options: ['regexp' => self::CURRENCY_REGEXP])] string $from,
        #[MapQueryParameter(filter: \FILTER_VALIDATE_REGEXP, options: ['regexp' => self::CURRENCY_REGEXP])] string $to,
        #[MapQueryParameter(filter: \FILTER_VALIDATE_FLOAT)] float $amount,
        CurrencyManager $currencyManager
    ): JsonResponse
    {
        $from = strtolower($from);
        $to = strtolower($to);
        try {
            $currencies = $currencyManager->getCurrencies($to);
            if (empty($currencies[$to])) {
                return $this->json(['error' => 'Unknown currency: ' . $to], Response::HTTP_BAD_REQUEST);
            }
            $result = [
                'amount' => $amount/$currencies[$from]['rate'],
                'currency_from' => [
                    'rate' => $currencies[$from]['rate'],
                    'code' => $currencies[$from]['code']
                ],
                'currency_to' => [
                    'rate' => $currencies[$to]['rate'],
                    'code' => $currencies[$to]['code']
                ]
            ];
            return $this->json($result);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}
