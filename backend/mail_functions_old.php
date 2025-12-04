<?php
/**
 * E-Fatura Mail Gönderme Sistemi
 * SMTP ile fatura maillerini gönderir
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * HTML'i geçici URL'e kaydet ve iLovePDF API ile PDF'e çevir
 */
function convertHtmlToPdfViaUrl($htmlContent, $invoiceNumber) {
    try {
        // Localhost kontrolü - localhost'ta URL yerine direkt HTML gönder
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $isLocalhost = (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false);
        
        if ($isLocalhost) {
            error_log("Localhost tespit edildi - HTML direkt gönderiliyor");
            return convertHtmlToPdfWithILovePDFDirect($htmlContent);
        }
        
        // 1. HTML'i geçici klasöre kaydet
        $tempDir = __DIR__ . '/../temp_invoices';
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        
        // Benzersiz dosya adı
        $filename = 'invoice_' . $invoiceNumber . '_' . time() . '.html';
        $filepath = $tempDir . '/' . $filename;
        
        file_put_contents($filepath, $htmlContent);
        error_log("HTML geçici dosyaya kaydedildi: $filename");
        
        // 2. Public URL oluştur
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $baseUrl = $protocol . '://' . $host;
        
        // Script'in bulunduğu dizini bul ve backend/ kısmını çıkar
        $scriptPath = dirname($_SERVER['SCRIPT_NAME']);
        $scriptPath = str_replace('/backend', '', $scriptPath);
        $publicUrl = $baseUrl . $scriptPath . '/temp_invoices/' . $filename;
        
        error_log("Public URL: $publicUrl");
        
        // 3. iLovePDF API ile PDF'e çevir (URL ile)
        $pdfContent = convertUrlToPdfWithILovePDF($publicUrl);
        
        // 4. Geçici dosyayı sil
        if (file_exists($filepath)) {
            unlink($filepath);
            error_log("Geçici dosya silindi: $filename");
        }
        
        return $pdfContent;
        
    } catch (Exception $e) {
        // Hata olursa geçici dosyayı temizle
        if (isset($filepath) && file_exists($filepath)) {
            unlink($filepath);
        }
        throw new Exception("URL to PDF hatası: " . $e->getMessage());
    }
}

/**
 * iLovePDF API ile HTML'i direkt PDF'e çevirir (localhost için)
 */
function convertHtmlToPdfWithILovePDFDirect($htmlContent) {
    try {
        require_once __DIR__ . '/vendor/autoload.php';
        
        // API Keys
        $publicKey = 'project_public_f99fd476f6c9530445242971c8dcd8ee_ToPeZ80e5c4242dce55d49d789c3de2bb7be7';
        $secretKey = 'secret_key_6937527f406e2bec65cea2435bfe048e_yU3cx8c2850177135442d5474ffb04481a403';
        
        error_log("iLovePDF - HTML direkt PDF'e çevriliyor");
        
        // HTML'i geçici dosyaya kaydet
        $tempHtmlFile = tempnam(sys_get_temp_dir(), 'invoice_') . '.html';
        file_put_contents($tempHtmlFile, $htmlContent);
        
        // iLovePDF instance
        $ilovepdf = new \Ilovepdf\Ilovepdf($publicKey, $secretKey);
        
        // HTML to PDF task oluştur
        $task = $ilovepdf->newTask('htmlpdf');
        
        // HTML dosyasını yükle
        $file = $task->addFile($tempHtmlFile);
        
        // Ayarlar
        $task->setPageSize('A4');
        $task->setMargin(10, 10, 10, 10);
        $task->setSinglePage(false);
        
        // PDF'e çevir
        error_log("iLovePDF - Dönüşüm başlatılıyor...");
        $task->execute();
        
        // PDF'i indir
        error_log("iLovePDF - PDF indiriliyor...");
        $task->download(sys_get_temp_dir());
        
        // İndirilen PDF'i oku
        $pdfFile = sys_get_temp_dir() . '/' . $file->output_filename;
        
        if (!file_exists($pdfFile)) {
            throw new Exception("PDF dosyası oluşturulamadı");
        }
        
        $pdfContent = file_get_contents($pdfFile);
        
        // Geçici dosyaları temizle
        if (file_exists($tempHtmlFile)) {
            unlink($tempHtmlFile);
        }
        if (file_exists($pdfFile)) {
            unlink($pdfFile);
        }
        
        error_log("iLovePDF - PDF oluşturuldu (Boyut: " . strlen($pdfContent) . " bytes)");
        
        return $pdfContent;
        
    } catch (Exception $e) {
        // Geçici dosyayı temizle
        if (isset($tempHtmlFile) && file_exists($tempHtmlFile)) {
            unlink($tempHtmlFile);
        }
        throw new Exception("iLovePDF Direct hatası: " . $e->getMessage());
    }
}

/**
 * iLovePDF API ile URL'den PDF oluştur
 */
function convertUrlToPdfWithILovePDF($url) {
    try {
        require_once __DIR__ . '/vendor/autoload.php';
        
        // API Keys
        $publicKey = 'project_public_f99fd476f6c9530445242971c8dcd8ee_ToPeZ80e5c4242dce55d49d789c3de2bb7be7';
        $secretKey = 'secret_key_6937527f406e2bec65cea2435bfe048e_yU3cx8c2850177135442d5474ffb04481a403';
        
        error_log("iLovePDF - URL'den PDF oluşturuluyor: $url");
        
        // iLovePDF instance
        $ilovepdf = new \Ilovepdf\Ilovepdf($publicKey, $secretKey);
        
        // HTML to PDF task oluştur
        $task = $ilovepdf->newTask('htmlpdf');
        
        // URL'i ekle
        $file = $task->addFile($url);
        
        // Ayarlar
        $task->setPageSize('A4');
        $task->setMargin(10, 10, 10, 10); // top, right, bottom, left (mm)
        $task->setSinglePage(false);
        
        // PDF'e çevir
        error_log("iLovePDF - Dönüşüm başlatılıyor...");
        $task->execute();
        
        // PDF'i indir
        error_log("iLovePDF - PDF indiriliyor...");
        $task->download(sys_get_temp_dir());
        
        // İndirilen PDF'i oku
        $pdfFile = sys_get_temp_dir() . '/' . $file->output_filename;
        
        if (!file_exists($pdfFile)) {
            throw new Exception("PDF dosyası oluşturulamadı");
        }
        
        $pdfContent = file_get_contents($pdfFile);
        
        // Geçici PDF dosyasını sil
        if (file_exists($pdfFile)) {
            unlink($pdfFile);
        }
        
        error_log("iLovePDF - PDF oluşturuldu (Boyut: " . strlen($pdfContent) . " bytes)");
        
        return $pdfContent;
        
    } catch (Exception $e) {
        throw new Exception("iLovePDF API hatası: " . $e->getMessage());
    }
}

/**
 * Dompdf ile HTML'i PDF'e çevirir (YEDEK)
 */
function convertHtmlToPdfWithDompdf_OLD($htmlContent) {
    try {
        require_once __DIR__ . '/../vendor/autoload.php';
        
        $options = new \Dompdf\Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isFontSubsettingEnabled', true);
        
        $dompdf = new \Dompdf\Dompdf($options);
        
        // HTML'i yükle
        $dompdf->loadHtml($htmlContent);
        
        // Sayfa boyutu
        $dompdf->setPaper('A4', 'portrait');
        
        // PDF'e çevir
        $dompdf->render();
        
        // PDF içeriğini al
        $pdfContent = $dompdf->output();
        
        error_log("Dompdf - PDF oluşturuldu (Boyut: " . strlen($pdfContent) . " bytes)");
        
        return $pdfContent;
        
    } catch (Exception $e) {
        throw new Exception("Dompdf hatası: " . $e->getMessage());
    }
}

/**
 * html2pdf.com ile HTML'i PDF'e çevirir (Reverse Engineered) - YEDEK
 */
function convertHtmlToPdfWithHtml2Pdf_OLD($htmlContent, $filename = 'fatura.html') {
    try {
        // 1. Session ID oluştur
        $sid = substr(md5(uniqid(rand(), true)), 0, 16);
        
        // 2. File ID oluştur
        $fid = 'file_' . substr(md5(uniqid(rand(), true)), 0, 20);
        
        error_log("html2pdf.com - SID: $sid, FID: $fid");
        
        // 3. HTML dosyasını upload et
        $uploadUrl = "https://html2pdf.com/upload/$sid";
        
        $boundary = '----WebKitFormBoundary' . uniqid();
        $postData = "--$boundary\r\n";
        $postData .= "Content-Disposition: form-data; name=\"file\"; filename=\"$filename\"\r\n";
        $postData .= "Content-Type: text/html\r\n\r\n";
        $postData .= $htmlContent . "\r\n";
        $postData .= "--$boundary\r\n";
        $postData .= "Content-Disposition: form-data; name=\"id\"\r\n\r\n";
        $postData .= $fid . "\r\n";
        $postData .= "--$boundary\r\n";
        $postData .= "Content-Disposition: form-data; name=\"name\"\r\n\r\n";
        $postData .= $filename . "\r\n";
        $postData .= "--$boundary\r\n";
        $postData .= "Content-Disposition: form-data; name=\"rnd\"\r\n\r\n";
        $postData .= (string)(mt_rand() / mt_getrandmax()) . "\r\n";
        $postData .= "--$boundary--\r\n";
        
        $ch = curl_init($uploadUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: multipart/form-data; boundary=$boundary",
            "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36",
            "Referer: https://html2pdf.com/"
        ]);
        
        $uploadResponse = curl_exec($ch);
        $uploadHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($uploadHttpCode !== 200) {
            throw new Exception("Upload failed: HTTP $uploadHttpCode");
        }
        
        error_log("html2pdf.com - Upload başarılı");
        
        // 4. Dönüşümü başlat
        $convertUrl = "https://html2pdf.com/convert/$sid/$fid?rnd=" . (mt_rand() / mt_getrandmax());
        
        $ch = curl_init($convertUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36",
            "Referer: https://html2pdf.com/"
        ]);
        
        $convertResponse = curl_exec($ch);
        curl_close($ch);
        
        $convertData = json_decode($convertResponse, true);
        if (!isset($convertData['status']) || $convertData['status'] !== 'success') {
            throw new Exception("Convert başlatılamadı");
        }
        
        error_log("html2pdf.com - Dönüşüm başlatıldı");
        
        // 5. Status kontrolü (polling - max 30 saniye)
        $maxAttempts = 30;
        $attempt = 0;
        $pdfFilename = null;
        
        while ($attempt < $maxAttempts) {
            sleep(1);
            $attempt++;
            
            $statusUrl = "https://html2pdf.com/status/$sid/$fid";
            
            $ch = curl_init($statusUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36",
                "Referer: https://html2pdf.com/"
            ]);
            
            $statusResponse = curl_exec($ch);
            curl_close($ch);
            
            $statusData = json_decode($statusResponse, true);
            
            if (isset($statusData['status']) && $statusData['status'] === 'success' && isset($statusData['convert_result'])) {
                $pdfFilename = $statusData['convert_result'];
                error_log("html2pdf.com - PDF hazır: $pdfFilename (Deneme: $attempt)");
                break;
            }
            
            if (isset($statusData['status']) && $statusData['status'] === 'error') {
                throw new Exception("Dönüşüm hatası: " . ($statusData['status_text'] ?? 'Unknown'));
            }
        }
        
        if (!$pdfFilename) {
            throw new Exception("Timeout: PDF oluşturulamadı (30 saniye)");
        }
        
        // 6. PDF'i indir - Farklı URL formatlarını dene
        $downloadUrls = [
            "https://html2pdf.com/files/$sid/$fid/$pdfFilename",
            "https://html2pdf.com/download/$sid/$fid/$pdfFilename",
            "https://html2pdf.com/download/$sid/$pdfFilename",
            "https://html2pdf.com/files/$pdfFilename"
        ];
        
        $pdfContent = null;
        $lastError = '';
        
        foreach ($downloadUrls as $downloadUrl) {
            error_log("html2pdf.com - İndirme deneniyor: $downloadUrl");
            
            $ch = curl_init($downloadUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36",
                "Accept: application/pdf,*/*",
                "Referer: https://html2pdf.com/",
                "Origin: https://html2pdf.com"
            ]);
            
            $pdfContent = curl_exec($ch);
            $downloadHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            curl_close($ch);
            
            error_log("html2pdf.com - HTTP: $downloadHttpCode, Content-Type: $contentType, Boyut: " . strlen($pdfContent));
            
            // PDF başlığı kontrolü (%PDF)
            if ($downloadHttpCode === 200 && !empty($pdfContent) && substr($pdfContent, 0, 4) === '%PDF') {
                error_log("html2pdf.com - PDF başarıyla indirildi (Boyut: " . strlen($pdfContent) . " bytes)");
                return $pdfContent;
            }
            
            $lastError = "HTTP $downloadHttpCode";
        }
        
        throw new Exception("PDF indirilemedi - Tüm URL'ler denendi. Son hata: $lastError");
        
    } catch (Exception $e) {
        throw new Exception("html2pdf.com hatası: " . $e->getMessage());
    }
}

/**
 * iLovePDF API ile HTML'i PDF'e çevirir (YEDEKte)
 */
function convertHtmlToPdfWithILovePDF_OLD($htmlContent) {
    // API Key
    $publicKey = 'project_public_f99fd476f6c9530445242971c8dcd8ee_ToPeZ80e5c4242dce55d49d789c3de2bb7be7';
    $secretKey = 'secret_key_6937527f406e2bec65cea2435bfe048e_yU3cx8c2850177135442d5474ffb04481a403';
    
    // iLovePDF instance
    $ilovepdf = new Ilovepdf($publicKey, $secretKey);
    
    // HTML'i geçici dosyaya kaydet
    $tempHtmlFile = tempnam(sys_get_temp_dir(), 'invoice_') . '.html';
    file_put_contents($tempHtmlFile, $htmlContent);
    
    try {
        // HTML to PDF task oluştur
        $task = $ilovepdf->newTask('htmlpdf');
        
        // HTML dosyasını yükle
        $file = $task->addFile($tempHtmlFile);
        
        // PDF'e çevir
        $task->execute();
        
        // PDF'i indir
        $task->download(sys_get_temp_dir());
        
        // İndirilen PDF'i oku
        $pdfFile = sys_get_temp_dir() . '/' . $file->output_filename;
        $pdfContent = file_get_contents($pdfFile);
        
        // Geçici dosyaları temizle
        if (file_exists($tempHtmlFile)) {
            unlink($tempHtmlFile);
        }
        if (file_exists($pdfFile)) {
            unlink($pdfFile);
        }
        
        return $pdfContent;
        
    } catch (Exception $e) {
        // Geçici dosyayı temizle
        if (file_exists($tempHtmlFile)) {
            unlink($tempHtmlFile);
        }
        throw new Exception("iLovePDF API hatası: " . $e->getMessage());
    }
}

/**
 * SMTP ayarlarını session'a kaydeder
 */
function saveSmtpSettings($settings) {
    $_SESSION['smtp_settings'] = [
        'host' => $settings['host'] ?? '',
        'port' => $settings['port'] ?? 587,
        'username' => $settings['username'] ?? '',
        'password' => $settings['password'] ?? '',
        'from_email' => $settings['from_email'] ?? '',
        'from_name' => $settings['from_name'] ?? 'E-Fatura Sistemi',
        'encryption' => $settings['encryption'] ?? 'tls', // tls veya ssl
        'logo_url' => $settings['logo_url'] ?? '',
        'custom_message' => $settings['custom_message'] ?? '',
        'template_type' => $settings['template_type'] ?? 'default'
    ];
    return ['success' => true, 'message' => 'SMTP ayarları kaydedildi.'];
}

/**
 * SMTP ayarlarını getirir
 */
function getSmtpSettings() {
    return $_SESSION['smtp_settings'] ?? null;
}

/**
 * SMTP bağlantısını test eder
 */
function testSmtpConnection($settings) {
    try {
        $mail = new PHPMailer(true);
        
        // SMTP ayarları
        $mail->isSMTP();
        $mail->Host = $settings['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $settings['username'];
        $mail->Password = $settings['password'];
        $mail->SMTPSecure = $settings['encryption'] ?? 'tls';
        $mail->Port = $settings['port'];
        $mail->CharSet = 'UTF-8';
        
        // Timeout ayarları
        $mail->Timeout = 10;
        $mail->SMTPDebug = 0;
        
        // Bağlantıyı test et
        $mail->smtpConnect();
        $mail->smtpClose();
        
        return ['success' => true, 'message' => 'SMTP bağlantısı başarılı!'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'SMTP bağlantı hatası: ' . $e->getMessage()];
    }
}

/**
 * Mail şablonunu oluşturur (Eski fonksiyon - geriye dönük uyumluluk için)
 */
function createInvoiceEmailTemplate($invoiceData, $logoUrl = '', $customMessage = '', $templateType = 'default') {
    // Yeni şablon sistemini kullan
    try {
        if (!function_exists('generateEmailFromTemplate')) {
            require_once __DIR__ . '/mail_templates.php';
        }
        return generateEmailFromTemplate($invoiceData, $templateType, $customMessage, $logoUrl);
    } catch (Exception $e) {
        error_log("Mail template error: " . $e->getMessage());
        // Fallback: Eski şablonu kullan
        return createInvoiceEmailTemplate_OLD($invoiceData, $logoUrl);
    }
}

/**
 * Eski mail şablonu fonksiyonu (yedek)
 */
function createInvoiceEmailTemplate_OLD($invoiceData, $logoUrl = '') {
    $faturaNo = $invoiceData['belgeNumarasi'] ?? 'N/A';
    $faturaTarihi = $invoiceData['faturaTarihi'] ?? 'N/A';
    $aliciAd = $invoiceData['aliciUnvanAdSoyad'] ?? 'Değerli Müşterimiz';
    $toplamTutar = $invoiceData['toplamTutar'] ?? '0.00';
    $paraBirimi = $invoiceData['paraBirimi'] ?? 'TRY';
    
    // Para birimi sembolü
    $currencySymbol = match($paraBirimi) {
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        default => '₺'
    };
    
    $logoHtml = '';
    if (!empty($logoUrl)) {
        $logoHtml = '<img src="' . htmlspecialchars($logoUrl) . '" alt="Firma Logosu" style="max-width: 200px; margin-bottom: 20px;">';
    }
    
    $html = '
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Arşiv Fatura</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f7f9;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f7f9; padding: 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <!-- Logo -->
                    <tr>
                        <td style="padding: 30px 30px 20px 30px; text-align: center;">
                            ' . $logoHtml . '
                        </td>
                    </tr>
                    
                    <!-- Başlık -->
                    <tr>
                        <td style="padding: 0 30px 20px 30px;">
                            <h2 style="color: #333; margin: 0; font-size: 24px; text-align: center;">E-Arşiv Fatura</h2>
                        </td>
                    </tr>
                    
                    <!-- Mesaj -->
                    <tr>
                        <td style="padding: 0 30px 20px 30px;">
                            <p style="color: #666; font-size: 16px; line-height: 1.6; margin: 0;">
                                Sayın <strong>' . htmlspecialchars($aliciAd) . '</strong>,
                            </p>
                            <p style="color: #666; font-size: 16px; line-height: 1.6; margin: 10px 0 0 0;">
                                <strong>' . htmlspecialchars($faturaTarihi) . '</strong> tarihinde düzenlenen e-arşiv faturanız ektedir.
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Fatura Bilgileri -->
                    <tr>
                        <td style="padding: 0 30px 30px 30px;">
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8f9fa; border-radius: 6px; padding: 20px;">
                                <tr>
                                    <td style="padding: 8px 0;">
                                        <table width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="color: #666; font-size: 14px; padding: 5px 0;">
                                                    <strong>Fatura Tarihi:</strong>
                                                </td>
                                                <td style="color: #333; font-size: 14px; text-align: right; padding: 5px 0;">
                                                    ' . htmlspecialchars($faturaTarihi) . '
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="color: #666; font-size: 14px; padding: 5px 0;">
                                                    <strong>Fatura Numarası:</strong>
                                                </td>
                                                <td style="color: #333; font-size: 14px; text-align: right; padding: 5px 0;">
                                                    ' . htmlspecialchars($faturaNo) . '
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="color: #666; font-size: 14px; padding: 5px 0;">
                                                    <strong>Toplam Tutar:</strong>
                                                </td>
                                                <td style="color: #007bff; font-size: 18px; font-weight: bold; text-align: right; padding: 5px 0;">
                                                    ' . number_format($toplamTutar, 2, ',', '.') . ' ' . $currencySymbol . '
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="padding: 20px 30px; background-color: #f8f9fa; border-radius: 0 0 8px 8px;">
                            <p style="color: #999; font-size: 12px; line-height: 1.5; margin: 0; text-align: center;">
                                Bu e-posta otomatik olarak gönderilmiştir. Lütfen yanıtlamayınız.<br>
                                <strong>Faturanız ekte HTML formatında bulunmaktadır.</strong><br>
                                Eki indirip tarayıcınızda açarak görüntüleyebilir ve yazdırabilirsiniz.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
    
    return $html;
}

/**
 * Tek bir faturayı mail olarak gönderir
 */
function sendInvoiceEmail($invoiceData, $pdfContent, $smtpSettings, $pdfIsHtml = false) {
    try {
        // E-posta adresi kontrolü
        $recipientEmail = $invoiceData['aliciEmail'] ?? '';
        if (empty($recipientEmail) || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Geçersiz veya eksik e-posta adresi: ' . $recipientEmail);
        }
        
        $mail = new PHPMailer(true);
        
        // SMTP ayarları
        $mail->isSMTP();
        $mail->Host = $smtpSettings['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $smtpSettings['username'];
        $mail->Password = $smtpSettings['password'];
        $mail->SMTPSecure = $smtpSettings['encryption'] ?? 'tls';
        $mail->Port = $smtpSettings['port'];
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        
        // Timeout ayarları
        $mail->Timeout = 30;
        $mail->SMTPDebug = 0;
        
        // Gönderen bilgileri
        $mail->setFrom($smtpSettings['from_email'], $smtpSettings['from_name']);
        
        // Alıcı bilgileri
        $mail->addAddress($recipientEmail, $invoiceData['aliciUnvanAdSoyad'] ?? '');
        
        // Konu
        $faturaNo = $invoiceData['belgeNumarasi'] ?? 'N/A';
        $mail->Subject = 'E-Arşiv Fatura - ' . $faturaNo;
        
        // Mail içeriği
        $logoUrl = $smtpSettings['logo_url'] ?? '';
        $customMessage = $smtpSettings['custom_message'] ?? '';
        $templateType = $smtpSettings['template_type'] ?? 'default';
        $mail->isHTML(true);
        $mail->Body = createInvoiceEmailTemplate($invoiceData, $logoUrl, $customMessage, $templateType);
        $mail->AltBody = 'Sayın ' . ($invoiceData['aliciUnvanAdSoyad'] ?? 'Müşterimiz') . ', ' . 
                         ($invoiceData['faturaTarihi'] ?? '') . ' tarihli e-arşiv faturanız ektedir. ' .
                         'Fatura No: ' . $faturaNo;
        
        // PDF/HTML eki
        if (!empty($pdfContent)) {
            // HTML mi PDF mi kontrol et
            if (isset($pdfIsHtml) && $pdfIsHtml) {
                $mail->addStringAttachment($pdfContent, 'fatura_' . $faturaNo . '.html', 'binary', 'text/html');
                error_log("HTML ek olarak eklendi: fatura_" . $faturaNo . ".html");
            } else {
                $mail->addStringAttachment($pdfContent, 'fatura_' . $faturaNo . '.pdf', 'binary', 'application/pdf');
                error_log("PDF ek olarak eklendi: fatura_" . $faturaNo . ".pdf");
            }
        } else {
            error_log("Ek yok - mail eksiz gönderilecek");
        }
        
        // Maili gönder
        $mail->send();
        
        return [
            'success' => true, 
            'message' => 'Mail başarıyla gönderildi: ' . $recipientEmail,
            'email' => $recipientEmail
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false, 
            'message' => 'Mail gönderme hatası: ' . $e->getMessage(),
            'email' => $recipientEmail ?? 'N/A'
        ];
    }
}

/**
 * Toplu fatura maili gönderir
 */
function sendBulkInvoiceEmails($invoices, $smtpSettings, $gib) {
    error_log("sendBulkInvoiceEmails başladı - Fatura sayısı: " . count($invoices));
    
    $results = [
        'success' => true,
        'total' => count($invoices),
        'sent' => 0,
        'failed' => 0,
        'details' => []
    ];
    
    foreach ($invoices as $invoice) {
        try {
            error_log("Fatura işleniyor: " . ($invoice['belgeNumarasi'] ?? 'N/A'));
            
            // E-posta adresi kontrolü
            if (empty($invoice['aliciEmail'])) {
                error_log("E-posta adresi yok: " . ($invoice['belgeNumarasi'] ?? 'N/A'));
                $results['failed']++;
                $results['details'][] = [
                    'belgeNumarasi' => $invoice['belgeNumarasi'],
                    'success' => false,
                    'message' => 'E-posta adresi bulunamadı'
                ];
                continue;
            }
            
            // PDF'i indir
            $uuid = $invoice['uuid'];
            $pdfContent = null;
            
            try {
                error_log("PDF indiriliyor (UUID: $uuid)");
                
                // Geçici dosya oluştur
                $tmpInvoiceZip = tempnam(sys_get_temp_dir(), 'invzip');
                $zipFilePath = $tmpInvoiceZip . '.zip';
                
                error_log("Temp ZIP yolu: $zipFilePath");
                
                // Faturayı ZIP olarak kaydet
                $result = $gib->saveToDisk($uuid, dirname($tmpInvoiceZip), basename($tmpInvoiceZip));
                
                error_log("saveToDisk sonucu: " . ($result ? 'true' : 'false'));
                error_log("ZIP dosyası var mı: " . (file_exists($zipFilePath) ? 'evet' : 'hayır'));
                
                if ($result && file_exists($zipFilePath)) {
                    error_log("ZIP boyutu: " . filesize($zipFilePath) . " bytes");
                    
                    // ZIP'i aç ve PDF'i çıkar
                    $zip = new ZipArchive();
                    if ($zip->open($zipFilePath) === TRUE) {
                        error_log("ZIP açıldı - Dosya sayısı: " . $zip->numFiles);
                        
                        // ZIP içindeki tüm dosyaları listele
                        $htmlContent = null;
                        for ($i = 0; $i < $zip->numFiles; $i++) {
                            $filename = $zip->getNameIndex($i);
                            $ext = pathinfo($filename, PATHINFO_EXTENSION);
                            error_log("ZIP içinde dosya #$i: $filename (uzantı: $ext)");
                            
                            if ($ext === 'pdf') {
                                $pdfContent = $zip->getFromIndex($i);
                                error_log("PDF bulundu: $filename (Boyut: " . strlen($pdfContent) . " bytes)");
                                break;
                            } elseif ($ext === 'html' && $htmlContent === null) {
                                // HTML dosyasını al (PDF yoksa HTML kullanacağız)
                                $htmlContent = $zip->getFromIndex($i);
                                error_log("HTML bulundu: $filename (Boyut: " . strlen($htmlContent) . " bytes)");
                            }
                        }
                        
                        // PDF bulunamadıysa HTML'i direkt kullan
                        if (!$pdfContent && $htmlContent) {
                            error_log("PDF yok, HTML eki olarak gönderilecek");
                            $pdfContent = $htmlContent;
                            $pdfIsHtml = true;
                        }
                        $zip->close();
                    } else {
                        error_log("ZIP açılamadı!");
                    }
                    
                    // Geçici dosyaları temizle
                    if (file_exists($zipFilePath)) {
                        unlink($zipFilePath);
                    }
                    if (file_exists($tmpInvoiceZip)) {
                        unlink($tmpInvoiceZip);
                    }
                }
                
                if ($pdfContent) {
                    error_log("PDF başarıyla indirildi (Boyut: " . strlen($pdfContent) . " bytes)");
                } else {
                    error_log("PDF bulunamadı");
                }
                
            } catch (Exception $e) {
                // PDF indirilemese bile mail göndermeyi dene
                error_log("PDF indirme hatası (UUID: $uuid): " . $e->getMessage());
                $pdfContent = null;
                $pdfIsHtml = false;
            }
            
            // Maili gönder
            $result = sendInvoiceEmail($invoice, $pdfContent, $smtpSettings, $pdfIsHtml ?? false);
            
            if ($result['success']) {
                $results['sent']++;
            } else {
                $results['failed']++;
            }
            
            $results['details'][] = [
                'belgeNumarasi' => $invoice['belgeNumarasi'],
                'email' => $result['email'],
                'success' => $result['success'],
                'message' => $result['message']
            ];
            
            // Rate limiting - her mail arasında kısa bir bekleme
            usleep(500000); // 0.5 saniye
            
        } catch (Exception $e) {
            $results['failed']++;
            $results['details'][] = [
                'belgeNumarasi' => $invoice['belgeNumarasi'] ?? 'N/A',
                'success' => false,
                'message' => 'Hata: ' . $e->getMessage()
            ];
        }
    }
    
    $results['success'] = $results['sent'] > 0;
    $results['message'] = "{$results['sent']} mail gönderildi, {$results['failed']} başarısız.";
    
    return $results;
}
