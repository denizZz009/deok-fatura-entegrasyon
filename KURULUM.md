# 🔧 E-Arşiv Fatura Yönetim Sistemi - Kurulum Kılavuzu

<div align="center">

**Detaylı kurulum adımları ve yapılandırma rehberi**

[Ana Sayfa](README.md) • [Kullanım Kılavuzu](KULLANIM.md)

</div>

---

## 📋 İçindekiler

- [Kurulum Yöntemleri](#-kurulum-yöntemleri)
- [Portable Kurulum](#-portable-kurulum-önerilen)
- [Manuel Kurulum](#-manuel-kurulum)
- [Yapılandırma](#-yapılandırma)
- [Sorun Giderme](#-sorun-giderme)
- [Güncelleme](#-güncelleme)

---

## 🎯 Kurulum Yöntemleri

Bu proje iki farklı kurulum yöntemi sunar:

| Yöntem | Avantajlar | Dezavantajlar | Önerilen |
|--------|-----------|---------------|----------|
| **Portable** | ✅ Kolay<br>✅ Hızlı<br>✅ Ek yazılım gerekmez | ❌ Sadece Windows | ⭐ Evet |
| **Manuel** | ✅ Özelleştirilebilir<br>✅ Tüm platformlar | ❌ Teknik bilgi gerekli<br>❌ Uzun sürer | ⚠️ İleri seviye |

---

## 🚀 Portable Kurulum (Önerilen)

Portable kurulum, hiçbir ek yazılım gerektirmeden uygulamayı çalıştırmanızı sağlar.

### Adım 1: Projeyi İndirin

#### Git ile (Önerilen)

```bash
git clone https://github.com/denizZz009/deok-fatura-entegrasyon.git
cd deok-fatura-entegrasyon
```

#### ZIP ile

1. [Releases](https://github.com/denizZz009/deok-fatura-entegrasyon/releases) sayfasından son sürümü indirin
2. ZIP dosyasını istediğiniz klasöre çıkarın
3. Klasöre girin

### Adım 2: Otomatik Kurulum

#### Yöntem 1: Tam Otomatik (Önerilen)

```batch
# 1. PHP ve Node.js'i otomatik indir (~80 MB)
otomatik-indir.bat

# 2. Bağımlılıkları yükle
portable-setup.bat

# 3. Uygulamayı başlat
BASLA.bat
```

#### Yöntem 2: Manuel İndirme

Eğer otomatik indirme çalışmazsa:

1. **`portable-download-links.txt`** dosyasını açın
2. İçindeki linkleri kullanarak PHP ve Node.js'i indirin
3. İndirilen dosyaları `portable/` klasörüne çıkarın
4. `portable-setup.bat` dosyasını çalıştırın
5. `BASLA.bat` ile başlatın

### Adım 3: İlk Çalıştırma

1. **BASLA.bat** dosyasına çift tıklayın
2. Tarayıcı otomatik olarak açılacak
3. GİB bilgilerinizle giriş yapın
4. Kullanmaya başlayın!

### Portable Klasör Yapısı

```
deok-fatura-entegrasyon/
├── portable/
│   ├── php/                    # PHP 8.1.27 (64-bit)
│   │   ├── php.exe
│   │   ├── php.ini
│   │   └── ext/               # PHP eklentileri
│   ├── node/                   # Node.js 18.19.0
│   │   ├── node.exe
│   │   └── npm.cmd
│   └── composer/               # Composer
│       └── composer.phar
├── BASLA.bat                   # Ana başlatıcı
├── portable-setup.bat          # Kurulum scripti
└── otomatik-indir.bat         # Otomatik indirme
```

---

## 🛠️ Manuel Kurulum

Manuel kurulum, tüm bileşenleri kendiniz yüklemek istiyorsanız veya Linux/macOS kullanıyorsanız önerilir.

### Ön Gereksinimler

#### 1. PHP Kurulumu

**Windows:**

1. [PHP 8.1 veya üzeri](https://windows.php.net/download/) indirin (Thread Safe)
2. `C:\php` klasörüne çıkarın
3. `php.ini-development` dosyasını `php.ini` olarak kopyalayın
4. `php.ini` dosyasında şu satırları aktif edin (`;` işaretini kaldırın):

```ini
extension=curl
extension=fileinfo
extension=gd
extension=mbstring
extension=mysqli
extension=openssl
extension=pdo_mysql
extension=zip
```

5. PHP'yi PATH'e ekleyin:
   - Windows Arama: "Ortam Değişkenleri"
   - "Sistem Ortam Değişkenlerini Düzenle"
   - "Ortam Değişkenleri" → "Path" → "Düzenle"
   - "Yeni" → `C:\php` ekleyin

6. Test edin:
```bash
php -v
```

**Linux (Ubuntu/Debian):**

```bash
sudo apt update
sudo apt install php8.1 php8.1-cli php8.1-curl php8.1-mbstring php8.1-xml php8.1-zip
php -v
```

**macOS:**

```bash
brew install php@8.1
php -v
```

#### 2. Composer Kurulumu

**Windows:**

1. [Composer-Setup.exe](https://getcomposer.org/download/) indirin
2. Kurulum sırasında PHP yolunu seçin: `C:\php\php.exe`
3. Test edin:
```bash
composer --version
```

**Linux/macOS:**

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
composer --version
```

#### 3. Node.js Kurulumu

**Windows:**

1. [Node.js LTS](https://nodejs.org/) indirin
2. Kurulum sırasında "Automatically install necessary tools" seçeneğini işaretleyin
3. Test edin:
```bash
node --version
npm --version
```

**Linux (Ubuntu/Debian):**

```bash
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs
node --version
npm --version
```

**macOS:**

```bash
brew install node@18
node --version
npm --version
```

### Proje Kurulumu

#### 1. Projeyi İndirin

```bash
git clone https://github.com/denizZz009/deok-fatura-entegrasyon.git
cd deok-fatura-entegrasyon
```

#### 2. PHP Bağımlılıklarını Yükleyin

```bash
# Ana bağımlılıklar
composer install

# Backend bağımlılıkları
cd backend
composer install
cd ..
```

#### 3. Node.js Bağımlılıklarını Yükleyin

```bash
# PDF oluşturma için
cd html2pdf
npm install

# Chrome/Chromium'u yükleyin
npx puppeteer browsers install chrome

cd ..
```

#### 4. Sunucuyu Başlatın

**Windows:**

```batch
# Proje klasöründe
php -S localhost:8000
```

**Linux/macOS:**

```bash
# Proje klasöründe
php -S localhost:8000
```

#### 5. Tarayıcıda Açın

```
http://localhost:8000
```

---

## ⚙️ Yapılandırma

### SMTP Ayarları

Mail göndermek için SMTP ayarlarını yapılandırmanız gerekir.

#### Outlook/Office365

```
SMTP Sunucu: smtp.office365.com
Port: 587
Şifreleme: TLS
Kullanıcı Adı: email@domain.com
Şifre: mail şifreniz
```

#### Gmail

```
SMTP Sunucu: smtp.gmail.com
Port: 587
Şifreleme: TLS
Kullanıcı Adı: email@gmail.com
Şifre: Uygulama Şifresi (2FA gerekli)
```

**Gmail için Uygulama Şifresi Oluşturma:**

1. [Google Hesap Güvenliği](https://myaccount.google.com/security) sayfasına gidin
2. "2 Adımlı Doğrulama"yı aktif edin
3. [Uygulama Şifreleri](https://myaccount.google.com/apppasswords) sayfasına gidin
4. "Uygulama seç" → "Diğer" → "E-Fatura Sistemi"
5. Oluşturulan 16 haneli şifreyi kullanın

#### Yandex

```
SMTP Sunucu: smtp.yandex.com
Port: 465
Şifreleme: SSL
Kullanıcı Adı: email@yandex.com
Şifre: mail şifreniz
```

### Port Değiştirme

Eğer 8000 portu kullanımdaysa:

```bash
# Farklı port kullanın
php -S localhost:8080

# Veya
php -S localhost:3000
```

### Güvenlik Ayarları

#### Session Zaman Aşımı

`backend/index.php` dosyasında:

```php
// Varsayılan: 1200 saniye (20 dakika)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1200)) {
    // Zaman aşımı
}

// Değiştirmek için:
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 3600)) {
    // 60 dakika
}
```

#### Brute Force Koruması

```php
// Varsayılan: 10 deneme / 10 dakika
function is_brute_forced() {
    return count($_SESSION['brute_force']) >= 10;
}

// Değiştirmek için:
function is_brute_forced() {
    return count($_SESSION['brute_force']) >= 5; // 5 deneme
}
```

---

## 🐛 Sorun Giderme

### PHP Sorunları

#### "php: command not found"

**Çözüm:**
```bash
# Windows: PHP'yi PATH'e ekleyin (yukarıdaki adımlara bakın)

# Linux/macOS:
which php
# Eğer bulunamazsa:
sudo apt install php8.1-cli  # Linux
brew install php@8.1         # macOS
```

#### "Class 'PHPMailer' not found"

**Çözüm:**
```bash
composer install
cd backend
composer install
```

#### PHP Extension Hatası

**Çözüm:**
```bash
# php.ini dosyasında ilgili extension'ı aktif edin
# Örnek: extension=curl

# Linux'ta:
sudo apt install php8.1-curl php8.1-mbstring php8.1-xml
```

### Node.js Sorunları

#### "node: command not found"

**Çözüm:**
```bash
# Node.js'i yeniden kurun
# Windows: https://nodejs.org/
# Linux: curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
# macOS: brew install node@18
```

#### "Module 'puppeteer' not found"

**Çözüm:**
```bash
cd html2pdf
npm install
npx puppeteer browsers install chrome
```

#### "Could not find Chrome"

**Çözüm:**
```bash
cd html2pdf
npx puppeteer browsers install chrome

# Veya manuel Chrome yolu belirtin
# convert-to-pdf.js dosyasında:
# executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe'
```

### PDF Sorunları

#### PDF Oluşturulmuyor

**Çözüm 1: Puppeteer'ı yeniden yükleyin**
```bash
cd html2pdf
rm -rf node_modules
npm install
npx puppeteer browsers install chrome
```

**Çözüm 2: DomPDF kullanın**
```php
// backend/index.php dosyasında PDF oluşturma kısmını değiştirin
// Puppeteer yerine DomPDF kullanın
```

**Çözüm 3: Üçüncü parti API**
```php
// ILovePDF veya benzeri bir API kullanın
```

### Mail Sorunları

#### Mail Gönderilmiyor

**Çözüm 1: SMTP Ayarlarını Kontrol Edin**
- "Bağlantıyı Test Et" butonunu kullanın
- Port ve şifreleme ayarlarını doğrulayın

**Çözüm 2: Gmail için Uygulama Şifresi**
- Normal şifre yerine Uygulama Şifresi kullanın
- 2FA'yı aktif edin

**Çözüm 3: Firewall/Antivirus**
- SMTP portlarını (587, 465) açın
- Antivirüs'ü geçici olarak devre dışı bırakın

#### "SMTP connect() failed"

**Çözüm:**
```php
// PHPMailer debug modunu aktif edin
$mail->SMTPDebug = 2;
$mail->Debugoutput = 'html';

// Hata mesajlarını kontrol edin
```

### Port Sorunları

#### "Port 8000 already in use"

**Çözüm:**
```bash
# Farklı port kullanın
php -S localhost:8080

# Veya portu kullanan işlemi bulun ve sonlandırın
# Windows:
netstat -ano | findstr :8000
taskkill /PID <PID> /F

# Linux/macOS:
lsof -i :8000
kill -9 <PID>
```

### Session Sorunları

#### "Session timeout" Hatası

**Çözüm:**
- Oturum 20 dakika inaktivite sonrası sona erer
- Tekrar giriş yapın
- Session timeout süresini artırın (yukarıdaki yapılandırma bölümüne bakın)

#### "Session güvenliği nedeniyle sonlandırıldı"

**Çözüm:**
- IP adresiniz değişmiş olabilir (VPN, proxy)
- Tarayıcı önbelleğini temizleyin
- Tekrar giriş yapın

---

## 🔄 Güncelleme

### Git ile Güncelleme

```bash
# Değişiklikleri kaydedin
git stash

# Son sürümü çekin
git pull origin main

# Değişikliklerinizi geri yükleyin
git stash pop

# Bağımlılıkları güncelleyin
composer update
cd backend && composer update && cd ..
cd html2pdf && npm update && cd ..
```

### Manuel Güncelleme

1. Mevcut dosyalarınızı yedekleyin
2. Yeni sürümü indirin
3. Özel ayarlarınızı yeni sürüme kopyalayın
4. Bağımlılıkları yeniden yükleyin

---

## 📊 Performans Optimizasyonu

### PHP Optimizasyonu

```ini
; php.ini dosyasında
memory_limit = 256M
max_execution_time = 300
upload_max_filesize = 10M
post_max_size = 10M
```

### Puppeteer Optimizasyonu

```javascript
// html2pdf/convert-to-pdf.js dosyasında
const browser = await puppeteer.launch({
    headless: true,
    args: [
        '--no-sandbox',
        '--disable-setuid-sandbox',
        '--disable-dev-shm-usage',
        '--disable-gpu'
    ]
});
```

### Composer Optimizasyonu

```bash
# Autoload'u optimize edin
composer dump-autoload --optimize

# Production için
composer install --no-dev --optimize-autoloader
```

---

## 🔐 Güvenlik Önerileri

### Production Ortamı

1. **HTTPS Kullanın**
   ```apache
   # .htaccess
   RewriteEngine On
   RewriteCond %{HTTPS} off
   RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
   ```

2. **Hata Mesajlarını Gizleyin**
   ```php
   // php.ini
   display_errors = Off
   log_errors = On
   error_log = /path/to/error.log
   ```

3. **Güvenli Session Ayarları**
   ```php
   ini_set('session.cookie_httponly', 1);
   ini_set('session.cookie_secure', 1);
   ini_set('session.use_strict_mode', 1);
   ```

4. **CORS Ayarları**
   ```php
   // Sadece belirli domainlere izin verin
   header('Access-Control-Allow-Origin: https://yourdomain.com');
   ```

---

## 📞 Destek

Kurulum sırasında sorun yaşarsanız:

1. **Dokümantasyonu kontrol edin:** [README.md](README.md)
2. **Sorun bildirin:** [GitHub Issues](https://github.com/denizZz009/deok-fatura-entegrasyon/issues)
3. **Tartışmalara katılın:** [GitHub Discussions](https://github.com/denizZz009/deok-fatura-entegrasyon/discussions)

---

<div align="center">

**Kurulum tamamlandı! 🎉**

[Ana Sayfa](README.md) • [Kullanım Kılavuzu](KULLANIM.md)

</div>
