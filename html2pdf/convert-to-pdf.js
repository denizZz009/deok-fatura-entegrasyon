// Node.js Puppeteer ile PDF dönüştürme
const puppeteer = require('puppeteer');
const fs = require('fs');

async function convertToPdf(htmlPath, pdfPath) {
    let browser;
    
    try {
        browser = await puppeteer.launch({
            headless: 'new',
            args: [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage'
            ]
        });
        
        const page = await browser.newPage();
        
        // HTML'i yükle
        const htmlContent = fs.readFileSync(htmlPath, 'utf8');
        await page.setContent(htmlContent, {
            waitUntil: 'domcontentloaded', // Daha hızlı
            timeout: 15000 // 15 saniye
        });
        
        // QR kod varsa bekle (opsiyonel)
        try {
            await page.waitForSelector('#qrcode canvas', { 
                timeout: 2000 
            });
            await page.waitForTimeout(500);
        } catch (e) {
            // QR kod yok veya yüklenemedi, devam et
        }
        
        // PDF oluştur
        await page.pdf({
            path: pdfPath,
            format: 'A4',
            printBackground: true,
            margin: {
                top: '15mm',
                right: '10mm',
                bottom: '15mm',
                left: '10mm'
            }
        });
        
        console.log('PDF oluşturuldu: ' + pdfPath);
        
    } catch (error) {
        console.error('HATA:', error.message);
        throw error;
    } finally {
        if (browser) {
            await browser.close();
        }
    }
}

// Komut satırı argümanları
const args = process.argv.slice(2);
if (args.length !== 2) {
    console.error('Kullanım: node convert-to-pdf.js <html-path> <pdf-path>');
    process.exit(1);
}

convertToPdf(args[0], args[1])
    .then(() => process.exit(0))
    .catch(err => {
        console.error(err);
        process.exit(1);
    });