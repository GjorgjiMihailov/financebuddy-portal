<?php

namespace App\Http\Controllers;

use App\Services\NbrmExchangeRateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExchangeRateController extends Controller
{
    public function show(Request $request, NbrmExchangeRateService $nbrm): JsonResponse
    {
        $currency = strtoupper($request->string('currency', 'EUR'));
        $date     = $request->string('date', now()->format('Y-m-d'));

        $rate = $nbrm->getRate($currency, $date);

        return response()->json([
            'currency' => $currency,
            'date'     => $date,
            'rate'     => $rate,
        ]);
    }
}
