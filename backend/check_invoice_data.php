<?php
/**
 * Fatura Veri Yapısı Kontrol Aracı
 * 
 * GİB'den gelen ham veriyi görmek için
 */

session_start();

if (!isset($_SESSION['usercode']) || !isset($_SESSION['password'])) {
    die('Önce giriş yapmalısınız!');
}

require __DIR__ . '/vendor/autoload.php';

use Mlevent\Fatura\Gib;

try {
    $gib = (new Gib)->login($_SESSION['usercode'], $_SESSION['password']);
    
    // Son 7 günün faturalarını al
    $start = date('d/m/Y', strtotime('-7 days'));
    $end = date('d/m/Y');
    
    echo "<h1>Fatura Veri Yapısı Analizi</h1>";
    echo "<p>Tarih Aralığı: $start - $end</p>";
    echo "<hr>";
    
    $invoices = $gib->getAll($start, $end);
    
    echo "<h2>Toplam Fatura: " . count($invoices) . "</h2>";
    
    if (count($invoices) > 0) {
        echo "<h2>İlk Fatura Veri Yapısı:</h2>";
        
        $firstInvoice = $invoices[0];
        
        echo "<h3>Tüm Alanlar:</h3>";
        echo "<pre>";
        print_r(array_keys($firstInvoice));
        echo "</pre>";
        
        echo "<h3>Tutar İle İlgili Alanlar:</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Alan Adı</th><th>Değer</th><th>Tip</th></tr>";
        
        $amountFields = [
            'vergilerDahilToplamTutar',
            'toplamTutar',
            'odenecekTutar',
            'genelToplam',
            'malhizmetToplamTutari',
            'malHizmetToplamTutari',
            'matrah',
            'hesaplanankdv',
            'hesaplananKdv',
            'kdvTutari',
            'vergiTutari'
        ];
        
        foreach ($amountFields as $field) {
            $value = $firstInvoice[$field] ?? 'YOK';
            $type = isset($firstInvoice[$field]) ? gettype($firstInvoice[$field]) : '-';
            $color = ($value !== 'YOK' && $value > 0) ? 'green' : 'red';
            echo "<tr style='color: $color'>";
            echo "<td><strong>$field</strong></td>";
            echo "<td>$value</td>";
            echo "<td>$type</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        echo "<h3>Fatura Kalemleri:</h3>";
        if (isset($firstInvoice['faturaKalemleri'])) {
            echo "<pre>";
            print_r($firstInvoice['faturaKalemleri']);
            echo "</pre>";
        } else {
            echo "<p style='color: red;'>faturaKalemleri alanı YOK</p>";
        }
        
        if (isset($firstInvoice['malHizmetTable'])) {
            echo "<h3>Mal Hizmet Table:</h3>";
            echo "<pre>";
            print_r($firstInvoice['malHizmetTable']);
            echo "</pre>";
        }
        
        echo "<h3>Tam Veri (JSON):</h3>";
        echo "<pre style='background: #f5f5f5; padding: 10px; overflow: auto; max-height: 400px;'>";
        echo json_encode($firstInvoice, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        echo "</pre>";
        
        // İkinci fatura varsa onu da göster
        if (count($invoices) > 1) {
            echo "<hr>";
            echo "<h2>İkinci Fatura (Karşılaştırma):</h2>";
            
            $secondInvoice = $invoices[1];
            
            echo "<h3>Tutar Alanları:</h3>";
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>Alan Adı</th><th>Değer</th></tr>";
            
            foreach ($amountFields as $field) {
                $value = $secondInvoice[$field] ?? 'YOK';
                $color = ($value !== 'YOK' && $value > 0) ? 'green' : 'red';
                echo "<tr style='color: $color'>";
                echo "<td><strong>$field</strong></td>";
                echo "<td>$value</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        }
    } else {
        echo "<p>Fatura bulunamadı.</p>";
    }
    
    echo "<hr>";
    echo "<p><a href='../index.html'>Ana Sayfaya Dön</a></p>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>Hata:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
