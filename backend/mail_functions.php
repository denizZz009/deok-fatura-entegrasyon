<?php
/**
 * E-Fatura Mail Gönderme Sistemi - Temiz Versiyon
 * SMTP ile fatura maillerini HTML eki olarak gönderir
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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
        'encryption' => $settings['encryption'] ?? 'tls',
        'logo_url' => $settings['logo_url'] ?? '',
        'custom_message' => $settings['custom_message'] ?? '',
        'template_type' => $settings['template_type'] ?? 'default'
    ];
    return ['success' => true, 'message' => 'SMTP ayarları kaydedildi.'];
}

/**
 * SMTP ayarlarını JSON olarak dışa aktarır
 */
function exportSmtpSettings() {
    $settings = $_SESSION['smtp_settings'] ?? null;
    
    if (!$settings) {
        return ['success' => false, 'message' => 'Kaydedilmiş SMTP ayarı bulunamadı.'];
    }
    
    $export = [
        'version' => '1.0',
        'export_date' => date('Y-m-d H:i:s'),
        'smtp_settings' => [
            'server' => [
                'host' => $settings['host'],
                'port' => $settings['port'],
                'encryption' => $settings['encryption']
            ],
            'auth' => [
                'username' => $settings['username'],
                'password' => $settings['password']
            ],
            'sender' => [
                'email' => $settings['from_email'],
                'name' => $settings['from_name']
            ],
            'template' => [
                'type' => $settings['template_type'],
                'logo_url' => $settings['logo_url'],
                'custom_message' => $settings['custom_message']
            ]
        ]
    ];
    
    return [
        'success' => true,
        'data' => $export,
        'filename' => 'smtp_ayarlari_' . date('Y-m-d_His') . '.json'
    ];
}

/**
 * JSON'dan SMTP ayarlarını içe aktarır
 */
function importSmtpSettings($jsonData) {
    try {
        $data = json_decode($jsonData, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Geçersiz JSON formatı: ' . json_last_error_msg());
        }
        
        if (!isset($data['smtp_settings'])) {
            throw new Exception('SMTP ayarları bulunamadı.');
        }
        
        $smtp = $data['smtp_settings'];
        
        // Ayarları session'a kaydet
        $_SESSION['smtp_settings'] = [
            'host' => $smtp['server']['host'] ?? '',
            'port' => $smtp['server']['port'] ?? 587,
            'encryption' => $smtp['server']['encryption'] ?? 'tls',
            'username' => $smtp['auth']['username'] ?? '',
            'password' => $smtp['auth']['password'] ?? '',
            'from_email' => $smtp['sender']['email'] ?? '',
            'from_name' => $smtp['sender']['name'] ?? 'E-Fatura Sistemi',
            'template_type' => $smtp['template']['type'] ?? 'default',
            'logo_url' => $smtp['template']['logo_url'] ?? '',
            'custom_message' => $smtp['template']['custom_message'] ?? ''
        ];
        
        return ['success' => true, 'message' => 'SMTP ayarları başarıyla içe aktarıldı.'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'İçe aktarma hatası: ' . $e->getMessage()];
    }
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
        
        $mail->isSMTP();
        $mail->Host = $settings['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $settings['username'];
        $mail->Password = $settings['password'];
        $mail->SMTPSecure = $settings['encryption'] ?? 'tls';
        $mail->Port = $settings['port'];
        $mail->CharSet = 'UTF-8';
        $mail->Timeout = 10;
        $mail->SMTPDebug = 0;
        
        $mail->smtpConnect();
        $mail->smtpClose();
        
        return ['success' => true, 'message' => 'SMTP bağlantısı başarılı!'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'SMTP bağlantı hatası: ' . $e->getMessage()];
    }
}

/**
 * Mail şablonunu oluşturur
 */
function createInvoiceEmailTemplate($invoiceData, $logoUrl = '', $customMessage = '', $templateType = 'default') {
    try {
        if (!function_exists('generateEmailFromTemplate')) {
            require_once __DIR__ . '/mail_templates.php';
        }
        return generateEmailFromTemplate($invoiceData, $templateType, $customMessage, $logoUrl);
    } catch (Exception $e) {
        error_log("Mail template error: " . $e->getMessage());
        return createInvoiceEmailTemplate_Fallback($invoiceData, $logoUrl);
    }
}

/**
 * Puppeteer ile HTML'i PDF'e çevirir (Direkt fonksiyon çağrısı)
 */
function convertHtmlToPdfWithPuppeteer($htmlContent) {
    try {
        // Converter fonksiyonunu include et
        $converterPath = __DIR__ . '/../html2pdf/converter-function.php';
        
        if (!file_exists($converterPath)) {
            throw new Exception("Converter dosyası bulunamadı: $converterPath");
        }
        
        require_once $converterPath;
        
        error_log("Puppeteer converter çağrılıyor...");
        
        // Direkt fonksiyon çağrısı (HTTP request yok)
        $result = convertHtmlToPdf($htmlContent);
        
        if (!$result['success']) {
            $errorMsg = $result['error'] ?? 'Bilinmeyen hata';
            $errorDetails = $result['details'] ?? '';
            throw new Exception("PDF oluşturulamadı: $errorMsg - $errorDetails");
        }
        
        $pdfContent = $result['pdf'];
        
        error_log("Puppeteer PDF oluşturuldu: " . strlen($pdfContent) . " bytes");
        
        return $pdfContent;
        
    } catch (Exception $e) {
        throw new Exception("Puppeteer hatası: " . $e->getMessage());
    }
}

/**
 * Basit mail şablonu (fallback)
 */
function createInvoiceEmailTemplate_Fallback($invoiceData, $logoUrl = '') {
    $faturaNo = $invoiceData['belgeNumarasi'] ?? 'N/A';
    $faturaTarihi = $invoiceData['faturaTarihi'] ?? 'N/A';
    $aliciAd = $invoiceData['aliciUnvanAdSoyad'] ?? 'Değerli Müşterimiz';
    
    return '<!DOCTYPE html><html><body style="font-family:Arial,sans-serif;padding:20px;">
        <h2>E-Arşiv Fatura</h2>
        <p>Sayın <strong>' . htmlspecialchars($aliciAd) . '</strong>,</p>
        <p>' . htmlspecialchars($faturaTarihi) . ' tarihli e-arşiv faturanız ektedir.</p>
        <p><strong>Fatura No:</strong> ' . htmlspecialchars($faturaNo) . '</p>
        <hr>
        <p style="font-size:12px;color:#666;">
            <strong>Faturanız ekte ZIP formatında bulunmaktadır.</strong><br>
            ZIP dosyasını açarak HTML dosyasını tarayıcınızda görüntüleyebilir ve yazdırabilirsiniz.
        </p>
    </body></html>';
}

/**
 * Tek bir faturayı mail olarak gönderir
 */
function sendInvoiceEmail($invoiceData, $pdfContent, $smtpSettings) {
    try {
        $recipientEmail = $invoiceData['aliciEmail'] ?? '';
        if (empty($recipientEmail) || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Geçersiz e-posta adresi: ' . $recipientEmail);
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
        $mail->Timeout = 30;
        $mail->SMTPDebug = 0;
        
        // Gönderen ve alıcı
        $mail->setFrom($smtpSettings['from_email'], $smtpSettings['from_name']);
        $mail->addAddress($recipientEmail, $invoiceData['aliciUnvanAdSoyad'] ?? '');
        
        // Konu
        $faturaNo = $invoiceData['belgeNumarasi'] ?? 'N/A';
        $mail->Subject = 'E-Arşiv Fatura - ' . $faturaNo;
        
        // Mail içeriği
        $mail->isHTML(true);
        $mail->Body = createInvoiceEmailTemplate(
            $invoiceData, 
            $smtpSettings['logo_url'] ?? '', 
            $smtpSettings['custom_message'] ?? '', 
            $smtpSettings['template_type'] ?? 'default'
        );
        $mail->AltBody = 'Sayın ' . ($invoiceData['aliciUnvanAdSoyad'] ?? 'Müşterimiz') . ', ' . 
                         ($invoiceData['faturaTarihi'] ?? '') . ' tarihli e-arşiv faturanız ektedir.';
        
        // PDF eki
        if (!empty($pdfContent)) {
            $mail->addStringAttachment($pdfContent, 'fatura_' . $faturaNo . '.pdf', 'base64', 'application/pdf');
            error_log("PDF ek eklendi: fatura_" . $faturaNo . ".pdf");
        } else {
            error_log("UYARI: PDF içeriği boş, ek eklenemedi!");
        }
        
        $mail->send();
        
        return [
            'success' => true, 
            'message' => 'Mail gönderildi: ' . $recipientEmail,
            'email' => $recipientEmail
        ];
        
    } catch (Exception $e) {
        $errorMsg = $e->getMessage();
        error_log("Mail gönderme hatası: $errorMsg");
        
        // SMTP authentication hatası için özel mesaj
        if (strpos($errorMsg, 'Could not authenticate') !== false) {
            $errorMsg = 'SMTP kimlik doğrulama hatası. Lütfen kullanıcı adı ve şifrenizi kontrol edin. Gmail kullanıyorsanız "Uygulama Şifresi" oluşturun.';
        }
        
        return [
            'success' => false, 
            'message' => 'Mail hatası: ' . $errorMsg,
            'email' => $recipientEmail ?? 'N/A'
        ];
    }
}

/**
 * Toplu fatura maili gönderir
 */
function sendBulkInvoiceEmails($invoices, $smtpSettings, $gib) {
    // Timeout'u artır (her fatura için ~60 saniye)
    set_time_limit(count($invoices) * 90);
    
    error_log("Toplu mail başladı - Fatura sayısı: " . count($invoices));
    
    $results = [
        'success' => true,
        'total' => count($invoices),
        'sent' => 0,
        'failed' => 0,
        'details' => []
    ];
    
    foreach ($invoices as $invoice) {
        try {
            error_log("İşleniyor: " . ($invoice['belgeNumarasi'] ?? 'N/A'));
            
            if (empty($invoice['aliciEmail'])) {
                $results['failed']++;
                $results['details'][] = [
                    'belgeNumarasi' => $invoice['belgeNumarasi'],
                    'success' => false,
                    'message' => 'E-posta adresi yok'
                ];
                continue;
            }
            
            // Yeni ZIP oluştur (sadece HTML ile)
            $uuid = $invoice['uuid'];
            $zipContent = null;
            
            try {
                error_log("ZIP indiriliyor (UUID: $uuid)");
                $tmpBase = tempnam(sys_get_temp_dir(), 'inv');
                $tmpZip = $tmpBase . '.zip';
                
                // saveToDisk .zip uzantısını otomatik ekliyor
                $baseNameWithoutExt = basename($tmpBase);
                
                if ($gib->saveToDisk($uuid, dirname($tmpBase), $baseNameWithoutExt)) {
                    error_log("Orijinal ZIP kaydedildi");
                    
                    // Dosya var mı kontrol et
                    if (!file_exists($tmpZip) && file_exists($tmpBase)) {
                        $tmpZip = $tmpBase;
                    }
                    
                    // Orijinal ZIP'i aç ve HTML'i çıkar
                    $htmlContent = null;
                    $zip = new ZipArchive();
                    if ($zip->open($tmpZip) === TRUE) {
                        error_log("ZIP açıldı - Dosya sayısı: " . $zip->numFiles);
                        for ($i = 0; $i < $zip->numFiles; $i++) {
                            $filename = $zip->getNameIndex($i);
                            $ext = pathinfo($filename, PATHINFO_EXTENSION);
                            if ($ext === 'html') {
                                $htmlContent = $zip->getFromIndex($i);
                                error_log("HTML bulundu: " . strlen($htmlContent) . " bytes");
                                break;
                            }
                        }
                        $zip->close();
                    }
                    
                    // PDF oluştur (direkt PDF gönder, ZIP yok)
                    $pdfContent = null;
                    if ($htmlContent) {
                        try {
                            error_log("PDF oluşturuluyor (Puppeteer)...");
                            $startTime = microtime(true);
                            $pdfContent = convertHtmlToPdfWithPuppeteer($htmlContent);
                            $duration = round(microtime(true) - $startTime, 2);
                            
                            if ($pdfContent) {
                                error_log("✓ PDF oluşturuldu: " . strlen($pdfContent) . " bytes ($duration saniye)");
                            }
                        } catch (Exception $e) {
                            error_log("✗ PDF oluşturulamadı: " . $e->getMessage());
                            $pdfContent = null;
                        }
                    }
                    
                    /* ZIP ile gönderme (şimdilik kapalı)
                    if ($htmlContent) {
                        $newZipPath = tempnam(sys_get_temp_dir(), 'new_inv') . '.zip';
                        $newZip = new ZipArchive();
                        
                        if ($newZip->open($newZipPath, ZipArchive::CREATE) === TRUE) {
                            $htmlFilename = 'fatura_' . $invoice['belgeNumarasi'] . '.html';
                            $newZip->addFromString($htmlFilename, $htmlContent);
                            
                            if ($pdfContent) {
                                $pdfFilename = 'fatura_' . $invoice['belgeNumarasi'] . '.pdf';
                                $newZip->addFromString($pdfFilename, $pdfContent);
                            }
                            
                            $newZip->close();
                            $zipContent = file_get_contents($newZipPath);
                            unlink($newZipPath);
                        }
                    }
                    */
                    
                    // Geçici dosyaları temizle
                    if (file_exists($tmpZip)) unlink($tmpZip);
                    if (file_exists($tmpBase)) unlink($tmpBase);
                } else {
                    error_log("saveToDisk başarısız!");
                }
            } catch (Exception $e) {
                error_log("ZIP işleme hatası: " . $e->getMessage());
            }

            
            // Mail gönder (PDF ile)
            $result = sendInvoiceEmail($invoice, $pdfContent, $smtpSettings);
            
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
            
            usleep(500000); // 0.5 saniye bekle
            
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
    
    error_log("Toplu mail tamamlandı: " . $results['message']);
    
    return $results;
}
