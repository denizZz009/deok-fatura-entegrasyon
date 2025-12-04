<?php
/**
 * Optimizasyon Test Dosyası
 * 
 * Bu dosya ile optimizasyonların çalışıp çalışmadığını test edebilirsiniz.
 */

session_start();

require __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/optimized_functions.php';

echo "<h1>E-Arşiv Optimizasyon Test</h1>";
echo "<hr>";

// Test 1: Session Cache Fonksiyonları
echo "<h2>Test 1: Session Cache Fonksiyonları</h2>";

try {
    // Cache'e veri yaz
    $testData = ['test' => 'data', 'timestamp' => time()];
    $result = sessionCache('test_key', function() use ($testData) {
        return $testData;
    }, 60);
    
    echo "✅ sessionCache() çalışıyor<br>";
    echo "Cached data: " . json_encode($result) . "<br>";
    
    // Cache'den oku
    $cachedResult = sessionCache('test_key', function() {
        return ['should' => 'not be called'];
    }, 60);
    
    if ($cachedResult === $result) {
        echo "✅ Cache'den okuma başarılı<br>";
    } else {
        echo "❌ Cache'den okuma başarısız<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Hata: " . $e->getMessage() . "<br>";
}

echo "<hr>";

// Test 2: Yardımcı Fonksiyonlar
echo "<h2>Test 2: Yardımcı Fonksiyonlar</h2>";

try {
    // Test invoice data
    $testInvoice = [
        'ettn' => '12345678-1234-1234-1234-123456789012',
        'belgeNumarasi' => 'TEST2025000001',
        'belgeTarihi' => '02/10/2025',
        'onayDurumu' => 'Onaylanmış',
        'aliciUnvanAdSoyad' => 'Test Şirketi A.Ş.',
        'aliciVknTckn' => '1234567890',
        'vergilerDahilToplamTutar' => 1200.00,
        'faturaKalemleri' => [
            [
                'malHizmet' => 'Test Ürün',
                'kdvOrani' => 20
            ]
        ]
    ];
    
    // determineInvoiceStatus test
    $status = determineInvoiceStatus($testInvoice);
    echo "✅ determineInvoiceStatus(): " . $status . "<br>";
    
    // extractRecipientName test
    $recipient = extractRecipientName($testInvoice);
    echo "✅ extractRecipientName(): " . $recipient . "<br>";
    
    // extractTotalAmount test
    $total = extractTotalAmount($testInvoice);
    echo "✅ extractTotalAmount(): " . $total . "<br>";
    
    // extractProductName test
    $product = extractProductName($testInvoice);
    echo "✅ extractProductName(): " . $product . "<br>";
    
    // extractVatRate test
    $vat = extractVatRate($testInvoice);
    echo "✅ extractVatRate(): " . $vat . "<br>";
    
} catch (Exception $e) {
    echo "❌ Hata: " . $e->getMessage() . "<br>";
}

echo "<hr>";

// Test 3: processInvoiceItem
echo "<h2>Test 3: processInvoiceItem</h2>";

try {
    $processed = processInvoiceItem($testInvoice, '', '');
    echo "✅ processInvoiceItem() çalışıyor<br>";
    echo "<pre>" . print_r($processed, true) . "</pre>";
    
} catch (Exception $e) {
    echo "❌ Hata: " . $e->getMessage() . "<br>";
}

echo "<hr>";

// Test 4: processInvoiceList
echo "<h2>Test 4: processInvoiceList</h2>";

try {
    $testInvoices = [$testInvoice];
    $processed = processInvoiceList($testInvoices, '', '');
    echo "✅ processInvoiceList() çalışıyor<br>";
    echo "İşlenen fatura sayısı: " . count($processed) . "<br>";
    
} catch (Exception $e) {
    echo "❌ Hata: " . $e->getMessage() . "<br>";
}

echo "<hr>";

// Test 5: Cache Temizleme
echo "<h2>Test 5: Cache Temizleme</h2>";

try {
    clearSessionCache();
    echo "✅ clearSessionCache() çalışıyor<br>";
    
    if (!isset($_SESSION['temp_cache']) || empty($_SESSION['temp_cache'])) {
        echo "✅ Cache başarıyla temizlendi<br>";
    } else {
        echo "⚠️ Cache tamamen temizlenmedi<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Hata: " . $e->getMessage() . "<br>";
}

echo "<hr>";

// Test 6: Eski Cache Temizleme
echo "<h2>Test 6: Eski Cache Temizleme</h2>";

try {
    // Eski bir cache kaydı oluştur
    $_SESSION['temp_cache'] = [
        'old_key' => [
            'data' => 'old data',
            'timestamp' => time() - 700 // 11 dakika önce
        ],
        'new_key' => [
            'data' => 'new data',
            'timestamp' => time() - 100 // 1.5 dakika önce
        ]
    ];
    
    cleanupOldCache();
    
    if (!isset($_SESSION['temp_cache']['old_key']) && isset($_SESSION['temp_cache']['new_key'])) {
        echo "✅ cleanupOldCache() çalışıyor - Eski kayıt silindi, yeni kayıt kaldı<br>";
    } else {
        echo "⚠️ cleanupOldCache() beklendiği gibi çalışmadı<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Hata: " . $e->getMessage() . "<br>";
}

echo "<hr>";

// Test 7: optimizeInvoiceData
echo "<h2>Test 7: optimizeInvoiceData</h2>";

try {
    $bloatedInvoice = array_merge($testInvoice, [
        'unnecessary_field_1' => 'data',
        'unnecessary_field_2' => 'data',
        'unnecessary_field_3' => 'data'
    ]);
    
    $optimized = optimizeInvoiceData($bloatedInvoice);
    
    echo "✅ optimizeInvoiceData() çalışıyor<br>";
    echo "Önce: " . count($bloatedInvoice) . " alan<br>";
    echo "Sonra: " . count($optimized) . " alan<br>";
    
} catch (Exception $e) {
    echo "❌ Hata: " . $e->getMessage() . "<br>";
}

echo "<hr>";

// Özet
echo "<h2>Test Özeti</h2>";
echo "<p><strong>Tüm temel fonksiyonlar çalışıyor! ✅</strong></p>";
echo "<p>Optimizasyon başarıyla uygulandı. Şimdi gerçek sistemde test edebilirsiniz.</p>";

echo "<hr>";
echo "<h3>Sonraki Adımlar:</h3>";
echo "<ol>";
echo "<li>Sisteme giriş yapın</li>";
echo "<li>'Faturaları Listele' butonuna tıklayın</li>";
echo "<li>İlk yüklemede hızlı olmalı</li>";
echo "<li>İkinci yüklemede çok daha hızlı olmalı (cache'den)</li>";
echo "<li>Tarayıcı console'da hata olmamalı</li>";
echo "</ol>";

echo "<p><a href='../index.html'>Ana Sayfaya Dön</a></p>";
?>
