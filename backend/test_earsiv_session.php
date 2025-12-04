<?php
// e-Arşiv portalı session test script

require __DIR__ . '/vendor/autoload.php';

session_start();

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
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception('e-Arşiv portalına bağlanılamadı. HTTP kodu: ' . $httpCode);
        }
        
        // Token'ı response içinden çıkar
        if (preg_match('/token=([a-f0-9]+)/', $response, $matches)) {
            $token = $matches[1];
            // Cookie dosyasını session'a kaydet
            $_SESSION['earsiv_cookie_file'] = $cookieFile;
            $_SESSION['earsiv_token'] = $token;
            return ['success' => true, 'token' => $token, 'cookie_file' => $cookieFile];
        } else {
            throw new Exception('e-Arşiv portalından token alınamadı.');
        }
        
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
        curl_close($ch);
        
        // Cookie dosyasını sil
        if (file_exists($cookieFile)) {
            unlink($cookieFile);
        }
        
        // Session değişkenlerini temizle
        unset($_SESSION['earsiv_cookie_file']);
        unset($_SESSION['earsiv_token']);
        
        if ($httpCode === 200) {
            return ['success' => true, 'message' => 'e-Arşiv oturumu başarıyla kapatıldı.'];
        } else {
            return ['success' => false, 'message' => 'e-Arşiv oturumu kapatılırken hata oluştu. HTTP kodu: ' . $httpCode];
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

// Test işlemleri
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'login') {
        $usercode = $_POST['usercode'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if ($usercode && $password) {
            try {
                $result = login_to_earsiv_portal($usercode, $password);
                echo json_encode($result);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Kullanıcı kodu ve şifre gerekli.']);
        }
    } elseif ($action === 'logout') {
        $result = logout_from_earsiv_portal();
        echo json_encode($result);
    } else {
        echo json_encode(['success' => false, 'message' => 'Geçersiz işlem.']);
    }
} else {
    // Test formu
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>e-Arşiv Portalı Session Test</title>
        <meta charset="UTF-8">
    </head>
    <body>
        <h1>e-Arşiv Portalı Session Test</h1>
        
        <h2>Login Test</h2>
        <form id="loginForm">
            <label>Kullanıcı Kodu: <input type="text" id="usercode" name="usercode" required></label><br><br>
            <label>Şifre: <input type="password" id="password" name="password" required></label><br><br>
            <button type="submit">Login</button>
        </form>
        
        <h2>Logout Test</h2>
        <form id="logoutForm">
            <button type="submit">Logout</button>
        </form>
        
        <h2>Sonuç</h2>
        <div id="result" style="border: 1px solid #ccc; padding: 10px; min-height: 50px;"></div>
        
        <script>
            document.getElementById('loginForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData();
                formData.append('action', 'login');
                formData.append('usercode', document.getElementById('usercode').value);
                formData.append('password', document.getElementById('password').value);
                
                fetch('test_earsiv_session.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    document.getElementById('result').innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
                })
                .catch(error => {
                    document.getElementById('result').innerHTML = '<p style="color: red;">Hata: ' + error.message + '</p>';
                });
            });
            
            document.getElementById('logoutForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData();
                formData.append('action', 'logout');
                
                fetch('test_earsiv_session.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    document.getElementById('result').innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
                })
                .catch(error => {
                    document.getElementById('result').innerHTML = '<p style="color: red;">Hata: ' + error.message + '</p>';
                });
            });
        </script>
    </body>
    </html>
    <?php
}
?>