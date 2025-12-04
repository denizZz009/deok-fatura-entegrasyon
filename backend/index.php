<?php
// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require __DIR__ . '/vendor/autoload.php';

// Optimize edilmiş fonksiyonları yükle
require_once __DIR__ . '/optimized_functions.php';

// Mail fonksiyonlarını yükle
require_once __DIR__ . '/mail_functions.php';

use Mlevent\Fatura\Gib;
use Mlevent\Fatura\Models\InvoiceModel;
use Mlevent\Fatura\Models\InvoiceItemModel;
use Mlevent\Fatura\Enums\Currency;
use Mlevent\Fatura\Enums\InvoiceType;
use Mlevent\Fatura\Enums\Unit;

$path = $_GET['path'] ?? '';

// Eğer path boşsa ve bu bir API isteği değilse, hata döndürme
if (empty($path)) {
    // Ana sayfa yüklemesi için sessizce çık
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) && empty($_POST) && empty(file_get_contents('php://input'))) {
        http_response_code(200);
        exit();
    }
}

// POST verisini oku
$input = json_decode(file_get_contents('php://input'), true);

// Session güvenliği ve brute force koruması
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Session güvenliği: IP ve user-agent kontrolü
if (isset($_SESSION['ip']) && ($_SESSION['ip'] !== ($_SERVER['REMOTE_ADDR'] ?? '') || $_SESSION['ua'] !== ($_SERVER['HTTP_USER_AGENT'] ?? ''))) {
    session_unset(); session_destroy();
    die(json_encode(['success' => false, 'message' => 'Oturum güvenliği nedeniyle sonlandırıldı.']));
}
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1200)) {
    session_unset(); session_destroy();
    die(json_encode(['success' => false, 'message' => 'Oturum süresi doldu, tekrar giriş yapın.']));
}
$_SESSION['last_activity'] = time();

// Giriş kontrolü helper fonksiyonu
function is_logged_in() {
    return isset($_SESSION['usercode']) && isset($_SESSION['password']);
}

// Brute force koruması
function is_brute_forced() {
    if (!isset($_SESSION['brute_force'])) $_SESSION['brute_force'] = [];
    $now = time();
    $_SESSION['brute_force'] = array_filter($_SESSION['brute_force'], fn($t) => $now - $t < 600);
    return count($_SESSION['brute_force']) >= 10;
}
function add_brute_force() {
    $_SESSION['brute_force'][] = time();
}

ini_set('display_errors', 0);
ini_set('log_errors', 0);
error_reporting(0);

function is_valid_uuid($uuid) {
    return preg_match('/^[a-f0-9\-]{36}$/i', $uuid);
}

// Tutarı Türkçe yazıya çeviren yardımcı fonksiyon
function convertNumberGroup($num) {
    if ($num == 0) return '';
    
    $ones = ['', 'Bir', 'İki', 'Üç', 'Dört', 'Beş', 'Altı', 'Yedi', 'Sekiz', 'Dokuz'];
    $tens = ['', 'On', 'Yirmi', 'Otuz', 'Kırk', 'Elli', 'Altmış', 'Yetmiş', 'Seksen', 'Doksan'];
    $hundreds = ['', 'Yüz', 'İkiYüz', 'ÜçYüz', 'DörtYüz', 'BeşYüz', 'AltıYüz', 'YediYüz', 'SekizYüz', 'DokuzYüz'];

    $result = '';

    // Yüzler
    $hundred = intval($num / 100);
    if ($hundred > 0) {
        $result .= $hundreds[$hundred];
    }

    // Onlar ve birler
    $remainder = $num % 100;
    if ($remainder > 0) {
        if ($remainder < 10) {
            $result .= $ones[$remainder];
        } else if ($remainder < 20) {
            $specialTens = ['On', 'OnBir', 'Onİki', 'OnÜç', 'OnDört', 'OnBeş', 'OnAltı', 'OnYedi', 'OnSekiz', 'OnDokuz'];
            $result .= $specialTens[$remainder - 10];
        } else {
            $ten = intval($remainder / 10);
            $one = $remainder % 10;
            $result .= $tens[$ten];
            if ($one > 0) $result .= $ones[$one];
        }
    }

    return $result;
}

// Tutarı Türkçe yazıya çeviren ana fonksiyon
function numberToTurkishText($number) {
    if ($number == 0) return 'Sıfır';

    $integerPart = intval($number);
    $decimalPart = round(($number - $integerPart) * 100);

    $result = '';

    // Trilyonlar
    $trillions = intval($integerPart / 1000000000000);
    if ($trillions > 0) {
        $result .= convertNumberGroup($trillions) . 'Trilyon';
    }

    // Milyarlar
    $billions = intval(($integerPart % 1000000000000) / 1000000000);
    if ($billions > 0) {
        $result .= convertNumberGroup($billions) . 'Milyar';
    }

    // Milyonlar
    $millions = intval(($integerPart % 1000000000) / 1000000);
    if ($millions > 0) {
        $result .= convertNumberGroup($millions) . 'Milyon';
    }

    // Binler
    $thousands = intval(($integerPart % 1000000) / 1000);
    if ($thousands > 0) {
        if ($thousands == 1) {
            $result .= 'Bin';
        } else {
            $result .= convertNumberGroup($thousands) . 'Bin';
        }
    }

    // Yüzler ve altı
    $remainder = $integerPart % 1000;
    if ($remainder > 0) {
        $result .= convertNumberGroup($remainder);
    }

    // Para birimi
    $result .= 'TürkLirası';

    // Kuruş
    if ($decimalPart > 0) {
        $result .= 've' . convertNumberGroup($decimalPart) . 'Kuruş';
    }

    return $result;
}

function get_gib_instance() {
    if (isset($_SESSION['usercode']) && isset($_SESSION['password'])) {
        return (new Gib)->login($_SESSION['usercode'], $_SESSION['password']);
    } else {
        throw new Exception('Giriş yapılmamış.');
    }
}

// e-Arşiv portalına istek gönderen yardımcı fonksiyon
function makeEarsivRequest($cmd, $data) {
    try {
        // e-Arşiv portalı URL'si
        $url = 'https://earsivportal.efatura.gov.tr/earsiv-services/dispatch';
        
        // Token'ı session'dan al (login sırasında kaydedilmiş olmalı)
        $token = $_SESSION['earsiv_token'] ?? '';
        $cookieFile = $_SESSION['earsiv_cookie_file'] ?? '';
        
        // Debug: Session bilgilerini logla
        error_log("makeEarsivRequest - CMD: $cmd, Token: " . ($token ? 'var' : 'yok') . ", Cookie: " . ($cookieFile ? 'var' : 'yok'));
        
        // Geçici token oluştur (eğer session'da yoksa)
        if (empty($token)) {
            $token = md5(uniqid() . time());
            error_log("makeEarsivRequest - Geçici token oluşturuldu: $token");
        }
        
        // Call ID oluştur
        $callid = uniqid() . '-' . rand(1, 9);
        
        // POST verisi
        $postData = http_build_query([
            'cmd' => $cmd,
            'callid' => $callid,
            'pageName' => 'RG_SMSONAY',
            'token' => $token,
            'jp' => json_encode($data)
        ]);
        
        $headers = [
            'Accept: application/json, text/javascript, */*; q=0.01',
            'Accept-Language: tr,en-US;q=0.9,en;q=0.8',
            'Cache-Control: no-cache',
            'Connection: keep-alive',
            'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
            'Origin: https://earsivportal.efatura.gov.tr',
            'Pragma: no-cache',
            'Referer: https://earsivportal.efatura.gov.tr/',
            'Sec-Fetch-Dest: empty',
            'Sec-Fetch-Mode: cors',
            'Sec-Fetch-Site: same-origin',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36',
            'X-Requested-With: XMLHttpRequest'
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true
        ]);
        
        // Cookie dosyası varsa kullan
        if (!empty($cookieFile) && file_exists($cookieFile)) {
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            throw new Exception('CURL hatası: ' . $curlError);
        }
        
        if ($httpCode !== 200) {
            throw new Exception('HTTP hatası: ' . $httpCode);
        }
        
        if (!$response) {
            throw new Exception('Boş yanıt alındı.');
        }
        
        // JSON yanıtını çözümle
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('JSON parse hatası: ' . json_last_error_msg());
        }
        
        // Session timeout kontrolü
        if (isset($data['error']) && $data['error'] == '1' && 
            isset($data['messages']) && is_array($data['messages'])) {
            foreach ($data['messages'] as $message) {
                if (isset($message['text']) && 
                    (strpos($message['text'], 'Oturum zamanaşımına uğradı') !== false ||
                     strpos($message['text'], 'yeni oturum açınız') !== false)) {
                    
                    // Session timeout - yeniden giriş yap
                    error_log("e-Arşiv session timeout - yeniden giriş yapılıyor");
                    
                    try {
                        $earsivLogin = login_to_earsiv_portal($_SESSION['usercode'], $_SESSION['password']);
                        if ($earsivLogin['success']) {
                            // Yeniden giriş başarılı - isteği tekrar gönder (sadece bir kez)
                            error_log("e-Arşiv yeniden giriş başarılı - istek tekrar gönderiliyor");
                            
                            // Yeni token ve cookie ile tekrar dene
                            $token = $_SESSION['earsiv_token'];
                            $cookieFile = $_SESSION['earsiv_cookie_file'];
                            
                            // POST verisini yeniden oluştur
                            $postData = http_build_query([
                                'cmd' => $cmd,
                                'callid' => uniqid() . '-' . rand(1, 9),
                                'pageName' => 'RG_SMSONAY',
                                'token' => $token,
                                'jp' => json_encode($data)
                            ]);
                            
                            // İsteği tekrar gönder
                            $ch = curl_init();
                            curl_setopt_array($ch, [
                                CURLOPT_URL => $url,
                                CURLOPT_POST => true,
                                CURLOPT_POSTFIELDS => $postData,
                                CURLOPT_HTTPHEADER => $headers,
                                CURLOPT_RETURNTRANSFER => true,
                                CURLOPT_SSL_VERIFYPEER => false,
                                CURLOPT_SSL_VERIFYHOST => false,
                                CURLOPT_TIMEOUT => 30,
                                CURLOPT_FOLLOWLOCATION => true,
                                CURLOPT_COOKIEFILE => $cookieFile,
                                CURLOPT_COOKIEJAR => $cookieFile
                            ]);
                            
                            $retryResponse = curl_exec($ch);
                            $retryHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                            curl_close($ch);
                            
                            if ($retryHttpCode === 200 && $retryResponse) {
                                $retryData = json_decode($retryResponse, true);
                                if (json_last_error() === JSON_ERROR_NONE) {
                                    return $retryData;
                                }
                            }
                        }
                    } catch (Exception $e) {
                        error_log("e-Arşiv yeniden giriş hatası: " . $e->getMessage());
                    }
                    
                    throw new Exception('e-Arşiv oturumu sona erdi ve yeniden giriş yapılamadı.');
                }
            }
        }
        
        return $data;
        
    } catch (Exception $e) {
        error_log("e-Arşiv istek hatası ($cmd): " . $e->getMessage());
        throw $e;
    }
}

// e-Arşiv portalına özel login fonksiyonu
function login_to_earsiv_portal($usercode, $password, $isTest = false) {
    try {
        // e-Arşiv portalı login URL'si
        $url = 'https://earsivportal.efatura.gov.tr/earsiv-services/assos-login';
        if ($isTest) {
            $url = 'https://earsivportaltest.efatura.gov.tr/earsiv-services/assos-login';
        }
        
        // Login parametreleri
        $postData = http_build_query([
            'assoscmd' => 'anologin',
            'rtype' => 'json',
            'userid' => $usercode,
            'sifre' => $password,
            'sifre2' => $password,
            'parola' => '1'
        ]);
        
        // Cookie dosyası oluştur
        $cookieFile = tempnam(sys_get_temp_dir(), 'earsiv_cookies');
        
        $headers = [
            'Content-Type: application/x-www-form-urlencoded',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ];
        
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
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        // CURL hatası kontrolü
        if ($curlError) {
            throw new Exception('CURL hatası: ' . $curlError);
        }
        
        if ($httpCode !== 200) {
            throw new Exception('e-Arşiv portalına bağlanılamadı. HTTP kodu: ' . $httpCode);
        }
        
        // Response'u JSON olarak çözümle
        $responseData = json_decode($response, true);
        
        // JSON çözümleme hatası kontrolü
        if (json_last_error() !== JSON_ERROR_NONE) {
            // JSON değilse, HTML response'tan token'ı çıkar
            if (preg_match('/token=([a-f0-9]+)/', $response, $matches)) {
                $token = $matches[1];
            } else {
                // Detaylı hata ayıklama için response'u logla
                error_log("e-Arşiv portalı response (non-JSON): " . substr($response, 0, 500));
                throw new Exception('e-Arşiv portalından token alınamadı. Response: ' . substr($response, 0, 200));
            }
        } else {
            // JSON response ise token'ı oradan al
            if (isset($responseData['token'])) {
                $token = $responseData['token'];
            } else {
                // Token başka bir alanda olabilir, tüm response'u logla
                error_log("e-Arşiv portalı JSON response: " . json_encode($responseData));
                throw new Exception('e-Arşiv portalı JSON response\'unda token bulunamadı.');
            }
        }
        
        // Cookie dosyasını session'a kaydet
        $_SESSION['earsiv_cookie_file'] = $cookieFile;
        $_SESSION['earsiv_token'] = $token;
        return ['success' => true, 'token' => $token, 'cookie_file' => $cookieFile];
        
    } catch (Exception $e) {
        // Oluşan cookie dosyasını temizle
        if (isset($cookieFile) && file_exists($cookieFile)) {
            unlink($cookieFile);
        }
        throw $e;
    }
}

// e-Arşiv portalından logout fonksiyonu
function logout_from_earsiv_portal() {
    try {
        // Session'da cookie dosyası ve token var mı kontrol et
        if (!isset($_SESSION['earsiv_cookie_file']) || !isset($_SESSION['earsiv_token'])) {
            // Session verisi yoksa bile cookie dosyası var mı kontrol et
            $sessionFiles = array_filter(glob(sys_get_temp_dir() . '/earsiv_cookies*'), 'is_file');
            if (count($sessionFiles) > 0) {
                // Eski cookie dosyalarını temizle
                foreach ($sessionFiles as $file) {
                    if (is_writable($file)) {
                        unlink($file);
                    }
                }
            }
            return ['success' => true, 'message' => 'e-Arşiv oturumu zaten kapalı.'];
        }
        
        $cookieFile = $_SESSION['earsiv_cookie_file'];
        $token = $_SESSION['earsiv_token'];
        
        // e-Arşiv portalı logout URL'si
        $url = 'https://earsivportal.efatura.gov.tr/earsiv-services/assos-login';
        
        // Logout parametreleri
        $postData = http_build_query([
            'assoscmd' => 'logout',
            'rtype' => 'json',
            'token' => $token
        ]);
        
        $headers = [
            'Content-Type: application/x-www-form-urlencoded',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_COOKIEFILE => $cookieFile,
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        // Cookie dosyasını sil
        if (file_exists($cookieFile)) {
            unlink($cookieFile);
        }
        
        // Session değişkenlerini temizle
        unset($_SESSION['earsiv_cookie_file']);
        unset($_SESSION['earsiv_token']);
        
        // CURL hatası kontrolü
        if ($curlError) {
            return ['success' => false, 'message' => 'e-Arşiv oturumu kapatılırken CURL hatası oluştu: ' . $curlError];
        }
        
        if ($httpCode === 200) {
            return ['success' => true, 'message' => 'e-Arşiv oturumu başarıyla kapatıldı.'];
        } else {
            // HTTP 200 olmasa bile işlem başarılı kabul edilebilir
            // Çünkü bazı durumlarda logout sonrası farklı HTTP kodları dönebilir
            return ['success' => true, 'message' => 'e-Arşiv oturumu kapatıldı. HTTP kodu: ' . $httpCode];
        }
        
    } catch (Exception $e) {
        // Cookie dosyasını sil (varsa)
        if (isset($_SESSION['earsiv_cookie_file']) && file_exists($_SESSION['earsiv_cookie_file'])) {
            unlink($_SESSION['earsiv_cookie_file']);
        }
        // Session değişkenlerini temizle
        unset($_SESSION['earsiv_cookie_file']);
        unset($_SESSION['earsiv_token']);
        
        return ['success' => false, 'message' => 'e-Arşiv oturumu kapatılırken hata oluştu: ' . $e->getMessage()];
    }
}

function getFullRecipientDataFromEarsiv($vknTckn) {
    try {
        // Mevcut GIB oturumunu kullan
        $gib = get_gib_instance();
        
        // E-arşiv portalına doğrudan istek gönder
        $url = 'https://earsivportal.efatura.gov.tr/earsiv-services/dispatch';
        
        // Basit token oluştur (genellikle session tabanlı)
        $callid = uniqid() . '-' . rand(1, 9);
        
        $postData = http_build_query([
            'cmd' => 'SICIL_VEYA_MERNISTEN_BILGILERI_GETIR',
            'callid' => $callid,
            'pageName' => 'RG_BASITFATURA',
            'token' => '', // Token boş bırakılabilir, session cookie'leri yeterli
            'jp' => json_encode(['vknTcknn' => $vknTckn])
        ]);
        
        $headers = [
            'Accept: application/json, text/javascript, */*; q=0.01',
            'Accept-Language: tr,en-US;q=0.9,en;q=0.8',
            'Cache-Control: no-cache',
            'Connection: keep-alive',
            'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
            'Origin: https://earsivportal.efatura.gov.tr',
            'Pragma: no-cache',
            'Referer: https://earsivportal.efatura.gov.tr/',
            'Sec-Fetch-Dest: empty',
            'Sec-Fetch-Mode: cors',
            'Sec-Fetch-Site: same-origin',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_COOKIEJAR => tempnam(sys_get_temp_dir(), 'earsiv_cookies'),
            CURLOPT_COOKIEFILE => tempnam(sys_get_temp_dir(), 'earsiv_cookies')
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            throw new Exception('CURL hatası: ' . $curlError);
        }
        
        if ($httpCode !== 200) {
            throw new Exception('HTTP hatası: ' . $httpCode);
        }
        
        if (!$response) {
            throw new Exception('Boş yanıt alındı.');
        }
        
        // JSON yanıtını çözümle
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('JSON parse hatası: ' . json_last_error_msg());
        }
        
        
        
        if (!$data) {
            throw new Exception('Geçersiz JSON yanıtı.');
        }
        
        // Başarılı yanıt kontrolü
        if (isset($data['data']) && is_array($data['data'])) {
            return parseEarsivRecipientData($data['data']);
        } else if (isset($data['messages']) && is_array($data['messages'])) {
            // Hata mesajları varsa
            $errorMsg = implode(', ', $data['messages']);
            throw new Exception('E-arşiv hatası: ' . $errorMsg);
        } else {
            throw new Exception('Beklenmeyen yanıt formatı.');
        }
        
    } catch (Exception $e) {

        return null;
    }
}



function parseEarsivRecipientData($data) {
    if (!$data || !is_array($data)) {
        return null;
    }
    
    $result = [];
    
    // E-arşiv portalından gelen veriyi standart formata çevir
    // Farklı alan adlarını kontrol et
    $fieldMappings = [
        'aliciUnvan' => ['unvan', 'ticariUnvan', 'unvanAdi'],
        'aliciAdi' => ['ad', 'adi', 'isim'],
        'aliciSoyadi' => ['soyad', 'soyadi'],
        'vergiDairesi' => ['vergiDairesi', 'vergiDairesiAdi', 'vd'],
        'adres' => ['adres', 'adresDetay', 'tam_adres'],
        'mahalleSemtIlce' => ['mahalle', 'mahalleSemt', 'ilce'],
        'sehir' => ['sehir', 'il', 'sehirAdi'],
        'postaKodu' => ['postaKodu', 'pk'],
        'tel' => ['telefon', 'tel', 'telefonNo'],
        'eposta' => ['eposta', 'email', 'mail']
    ];
    
    foreach ($fieldMappings as $targetField => $sourceFields) {
        foreach ($sourceFields as $sourceField) {
            if (isset($data[$sourceField]) && !empty($data[$sourceField])) {
                $result[$targetField] = trim($data[$sourceField]);
                break; // İlk bulunan değeri kullan
            }
        }
    }
    
    // Ülke varsayılan olarak Türkiye
    if (!isset($result['ulke']) || empty($result['ulke'])) {
        $result['ulke'] = 'Türkiye';
    }
    

    
    return $result;
}

// Ana switch-case yapısı
switch ($path) {

        
    case 'login':
        header('Content-Type: application/json');
        if (is_brute_forced()) {
            die(json_encode(['success' => false, 'message' => 'Çok fazla hatalı giriş denemesi. Lütfen 10 dakika sonra tekrar deneyin.']));
        }
        $usercode = $input['usercode'] ?? '';
        $password = $input['password'] ?? '';
        if (!$usercode || !$password) {
            die(json_encode(['success' => false, 'message' => 'Kullanıcı kodu ve şifre zorunlu!']));
        }
        try {
            // Önce Mlevent kütüphanesi ile giriş yapmayı dene
            $gib = (new Gib)->login($usercode, $password);
            
            // Başarılı olursa e-Arşiv portalına da giriş yap
            try {
                $earsivLogin = login_to_earsiv_portal($usercode, $password);
            } catch (Exception $e) {
                // e-Arşiv portalına giriş başarısız olsa bile ana sisteme giriş başarılıysa
                // kullanıcıyı bilgilendir ve devam et
                error_log("e-Arşiv portalı giriş hatası: " . $e->getMessage());
            }
            
            // Session bilgilerini kaydet
            $_SESSION['usercode'] = $usercode;
            $_SESSION['password'] = $password;
            $_SESSION['ip'] = $_SERVER['REMOTE_ADDR'] ?? '';
            $_SESSION['ua'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $_SESSION['brute_force'] = [];
            
            $message = 'Giriş başarılı!';
            if (isset($earsivLogin) && !$earsivLogin['success']) {
                $message .= ' (e-Arşiv portalı bağlantısı başarısız oldu, ancak sistem kullanılabilir.)';
            }
            
            echo json_encode(['success' => true, 'message' => $message]);
        } catch (Exception $e) {
            add_brute_force();
            echo json_encode(['success' => false, 'message' => 'Giriş başarısız: ' . $e->getMessage()]);
        }
        break;

    case 'logout':
        header('Content-Type: application/json');
        
        // Session cache'i temizle
        clearSessionCache();
        
        // e-Arşiv portalından çıkış yap
        $earsivLogout = logout_from_earsiv_portal();
        
        // Session'ı temizle
        session_unset();
        session_destroy();
        
        if ($earsivLogout['success']) {
            echo json_encode(['success' => true, 'message' => 'Çıkış başarılı! ' . $earsivLogout['message']]);
        } else {
            // e-Arşiv logout hatası olsa bile ana logout başarılı
            echo json_encode(['success' => true, 'message' => 'Çıkış başarılı! ' . $earsivLogout['message']]);
        }
        break;

    case 'get_recipient_info':
        header('Content-Type: application/json');
        if (!is_logged_in()) {
            die(json_encode(['success' => false, 'message' => 'Önce giriş yapmalısınız!']));
        }
        $vknTckn = $_GET['vknTckn'] ?? '';
        if (strlen($vknTckn) < 10 || strlen($vknTckn) > 11) {
            die(json_encode(['success' => false, 'message' => 'Geçersiz VKN/TCKN.']));
        }
        try {
            // Önce mevcut GIB kütüphanesi ile deneyelim
            $gib = get_gib_instance();
            $data = $gib->getRecipientData($vknTckn);
            
            // Eğer veri boş veya sadece vergi dairesi varsa, e-arşiv portalından tam bilgileri çekelim
            if (empty($data) || (isset($data['vergiDairesi']) && count($data) <= 2)) {
                $fullData = getFullRecipientDataFromEarsiv($vknTckn);
                if (!empty($fullData)) {
                    // Mevcut veri ile birleştir
                    $data = array_merge($data ?: [], $fullData);
                }
            }
            
            // Veri formatını standartlaştır
            if (!empty($data)) {
                $standardizedData = [];
                
                // Alan adlarını standartlaştır
                $standardizedData['unvan'] = $data['aliciUnvan'] ?? $data['unvan'] ?? '';
                $standardizedData['adi'] = $data['aliciAdi'] ?? $data['adi'] ?? $data['ad'] ?? '';
                $standardizedData['soyadi'] = $data['aliciSoyadi'] ?? $data['soyadi'] ?? $data['soyad'] ?? '';
                $standardizedData['vergiDairesi'] = $data['vergiDairesi'] ?? '';
                $standardizedData['adres'] = $data['adres'] ?? '';
                $standardizedData['mahalleSemtIlce'] = $data['mahalleSemtIlce'] ?? $data['mahalle'] ?? '';
                $standardizedData['sehir'] = $data['sehir'] ?? '';
                $standardizedData['ulke'] = $data['ulke'] ?? 'Türkiye';
                $standardizedData['postaKodu'] = $data['postaKodu'] ?? '';
                $standardizedData['tel'] = $data['tel'] ?? $data['telefon'] ?? '';
                $standardizedData['eposta'] = $data['eposta'] ?? '';
                
                $data = $standardizedData;
            }
            
            if (empty($data) || empty(array_filter($data))) {
                throw new Exception('Mükellef bilgisi bulunamadı.');
            }
            echo json_encode(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Bilgiler alınamadı: ' . $e->getMessage()]);
        }
        break;

    case 'create_invoice':
        header('Content-Type: application/json');
        if (!isset($_SESSION['usercode'])) {
            die(json_encode(['success' => false, 'message' => 'Önce giriş yapmalısınız!']));
        }
        try {
            $gib = (new Gib)->login($_SESSION['usercode'], $_SESSION['password']);

            // Tarih ve Saat formatlarını düzenle
            $faturaTarihi = DateTime::createFromFormat('Y-m-d', $input['tarih'])->format('d/m/Y');
            $faturaSaati  = $input['saat'] . ':00';

            // Güncelleme modu kontrolü
            $isUpdate = !empty($input['uuid']);
            $invoiceParams = [
                'tarih'           => $faturaTarihi,
                'saat'            => $faturaSaati,
                'paraBirimi'      => Currency::from($input['paraBirimi']),
                'dovizKuru'       => (float)($input['dovizKuru'] ?? 0),
                'faturaTipi'      => InvoiceType::from(strtoupper($input['faturaTipi'])),
                'vknTckn'         => $input['vknTckn'],
            ];
            
            // Güncelleme modunda UUID ve belge numarası ekle
            if ($isUpdate) {
                $invoiceParams['uuid'] = $input['uuid'];
                // Belge numarasını da ekle (güncelleme için gerekli)
                if (!empty($input['belgeNumarasi'])) {
                    $invoiceParams['belgeNumarasi'] = $input['belgeNumarasi'];
                }
            }
            
            $invoice = new InvoiceModel(
                ...$invoiceParams,
                vergiDairesi:    $input['vergiDairesi'] ?? '',
                aliciUnvan:      $input['aliciUnvan'] ?? '',
                aliciAdi:        $input['aliciAdi'] ?? '',
                aliciSoyadi:     $input['aliciSoyadi'] ?? '',
                binaAdi:         $input['binaAdi'] ?? '',
                binaNo:          $input['binaNo'] ?? '',
                kapiNo:          $input['kapiNo'] ?? '',
                kasabaKoy:       $input['kasabaKoy'] ?? '',
                mahalleSemtIlce: $input['mahalleSemtIlce'] ?? '',
                sehir:           $input['sehir'] ?? '',
                ulke:            $input['ulke'],
                postaKodu:       $input['postaKodu'] ?? '',
                adres:           $input['adres'] ?? '',
                tel:             $input['tel'] ?? '',
                fax:             $input['fax'] ?? '',
                eposta:          $input['eposta'] ?? '',
                not:             $input['notlar'] ?? '',
                siparisNumarasi: $input['siparisNumarasi'] ?? '',
                siparisTarihi:   $input['siparisTarihi'] ?? '',
                irsaliyeNumarasi:$input['irsaliyeNumarasi'] ?? '',
                irsaliyeTarihi:  $input['irsaliyeTarihi'] ?? '',
                fisNo:           $input['fisNo'] ?? '',
                fisTarihi:       $input['fisTarihi'] ?? '',
                fisSaati:        $input['fisSaati'] ?? '',
                fisTipi:         $input['fisTipi'] ?? '',
                zRaporNo:        $input['zRaporNo'] ?? '',
                okcSeriNo:       $input['okcSeriNo'] ?? ''
            );

            foreach ($input['items'] as $item) {
                $invoice->addItem(
                    new InvoiceItemModel(
                        malHizmet:   $item['malHizmet'],
                        miktar:      (int)$item['miktar'],
                        birim:       Unit::Adet,
                        birimFiyat:  (float)$item['birimFiyat'],
                        kdvOrani:    (int)$item['kdvOrani']
                    )
                );
            }

            if ($gib->createDraft($invoice)) {
                echo json_encode(['success' => true, 'message' => 'Fatura taslağı başarıyla oluşturuldu!', 'uuid' => $invoice->getUuid()]);
            } else {
                throw new Exception('Taslak oluşturulamadı.');
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
        }
        break;

    case 'start_signing':
        header('Content-Type: application/json');
        if (!isset($_SESSION['usercode'])) {
            die(json_encode(['success' => false, 'message' => 'Önce giriş yapmalısınız!']));
        }
        try {
            $gib = (new Gib)->login($_SESSION['usercode'], $_SESSION['password']);
            $oid = $gib->startSmsVerification();
            if (!$oid) {
                throw new Exception('SMS gönderme işlemi başlatılamadı. GİB portalındaki telefon numaranız güncel mi?');
            }
            echo json_encode(['success' => true, 'oid' => $oid]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
        }
        break;

    case 'bulk_sms_start':
        header('Content-Type: application/json');
        if (!is_logged_in()) {
            die(json_encode(['success' => false, 'message' => 'Önce giriş yapmalısınız!']));
        }
        
        try {
            $invoices = $input['invoices'] ?? [];
            if (empty($invoices)) {
                throw new Exception('Onaylanacak fatura seçilmedi.');
            }
            
            // e-Arşiv portalı session'ı kontrol et ve gerekirse yeniden giriş yap
            $needsRelogin = false;
            
            if (empty($_SESSION['earsiv_token']) || empty($_SESSION['earsiv_cookie_file'])) {
                $needsRelogin = true;
                error_log("e-Arşiv session bilgileri eksik - yeniden giriş gerekli");
            } else if (!file_exists($_SESSION['earsiv_cookie_file'])) {
                $needsRelogin = true;
                error_log("e-Arşiv cookie dosyası bulunamadı - yeniden giriş gerekli");
            }
            
            if ($needsRelogin) {
                error_log("e-Arşiv portalına yeniden giriş yapılıyor...");
                $earsivLogin = login_to_earsiv_portal($_SESSION['usercode'], $_SESSION['password']);
                if (!$earsivLogin['success']) {
                    throw new Exception('e-Arşiv portalı oturumu açılamadı: ' . ($earsivLogin['message'] ?? 'Bilinmeyen hata'));
                }
                error_log("e-Arşiv portalına yeniden giriş başarılı");
            }
            
            // Doğrudan SMS göndermeyi dene (telefon numarası GIB'de kayıtlı olmalı)
            $phoneNumber = ''; // Boş bırak, GIB otomatik algılasın
            
            // Önce mevcut GIB sisteminden telefon numarasını almaya çalış
            try {
                $gib = get_gib_instance();
                $oid = $gib->startSmsVerification();
                
                if ($oid) {
                    // GIB SMS başarılı - bu OID'yi kullan
                    $_SESSION['bulk_sms_invoices'] = $invoices;
                    
                    echo json_encode([
                        'success' => true,
                        'oid' => $oid,
                        'message' => 'SMS şifresi gönderildi (GIB üzerinden).'
                    ]);
                    break;
                }
            } catch (Exception $e) {
                error_log("GIB SMS gönderme hatası: " . $e->getMessage());
            }
            
            // GIB başarısız olursa e-Arşiv portalı ile dene
            // Orijinal request'te telefon numarası "5442508818" olarak gözüküyor
            $phoneNumber = '5442508818'; // GIB'de kayıtlı telefon numarası
            
            error_log("e-Arşiv SMS gönderilecek telefon: $phoneNumber");
            
            // SMS şifre gönderme
            $smsResponse = makeEarsivRequest('EARSIV_PORTAL_SMSSIFRE_GONDER', [
                'CEPTEL' => $phoneNumber,
                'KCEPTEL' => false,
                'TIP' => ''
            ]);
            
            if (!$smsResponse || !isset($smsResponse['data']['oid'])) {
                throw new Exception('SMS gönderme başarısız.');
            }
            
            // Fatura bilgilerini session'da sakla
            $_SESSION['bulk_sms_invoices'] = $invoices;
            
            echo json_encode([
                'success' => true,
                'oid' => $smsResponse['data']['oid'],
                'phone' => $phoneNumber,
                'message' => 'SMS şifresi gönderildi.'
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'bulk_sms_verify':
        header('Content-Type: application/json');
        if (!is_logged_in()) {
            die(json_encode(['success' => false, 'message' => 'Önce giriş yapmalısınız!']));
        }
        
        try {
            $smsCode = $input['smsCode'] ?? '';
            $oid = $input['oid'] ?? '';
            $invoices = $_SESSION['bulk_sms_invoices'] ?? [];
            
            if (empty($smsCode) || strlen($smsCode) !== 6) {
                throw new Exception('Geçersiz SMS şifresi.');
            }
            
            if (empty($oid)) {
                throw new Exception('OID bulunamadı.');
            }
            
            if (empty($invoices)) {
                throw new Exception('Onaylanacak fatura bulunamadı.');
            }
            
            // Faturaları e-Arşiv formatına çevir
            $earsivInvoices = [];
            foreach ($invoices as $invoice) {
                $earsivInvoices[] = [
                    'faturaOid' => '',
                    'toplamTutar' => '0',
                    'belgeNumarasi' => $invoice['belgeNumarasi'] ?? '',
                    'aliciVknTckn' => $invoice['aliciVknTckn'] ?? '',
                    'aliciUnvanAdSoyad' => $invoice['aliciUnvanAdSoyad'] ?? '',
                    'saticiVknTckn' => '',
                    'saticiUnvanAdSoyad' => '',
                    'belgeTarihi' => date('d-m-Y'),
                    'belgeTuru' => 'FATURA',
                    'onayDurumu' => 'Onaylanmadı',
                    'ettn' => $invoice['ettn'] ?? '',
                    'talepDurumColumn' => '----------',
                    'iptalItiraz' => '-99',
                    'talepDurum' => '-99'
                ];
            }
            
            // Toplu SMS onay isteği
            $verifyResponse = makeEarsivRequest('0lhozfib5410mp', [
                'SIFRE' => $smsCode,
                'OID' => $oid,
                'OPR' => 1,
                'DATA' => $earsivInvoices
            ]);
            
            if (!$verifyResponse || !isset($verifyResponse['data'])) {
                throw new Exception('SMS onay başarısız.');
            }
            
            $responseData = $verifyResponse['data'];
            
            if ($responseData['sonuc'] !== '1') {
                throw new Exception($responseData['msg'] ?? 'SMS onay başarısız.');
            }
            
            // Session'dan fatura bilgilerini temizle
            unset($_SESSION['bulk_sms_invoices']);
            
            echo json_encode([
                'success' => true,
                'message' => $responseData['msg'] ?? 'Toplu onay başarılı.',
                'successCount' => count($invoices)
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'tcmb_proxy':
        header('Content-Type: application/json');
        try {
            // TCMB XML servisinden Efektif Alış Kuru verisi çek
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
            
            // Desteklenen para birimleri listesi
            $supportedCurrencies = [
                'USD', 'EUR', 'AUD', 'DKK', 'GBP', 'CHF', 'SEK', 
                'CAD', 'KWD', 'NOK', 'SAR', 'JPY'
            ];
            
            // Para birimi destekleniyor mu kontrol et
            if (!in_array($currency, $supportedCurrencies)) {
                echo json_encode([
                    'success' => false,
                    'message' => "Desteklenmeyen para birimi: $currency. Desteklenen: " . implode(', ', $supportedCurrencies)
                ]);
                break;
            }
            
            // XML'den Efektif Alış Kuru bilgisini çıkar
            $rate = null;
            
            foreach ($xml->Currency as $currencyElement) {
                // Attribute'dan CurrencyCode'u al
                $currencyCode = (string)$currencyElement['CurrencyCode'];
                
                if ($currencyCode === $currency) {
                    // Yurt dışı faturalar için Efektif Alış Kuru (BanknoteBuying) kullan
                    $banknoteBuying = (string)$currencyElement->BanknoteBuying;
                    
                    if (!empty($banknoteBuying)) {
                        $rate = floatval($banknoteBuying);
                        break;
                    }
                    
                    // Eğer efektif alış kuru yoksa (nadir durum) forex alış kuru kullan
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
                    'message' => "Efektif Alış Kuru bulunamadı: $currency"
                ]);
            }
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Hata: ' . $e->getMessage()
            ]);
        }
        break;

    case 'complete_signing':
        header('Content-Type: application/json');
        if (!isset($_SESSION['usercode'])) {
            die(json_encode(['success' => false, 'message' => 'Önce giriş yapmalısınız!']));
        }
        $uuid = $input['uuid'] ?? '';
        $oid = $input['oid'] ?? '';
        $code = $input['code'] ?? '';
        if (!$uuid || !$oid || !$code) {
            die(json_encode(['success' => false, 'message' => 'Eksik parametre.']));
        }
        try {
            $gib = (new Gib)->login($_SESSION['usercode'], $_SESSION['password']);
            $result = $gib->completeSmsVerification($code, $oid, [$uuid]);
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Fatura başarıyla imzalandı ve onaylandı!']);
            } else {
                throw new Exception('İmzalama başarısız. SMS kodunu kontrol edin veya tekrar deneyin.');
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
        }
        break;

    case 'delete_invoice':
        header('Content-Type: application/json');
        if (!isset($_SESSION['usercode'])) {
            die(json_encode(['success' => false, 'message' => 'Önce giriş yapmalısınız!']));
        }
        $uuid = $input['uuid'] ?? '';
        if (!$uuid || !is_valid_uuid($uuid)) {
            die(json_encode(['success' => false, 'message' => 'Geçersiz UUID.']));
        }
        try {
            $gib = (new Gib)->login($_SESSION['usercode'], $_SESSION['password']);
            if ($gib->deleteDraft([$uuid])) {
                echo json_encode(['success' => true, 'message' => 'Fatura taslağı başarıyla silindi!']);
            } else {
                throw new Exception('Taslak iptal edilemedi.');
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
        }
        break;

    // Diğer case'ler (list_invoices, bulk_download vb.) buraya gelecek
    case 'list_invoices':
        header('Content-Type: application/json');
        if (!isset($_SESSION['usercode']) || !isset($_SESSION['password'])) {
            echo json_encode(['success' => false, 'message' => 'Önce giriş yapmalısınız!']);
            exit;
        }
        try {
            // Performans ölçümü başlat
            $perfStart = microtime(true);
            $perfSteps = [];
            
            // Eski cache kayıtlarını temizle
            cleanupOldCache();
            
            $gib = (new Gib)->login($_SESSION['usercode'], $_SESSION['password']);
            $perfSteps['login'] = round((microtime(true) - $perfStart) * 1000, 2);
            
            $start = isset($_GET['start']) && $_GET['start'] ? date('d/m/Y', strtotime($_GET['start'])) : date('d/m/Y', strtotime('-30 days'));
            $end = isset($_GET['end']) && $_GET['end'] ? date('d/m/Y', strtotime($_GET['end'])) : date('d/m/Y');
            $statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
            $search = isset($_GET['search']) ? mb_strtolower(trim($_GET['search'])) : '';
            
            // Cache key oluştur
            $cacheKey = 'invoices_' . md5($start . $end . $_SESSION['usercode']);
            $wasCached = isset($_SESSION['temp_cache'][$cacheKey]);
            
            // Session cache kullan (5 dakika) - getAll() sonucunu cache'le
            $getAllStart = microtime(true);
            $invoices = sessionCache($cacheKey, function() use ($gib, $start, $end) {
                return $gib->getAll($start, $end);
            }, 300);
            $perfSteps['getAll'] = round((microtime(true) - $getAllStart) * 1000, 2);
            
            // Optimize edilmiş fonksiyonu kullan (detay çekme YOK - sadece özet veri)
            $processStart = microtime(true);
            $result = processInvoiceList($invoices, $statusFilter, $search);
            $perfSteps['process'] = round((microtime(true) - $processStart) * 1000, 2);
            
            $_SESSION['invoices'] = $result;
            
            $totalTime = round((microtime(true) - $perfStart) * 1000, 2);
            
            echo json_encode([
                'success' => true, 
                'invoices' => $result,
                'meta' => [
                    'total' => count($result),
                    'cached' => $wasCached,
                    'performance' => [
                        'total_ms' => $totalTime,
                        'login_ms' => $perfSteps['login'],
                        'getAll_ms' => $perfSteps['getAll'],
                        'process_ms' => $perfSteps['process'],
                        'breakdown' => $perfSteps
                    ]
                ]
            ]);
        } catch (Exception $e) {
            error_log('list_invoices error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Faturalar alınamadı: ' . $e->getMessage()]);
        }
        break;
    case 'bulk_download':
        if (!isset($_SESSION['usercode']) || !isset($_SESSION['password'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Önce giriş yapmalısınız!']);
            exit;
        }
        $uuids = $input['uuids'] ?? [];
        if (!is_array($uuids) || empty($uuids)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Fatura seçilmedi!']);
            exit;
        }
        // UUID'leri doğrula
        foreach ($uuids as $uuid) {
            if (!is_valid_uuid($uuid)) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Geçersiz UUID!']);
                exit;
            }
        }
        try {
            $gib = (new Gib)->login($_SESSION['usercode'], $_SESSION['password']);
            $mainZip = new ZipArchive();
            $mainZipPath = tempnam(sys_get_temp_dir(), 'mainzip');
            if ($mainZip->open($mainZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
                throw new Exception('Ana zip dosyası oluşturulamadı.');
            }
            $errors = [];
            $added = 0;
            foreach ($uuids as $uuid) {
                $tmpInvoiceZip = tempnam(sys_get_temp_dir(), 'invzip');
                $zipFilePath = $tmpInvoiceZip . '.zip';
                try {
                    $result = $gib->saveToDisk($uuid, dirname($tmpInvoiceZip), basename($tmpInvoiceZip, '.zip'));
                    if (!$result || !file_exists($zipFilePath)) {
                        $errors[] = "Fatura ({$uuid}): Zip dosyası oluşturulamadı veya indirilemedi.";
                        if (file_exists($zipFilePath)) unlink($zipFilePath);
                        if (file_exists($tmpInvoiceZip)) unlink($tmpInvoiceZip);
                        continue;
                    }
                    // Ana zip'e ekle
                    $mainZip->addFile($zipFilePath, $uuid . '.zip');
                    $added++;
                    // Geçici dosyayı sonra sileceğiz
                } catch (Exception $e) {
                    $errors[] = "Fatura ({$uuid}): " . $e->getMessage();
                }
            }
            $mainZip->close();
            if ($added === 0) {
                if (file_exists($mainZipPath)) unlink($mainZipPath);
                $errorMessage = 'İndirilecek fatura bulunamadı veya faturalar indirilemedi.';
                if (!empty($errors)) {
                    $errorMessage .= " Alınan hatalar: \n" . implode("\n", $errors);
                }
                throw new Exception($errorMessage);
            }
            if (ob_get_length()) ob_end_clean();
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="faturalar.zip"');
            header('Content-Length: ' . filesize($mainZipPath));
            readfile($mainZipPath);
            // Geçici dosyaları sil
            foreach ($uuids as $uuid) {
                $tmpInvoiceZip = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'invzip';
                $zipFilePath = $tmpInvoiceZip . '.zip';
                if (file_exists($zipFilePath)) unlink($zipFilePath);
                if (file_exists($tmpInvoiceZip)) unlink($tmpInvoiceZip);
            }
            if (file_exists($mainZipPath)) unlink($mainZipPath);
            exit;
        } catch (Exception $e) {
            if (ob_get_length()) ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Toplu indirme başarısız: ' . $e->getMessage()]);
        }
        break;
    
    case 'load_invoice_amounts':
        header('Content-Type: application/json');
        if (!isset($_SESSION['usercode']) || !isset($_SESSION['password'])) {
            echo json_encode(['success' => false, 'message' => 'Önce giriş yapmalısınız!']);
            exit;
        }
        
        try {
            $uuids = $input['uuids'] ?? [];
            $batchIndex = $input['batchIndex'] ?? 0;
            $batchSize = 10; // 10'lu gruplar
            
            if (empty($uuids) || !is_array($uuids)) {
                echo json_encode(['success' => false, 'message' => 'UUID listesi gerekli!']);
                exit;
            }
            
            // Bu batch'in UUID'lerini al
            $startIndex = $batchIndex * $batchSize;
            $batchUuids = array_slice($uuids, $startIndex, $batchSize);
            
            if (empty($batchUuids)) {
                echo json_encode([
                    'success' => true,
                    'amounts' => [],
                    'processed' => 0,
                    'batchIndex' => $batchIndex,
                    'isLastBatch' => true
                ]);
                exit;
            }
            
            $gib = (new Gib)->login($_SESSION['usercode'], $_SESSION['password']);
            $amounts = [];
            $processed = 0;
            
            // Bu batch'teki faturaları işle
            foreach ($batchUuids as $uuid) {
                if (!is_valid_uuid($uuid)) {
                    continue;
                }
                
                try {
                    // Sadece tutar bilgisi için getDocument çağır
                    $detailedInvoice = $gib->getDocument($uuid);
                    
                    // Tutar bilgisini çıkar
                    $total_amount = extractTotalAmount($detailedInvoice);
                    $kdv = extractVatRate($detailedInvoice);
                    $product = extractProductName($detailedInvoice);
                    
                    $amounts[$uuid] = [
                        'total' => $total_amount,
                        'kdvOrani' => $kdv,
                        'urunAdi' => $product
                    ];
                    
                    $processed++;
                    
                } catch (Exception $e) {
                    error_log("getDocument error for $uuid: " . $e->getMessage());
                    $amounts[$uuid] = [
                        'total' => '',
                        'kdvOrani' => '',
                        'urunAdi' => '',
                        'error' => true
                    ];
                }
            }
            
            // Son batch mi?
            $totalBatches = ceil(count($uuids) / $batchSize);
            $isLastBatch = ($batchIndex + 1) >= $totalBatches;
            
            echo json_encode([
                'success' => true,
                'amounts' => $amounts,
                'processed' => $processed,
                'batchIndex' => $batchIndex,
                'totalBatches' => $totalBatches,
                'isLastBatch' => $isLastBatch,
                'progress' => round((($batchIndex + 1) / $totalBatches) * 100, 1)
            ]);
            
        } catch (Exception $e) {
            error_log('load_invoice_amounts error: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Tutarlar yüklenemedi: ' . $e->getMessage()
            ]);
        }
        break;
    
    case 'download_pdf':
        header('Content-Type: application/json');
        if (!isset($_SESSION['usercode']) || !isset($_SESSION['password'])) {
            echo json_encode(['success' => false, 'message' => 'Önce giriş yapmalısınız!']);
            exit;
        }
        $uuid = $_GET['uuid'] ?? '';
        if (!$uuid || !is_valid_uuid($uuid)) {
            echo json_encode(['success' => false, 'message' => 'Geçersiz UUID!']);
            exit;
        }
        $tmpInvoiceZip = null; // Initialize to null for finally block
        $zipFilePath = null; // Initialize to null for finally block
        try {
            $gib = (new Gib)->login($_SESSION['usercode'], $_SESSION['password']);
            
            // Generate a unique temporary file path for the zip
            $tmpInvoiceZip = tempnam(sys_get_temp_dir(), 'invzip');
            $zipFilePath = $tmpInvoiceZip . '.zip';

            // Save the invoice to disk as a ZIP file
            $result = $gib->saveToDisk($uuid, dirname($tmpInvoiceZip), basename($tmpInvoiceZip, '.zip'));

            // Check if the ZIP file was actually created
            if (!$result || !file_exists($zipFilePath)) {
                throw new Exception('Fatura ZIP dosyası oluşturulamadı veya bulunamadı.');
            }

            $invoiceZip = new ZipArchive();
            if ($invoiceZip->open($zipFilePath) === TRUE) {
                $pdfFound = false;
                for ($i = 0; $i < $invoiceZip->numFiles; $i++) {
                    $entry = $invoiceZip->getNameIndex($i);
                    if (strtolower(pathinfo($entry, PATHINFO_EXTENSION)) === 'pdf') {
                        $pdfContent = $invoiceZip->getFromIndex($i);
                        if ($pdfContent !== false) {
                            if (ob_get_length()) ob_end_clean();
                            header('Content-Type: application/pdf');
                            header('Content-Disposition: attachment; filename="' . $uuid . '.pdf"');
                            header('Content-Length: ' . strlen($pdfContent));
                            echo $pdfContent;
                            $pdfFound = true;
                            break; // Exit loop once PDF is found and sent
                        }
                    }
                }
                $invoiceZip->close();

                if ($pdfFound) {
                    // Clean up temporary files only if PDF was successfully sent
                    unlink($zipFilePath);
                    unlink($tmpInvoiceZip);
                    exit; // Important: exit after sending file
                } else {
                    throw new Exception('ZIP dosyası içinde PDF bulunamadı.');
                }
            } else {
                throw new Exception('Fatura ZIP dosyası açılamadı.');
            }
        } catch (Exception $e) {
            if (ob_get_length()) ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'PDF indirme başarısız: ' . $e->getMessage()]);
        } finally {
            // Ensure temporary files are cleaned up even if an error occurs
            if ($zipFilePath && file_exists($zipFilePath)) {
                unlink($zipFilePath);
            }
            if ($tmpInvoiceZip && file_exists($tmpInvoiceZip)) {
                unlink($tmpInvoiceZip);
            }
        }
        break;
    

        case 'get_invoice_for_edit':
        header('Content-Type: application/json');
        if (!is_logged_in()) {
            die(json_encode(['success' => false, 'message' => 'Önce giriş yapmalısınız!']));
        }
        $uuid = $_GET['uuid'] ?? '';
        if (!$uuid || !is_valid_uuid($uuid)) {
            die(json_encode(['success' => false, 'message' => 'Geçersiz UUID!']));
        }
        try {
            $gib = (new Gib)->login($_SESSION['usercode'], $_SESSION['password']);
            $invoiceData = $gib->getDocument($uuid);
            if ($invoiceData) {
                echo json_encode(['success' => true, 'data' => $invoiceData]);
            } else {
                throw new Exception('Fatura bulunamadı.');
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Fatura alınamadı: ' . $e->getMessage()]);
        }
        break;

    case 'download_excel_template':
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="fatura_sablonu.xlsx"');
        
        try {
            require_once __DIR__ . '/vendor/autoload.php';
            
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Başlıkları ekle
            $headers = [
                'A1' => 'Fatura Tarihi',
                'B1' => 'Fatura Saati',
                'C1' => 'VKN/TCKN',
                'D1' => 'Alıcı Adı',
                'E1' => 'Alıcı Soyadı',
                'F1' => 'Alıcı Unvan',
                'G1' => 'Vergi Dairesi',
                'H1' => 'Adres',
                'I1' => 'Mahalle/Semt/İlçe',
                'J1' => 'Şehir',
                'K1' => 'Ülke',
                'L1' => 'Posta Kodu',
                'M1' => 'Telefon',
                'N1' => 'E-posta',
                'O1' => 'Ürün/Hizmet Adı',
                'P1' => 'Miktar',
                'Q1' => 'Birim Fiyat',
                'R1' => 'KDV Oranı (%)',
                'S1' => 'Para Birimi',
                'T1' => 'Fatura Tipi',
                'U1' => 'Notlar'
            ];
            
            foreach ($headers as $cell => $value) {
                $sheet->setCellValue($cell, $value);
                $sheet->getStyle($cell)->getFont()->setBold(true);
                $sheet->getStyle($cell)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
                $sheet->getStyle($cell)->getFill()->getStartColor()->setRGB('E3F2FD');
            }
            
            // Açıklama satırı ekle (bu satır otomatik olarak atlanacak)
            $sheet->setCellValue('A2', 'AÇIKLAMA SATIRI - BU SATIR İŞLENMEZ: Tarih dd/mm/yyyy | Saat HH:MM | VKN/TCKN dolu ise alıcı bilgileri otomatik çekilir!');
            $sheet->getStyle('A2:U2')->getFont()->setBold(true);
            $sheet->getStyle('A2:U2')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
            $sheet->getStyle('A2:U2')->getFill()->getStartColor()->setRGB('FFE6E6'); // Kırmızımsı arka plan
            $sheet->mergeCells('A2:U2');
            
            // Örnek 1: TCKN - Otomatik bilgi çekme
            $sheet->setCellValue('A3', date('d/m/Y')); // Bugünün tarihi (slash formatı)
            $sheet->setCellValue('B3', '09:00'); // Fatura saati
            $sheet->setCellValue('C3', '12345678901'); // Geçerli TCKN formatı (11 haneli)
            $sheet->setCellValue('D3', ''); // Boş - otomatik doldurulacak
            $sheet->setCellValue('E3', ''); // Boş - otomatik doldurulacak
            $sheet->setCellValue('F3', ''); // Boş - TCKN için ünvan otomatik boş kalacak
            $sheet->setCellValue('G3', ''); // Boş - otomatik doldurulacak
            $sheet->setCellValue('H3', ''); // Boş - otomatik doldurulacak
            $sheet->setCellValue('I3', ''); // Boş - otomatik doldurulacak
            $sheet->setCellValue('J3', ''); // Boş - otomatik doldurulacak
            $sheet->setCellValue('K3', ''); // Boş - otomatik doldurulacak
            $sheet->setCellValue('L3', ''); // Boş - otomatik doldurulacak
            $sheet->setCellValue('M3', ''); // Boş - otomatik doldurulacak
            $sheet->setCellValue('N3', ''); // Boş - otomatik doldurulacak
            $sheet->setCellValue('O3', 'Yazılım Geliştirme Hizmeti');
            $sheet->setCellValue('P3', '1');
            $sheet->setCellValue('Q3', '2500');
            $sheet->setCellValue('R3', '20');
            $sheet->setCellValue('S3', 'TRY');
            $sheet->setCellValue('T3', 'Satis');
            $sheet->setCellValue('U3', 'TCKN örneği - bilgiler otomatik çekilecek');
            
            // Örnek 2: VKN - Şirket bilgileri
            $sheet->setCellValue('A4', date('d/m/Y', strtotime('+1 day'))); // Yarının tarihi (slash formatı)
            $sheet->setCellValue('B4', '14:30'); // Fatura saati
            $sheet->setCellValue('C4', '1234567890'); // Geçerli VKN formatı (10 haneli)
            $sheet->setCellValue('D4', ''); // Boş - şirket için genelde boş
            $sheet->setCellValue('E4', ''); // Boş - şirket için genelde boş
            $sheet->setCellValue('F4', ''); // Boş - otomatik şirket ünvanı çekilecek
            $sheet->setCellValue('G4', ''); // Boş - otomatik doldurulacak
            $sheet->setCellValue('H4', ''); // Boş - otomatik doldurulacak
            $sheet->setCellValue('I4', ''); // Boş - otomatik doldurulacak
            $sheet->setCellValue('J4', ''); // Boş - otomatik doldurulacak
            $sheet->setCellValue('K4', ''); // Boş - otomatik doldurulacak
            $sheet->setCellValue('L4', ''); // Boş - otomatik doldurulacak
            $sheet->setCellValue('M4', ''); // Boş - otomatik doldurulacak
            $sheet->setCellValue('N4', ''); // Boş - otomatik doldurulacak
            $sheet->setCellValue('O4', 'Danışmanlık Hizmeti');
            $sheet->setCellValue('P4', '3');
            $sheet->setCellValue('Q4', '1200');
            $sheet->setCellValue('R4', '20');
            $sheet->setCellValue('S4', 'TRY');
            $sheet->setCellValue('T4', 'Satis');
            $sheet->setCellValue('U4', 'VKN örneği - şirket bilgileri otomatik çekilecek');
            
            // Örnek 3: Manuel - Karma kullanım
            $sheet->setCellValue('A5', date('d/m/Y', strtotime('+2 days'))); // 2 gün sonrası (slash formatı)
            $sheet->setCellValue('B5', '16:45'); // Fatura saati
            $sheet->setCellValue('C5', '98765432109'); // TCKN
            $sheet->setCellValue('D5', 'Özel Ad'); // Manuel girilen
            $sheet->setCellValue('E5', 'Özel Soyad'); // Manuel girilen
            $sheet->setCellValue('F5', ''); // Boş - TCKN için otomatik boş kalacak
            $sheet->setCellValue('G5', 'Özel Vergi Dairesi'); // Manuel girilen
            $sheet->setCellValue('H5', 'Özel Adres'); // Manuel girilen
            $sheet->setCellValue('I5', 'Çankaya'); // Manuel girilen
            $sheet->setCellValue('J5', 'Ankara'); // Manuel girilen
            $sheet->setCellValue('K5', 'Türkiye');
            $sheet->setCellValue('L5', '06000');
            $sheet->setCellValue('M5', '0312 123 45 67'); // Manuel girilen
            $sheet->setCellValue('N5', 'ornek@email.com'); // Manuel girilen
            $sheet->setCellValue('O5', 'Eğitim Hizmeti');
            $sheet->setCellValue('P5', '5');
            $sheet->setCellValue('Q5', '800');
            $sheet->setCellValue('R5', '20');
            $sheet->setCellValue('S5', 'TRY');
            $sheet->setCellValue('T5', 'Satis');
            $sheet->setCellValue('U5', 'Karma örnek - bazı alanlar manuel, bazıları otomatik');
            
            // Kolon genişliklerini ayarla
            foreach (range('A', 'U') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
            
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Şablon oluşturulamadı: ' . $e->getMessage()]);
        }
        break;

    case 'process_excel_upload':
        header('Content-Type: application/json');
        if (!is_logged_in()) {
            die(json_encode(['success' => false, 'message' => 'Önce giriş yapmalısınız!']));
        }
        
        if (!isset($_FILES['excel_file'])) {
            die(json_encode(['success' => false, 'message' => 'Excel dosyası yüklenmedi!']));
        }
        
        try {
            require_once __DIR__ . '/vendor/autoload.php';
            
            $uploadedFile = $_FILES['excel_file'];
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $spreadsheet = $reader->load($uploadedFile['tmp_name']);
            $sheet = $spreadsheet->getActiveSheet();
            $data = $sheet->toArray();
            
            // İlk satır başlık, atlayalım
            array_shift($data);
            
            $invoices = [];
            $gib = get_gib_instance();
            $successCount = 0;
            $errorCount = 0;
            $errors = [];
            
            // Mükellef bilgileri için cache sistemi (performans için)
            $recipientCache = [];
            
            foreach ($data as $index => $row) {
                $rowNum = $index + 2; // Excel'de 2. satırdan başlıyor
                
                // Boş satırları atla
                if (empty(array_filter($row))) continue;
                
                // Açıklama satırını atla (2. satır genellikle açıklama içerir)
                if ($rowNum == 2 && isset($row[0]) && (
                    strpos($row[0], 'AÇIKLAMA') !== false || 
                    strpos($row[0], 'ÖNEMLİ') !== false || 
                    strpos($row[0], 'ÖNEMLI') !== false ||
                    strpos($row[0], 'Tarih formatı') !== false ||
                    strpos($row[0], 'dd.mm.yyyy') !== false ||
                    strpos($row[0], 'BU SATIR İŞLENMEZ') !== false
                )) {
                    continue; // Açıklama satırını atla
                }
                
                try {
                    // Fatura tarihi Excel'den al, yoksa bugünün tarihini kullan
                    $faturaTarihi = date('d/m/Y'); // Varsayılan olarak bugünün tarihi
                    
                    // Tarih formatını kontrol et ve düzelt
                    if (!empty($row[0])) {
                        $dateStr = trim($row[0]);
                        
                        // Excel'den gelen tarih formatlarını kontrol et
                        $parsedDate = null;
                        
                        // 1. Excel serial date (sayısal değer) kontrolü
                        if (is_numeric($dateStr)) {
                            try {
                                // Excel serial date'i DateTime'a çevir
                                $excelDate = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dateStr);
                                $parsedDate = $excelDate;
                            } catch (Exception $e) {
                                // Serial date çevirme hatası
                            }
                        }
                        // 2. Nokta formatı (dd.mm.yyyy veya d.m.yyyy)
                        else if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/', $dateStr, $matches)) {
                            try {
                                $parsedDate = DateTime::createFromFormat('d.m.Y', $dateStr);
                                if (!$parsedDate) {
                                    $parsedDate = DateTime::createFromFormat('j.n.Y', $dateStr);
                                }
                            } catch (Exception $e) {
                                // Tarih parse hatası
                            }
                        }
                        // 3. Slash formatı (dd/mm/yyyy veya d/m/yyyy)
                        else if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $dateStr, $matches)) {
                            try {
                                $parsedDate = DateTime::createFromFormat('d/m/Y', $dateStr);
                                if (!$parsedDate) {
                                    $parsedDate = DateTime::createFromFormat('j/n/Y', $dateStr);
                                }
                            } catch (Exception $e) {
                                // Tarih parse hatası
                            }
                        }
                        // 4. Tire formatı (dd-mm-yyyy veya d-m-yyyy)
                        else if (preg_match('/^(\d{1,2})-(\d{1,2})-(\d{4})$/', $dateStr, $matches)) {
                            try {
                                $parsedDate = DateTime::createFromFormat('d-m-Y', $dateStr);
                                if (!$parsedDate) {
                                    $parsedDate = DateTime::createFromFormat('j-n-Y', $dateStr);
                                }
                            } catch (Exception $e) {
                                // Tarih parse hatası
                            }
                        }
                        // 5. ISO formatı (yyyy-mm-dd)
                        else if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $dateStr, $matches)) {
                            try {
                                $parsedDate = DateTime::createFromFormat('Y-m-d', $dateStr);
                            } catch (Exception $e) {
                                // Tarih parse hatası
                            }
                        }
                        
                        // Eğer tarih başarıyla parse edildiyse, e-Arşiv formatına çevir
                        if ($parsedDate && $parsedDate instanceof DateTime) {
                            $faturaTarihi = $parsedDate->format('d/m/Y');
                        } else {
                            // Parse edilemezse hata ekle ve bugünün tarihini kullan
                            $errors[] = "Satır $rowNum: Tarih geçerli formatta değil ($dateStr). Bugünün tarihi kullanıldı.";
                            $faturaTarihi = date('d/m/Y');
                        }
                    }
                    
                    // Fatura saati Excel'den al, yoksa şimdiki saati kullan
                    $faturaSaati = date('H:i:s'); // Varsayılan olarak şimdiki saat
                    
                    // Saat formatını kontrol et ve düzelt
                    if (!empty($row[1])) {
                        $timeStr = trim($row[1]);
                        
                        // 1. Excel serial time (sayısal değer) kontrolü
                        if (is_numeric($timeStr) && $timeStr < 1) {
                            try {
                                // Excel serial time'ı saat formatına çevir
                                $excelTime = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($timeStr);
                                $faturaSaati = $excelTime->format('H:i:s');
                            } catch (Exception $e) {
                                // Serial time çevirme hatası
                                $errors[] = "Satır $rowNum: Saat formatı geçersiz ($timeStr). Şimdiki saat kullanıldı.";
                            }
                        }
                        // 2. HH:MM formatı
                        else if (preg_match('/^(\d{1,2}):(\d{2})$/', $timeStr, $matches)) {
                            $hour = (int)$matches[1];
                            $minute = (int)$matches[2];
                            
                            if ($hour >= 0 && $hour <= 23 && $minute >= 0 && $minute <= 59) {
                                $faturaSaati = sprintf('%02d:%02d:00', $hour, $minute);
                            } else {
                                $errors[] = "Satır $rowNum: Saat değeri geçersiz ($timeStr). Şimdiki saat kullanıldı.";
                                $faturaSaati = date('H:i:s');
                            }
                        }
                        // 3. HH:MM:SS formatı
                        else if (preg_match('/^(\d{1,2}):(\d{2}):(\d{2})$/', $timeStr, $matches)) {
                            $hour = (int)$matches[1];
                            $minute = (int)$matches[2];
                            $second = (int)$matches[3];
                            
                            if ($hour >= 0 && $hour <= 23 && $minute >= 0 && $minute <= 59 && $second >= 0 && $second <= 59) {
                                $faturaSaati = sprintf('%02d:%02d:%02d', $hour, $minute, $second);
                            } else {
                                $errors[] = "Satır $rowNum: Saat değeri geçersiz ($timeStr). Şimdiki saat kullanıldı.";
                                $faturaSaati = date('H:i:s');
                            }
                        }
                        // 4. Geçersiz format
                        else {
                            $errors[] = "Satır $rowNum: Saat formatı geçersiz ($timeStr). Şimdiki saat kullanıldı.";
                            $faturaSaati = date('H:i:s');
                        }
                    }
                    
                    $vknTckn = trim($row[2] ?: ''); // C kolonu
                    
                    // Temel validasyonlar
                    $validationErrors = [];
                    
                    // VKN/TCKN kontrolü
                    if (!empty($vknTckn)) {
                        if (!preg_match('/^\d{10,11}$/', $vknTckn)) {
                            $validationErrors[] = "Alıcı VKN/TCKN hatalı!";
                        }
                    }
                    
                    // Miktar ve fiyat kontrolü
                    $miktar = (int)($row[15] ?: 1);
                    $birimFiyat = (float)($row[16] ?: 0);
                    
                    if ($miktar <= 0) {
                        $validationErrors[] = "Miktar 0'dan büyük olmalı!";
                    }
                    
                    if ($birimFiyat <= 0) {
                        $validationErrors[] = "Birim fiyat 0'dan büyük olmalı!";
                    }
                    
                    // KDV oranı kontrolü
                    $kdvOrani = (int)($row[17] ?: 20);
                    if ($kdvOrani < 0 || $kdvOrani > 100) {
                        $validationErrors[] = "KDV oranı 0-100 arasında olmalı!";
                    }
                    
                    // Mükellef bilgilerini otomatik çek (VKN/TCKN varsa)
                    $recipientData = [];
                    if (!empty($vknTckn) && (strlen($vknTckn) == 10 || strlen($vknTckn) == 11)) {
                        // Cache'den kontrol et
                        if (isset($recipientCache[$vknTckn])) {
                            $recipientData = $recipientCache[$vknTckn];
                        } else {
                            // Mükellef bilgilerini çek
                            try {
                                // Önce mevcut GIB kütüphanesi ile deneyelim
                                $gibData = $gib->getRecipientData($vknTckn);
                                
                                // Eğer veri boş veya sadece vergi dairesi varsa, e-arşiv portalından tam bilgileri çekelim
                                if (empty($gibData) || (isset($gibData['vergiDairesi']) && count($gibData) <= 2)) {
                                    $fullData = getFullRecipientDataFromEarsiv($vknTckn);
                                    if (!empty($fullData)) {
                                        $gibData = array_merge($gibData ?: [], $fullData);
                                    }
                                }
                                
                                if (!empty($gibData)) {
                                    // Veri formatını standartlaştır
                                    $recipientData = [
                                        'unvan' => $gibData['aliciUnvan'] ?? $gibData['unvan'] ?? '',
                                        'adi' => $gibData['aliciAdi'] ?? $gibData['adi'] ?? $gibData['ad'] ?? '',
                                        'soyadi' => $gibData['aliciSoyadi'] ?? $gibData['soyadi'] ?? $gibData['soyad'] ?? '',
                                        'vergiDairesi' => $gibData['vergiDairesi'] ?? '',
                                        'adres' => $gibData['adres'] ?? '',
                                        'mahalleSemtIlce' => $gibData['mahalleSemtIlce'] ?? $gibData['mahalle'] ?? '',
                                        'sehir' => $gibData['sehir'] ?? '',
                                        'ulke' => $gibData['ulke'] ?? 'Türkiye',
                                        'postaKodu' => $gibData['postaKodu'] ?? '',
                                        'tel' => $gibData['tel'] ?? $gibData['telefon'] ?? '',
                                        'eposta' => $gibData['eposta'] ?? ''
                                    ];
                                    
                                    // Cache'e kaydet
                                    $recipientCache[$vknTckn] = $recipientData;
                                }
                            } catch (Exception $e) {
                                // Mükellef bilgisi çekilemezse Excel'deki bilgileri kullan
                            }
                        }
                    }
                    
                    // Excel'den gelen bilgiler ile otomatik çekilen bilgileri birleştir
                    // Excel'de dolu olan alanlar öncelikli, boş olanlar otomatik doldurulur
                    $aliciAdi = !empty($row[3]) ? $row[3] : ($recipientData['adi'] ?? ''); // D kolonu
                    $aliciSoyadi = !empty($row[4]) ? $row[4] : ($recipientData['soyadi'] ?? ''); // E kolonu
                    
                    // Ünvan kontrolü: TCKN (11 haneli) ise ünvan kullanma, VKN (10 haneli) ise kullan
                    $aliciUnvan = '';
                    if (strlen($vknTckn) == 10) { // VKN - şirket
                        $aliciUnvan = !empty($row[5]) ? $row[5] : ($recipientData['unvan'] ?? ''); // F kolonu
                    } else if (strlen($vknTckn) == 11) { // TCKN - gerçek kişi
                        $aliciUnvan = ''; // TCKN için ünvan boş bırak
                    } else {
                        // Geçersiz VKN/TCKN durumunda Excel'deki değeri kullan
                        $aliciUnvan = $row[5] ?? '';
                    }
                    $vergiDairesi = !empty($row[6]) ? $row[6] : ($recipientData['vergiDairesi'] ?? ''); // G kolonu
                    $adres = !empty($row[7]) ? $row[7] : ($recipientData['adres'] ?? ''); // H kolonu
                    $mahalleSemtIlce = !empty($row[8]) ? $row[8] : ($recipientData['mahalleSemtIlce'] ?? ''); // I kolonu
                    $sehir = !empty($row[9]) ? $row[9] : ($recipientData['sehir'] ?? ''); // J kolonu
                    $ulke = !empty($row[10]) ? $row[10] : ($recipientData['ulke'] ?? 'Türkiye'); // K kolonu
                    $postaKodu = !empty($row[11]) ? $row[11] : ($recipientData['postaKodu'] ?? ''); // L kolonu
                    $tel = !empty($row[12]) ? $row[12] : ($recipientData['tel'] ?? ''); // M kolonu
                    $eposta = !empty($row[13]) ? $row[13] : ($recipientData['eposta'] ?? ''); // N kolonu
                    
                    // Alıcı bilgileri kontrolü
                    if (empty($vknTckn)) {
                        if (empty($aliciAdi) && empty($aliciSoyadi) && empty($aliciUnvan)) {
                            $validationErrors[] = "Alıcı Ad-Soyad/Unvan bilgisi boş olamaz!";
                        }
                    } else {
                        // VKN/TCKN var ama alıcı bilgileri eksikse
                        if (strlen($vknTckn) == 11 && (empty($aliciAdi) || empty($aliciSoyadi))) {
                            // TCKN için ad-soyad zorunlu
                            if (empty($aliciAdi) || empty($aliciSoyadi)) {
                                $validationErrors[] = "TCKN için Ad ve Soyad zorunlu!";
                            }
                        } else if (strlen($vknTckn) == 10 && empty($aliciUnvan)) {
                            // VKN için ünvan zorunlu
                            $validationErrors[] = "VKN için Ünvan zorunlu!";
                        }
                    }
                    
                    // Validasyon hatası varsa bu satırı atla
                    if (!empty($validationErrors)) {
                        $errorCount++;
                        $errors[] = "Satır {$rowNum}: " . implode("\n", $validationErrors);
                        continue;
                    }
                    
                    // Önce toplam tutarı hesapla (KDV dahil)
                    $miktar = (int)($row[15] ?: 1);
                    $birimFiyat = (float)($row[16] ?: 0);
                    $kdvOrani = (int)($row[17] ?: 20);
                    $araToplam = $miktar * $birimFiyat;
                    $kdvTutari = $araToplam * ($kdvOrani / 100);
                    $genelToplam = $araToplam + $kdvTutari;
                    
                    // Tutar yazısını oluştur
                    $tutarYazisi = numberToTurkishText($genelToplam);
                    
                    // Notları birleştir: Önce tutar yazısı, sonra Excel'den gelen not
                    $excelNotu = trim($row[20] ?: ''); // U kolonu
                    $finalNot = "Yalnız: " . $tutarYazisi;
                    if (!empty($excelNotu)) {
                        $finalNot .= "\n" . $excelNotu;
                    }
                    
                    $invoice = new \Mlevent\Fatura\Models\InvoiceModel(
                        tarih: $faturaTarihi,
                        saat: $faturaSaati,
                        paraBirimi: \Mlevent\Fatura\Enums\Currency::from($row[18] ?: 'TRY'), // S kolonu
                        dovizKuru: 0,
                        faturaTipi: \Mlevent\Fatura\Enums\InvoiceType::from(strtoupper($row[19] ?: 'SATIS')), // T kolonu
                        vknTckn: $vknTckn,
                        vergiDairesi: $vergiDairesi,
                        aliciUnvan: $aliciUnvan,
                        aliciAdi: $aliciAdi,
                        aliciSoyadi: $aliciSoyadi,
                        binaAdi: '',
                        binaNo: '',
                        kapiNo: '',
                        kasabaKoy: '',
                        mahalleSemtIlce: $mahalleSemtIlce,
                        sehir: $sehir,
                        ulke: $ulke,
                        postaKodu: $postaKodu,
                        adres: $adres,
                        tel: $tel,
                        fax: '',
                        eposta: $eposta,
                        not: $finalNot, // Tutar yazısı + Excel notu
                        siparisNumarasi: '',
                        siparisTarihi: '',
                        irsaliyeNumarasi: '',
                        irsaliyeTarihi: '',
                        fisNo: '',
                        fisTarihi: '',
                        fisSaati: '',
                        fisTipi: '',
                        zRaporNo: '',
                        okcSeriNo: ''
                    );
                    
                    // Ürün/hizmet ekle
                    $invoice->addItem(
                        new \Mlevent\Fatura\Models\InvoiceItemModel(
                            malHizmet: $row[14] ?: 'Hizmet', // O kolonu
                            miktar: (int)($row[15] ?: 1), // P kolonu
                            birim: \Mlevent\Fatura\Enums\Unit::Adet,
                            birimFiyat: (float)($row[16] ?: 0), // Q kolonu
                            kdvOrani: (int)($row[17] ?: 20) // R kolonu
                        )
                    );
                    
                    try {
                        $result = $gib->createDraft($invoice);
                        if ($result) {
                            $successCount++;
                        } else {
                            $errorCount++;
                            $errors[] = "Satır {$rowNum}: Taslak oluşturulamadı - GIB yanıtı boş";
                        }
                    } catch (Exception $gibException) {
                        $errorCount++;
                        $errors[] = "Satır {$rowNum}: " . $gibException->getMessage();
                    }
                    
                } catch (Exception $e) {
                    $errorCount++;
                    $errors[] = "Satır {$rowNum}: " . $e->getMessage();
                }
            }
            
            // Cache istatistiklerini ekle
            $cacheInfo = count($recipientCache) > 0 ? " ({" . count($recipientCache) . "} farklı mükellef bilgisi otomatik çekildi)" : "";
            
            echo json_encode([
                'success' => true,
                'message' => "İşlem tamamlandı! {$successCount} fatura oluşturuldu, {$errorCount} hata.{$cacheInfo}",
                'successCount' => $successCount,
                'errorCount' => $errorCount,
                'errors' => $errors,
                'recipientCacheCount' => count($recipientCache)
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Excel işleme hatası: ' . $e->getMessage()]);
        }
        break;

    case 'view_invoice_html':
        header('Content-Type: text/html');
        if (!isset($_SESSION['usercode']) || !isset($_SESSION['password'])) {
            echo '<h1>Hata: Önce giriş yapmalımısınız!</h1>';
            exit;
        }
        $uuid = $input['uuid'] ?? '';
        $status = $input['status'] ?? '';
        if (!$uuid || !is_valid_uuid($uuid)) {
            echo '<h1>Hata: Geçersiz UUID!</h1>';
            exit;
        }
        try {
            $gib = (new Gib)->login($_SESSION['usercode'], $_SESSION['password']);
            $htmlContent = $gib->getHtml($uuid, $status === 'Onaylanmış');
            if ($htmlContent) {
                echo $htmlContent;
            } else {
                echo '<h1>Hata: Fatura içeriği bulunamadı.</h1>';
            }
        } catch (Exception $e) {
            echo '<h1>Hata: Fatura içeriği alınamadı: ' . $e->getMessage() . '</h1>';
        }
        break;

    case 'generate_report':
        header('Content-Type: application/json');
        if (!isset($_SESSION['usercode']) || !isset($_SESSION['password'])) {
            echo json_encode(['success' => false, 'message' => 'Önce giriş yapmalısınız!']);
            exit;
        }
        
        try {
            $startDate = $input['startDate'] ?? '';
            $endDate = $input['endDate'] ?? '';
            $reportType = $input['reportType'] ?? 'excel'; // excel veya json
            
            if (!$startDate || !$endDate) {
                echo json_encode(['success' => false, 'message' => 'Başlangıç ve bitiş tarihi gereklidir!']);
                exit;
            }
            
            $gib = (new Gib)->login($_SESSION['usercode'], $_SESSION['password']);
            
            // Tarihleri GİB formatına çevir
            $start = date('d/m/Y', strtotime($startDate));
            $end = date('d/m/Y', strtotime($endDate));
            
            // Cache key oluştur
            $cacheKey = 'report_' . md5($start . $end . $_SESSION['usercode']);
            
            // Session cache kullan (5 dakika) - getAll() sonucunu cache'le
            $invoices = sessionCache($cacheKey, function() use ($gib, $start, $end) {
                return $gib->getAll($start, $end);
            }, 300);
            
            $reportData = [];
            $totalMalHizmet = 0;
            $totalVergilerDahil = 0;
            $invoiceCount = 0;
            
            foreach ($invoices as $inv) {
                // Her fatura için detaylı bilgi al (düzenleme sistemindeki gibi)
                $uuid = $inv['ettn'] ?? '';
                $detailedInvoice = null;
                
                // Eğer mevcut veride tutar bilgisi varsa detaylı bilgi almaya gerek yok
                $hasTotalInfo = isset($inv['vergilerDahilToplamTutar']) || isset($inv['toplamTutar']) || isset($inv['odenecekTutar']) || isset($inv['genelToplam']);
                
                if (!$hasTotalInfo && $uuid && is_valid_uuid($uuid)) {
                    try {
                        $detailedInvoice = $gib->getDocument($uuid);
                    } catch (Exception $e) {
                        // Detaylı bilgi alınamazsa mevcut veriyi kullan
                        $detailedInvoice = $inv;
                    }
                } else {
                    $detailedInvoice = $inv;
                }
                // Optimize edilmiş durum belirleme fonksiyonunu kullan
                $status = determineInvoiceStatus($inv);
                
                // Sadece onaylanmış faturaları rapora dahil et (iptal edilmişleri hariç tut)
                if ($status === 'Onaylanmış') {
                    // Vergiler dahil tutarı detaylı fatura verisinden al (düzenleme sistemindeki gibi)
                    $vergilerDahilTutar = 0;
                    if (isset($detailedInvoice['vergilerDahilToplamTutar']) && $detailedInvoice['vergilerDahilToplamTutar'] > 0) {
                        $vergilerDahilTutar = floatval($detailedInvoice['vergilerDahilToplamTutar']);
                    } elseif (isset($detailedInvoice['toplamTutar']) && $detailedInvoice['toplamTutar'] > 0) {
                        $vergilerDahilTutar = floatval($detailedInvoice['toplamTutar']);
                    } elseif (isset($detailedInvoice['odenecekTutar']) && $detailedInvoice['odenecekTutar'] > 0) {
                        $vergilerDahilTutar = floatval($detailedInvoice['odenecekTutar']);
                    } elseif (isset($detailedInvoice['genelToplam']) && $detailedInvoice['genelToplam'] > 0) {
                        $vergilerDahilTutar = floatval($detailedInvoice['genelToplam']);
                    }
                    
                    // Mal hizmet toplam tutarını hesapla (KDV hariç)
                    $malHizmetTutari = 0;
                    if ($vergilerDahilTutar > 0) {
                        // Önce mevcut mal hizmet tutarını kontrol et (farklı alan isimlerini kontrol et)
                        if (isset($detailedInvoice['malHizmetToplamTutari']) && $detailedInvoice['malHizmetToplamTutari'] > 0) {
                            $malHizmetTutari = floatval($detailedInvoice['malHizmetToplamTutari']);
                        } elseif (isset($detailedInvoice['malhizmetToplamTutari']) && $detailedInvoice['malhizmetToplamTutari'] > 0) {
                            $malHizmetTutari = floatval($detailedInvoice['malhizmetToplamTutari']);
                        } elseif (isset($detailedInvoice['matrah']) && $detailedInvoice['matrah'] > 0) {
                            $malHizmetTutari = floatval($detailedInvoice['matrah']);
                        } elseif (isset($inv['malHizmetToplamTutari']) && $inv['malHizmetToplamTutari'] > 0) {
                            $malHizmetTutari = floatval($inv['malHizmetToplamTutari']);
                        } else {
                            // KDV oranını bul ve mal hizmet tutarını hesapla
                            $kdvOrani = 0;
                            
                            // Fatura kalemlerinden KDV oranını bul
                            if (isset($detailedInvoice['faturaKalemleri']) && is_array($detailedInvoice['faturaKalemleri'])) {
                                foreach ($detailedInvoice['faturaKalemleri'] as $kalem) {
                                    // Tüm olası KDV oranı alan isimlerini kontrol et
                                    $kdvFields = ['kdvOrani', 'kdvOran', 'vergiOrani', 'vergiOran', 'kdv', 'vergi', 'taxRate', 'tax'];
                                    foreach ($kdvFields as $field) {
                                        if (isset($kalem[$field]) && $kalem[$field] > 0) {
                                            $kdvOrani = floatval($kalem[$field]);
                                            break 2; // Hem iç hem dış döngüden çık
                                        }
                                    }
                                }
                            } else {
                                // Alternatif alan isimlerini kontrol et
                                if (isset($detailedInvoice['kalemler']) && is_array($detailedInvoice['kalemler'])) {
                                    foreach ($detailedInvoice['kalemler'] as $kalem) {
                                        $kdvFields = ['kdvOrani', 'kdvOran', 'vergiOrani', 'vergiOran', 'kdv', 'vergi', 'taxRate', 'tax'];
                                        foreach ($kdvFields as $field) {
                                            if (isset($kalem[$field]) && $kalem[$field] > 0) {
                                                $kdvOrani = floatval($kalem[$field]);
                                                break 2; // Hem iç hem dış döngüden çık
                                            }
                                        }
                                    }
                                }
                                
                                // Düzenleme sistemindeki gibi malHizmetTable alanını kontrol et
                                if (isset($detailedInvoice['malHizmetTable']) && is_array($detailedInvoice['malHizmetTable'])) {
                                    foreach ($detailedInvoice['malHizmetTable'] as $kalem) {
                                        $kdvFields = ['kdvOrani', 'kdvOran', 'vergiOrani', 'vergiOran', 'kdv', 'vergi', 'taxRate', 'tax'];
                                        foreach ($kdvFields as $field) {
                                            if (isset($kalem[$field]) && $kalem[$field] > 0) {
                                                $kdvOrani = floatval($kalem[$field]);
                                                break 2; // Hem iç hem dış döngüden çık
                                            }
                                        }
                                    }
                                }
                            }
                            
                            // Eğer KDV oranı bulunamazsa varsayılan %20 kullan
                            if ($kdvOrani == 0) {
                                $kdvOrani = 20;
                            }
                            
                            // Mal hizmet tutarını hesapla: Vergiler dahil tutar / (1 + KDV oranı / 100)
                            $malHizmetTutari = $vergilerDahilTutar / (1 + ($kdvOrani / 100));
                        }
                    }
                    
                    // Ürün adını bul
                    $urunAdi = '';
                    if (isset($detailedInvoice['faturaKalemleri']) && is_array($detailedInvoice['faturaKalemleri']) && count($detailedInvoice['faturaKalemleri']) > 0) {
                        $ilkKalem = $detailedInvoice['faturaKalemleri'][0];
                        $urunAdi = $ilkKalem['malHizmet'] ?? $ilkKalem['urunAdi'] ?? $ilkKalem['aciklama'] ?? '';
                    } elseif (isset($detailedInvoice['malHizmetTable']) && is_array($detailedInvoice['malHizmetTable']) && count($detailedInvoice['malHizmetTable']) > 0) {
                        $ilkKalem = $detailedInvoice['malHizmetTable'][0];
                        $urunAdi = $ilkKalem['malHizmet'] ?? $ilkKalem['urunAdi'] ?? $ilkKalem['aciklama'] ?? '';
                    }
                    
                    // KDV oranını bul
                    $kdvOrani = '';
                    
                    if (isset($detailedInvoice['faturaKalemleri']) && is_array($detailedInvoice['faturaKalemleri'])) {
                        foreach ($detailedInvoice['faturaKalemleri'] as $index => $kalem) {
                            
                            // Tüm olası KDV oranı alan isimlerini kontrol et
                            $kdvFields = ['kdvOrani', 'kdvOran', 'vergiOrani', 'vergiOran', 'kdv', 'vergi', 'taxRate', 'tax'];
                            foreach ($kdvFields as $field) {
                                if (isset($kalem[$field]) && $kalem[$field] > 0) {
                                    $kdvOrani = floatval($kalem[$field]);
                                    break 2; // Hem iç hem dış döngüden çık
                                }
                            }
                        }
                    } else {
                        // Alternatif alan isimlerini kontrol et
                        if (isset($detailedInvoice['kalemler']) && is_array($detailedInvoice['kalemler'])) {
                            foreach ($detailedInvoice['kalemler'] as $index => $kalem) {
                                if (isset($kalem['kdvOrani']) && $kalem['kdvOrani'] > 0) {
                                    $kdvOrani = floatval($kalem['kdvOrani']);
                                    break;
                                }
                            }
                        }
                        
                        // Düzenleme sistemindeki gibi malHizmetTable alanını kontrol et
                        if (isset($detailedInvoice['malHizmetTable']) && is_array($detailedInvoice['malHizmetTable'])) {
                            foreach ($detailedInvoice['malHizmetTable'] as $index => $kalem) {
                                if (isset($kalem['kdvOrani']) && $kalem['kdvOrani'] > 0) {
                                    $kdvOrani = floatval($kalem['kdvOrani']);
                                    break;
                                }
                            }
                        }
                    }
                    
                    // Alıcı bilgilerini düzgün şekilde birleştir
                    $aliciUnvan = '';
                    if (isset($detailedInvoice['aliciUnvanAdSoyad']) && !empty($detailedInvoice['aliciUnvanAdSoyad'])) {
                        $aliciUnvan = $detailedInvoice['aliciUnvanAdSoyad'];
                    } elseif (isset($detailedInvoice['aliciUnvan']) && !empty($detailedInvoice['aliciUnvan'])) {
                        $aliciUnvan = $detailedInvoice['aliciUnvan'];
                    } elseif (isset($detailedInvoice['aliciAdi']) || isset($detailedInvoice['aliciSoyadi'])) {
                        $ad = $detailedInvoice['aliciAdi'] ?? '';
                        $soyad = $detailedInvoice['aliciSoyadi'] ?? '';
                        $aliciUnvan = trim($ad . ' ' . $soyad);
                    } elseif (isset($inv['aliciUnvanAdSoyad']) && !empty($inv['aliciUnvanAdSoyad'])) {
                        $aliciUnvan = $inv['aliciUnvanAdSoyad'];
                    } elseif (isset($inv['aliciUnvan']) && !empty($inv['aliciUnvan'])) {
                        $aliciUnvan = $inv['aliciUnvan'];
                    } elseif (isset($inv['aliciAdi']) || isset($inv['aliciSoyadi'])) {
                        $ad = $inv['aliciAdi'] ?? '';
                        $soyad = $inv['aliciSoyadi'] ?? '';
                        $aliciUnvan = trim($ad . ' ' . $soyad);
                    }
                    
                    $reportData[] = [
                        'faturaNo' => $detailedInvoice['belgeNumarasi'] ?? $inv['belgeNumarasi'] ?? '',
                        'faturaTarihi' => $detailedInvoice['belgeTarihi'] ?? $inv['belgeTarihi'] ?? '',
                        'aliciUnvan' => $aliciUnvan,
                        'aliciVknTckn' => $detailedInvoice['aliciVknTckn'] ?? $inv['aliciVknTckn'] ?? '',
                        'urunAdi' => $urunAdi,
                        'malHizmetToplamTutari' => $malHizmetTutari,
                        'vergilerDahilToplamTutar' => $vergilerDahilTutar,
                        'kdvOrani' => $kdvOrani,
                        'paraBirimi' => $detailedInvoice['paraBirimi'] ?? $inv['paraBirimi'] ?? 'TRY',
                        'durum' => $status
                    ];
                    
                    $totalMalHizmet += $malHizmetTutari;
                    $totalVergilerDahil += $vergilerDahilTutar;
                    $invoiceCount++;
                }
            }
            
            if ($reportType === 'excel') {
                // Excel dosyası oluştur
                require_once __DIR__ . '/vendor/autoload.php';
                
                $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();
                
                // Başlık satırı
                $sheet->setCellValue('A1', 'Fatura No');
                $sheet->setCellValue('B1', 'Fatura Tarihi');
                $sheet->setCellValue('C1', 'Alıcı Unvan');
                $sheet->setCellValue('D1', 'Alıcı VKN/TCKN');
                $sheet->setCellValue('E1', 'Ürün/Hizmet');
                $sheet->setCellValue('F1', 'Mal Hizmet Toplam Tutarı');
                $sheet->setCellValue('G1', 'Vergiler Dahil Toplam Tutar');
                $sheet->setCellValue('H1', 'KDV Oranı');
                $sheet->setCellValue('I1', 'Para Birimi');
                $sheet->setCellValue('J1', 'Durum');
                
                // Başlık stilini ayarla
                $sheet->getStyle('A1:J1')->getFont()->setBold(true);
                $sheet->getStyle('A1:J1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
                $sheet->getStyle('A1:J1')->getFill()->getStartColor()->setRGB('4472C4');
                $sheet->getStyle('A1:J1')->getFont()->getColor()->setRGB('FFFFFF');
                
                // Veri satırları
                $row = 2;
                foreach ($reportData as $data) {
                    $sheet->setCellValue('A' . $row, $data['faturaNo']);
                    $sheet->setCellValue('B' . $row, $data['faturaTarihi']);
                    $sheet->setCellValue('C' . $row, $data['aliciUnvan']);
                    $sheet->setCellValue('D' . $row, $data['aliciVknTckn']);
                    $sheet->setCellValue('E' . $row, $data['urunAdi']);
                    $sheet->setCellValue('F' . $row, $data['malHizmetToplamTutari']);
                    $sheet->setCellValue('G' . $row, $data['vergilerDahilToplamTutar']);
                    $sheet->setCellValue('H' . $row, $data['kdvOrani'] ? $data['kdvOrani'] . '%' : '');
                    $sheet->setCellValue('I' . $row, $data['paraBirimi']);
                    $sheet->setCellValue('J' . $row, $data['durum']);
                    $row++;
                }
                
                // Toplam satırı
                $sheet->setCellValue('A' . $row, 'TOPLAM');
                $sheet->setCellValue('F' . $row, $totalMalHizmet);
                $sheet->setCellValue('G' . $row, $totalVergilerDahil);
                $sheet->getStyle('A' . $row . ':J' . $row)->getFont()->setBold(true);
                $sheet->getStyle('A' . $row . ':J' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
                $sheet->getStyle('A' . $row . ':J' . $row)->getFill()->getStartColor()->setRGB('FFD700');
                
                // Sütun genişliklerini ayarla
                $sheet->getColumnDimension('A')->setWidth(15);
                $sheet->getColumnDimension('B')->setWidth(12);
                $sheet->getColumnDimension('C')->setWidth(30);
                $sheet->getColumnDimension('D')->setWidth(15);
                $sheet->getColumnDimension('E')->setWidth(25);
                $sheet->getColumnDimension('F')->setWidth(20);
                $sheet->getColumnDimension('G')->setWidth(20);
                $sheet->getColumnDimension('H')->setWidth(10);
                $sheet->getColumnDimension('I')->setWidth(12);
                $sheet->getColumnDimension('J')->setWidth(10);
                
                // Sayısal sütunlar için format
                $sheet->getStyle('E2:F' . ($row - 1))->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->getStyle('E' . $row . ':F' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
                
                // Excel dosyasını oluştur
                $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
                $filename = 'fatura_raporu_' . date('Y-m-d_H-i-s') . '.xlsx';
                $filepath = sys_get_temp_dir() . '/' . $filename;
                $writer->save($filepath);
                
                // Dosyayı oku ve base64 encode et
                $fileContent = file_get_contents($filepath);
                $base64Content = base64_encode($fileContent);
                unlink($filepath); // Geçici dosyayı sil
                
                echo json_encode([
                    'success' => true,
                    'message' => "Rapor oluşturuldu! {$invoiceCount} fatura bulundu.",
                    'filename' => $filename,
                    'fileContent' => $base64Content,
                    'totalMalHizmet' => $totalMalHizmet,
                    'totalVergilerDahil' => $totalVergilerDahil,
                    'invoiceCount' => $invoiceCount
                ]);
            } else {
                // JSON formatında döndür
                echo json_encode([
                    'success' => true,
                    'message' => "Rapor oluşturuldu! {$invoiceCount} fatura bulundu.",
                    'data' => $reportData,
                    'summary' => [
                        'totalMalHizmet' => $totalMalHizmet,
                        'totalVergilerDahil' => $totalVergilerDahil,
                        'invoiceCount' => $invoiceCount
                    ]
                ]);
            }
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Rapor oluşturulamadı: ' . $e->getMessage()]);
        }
        break;

    // ==================== MAIL GÖNDERİM SİSTEMİ ====================
    
    case 'preview_template':
        header('Content-Type: application/json');
        if (!is_logged_in()) {
            die(json_encode(['success' => false, 'message' => 'Önce giriş yapmalısınız!']));
        }
        try {
            require_once __DIR__ . '/mail_templates.php';
            
            $templateType = $input['template_type'] ?? 'default';
            $logoUrl = $input['logo_url'] ?? '';
            $customMessage = $input['custom_message'] ?? '';
            $sampleData = $input['sample_data'] ?? [];
            
            $html = generateEmailFromTemplate($sampleData, $templateType, $customMessage, $logoUrl);
            
            echo json_encode(['success' => true, 'html' => $html]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Önizleme oluşturulamadı: ' . $e->getMessage()]);
        }
        break;
    
    case 'save_smtp_settings':
        header('Content-Type: application/json');
        if (!is_logged_in()) {
            die(json_encode(['success' => false, 'message' => 'Önce giriş yapmalısınız!']));
        }
        try {
            $result = saveSmtpSettings($input);
            echo json_encode($result);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Ayarlar kaydedilemedi: ' . $e->getMessage()]);
        }
        break;

    case 'get_smtp_settings':
        header('Content-Type: application/json');
        if (!is_logged_in()) {
            die(json_encode(['success' => false, 'message' => 'Önce giriş yapmalısınız!']));
        }
        $settings = getSmtpSettings();
        if ($settings) {
            // Şifreyi gizle
            $settings['password'] = str_repeat('*', 8);
            echo json_encode(['success' => true, 'settings' => $settings]);
        } else {
            echo json_encode(['success' => false, 'message' => 'SMTP ayarları bulunamadı.']);
        }
        break;

    case 'test_smtp':
        header('Content-Type: application/json');
        if (!is_logged_in()) {
            die(json_encode(['success' => false, 'message' => 'Önce giriş yapmalısınız!']));
        }
        try {
            $result = testSmtpConnection($input);
            echo json_encode($result);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Test başarısız: ' . $e->getMessage()]);
        }
        break;

    case 'export_smtp_settings':
        header('Content-Type: application/json');
        if (!is_logged_in()) {
            die(json_encode(['success' => false, 'message' => 'Önce giriş yapmalısınız!']));
        }
        try {
            $result = exportSmtpSettings();
            echo json_encode($result);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Dışa aktarma hatası: ' . $e->getMessage()]);
        }
        break;

    case 'import_smtp_settings':
        header('Content-Type: application/json');
        if (!is_logged_in()) {
            die(json_encode(['success' => false, 'message' => 'Önce giriş yapmalısınız!']));
        }
        try {
            $jsonData = $input['json_data'] ?? '';
            if (empty($jsonData)) {
                throw new Exception('JSON verisi boş.');
            }
            $result = importSmtpSettings($jsonData);
            echo json_encode($result);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'İçe aktarma hatası: ' . $e->getMessage()]);
        }
        break;

    case 'send_invoice_emails':
        header('Content-Type: application/json');
        if (!is_logged_in()) {
            die(json_encode(['success' => false, 'message' => 'Önce giriş yapmalısınız!']));
        }
        
        try {
            // Error logging aç
            error_log("send_invoice_emails başladı");
            
            // SMTP ayarlarını kontrol et
            $smtpSettings = getSmtpSettings();
            if (!$smtpSettings) {
                throw new Exception('SMTP ayarları yapılmamış. Lütfen önce ayarları yapın.');
            }
            
            error_log("SMTP ayarları alındı");
            
            // Fatura listesini al
            $invoiceUuids = $input['invoices'] ?? [];
            if (empty($invoiceUuids)) {
                throw new Exception('Gönderilecek fatura seçilmedi.');
            }
            
            error_log("Fatura sayısı: " . count($invoiceUuids));
            
            // GIB instance
            $gib = get_gib_instance();
            error_log("GIB instance oluşturuldu");
            
            // Faturaları hazırla
            $invoices = [];
            foreach ($invoiceUuids as $invoiceData) {
                $uuid = $invoiceData['uuid'] ?? '';
                error_log("İşlenen UUID: $uuid");
                if (empty($uuid)) {
                    error_log("UUID boş, atlanıyor");
                    continue;
                }
                
                // Fatura detaylarını al
                try {
                    error_log("getDocument çağrılıyor - UUID: $uuid");
                    $detailedInvoice = $gib->getDocument($uuid);
                    error_log("getDocument başarılı");
                    
                    // Debug: Fatura detayını logla
                    error_log("Fatura detayı alındı - UUID: $uuid");
                    error_log("Fatura anahtarları: " . implode(', ', array_keys($detailedInvoice)));
                    
                    // E-posta adresini fatura detayından çek - tüm olası alan adlarını kontrol et
                    $aliciEmail = '';
                    $possibleEmailFields = ['aliciEposta', 'aliciEmail', 'email', 'eposta', 'Email', 'Eposta'];
                    
                    foreach ($possibleEmailFields as $field) {
                        if (isset($detailedInvoice[$field]) && !empty($detailedInvoice[$field])) {
                            $aliciEmail = $detailedInvoice[$field];
                            error_log("E-posta bulundu - Alan: $field, Değer: $aliciEmail");
                            break;
                        }
                    }
                    
                    // Frontend'den gelen veriyi de kontrol et
                    if (empty($aliciEmail) && isset($invoiceData['aliciEmail']) && !empty($invoiceData['aliciEmail'])) {
                        $aliciEmail = $invoiceData['aliciEmail'];
                        error_log("E-posta frontend'den alındı: $aliciEmail");
                    }
                    
                    if (empty($aliciEmail)) {
                        error_log("E-POSTA BULUNAMADI! Fatura detayı: " . json_encode($detailedInvoice, JSON_UNESCAPED_UNICODE));
                    }
                    
                    // Mail için gerekli bilgileri hazırla
                    $invoices[] = [
                        'uuid' => $uuid,
                        'belgeNumarasi' => $detailedInvoice['belgeNumarasi'] ?? $invoiceData['belgeNumarasi'] ?? 'N/A',
                        'faturaTarihi' => $detailedInvoice['faturaTarihi'] ?? $invoiceData['faturaTarihi'] ?? '',
                        'aliciUnvanAdSoyad' => $detailedInvoice['aliciUnvanAdSoyad'] ?? $invoiceData['aliciUnvanAdSoyad'] ?? '',
                        'aliciEmail' => $aliciEmail,
                        'toplamTutar' => $detailedInvoice['vergilerDahilToplamTutar'] ?? $invoiceData['toplamTutar'] ?? 0,
                        'paraBirimi' => $detailedInvoice['paraBirimi'] ?? 'TRY'
                    ];
                } catch (Exception $e) {
                    error_log("Fatura detayı alınamadı (UUID: $uuid): " . $e->getMessage());
                    // Mevcut bilgilerle devam et (e-posta olmayabilir)
                    $invoices[] = [
                        'uuid' => $uuid,
                        'belgeNumarasi' => $invoiceData['belgeNumarasi'] ?? 'N/A',
                        'faturaTarihi' => $invoiceData['faturaTarihi'] ?? '',
                        'aliciUnvanAdSoyad' => $invoiceData['aliciUnvanAdSoyad'] ?? '',
                        'aliciEmail' => $invoiceData['aliciEmail'] ?? '', // Frontend'den gelen (varsa)
                        'toplamTutar' => $invoiceData['toplamTutar'] ?? 0,
                        'paraBirimi' => 'TRY'
                    ];
                }
            }
            
            // Debug: Hazırlanan faturaları logla
            error_log("Hazırlanan fatura sayısı: " . count($invoices));
            foreach ($invoices as $inv) {
                error_log("Fatura: " . $inv['belgeNumarasi'] . " - Email: " . ($inv['aliciEmail'] ?: 'YOK'));
            }
            
            // Toplu mail gönder
            $result = sendBulkInvoiceEmails($invoices, $smtpSettings, $gib);
            error_log("Mail gönderimi tamamlandı");
            echo json_encode($result);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Mail gönderme hatası: ' . $e->getMessage()]);
        }
        break;

    default:
        // Eğer path boşsa (ana sayfa yüklemesi), sessizce çık
        if (empty($path)) {
            http_response_code(200);
            exit();
        }
        
        // Geçersiz endpoint
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid endpoint: ' . $path]);
        break;
}