<?php
// Debug için basit test
header('Content-Type: application/json');

// Test verisi - API'den dönen formatta
$testData = [
    'success' => true,
    'data' => [
        'unvan' => '',
        'adi' => 'DENİZ EGEMEN',
        'soyadi' => 'EMARE',
        'vergiDairesi' => 'ZİYAPAŞA VERGİ DAİRESİ MÜD.',
        'adres' => '',
        'mahalleSemtIlce' => '',
        'sehir' => '',
        'ulke' => 'Türkiye',
        'postaKodu' => '',
        'tel' => '',
        'eposta' => ''
    ]
];

echo json_encode($testData);
?>