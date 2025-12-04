<?php
/**
 * Optimize Edilmiş Case'ler
 * 
 * Bu dosyadaki kodlar index.php'deki ilgili case'lerin yerine konulacak
 */

// ============================================================================
// CASE: list_invoices (Optimize Edilmiş)
// ============================================================================
/*
case 'list_invoices':
    header('Content-Type: application/json');
    if (!isset($_SESSION['usercode']) || !isset($_SESSION['password'])) {
        echo json_encode(['success' => false, 'message' => 'Önce giriş yapmalısınız!']);
        exit;
    }
    
    try {
        // Eski cache kayıtlarını temizle
        cleanupOldCache();
        
        $gib = (new Gib)->login($_SESSION['usercode'], $_SESSION['password']);
        
        // Parametreleri al
        $start = isset($_GET['start']) && $_GET['start'] 
            ? date('d/m/Y', strtotime($_GET['start'])) 
            : date('d/m/Y', strtotime('-30 days'));
        $end = isset($_GET['end']) && $_GET['end'] 
            ? date('d/m/Y', strtotime($_GET['end'])) 
            : date('d/m/Y');
        $statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
        $search = isset($_GET['search']) ? mb_strtolower(trim($_GET['search'])) : '';
        
        // Cache key oluştur
        $cacheKey = 'invoices_' . md5($start . $end . $_SESSION['usercode']);
        
        // Session cache kullan (5 dakika)
        $invoices = sessionCache($cacheKey, function() use ($gib, $start, $end) {
            return $gib->getAll($start, $end);
        }, 300);
        
        // Faturaları optimize edilmiş şekilde işle
        $result = processInvoiceList($gib, $invoices, $statusFilter, $search);
        
        // Session'a kaydet (eski sistem uyumluluğu için)
        $_SESSION['invoices'] = $result;
        
        echo json_encode([
            'success' => true, 
            'invoices' => $result,
            'meta' => [
                'total' => count($result),
                'cached' => isset($_SESSION['temp_cache'][$cacheKey]),
                'processing_time' => 'optimized'
            ]
        ]);
        
    } catch (Exception $e) {
        error_log('list_invoices error: ' . $e->getMessage());
        echo json_encode([
            'success' => false, 
            'message' => 'Faturalar alınamadı: ' . $e->getMessage()
        ]);
    }
    break;
*/

// ============================================================================
// CASE: generate_report (Optimize Edilmiş)
// ============================================================================
/*
case 'generate_report':
    header('Content-Type: application/json');
    if (!isset($_SESSION['usercode']) || !isset($_SESSION['password'])) {
        echo json_encode(['success' => false, 'message' => 'Önce giriş yapmalısınız!']);
        exit;
    }
    
    try {
        $startDate = $input['startDate'] ?? '';
        $endDate = $input['endDate'] ?? '';
        $reportType = $input['reportType'] ?? 'excel';
        
        if (!$startDate || !$endDate) {
            echo json_encode(['success' => false, 'message' => 'Başlangıç ve bitiş tarihi gereklidir!']);
            exit;
        }
        
        $gib = (new Gib)->login($_SESSION['usercode'], $_SESSION['password']);
        
        // Tarihleri GİB formatına çevir
        $start = date('d/m/Y', strtotime($startDate));
        $end = date('d/m/Y', strtotime($endDate));
        
        // Cache key oluştur
        $cacheKey = 'report_' . md5($start . $end . $_SESSION['usercode']);
        
        // Session cache kullan (5 dakika)
        $invoices = sessionCache($cacheKey, function() use ($gib, $start, $end) {
            return $gib->getAll($start, $end);
        }, 300);
        
        // Rapor verilerini hazırla
        $reportData = [];
        $totalMalHizmet = 0;
        $totalVergilerDahil = 0;
        $invoiceCount = 0;
        
        // Sadece onaylanmış faturaları filtrele
        $approvedInvoices = array_filter($invoices, function($inv) {
            $status = determineInvoiceStatus($inv);
            return $status === 'Onaylanmış';
        });
        
        // Detay gerekenleri belirle
        $needsDetail = [];
        $ready = [];
        
        foreach ($approvedInvoices as $inv) {
            if (needsDetailedFetch($inv)) {
                $needsDetail[] = $inv;
            } else {
                $ready[] = $inv;
            }
        }
        
        // Detay gerekenleri paralel çek
        $detailedData = [];
        if (count($needsDetail) > 0) {
            $detailedData = getInvoiceDetailsParallel($gib, $needsDetail, 10);
        }
        
        // Tüm faturaları birleştir
        $allApprovedInvoices = array_merge($ready, array_values($detailedData));
        
        // Rapor verilerini oluştur
        foreach ($allApprovedInvoices as $inv) {
            // Vergiler dahil tutar
            $vergilerDahilTutar = extractTotalAmount($inv);
            
            if ($vergilerDahilTutar <= 0) {
                continue; // Tutar yoksa atla
            }
            
            // Mal hizmet tutarı
            $malHizmetTutari = 0;
            
            // Önce doğrudan mal hizmet tutarını kontrol et
            $malHizmetFields = [
                'malHizmetToplamTutari',
                'malhizmetToplamTutari',
                'matrah'
            ];
            
            foreach ($malHizmetFields as $field) {
                if (isset($inv[$field]) && $inv[$field] > 0) {
                    $malHizmetTutari = floatval($inv[$field]);
                    break;
                }
            }
            
            // Mal hizmet tutarı yoksa KDV'den hesapla
            if ($malHizmetTutari == 0) {
                $kdvOrani = extractVatRate($inv);
                
                if ($kdvOrani == 0) {
                    $kdvOrani = 20; // Varsayılan %20
                }
                
                $malHizmetTutari = $vergilerDahilTutar / (1 + ($kdvOrani / 100));
            }
            
            // Ürün adı
            $urunAdi = extractProductName($inv);
            
            // KDV oranı
            $kdvOrani = extractVatRate($inv);
            
            // Alıcı
            $recipient = extractRecipientName($inv);
            
            // Para birimi
            $paraBirimi = $inv['paraBirimi'] ?? 'TRY';
            
            $reportData[] = [
                'belgeNumarasi' => $inv['belgeNumarasi'] ?? '',
                'belgeTarihi' => $inv['belgeTarihi'] ?? '',
                'alici' => $recipient,
                'urunHizmet' => $urunAdi,
                'malHizmetTutari' => number_format($malHizmetTutari, 2, '.', ''),
                'vergilerDahilTutar' => number_format($vergilerDahilTutar, 2, '.', ''),
                'kdvOrani' => $kdvOrani,
                'paraBirimi' => $paraBirimi
            ];
            
            $totalMalHizmet += $malHizmetTutari;
            $totalVergilerDahil += $vergilerDahilTutar;
            $invoiceCount++;
        }
        
        // Excel raporu oluştur
        if ($reportType === 'excel') {
            require_once __DIR__ . '/vendor/autoload.php';
            
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Başlıklar
            $sheet->setCellValue('A1', 'Fatura No');
            $sheet->setCellValue('B1', 'Tarih');
            $sheet->setCellValue('C1', 'Alıcı');
            $sheet->setCellValue('D1', 'Ürün/Hizmet');
            $sheet->setCellValue('E1', 'Mal Hizmet Tutarı');
            $sheet->setCellValue('F1', 'Vergiler Dahil Tutar');
            $sheet->setCellValue('G1', 'KDV Oranı (%)');
            $sheet->setCellValue('H1', 'Para Birimi');
            
            // Başlık stili
            $headerStyle = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
            ];
            $sheet->getStyle('A1:H1')->applyFromArray($headerStyle);
            
            // Veriler
            $row = 2;
            foreach ($reportData as $data) {
                $sheet->setCellValue('A' . $row, $data['belgeNumarasi']);
                $sheet->setCellValue('B' . $row, $data['belgeTarihi']);
                $sheet->setCellValue('C' . $row, $data['alici']);
                $sheet->setCellValue('D' . $row, $data['urunHizmet']);
                $sheet->setCellValue('E' . $row, $data['malHizmetTutari']);
                $sheet->setCellValue('F' . $row, $data['vergilerDahilTutar']);
                $sheet->setCellValue('G' . $row, $data['kdvOrani']);
                $sheet->setCellValue('H' . $row, $data['paraBirimi']);
                $row++;
            }
            
            // Toplam satırı
            $sheet->setCellValue('A' . $row, 'TOPLAM');
            $sheet->setCellValue('E' . $row, number_format($totalMalHizmet, 2, '.', ''));
            $sheet->setCellValue('F' . $row, number_format($totalVergilerDahil, 2, '.', ''));
            
            $totalStyle = [
                'font' => ['bold' => true],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E7E6E6']]
            ];
            $sheet->getStyle('A' . $row . ':H' . $row)->applyFromArray($totalStyle);
            
            // Sütun genişlikleri
            $sheet->getColumnDimension('A')->setWidth(20);
            $sheet->getColumnDimension('B')->setWidth(15);
            $sheet->getColumnDimension('C')->setWidth(30);
            $sheet->getColumnDimension('D')->setWidth(30);
            $sheet->getColumnDimension('E')->setWidth(20);
            $sheet->getColumnDimension('F')->setWidth(20);
            $sheet->getColumnDimension('G')->setWidth(15);
            $sheet->getColumnDimension('H')->setWidth(15);
            
            // Dosya adı
            $fileName = 'fatura_raporu_' . date('Y-m-d_His') . '.xlsx';
            $filePath = __DIR__ . '/downloads/' . $fileName;
            
            // Downloads klasörünü oluştur
            if (!file_exists(__DIR__ . '/downloads')) {
                mkdir(__DIR__ . '/downloads', 0755, true);
            }
            
            // Excel dosyasını kaydet
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save($filePath);
            
            echo json_encode([
                'success' => true,
                'message' => 'Rapor başarıyla oluşturuldu!',
                'fileName' => $fileName,
                'downloadUrl' => 'backend/downloads/' . $fileName,
                'stats' => [
                    'invoiceCount' => $invoiceCount,
                    'totalMalHizmet' => number_format($totalMalHizmet, 2, '.', ''),
                    'totalVergilerDahil' => number_format($totalVergilerDahil, 2, '.', '')
                ]
            ]);
        } else {
            // JSON formatında döndür
            echo json_encode([
                'success' => true,
                'data' => $reportData,
                'stats' => [
                    'invoiceCount' => $invoiceCount,
                    'totalMalHizmet' => number_format($totalMalHizmet, 2, '.', ''),
                    'totalVergilerDahil' => number_format($totalVergilerDahil, 2, '.', '')
                ]
            ]);
        }
        
    } catch (Exception $e) {
        error_log('generate_report error: ' . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Rapor oluşturulamadı: ' . $e->getMessage()
        ]);
    }
    break;
*/

// ============================================================================
// LOGOUT CASE'İNE EKLENECEK (Cache temizleme)
// ============================================================================
/*
case 'logout':
    header('Content-Type: application/json');
    
    // Session cache'i temizle
    clearSessionCache();
    
    // e-Arşiv portalından çıkış yap
    $earsivLogout = logout_from_earsiv_portal();
    
    // Session'ı temizle
    session_unset();
    session_destroy();
    
    if ($earsivLogout['success']) {
        echo json_encode(['success' => true, 'message' => 'Çıkış başarılı! ' . $earsivLogout['message']]);
    } else {
        echo json_encode(['success' => true, 'message' => 'Çıkış başarılı! ' . $earsivLogout['message']]);
    }
    break;
*/
