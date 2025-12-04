<?php
/**
 * İptal Edilmiş Fatura Kontrolü Test Dosyası
 * 
 * E-Arşiv'den gelen iptalItiraz alanının doğru algılanıp algılanmadığını test eder
 */

// Optimized functions'ı dahil et
require_once 'optimized_functions.php';

echo "=== İPTAL EDİLMİŞ FATURA KONTROLÜ TEST ===\n\n";

// Test 1: İptal edilmiş fatura (iptalItiraz: "0" string)
$iptalEdilmisFatura = [
    'belgeNumarasi' => 'GIB2025000000589',
    'aliciVknTckn' => '14842622818',
    'aliciUnvanAdSoyad' => 'SELDA COŞKUN SAVAŞÇI',
    'belgeTarihi' => '13-09-2025',
    'belgeTuru' => 'FATURA',
    'ettn' => '55f00fbe-923f-11f0-88ea-cd0da5552dc4',
    'iptalItiraz' => '0',  // String olarak 0
    'onayDurumu' => 'Onaylandı',
    'talepDurum' => '1'
];

$durum1 = determineInvoiceStatus($iptalEdilmisFatura);
echo "Test 1 - İptal Edilmiş Fatura (iptalItiraz: '0' string)\n";
echo "Beklenen: İptal Edilmiş\n";
echo "Sonuç: $durum1\n";
echo $durum1 === 'İptal Edilmiş' ? "✅ BAŞARILI\n\n" : "❌ BAŞARISIZ\n\n";

// Test 2: İptal edilmiş fatura (iptalItiraz: 0 integer)
$iptalEdilmisFatura2 = [
    'belgeNumarasi' => 'GIB2025000000590',
    'aliciVknTckn' => '14842622818',
    'aliciUnvanAdSoyad' => 'TEST KULLANICI',
    'belgeTarihi' => '13-09-2025',
    'ettn' => '55f00fbe-923f-11f0-88ea-cd0da5552dc5',
    'iptalItiraz' => 0,  // Integer olarak 0
    'onayDurumu' => 'Onaylandı'
];

$durum2 = determineInvoiceStatus($iptalEdilmisFatura2);
echo "Test 2 - İptal Edilmiş Fatura (iptalItiraz: 0 integer)\n";
echo "Beklenen: İptal Edilmiş\n";
echo "Sonuç: $durum2\n";
echo $durum2 === 'İptal Edilmiş' ? "✅ BAŞARILI\n\n" : "❌ BAŞARISIZ\n\n";

// Test 3: Normal onaylı fatura (iptalItiraz yok)
$normalFatura = [
    'belgeNumarasi' => 'GIB2025000000585',
    'aliciVknTckn' => '37534108292',
    'aliciUnvanAdSoyad' => 'BETÜL KARACAKAYA CUYA',
    'belgeTarihi' => '13-09-2025',
    'ettn' => '536a899a-923f-11f0-8d10-1d92cd86ff2d',
    'onayDurumu' => 'Onaylandı'
    // iptalItiraz alanı yok
];

$durum3 = determineInvoiceStatus($normalFatura);
echo "Test 3 - Normal Onaylı Fatura (iptalItiraz yok)\n";
echo "Beklenen: Onaylanmış\n";
echo "Sonuç: $durum3\n";
echo $durum3 === 'Onaylanmış' ? "✅ BAŞARILI\n\n" : "❌ BAŞARISIZ\n\n";

// Test 4: Normal onaylı fatura (iptalItiraz: "1")
$normalFatura2 = [
    'belgeNumarasi' => 'GIB2025000000586',
    'aliciVknTckn' => '57529263324',
    'aliciUnvanAdSoyad' => 'ELİF ÖZMEN',
    'belgeTarihi' => '13-09-2025',
    'ettn' => '53d3c4aa-923f-11f0-90c2-ed81ecf730e3',
    'iptalItiraz' => '1',  // Normal durum
    'onayDurumu' => 'Onaylandı'
];

$durum4 = determineInvoiceStatus($normalFatura2);
echo "Test 4 - Normal Onaylı Fatura (iptalItiraz: '1')\n";
echo "Beklenen: Onaylanmış\n";
echo "Sonuç: $durum4\n";
echo $durum4 === 'Onaylanmış' ? "✅ BAŞARILI\n\n" : "❌ BAŞARISIZ\n\n";

// Test 5: Taslak fatura
$taslakFatura = [
    'belgeNumarasi' => 'GIB2025000000587',
    'aliciVknTckn' => '25079578328',
    'aliciUnvanAdSoyad' => 'HAMİDE ATÇI',
    'belgeTarihi' => '13-09-2025',
    'ettn' => '54e14070-923f-11f0-bf7a-c3c579850dd9',
    'onayDurumu' => 'Taslak'
];

$durum5 = determineInvoiceStatus($taslakFatura);
echo "Test 5 - Taslak Fatura\n";
echo "Beklenen: Taslak\n";
echo "Sonuç: $durum5\n";
echo $durum5 === 'Taslak' ? "✅ BAŞARILI\n\n" : "❌ BAŞARISIZ\n\n";

// Test 6: Silinmiş fatura
$silinmisFatura = [
    'belgeNumarasi' => 'GIB2025000000588',
    'aliciVknTckn' => '52372575242',
    'aliciUnvanAdSoyad' => 'ELİF ÖNDER',
    'belgeTarihi' => '13-09-2025',
    'ettn' => '55558106-923f-11f0-b0ef-7189e0116890',
    'onayDurumu' => 'Silinmiş'
];

$durum6 = determineInvoiceStatus($silinmisFatura);
echo "Test 6 - Silinmiş Fatura\n";
echo "Beklenen: Silinmiş\n";
echo "Sonuç: $durum6\n";
echo $durum6 === 'Silinmiş' ? "✅ BAŞARILI\n\n" : "❌ BAŞARISIZ\n\n";

echo "=== TEST TAMAMLANDI ===\n";
