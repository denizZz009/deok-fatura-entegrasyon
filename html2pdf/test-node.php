<?php
// Node.js yolunu test et

$possiblePaths = [
    '/opt/plesk/node/23/bin/node',
    '/opt/plesk/node/22/bin/node',
    '/opt/plesk/node/20/bin/node',
    '/opt/plesk/node/18/bin/node',
    '/usr/bin/node',
    '/usr/local/bin/node'
];

echo "<h2>Node.js Yol Testi</h2>";

foreach ($possiblePaths as $path) {
    echo "<p><strong>$path:</strong> ";
    if (file_exists($path)) {
        echo "✅ Dosya var - ";
        if (is_executable($path)) {
            echo "✅ Çalıştırılabilir<br>";
            
            // Versiyonu kontrol et
            exec("$path --version 2>&1", $output, $code);
            echo "Versiyon: " . implode(' ', $output) . " (Return code: $code)<br>";
            
            // Test çalıştır
            $testScript = __DIR__ . '/convert-to-pdf.js';
            $testHtml = tempnam(sys_get_temp_dir(), 'test_') . '.html';
            $testPdf = tempnam(sys_get_temp_dir(), 'test_') . '.pdf';
            
            file_put_contents($testHtml, '<html><body><h1>Test</h1></body></html>');
            
            $command = escapeshellarg($path) . " " . escapeshellarg($testScript) . " " . 
                       escapeshellarg($testHtml) . " " . escapeshellarg($testPdf) . " 2>&1";
            
            echo "Komut: $command<br>";
            exec($command, $testOutput, $testCode);
            echo "Test sonucu: " . implode('<br>', $testOutput) . "<br>";
            echo "Return code: $testCode<br>";
            
            if (file_exists($testPdf)) {
                echo "✅ PDF oluşturuldu! (" . filesize($testPdf) . " bytes)<br>";
                unlink($testPdf);
            } else {
                echo "❌ PDF oluşturulamadı<br>";
            }
            
            unlink($testHtml);
            
        } else {
            echo "❌ Çalıştırılamaz";
        }
    } else {
        echo "❌ Dosya yok";
    }
    echo "</p>";
}

// which node dene
echo "<h3>which node:</h3>";
exec('which node 2>&1', $whichOutput, $whichCode);
echo "<pre>" . implode("\n", $whichOutput) . "</pre>";
echo "Return code: $whichCode<br>";

// env kontrol
echo "<h3>PATH:</h3>";
echo "<pre>" . getenv('PATH') . "</pre>";
?>
