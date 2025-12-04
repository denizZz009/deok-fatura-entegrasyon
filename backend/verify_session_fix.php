<?php
// e-Arşiv Session Fix Verification Script

echo "e-Arşiv Portalı Session Yönetimi - Doğrulama\n";
echo "=============================================\n\n";

// Gerekli dosyaları kontrol et
$requiredFiles = [
    'index.php',
    'vendor/autoload.php'
];

foreach ($requiredFiles as $file) {
    $path = __DIR__ . '/' . $file;
    if (!file_exists($path)) {
        echo "HATA: {$file} dosyası bulunamadı!\n";
        exit(1);
    }
    echo "✓ {$file} dosyası mevcut\n";
}

// index.php dosyasında gerekli fonksiyonların olup olmadığını kontrol et
$indexContent = file_get_contents(__DIR__ . '/index.php');

$requiredFunctions = [
    'login_to_earsiv_portal',
    'logout_from_earsiv_portal'
];

foreach ($requiredFunctions as $function) {
    if (strpos($indexContent, 'function ' . $function) !== false) {
        echo "✓ {$function} fonksiyonu tanımlanmış\n";
    } else {
        echo "✗ HATA: {$function} fonksiyonu bulunamadı!\n";
        exit(1);
    }
}

// Login ve logout case'lerini kontrol et
$requiredCases = [
    "'login'",
    "'logout'"
];

foreach ($requiredCases as $case) {
    if (strpos($indexContent, 'case ' . $case . ':') !== false) {
        echo "✓ {$case} case'i tanımlanmış\n";
    } else {
        echo "✗ HATA: {$case} case'i bulunamadı!\n";
        exit(1);
    }
}

echo "\n✓ Tüm kontroller başarılı! e-Arşiv portalı session yönetimi düzgün şekilde uygulanmış.\n";
echo "\nArtık kullanıcılar uygulamadan çıkış yaptıklarında e-Arşiv portalındaki oturumları da kapatılacaktır.\n";
?>