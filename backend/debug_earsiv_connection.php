<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Debug: e-Arşiv portalı bağlantısını test et
echo "<h1>e-Arşiv Portalı Bağlantı Testi</h1>";

echo "<h2>Session Bilgileri:</h2>";
echo "<pre>";
echo "earsiv_token: " . ($_SESSION['earsiv_token'] ?? 'YOK') . "\n";
echo "earsiv_cookie_file: " . ($_SESSION['earsiv_cookie_file'] ?? 'YOK') . "\n";
echo "usercode: " . ($_SESSION['usercode'] ?? 'YOK') . "\n";
echo "</pre>";

if (isset($_SESSION['earsiv_cookie_file']) && file_exists($_SESSION['earsiv_cookie_file'])) {
    echo "<h2>Cookie Dosyası:</h2>";
    echo "<pre>" . file_get_contents($_SESSION['earsiv_cookie_file']) . "</pre>";
} else {
    echo "<h2>Cookie Dosyası: BULUNAMADI</h2>";
}

// Test telefon numarası sorgulama
if (isset($_GET['test']) && $_GET['test'] === 'phone') {
    echo "<h2>SMS Gönderme Testi:</h2>";
    
    try {
        require __DIR__ . '/index.php';
        
        // Doğrudan SMS gönderme testi yap
        $smsResponse = makeEarsivRequest('EARSIV_PORTAL_SMSSIFRE_GONDER', [
            'CEPTEL' => '5442508818',
            'KCEPTEL' => false,
            'TIP' => ''
        ]);
        
        echo "<h3>SMS Gönderme Başarılı!</h3>";
        echo "<pre>" . json_encode($smsResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
        
    } catch (Exception $e) {
        echo "<h3>Hata:</h3>";
        echo "<pre>" . $e->getMessage() . "</pre>";
    }
}

echo '<br><a href="?test=phone">SMS Gönderme Testi</a>';
echo '<br><a href="?test=relogin">e-Arşiv Yeniden Giriş Testi</a>';

// Test yeniden giriş
if (isset($_GET['test']) && $_GET['test'] === 'relogin') {
    echo "<h2>e-Arşiv Yeniden Giriş Testi:</h2>";
    
    try {
        require_once __DIR__ . '/index.php';
        
        $earsivLogin = login_to_earsiv_portal($_SESSION['usercode'], $_SESSION['password']);
        
        if ($earsivLogin['success']) {
            echo "<h3>Başarılı!</h3>";
            echo "<pre>Token: " . $_SESSION['earsiv_token'] . "</pre>";
            echo "<pre>Cookie: " . $_SESSION['earsiv_cookie_file'] . "</pre>";
        } else {
            echo "<h3>Başarısız!</h3>";
            echo "<pre>" . ($earsivLogin['message'] ?? 'Bilinmeyen hata') . "</pre>";
        }
        
    } catch (Exception $e) {
        echo "<h3>Hata:</h3>";
        echo "<pre>" . $e->getMessage() . "</pre>";
    }
}
?>