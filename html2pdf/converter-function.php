<?php
/**
 * HTML'i PDF'e çeviren fonksiyon
 * Direkt çağrılabilir, HTTP request gerektirmez
 */
function convertHtmlToPdf($htmlContent) {
    // Geçici dosyalar oluştur
    $tempHtml = tempnam(sys_get_temp_dir(), 'fatura_') . '.html';
    file_put_contents($tempHtml, $htmlContent);
    
    $pdfFile = tempnam(sys_get_temp_dir(), 'pdf_') . '.pdf';
    
    // Node.js script'ini çalıştır
    $nodeScript = __DIR__ . '/convert-to-pdf.js';
    
    // Node.js tam yolunu bul
    $nodePath = 'node'; // Varsayılan
    
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        // Windows'ta node.exe yolunu bul
        exec('where node', $whereOutput, $whereCode);
        if ($whereCode === 0 && !empty($whereOutput[0])) {
            $nodePath = trim($whereOutput[0]);
        }
    } else {
        // Linux/Unix'te node yolunu bul
        $possiblePaths = [
            '/opt/plesk/node/23/bin/node',  // Plesk Node.js 23 (sizin versiyonunuz)
            '/opt/plesk/node/22/bin/node',  // Plesk Node.js 22
            '/opt/plesk/node/20/bin/node',  // Plesk Node.js 20
            '/opt/plesk/node/18/bin/node',  // Plesk Node.js 18
            '/usr/bin/node',                 // Standart Linux
            '/usr/local/bin/node',           // Alternatif
            'node'                           // PATH'te varsa
        ];
        
        foreach ($possiblePaths as $path) {
            if (file_exists($path) && is_executable($path)) {
                $nodePath = $path;
                error_log("Node.js bulundu: $nodePath");
                break;
            }
        }
        
        // Eğer bulunamadıysa which komutu ile dene
        if ($nodePath === 'node') {
            exec('which node 2>/dev/null', $whichOutput, $whichCode);
            if ($whichCode === 0 && !empty($whichOutput[0])) {
                $nodePath = trim($whichOutput[0]);
                error_log("Node.js which ile bulundu: $nodePath");
            }
        }
    }
    
    $command = escapeshellarg($nodePath) . " " . escapeshellarg($nodeScript) . " " . 
               escapeshellarg($tempHtml) . " " . 
               escapeshellarg($pdfFile) . " 2>&1";
    
    error_log("Puppeteer command: $command");
    
    exec($command, $output, $returnCode);
    
    error_log("Puppeteer output: " . implode("\n", $output));
    error_log("Puppeteer return code: $returnCode");
    
    if ($returnCode === 0 && file_exists($pdfFile)) {
        // Başarılı
        $pdfSize = filesize($pdfFile);
        error_log("PDF başarıyla oluşturuldu: $pdfSize bytes");
        
        $pdfContent = file_get_contents($pdfFile);
        
        // Geçici dosyaları temizle
        unlink($tempHtml);
        unlink($pdfFile);
        
        return [
            'success' => true,
            'pdf' => $pdfContent,
            'size' => $pdfSize
        ];
    } else {
        // Hata
        error_log("PDF oluşturulamadı - Return code: $returnCode");
        
        // Geçici dosyaları temizle
        if (file_exists($tempHtml)) unlink($tempHtml);
        if (file_exists($pdfFile)) unlink($pdfFile);
        
        return [
            'success' => false,
            'error' => 'PDF oluşturulamadı',
            'details' => implode("\n", $output),
            'code' => $returnCode
        ];
    }
}
