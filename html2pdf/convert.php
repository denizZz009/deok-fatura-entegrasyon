<?php
// E-Arşiv HTML'i PDF'e çevirme
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $htmlContent = $_POST['html'] ?? '';
    
    if (empty($htmlContent)) {
        http_response_code(400);
        echo json_encode(['error' => 'HTML içeriği boş']);
        exit;
    }
    
    // Geçici dosyalar oluştur
    $tempHtml = tempnam(sys_get_temp_dir(), 'fatura_') . '.html';
    file_put_contents($tempHtml, $htmlContent);
    
    $pdfFile = tempnam(sys_get_temp_dir(), 'pdf_') . '.pdf';
    
    // Node.js script'ini çalıştır
    $nodeScript = __DIR__ . '/convert-to-pdf.js';
    
    // Windows için node.exe tam yolunu bul
    $nodePath = 'node'; // Varsayılan
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        // Windows'ta node.exe yolunu bul
        exec('where node', $whereOutput, $whereCode);
        if ($whereCode === 0 && !empty($whereOutput[0])) {
            $nodePath = $whereOutput[0];
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
        
        $pdfContent = base64_encode(file_get_contents($pdfFile));
        
        unlink($tempHtml);
        unlink($pdfFile);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'pdf' => $pdfContent,
            'filename' => 'fatura_' . date('Y-m-d_H-i-s') . '.pdf',
            'size' => $pdfSize
        ]);
    } else {
        // Hata
        error_log("PDF oluşturulamadı - Return code: $returnCode");
        error_log("Output: " . implode("\n", $output));
        
        unlink($tempHtml);
        if (file_exists($pdfFile)) unlink($pdfFile);
        
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'error' => 'PDF oluşturulamadı',
            'details' => implode("\n", $output),
            'code' => $returnCode
        ]);
    }
}
?>