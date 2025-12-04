<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// CORS preflight request'i handle et
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    // TCMB XML servisinden veri çek
    $url = 'https://www.tcmb.gov.tr/kurlar/today.xml';
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ]
    ]);
    
    $xmlContent = file_get_contents($url, false, $context);
    
    if ($xmlContent === false) {
        throw new Exception('TCMB servisine bağlanılamadı');
    }
    
    // XML'i parse et
    $xml = simplexml_load_string($xmlContent);
    
    if ($xml === false) {
        throw new Exception('XML parse edilemedi');
    }
    
    // Para birimi parametresini al
    $currency = $_GET['currency'] ?? 'USD';
    
    // XML'den kur bilgisini çıkar
    $rate = null;
    foreach ($xml->Currency as $currencyElement) {
        $currencyCode = (string)$currencyElement->CurrencyCode;
        
        if ($currencyCode === $currency) {
            // Önce efektif alış kuru (BanknoteBuying) dene
            $banknoteBuying = (string)$currencyElement->BanknoteBuying;
            if (!empty($banknoteBuying)) {
                $rate = floatval($banknoteBuying);
                break;
            }
            
            // Efektif alış kuru yoksa forex alış kuru kullan
            $forexBuying = (string)$currencyElement->ForexBuying;
            if (!empty($forexBuying)) {
                $rate = floatval($forexBuying);
                break;
            }
        }
    }
    
    if ($rate !== null) {
        echo json_encode([
            'success' => true,
            'currency' => $currency,
            'rate' => $rate,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => "Kur bilgisi bulunamadı: $currency"
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Hata: ' . $e->getMessage()
    ]);
}
?> 