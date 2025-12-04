<?php
/**
 * E-Fatura Mail Şablonları
 * Farklı mail şablonları ve özelleştirme seçenekleri
 */

/**
 * Varsayılan (Profesyonel) Şablon
 */
function getDefaultTemplate($data) {
    $faturaNo = $data['belgeNumarasi'] ?? 'N/A';
    $faturaTarihi = $data['faturaTarihi'] ?? 'N/A';
    $aliciAd = $data['aliciUnvanAdSoyad'] ?? 'Değerli Müşterimiz';
    $toplamTutar = $data['toplamTutar'] ?? '0.00';
    $paraBirimi = $data['paraBirimi'] ?? 'TRY';
    $logoUrl = $data['logoUrl'] ?? '';
    $customMessage = $data['customMessage'] ?? '';
    
    $currencySymbol = getCurrencySymbol($paraBirimi);
    
    $logoHtml = '';
    if (!empty($logoUrl)) {
        $logoHtml = '<img src="' . htmlspecialchars($logoUrl) . '" alt="Firma Logosu" style="max-width: 200px; height: auto; margin-bottom: 20px;">';
    }
    
    $customMessageHtml = '';
    if (!empty($customMessage)) {
        $customMessageHtml = '<p style="color: #666; font-size: 14px; line-height: 1.6; margin: 15px 0; padding: 15px; background: #f8f9fa; border-left: 4px solid #007bff; border-radius: 4px;">' . 
            nl2br(htmlspecialchars($customMessage)) . '</p>';
    }
    
    return '
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
                            ' . $customMessageHtml . '
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
                                e-Arşiv izni kapsamında elektronik ortamda iletilmiştir.<br>
                                Faturanız ekte PDF formatında bulunmaktadır. PDF dosyasını açarak faturanızı görüntüleyebilirsiniz.<br>
                               >
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
}

/**
 * Modern (Minimalist) Şablon
 */
function getModernTemplate($data) {
    $faturaNo = $data['belgeNumarasi'] ?? 'N/A';
    $faturaTarihi = $data['faturaTarihi'] ?? 'N/A';
    $aliciAd = $data['aliciUnvanAdSoyad'] ?? 'Değerli Müşterimiz';
    $toplamTutar = $data['toplamTutar'] ?? '0.00';
    $paraBirimi = $data['paraBirimi'] ?? 'TRY';
    $logoUrl = $data['logoUrl'] ?? '';
    $customMessage = $data['customMessage'] ?? '';
    
    $currencySymbol = getCurrencySymbol($paraBirimi);
    
    $logoHtml = '';
    if (!empty($logoUrl)) {
        $logoHtml = '<img src="' . htmlspecialchars($logoUrl) . '" alt="Logo" style="max-width: 150px; height: auto;">';
    }
    
    $customMessageHtml = '';
    if (!empty($customMessage)) {
        $customMessageHtml = '<div style="margin: 20px 0; padding: 15px; background: #e3f2fd; border-radius: 4px; color: #1976d2;">' . 
            nl2br(htmlspecialchars($customMessage)) . '</div>';
    }
    
    return '
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fatura</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif; background-color: #ffffff;">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width: 600px; margin: 0 auto; padding: 40px 20px;">
        <tr>
            <td>
                <!-- Header -->
                <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 30px;">
                    <tr>
                        <td style="text-align: left;">
                            ' . $logoHtml . '
                        </td>
                        <td style="text-align: right; color: #666; font-size: 14px;">
                            ' . htmlspecialchars($faturaTarihi) . '
                        </td>
                    </tr>
                </table>
                
                <!-- Divider -->
                <div style="height: 2px; background: linear-gradient(to right, #007bff, #00d4ff); margin: 20px 0;"></div>
                
                <!-- Content -->
                <h1 style="color: #333; font-size: 28px; font-weight: 300; margin: 20px 0;">Fatura</h1>
                <p style="color: #666; font-size: 16px; line-height: 1.6;">
                    Sayın <strong>' . htmlspecialchars($aliciAd) . '</strong>,
                </p>
                <p style="color: #666; font-size: 16px; line-height: 1.6;">
                    E-arşiv faturanız hazır.
                </p>
                
                ' . $customMessageHtml . '
                
                <!-- Invoice Details -->
                <table width="100%" cellpadding="0" cellspacing="0" style="margin: 30px 0; border-top: 1px solid #e0e0e0; border-bottom: 1px solid #e0e0e0;">
                    <tr>
                        <td style="padding: 15px 0; color: #999; font-size: 14px;">Fatura No</td>
                        <td style="padding: 15px 0; color: #333; font-size: 14px; text-align: right; font-weight: 500;">' . htmlspecialchars($faturaNo) . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 15px 0; color: #999; font-size: 14px; border-top: 1px solid #f0f0f0;">Tarih</td>
                        <td style="padding: 15px 0; color: #333; font-size: 14px; text-align: right; font-weight: 500; border-top: 1px solid #f0f0f0;">' . htmlspecialchars($faturaTarihi) . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 15px 0; color: #999; font-size: 14px; border-top: 1px solid #f0f0f0;">Toplam</td>
                        <td style="padding: 15px 0; color: #007bff; font-size: 24px; text-align: right; font-weight: 600; border-top: 1px solid #f0f0f0;">' . number_format($toplamTutar, 2, ',', '.') . ' ' . $currencySymbol . '</td>
                    </tr>
                </table>
                
                <!-- Footer -->
                <p style="color: #999; font-size: 12px; line-height: 1.5; margin-top: 40px; text-align: center;">
                    <br>e-Arşiv izni kapsamında elektronik ortamda iletilmiştir.<br>
                    <strong>Faturanız ekte PDF formatında bulunmaktadır.</strong><br>
                    PDF dosyasını açarak faturanızı görüntüleyebilirisiniz.
                </p>
            </td>
        </tr>
    </table>
</body>
</html>';
}

/**
 * Klasik (Resmi) Şablon
 */
function getClassicTemplate($data) {
    $faturaNo = $data['belgeNumarasi'] ?? 'N/A';
    $faturaTarihi = $data['faturaTarihi'] ?? 'N/A';
    $aliciAd = $data['aliciUnvanAdSoyad'] ?? 'Değerli Müşterimiz';
    $toplamTutar = $data['toplamTutar'] ?? '0.00';
    $paraBirimi = $data['paraBirimi'] ?? 'TRY';
    $logoUrl = $data['logoUrl'] ?? '';
    $customMessage = $data['customMessage'] ?? '';
    
    $currencySymbol = getCurrencySymbol($paraBirimi);
    
    $logoHtml = '';
    if (!empty($logoUrl)) {
        $logoHtml = '<img src="' . htmlspecialchars($logoUrl) . '" alt="Firma Logosu" style="max-width: 180px; height: auto; margin-bottom: 15px;">';
    }
    
    $customMessageHtml = '';
    if (!empty($customMessage)) {
        $customMessageHtml = '<p style="color: #333; font-size: 14px; line-height: 1.8; margin: 15px 0; padding: 10px; border-left: 3px solid #333;">' . 
            nl2br(htmlspecialchars($customMessage)) . '</p>';
    }
    
    return '
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Arşiv Fatura</title>
</head>
<body style="margin: 0; padding: 0; font-family: \'Times New Roman\', Times, serif; background-color: #ffffff;">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width: 650px; margin: 0 auto; padding: 40px 20px;">
        <tr>
            <td style="border: 2px solid #333; padding: 30px;">
                <!-- Logo -->
                <div style="text-align: center; margin-bottom: 20px;">
                    ' . $logoHtml . '
                </div>
                
                <!-- Başlık -->
                <h2 style="color: #333; margin: 0 0 20px 0; font-size: 22px; text-align: center; text-transform: uppercase; letter-spacing: 2px; border-bottom: 2px solid #333; padding-bottom: 10px;">
                    E-Arşiv Fatura
                </h2>
                
                <!-- Tarih -->
                <p style="color: #666; font-size: 14px; text-align: right; margin: 0 0 20px 0;">
                    Tarih: ' . htmlspecialchars($faturaTarihi) . '
                </p>
                
                <!-- Mesaj -->
                <p style="color: #333; font-size: 14px; line-height: 1.8; margin: 20px 0;">
                    Sayın <strong>' . htmlspecialchars($aliciAd) . '</strong>,
                </p>
                <p style="color: #333; font-size: 14px; line-height: 1.8; margin: 10px 0;">
                    ' . htmlspecialchars($faturaTarihi) . ' tarihinde düzenlenen e-arşiv faturanız ekte sunulmuştur.
                </p>
                
                ' . $customMessageHtml . '
                
                <!-- Fatura Detayları -->
                <table width="100%" cellpadding="0" cellspacing="0" style="margin: 30px 0; border: 1px solid #333;">
                    <tr style="background-color: #f5f5f5;">
                        <td colspan="2" style="padding: 10px; border-bottom: 1px solid #333;">
                            <strong style="color: #333; font-size: 14px;">FATURA BİLGİLERİ</strong>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 10px; border-bottom: 1px solid #ddd; color: #333; font-size: 14px; width: 40%;">
                            Fatura Numarası:
                        </td>
                        <td style="padding: 10px; border-bottom: 1px solid #ddd; color: #333; font-size: 14px; text-align: right;">
                            <strong>' . htmlspecialchars($faturaNo) . '</strong>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 10px; border-bottom: 1px solid #ddd; color: #333; font-size: 14px;">
                            Fatura Tarihi:
                        </td>
                        <td style="padding: 10px; border-bottom: 1px solid #ddd; color: #333; font-size: 14px; text-align: right;">
                            <strong>' . htmlspecialchars($faturaTarihi) . '</strong>
                        </td>
                    </tr>
                    <tr style="background-color: #f5f5f5;">
                        <td style="padding: 15px; color: #333; font-size: 16px;">
                            <strong>TOPLAM TUTAR:</strong>
                        </td>
                        <td style="padding: 15px; color: #333; font-size: 18px; text-align: right;">
                            <strong>' . number_format($toplamTutar, 2, ',', '.') . ' ' . $currencySymbol . '</strong>
                        </td>
                    </tr>
                </table>
                
                <!-- Footer -->
                <p style="color: #666; font-size: 12px; line-height: 1.6; margin-top: 30px; text-align: center; border-top: 1px solid #ddd; padding-top: 15px;">
                    e-Arşiv izni kapsamında elektronik ortamda iletilmiştir.<br>
                    <strong>Faturanız ekte PDF formatında bulunmaktadır.</strong><br>
                    PDF dosyasını açarak faturanızı görüntüleyebilirisiniz.
                </p>
            </td>
        </tr>
    </table>
</body>
</html>';
}

/**
 * Para birimi sembolünü döndürür
 */
function getCurrencySymbol($currency) {
    return match($currency) {
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        'JPY' => '¥',
        default => '₺'
    };
}

/**
 * Şablon seçimine göre mail içeriği oluşturur
 */
function generateEmailFromTemplate($invoiceData, $templateType = 'default', $customMessage = '', $logoUrl = '') {
    $data = [
        'belgeNumarasi' => $invoiceData['belgeNumarasi'] ?? 'N/A',
        'faturaTarihi' => $invoiceData['faturaTarihi'] ?? 'N/A',
        'aliciUnvanAdSoyad' => $invoiceData['aliciUnvanAdSoyad'] ?? 'Değerli Müşterimiz',
        'toplamTutar' => $invoiceData['toplamTutar'] ?? 0,
        'paraBirimi' => $invoiceData['paraBirimi'] ?? 'TRY',
        'logoUrl' => $logoUrl,
        'customMessage' => $customMessage
    ];
    
    return match($templateType) {
        'modern' => getModernTemplate($data),
        'classic' => getClassicTemplate($data),
        default => getDefaultTemplate($data)
    };
}
