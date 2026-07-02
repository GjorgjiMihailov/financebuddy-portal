<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class NbrmExchangeRateService
{
    public function getRate(string $currency, ?string $date = null): ?float
    {
        if (strtoupper($currency) === 'MKD') {
            return 1.0;
        }

        $date ??= now()->format('Y-m-d');
        $formattedDate = Carbon::parse($date)->format('Y-m-d');

        try {
            $response = Http::timeout(8)->get('https://www.nbrm.mk/KLService.asmx/GetExchangeRate', [
                'startDate' => $formattedDate,
                'endDate'   => $formattedDate,
                'language'  => 'mk',
            ]);

            if (! $response->ok()) {
                return null;
            }

            $xml = @simplexml_load_string($response->body());
            if ($xml === false) {
                return null;
            }

            $xml->registerXPathNamespace('diffgr', 'urn:schemas-microsoft-com:xml-diffgram-v1');
            $tables = $xml->xpath('//*[local-name()="Table"]');

            if (empty($tables)) {
                return null;
            }

            foreach ($tables as $table) {
                $code = (string) ($table->CurrencyCode ?? $table->currencyCode ?? '');
                if (strtoupper($code) === strtoupper($currency)) {
                    $middle = (float) ($table->Middle ?? $table->middle ?? 0);
                    return $middle > 0 ? $middle : null;
                }
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }
}
