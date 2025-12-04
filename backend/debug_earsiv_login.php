<?php
// e-Arşiv portalı login debug script

$usercode = $_GET['usercode'] ?? '60400544';
$password = $_GET['password'] ?? '991570';

echo "e-Arşiv Portalı Login Debug\n";
echo "==========================\n";
echo "Kullanıcı Kodu: " . $usercode . "\n";
echo "Şifre: " . str_repeat('*', strlen($password)) . "\n\n";

try {
    // e-Arşiv portalı login URL'si
    $url = 'https://earsivportal.efatura.gov.tr/earsiv-services/assos-login';
    
    // Login parametreleri
    $postData = http_build_query([
        'assoscmd' => 'anologin',
        'rtype' => 'json',
        'userid' => $usercode,
        'sifre' => $password,
        'sifre2' => $password,
        'parola' => '1'
    ]);
    
    echo "İstek URL'si: " . $url . "\n";
    echo "İstek Verisi: " . $postData . "\n\n";
    
    // Cookie dosyası oluştur
    $cookieFile = tempnam(sys_get_temp_dir(), 'earsiv_cookies');
    
    $headers = [
        'Content-Type: application/x-www-form-urlencoded',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
    ];
    
    echo "Header'lar:\n";
    foreach ($headers as $header) {
        echo "  " . $header . "\n";
    }
    echo "\n";
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_COOKIEJAR => $cookieFile,
        CURLOPT_COOKIEFILE => $cookieFile,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HEADER => true // Header'ları da al
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    // Header ve body'yi ayır
    $responseHeaders = substr($response, 0, $headerSize);
    $responseBody = substr($response, $headerSize);
    
    echo "HTTP Kodu: " . $httpCode . "\n";
    if ($curlError) {
        echo "CURL Hatası: " . $curlError . "\n";
    }
    
    echo "\nResponse Header'lar:\n";
    echo $responseHeaders . "\n";
    
    echo "\nResponse Body:\n";
    echo $responseBody . "\n";
    
    // JSON çözümle
    $responseData = json_decode($responseBody, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "\nJSON olarak çözümlendi:\n";
        echo json_encode($responseData, JSON_PRETTY_PRINT) . "\n";
        
        if (isset($responseData['token'])) {
            echo "\nToken bulundu: " . $responseData['token'] . "\n";
        } else {
            echo "\nToken bulunamadı!\n";
        }
    } else {
        echo "\nJSON çözümleme hatası: " . json_last_error_msg() . "\n";
        
        // HTML response'tan token ara
        if (preg_match('/token=([a-f0-9]+)/', $responseBody, $matches)) {
            echo "\nHTML response'tan token bulundu: " . $matches[1] . "\n";
        } else {
            echo "\nHTML response'ta da token bulunamadı.\n";
        }
    }
    
    // Cookie dosyasını temizle
    if (file_exists($cookieFile)) {
        unlink($cookieFile);
    }
    
} catch (Exception $e) {
    echo "Hata oluştu: " . $e->getMessage() . "\n";
}
?>