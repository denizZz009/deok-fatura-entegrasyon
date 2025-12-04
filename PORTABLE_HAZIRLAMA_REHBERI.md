# E-Arşiv Fatura Sistemi - Portable Hazırlama Rehberi

Bu rehber, uygulamayı portable (taşınabilir) hale getirmek için gereken adımları açıklar.

## 📦 Gerekli Dosyalar

### 1. PHP (Portable)
- **İndirme:** https://windows.php.net/download/
- **Versiyon:** PHP 8.1 veya üzeri (Thread Safe)
- **Dosya:** `php-8.1.x-Win32-vs16-x64.zip`

### 2. Node.js (Portable)
- **İndirme:** https://nodejs.org/dist/
- **Versiyon:** Node.js 18 LTS veya üzeri
- **Dosya:** `node-v18.x.x-win-x64.zip`

---

## 🛠️ Adım Adım Kurulum

### ADIM 1: Klasör Yapısını Oluştur

```
deok-fatura-entegrasyon-Portable/
├── portable/
│   ├── php/          (PHP buraya gelecek)
│   ├── node/         (Node.js buraya gelecek)
│   ├── composer/     (Otomatik oluşturulacak)
│   └── downloads/    (Otomatik oluşturulacak)
├── backend/
├── html2pdf/
├── vendor/
├── index.html
├── script.js
├── BASLA.bat         ← Ana başlatma dosyası
├── portable-setup.bat ← İlk kurulum dosyası
└── ... (diğer dosyalar)
```

### ADIM 2: PHP'yi Hazırla

1. **PHP'yi İndir:**
   - https://windows.php.net/download/
   - `php-8.1.x-Win32-vs16-x64.zip` dosyasını indir

2. **PHP'yi Çıkar:**
   - ZIP dosyasını `portable/php/` klasörüne çıkar
   - Sonuç: `portable/php/php.exe` olmalı

3. **php.ini Ayarla:**
   - `portable/php/php.ini-development` dosyasını kopyala
   - `php.ini` olarak yeniden adlandır
   - Aşağıdaki satırları bul ve `;` işaretini kaldır:

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

4. **Memory ve Upload Limitlerini Artır:**

```ini
memory_limit = 512M
upload_max_filesize = 50M
post_max_size = 50M
max_execution_time = 300
```

### ADIM 3: Node.js'i Hazırla

1. **Node.js'i İndir:**
   - https://nodejs.org/dist/v18.19.0/node-v18.19.0-win-x64.zip
   - (veya en son LTS versiyonu)

2. **Node.js'i Çıkar:**
   - ZIP dosyasını `portable/node/` klasörüne çıkar
   - Sonuç: `portable/node/node.exe` olmalı

3. **npm ve npx Kontrol:**
   - `portable/node/npm.cmd` var mı kontrol et
   - `portable/node/npx.cmd` var mı kontrol et

### ADIM 4: Bağımlılıkları Yükle

1. **portable-setup.bat Çalıştır:**
   ```
   Çift tıkla: portable-setup.bat
   ```

2. **Otomatik Yapılacaklar:**
   - Composer indirilecek
   - PHP paketleri yüklenecek (vendor/)
   - Node.js paketleri yüklenecek (html2pdf/node_modules/)
   - Puppeteer Chrome indirilecek

3. **Manuel Kontrol (Opsiyonel):**
   ```cmd
   REM PHP paketleri
   portable\php\php.exe portable\composer\composer.phar install

   REM Node.js paketleri
   cd html2pdf
   ..\portable\node\npm.cmd install
   ..\portable\node\npx.cmd puppeteer browsers install chrome
   ```

### ADIM 5: Test Et

1. **BASLA.bat Çalıştır:**
   ```
   Çift tıkla: BASLA.bat
   ```

2. **Kontrol Et:**
   - Tarayıcı otomatik açılmalı
   - http://localhost:8000 adresine gitmeli
   - Giriş ekranı görünmeli

3. **Test Senaryoları:**
   - ✅ Giriş yapabilme
   - ✅ Fatura listeleme
   - ✅ Yeni fatura oluşturma
   - ✅ PDF oluşturma
   - ✅ Mail gönderme

---

## 📤 Dağıtım İçin Hazırlama

### Yöntem 1: ZIP Dosyası

1. **Tüm Klasörü Sıkıştır:**
   ```
   deok-fatura-entegrasyon-Portable.zip
   ```

2. **Kullanıcı Talimatları:**
   ```
   1. ZIP'i çıkar
   2. BASLA.bat'a çift tıkla
   3. Tarayıcıda açılan sayfada giriş yap
   ```

### Yöntem 2: Installer (Opsiyonel)

**Inno Setup ile installer oluşturabilirsiniz:**
- https://jrsoftware.org/isinfo.php

---

## 📋 Kullanıcı İçin Basit Talimatlar

### İLK KULLANIM:

1. **Klasörü Çıkar:**
   - ZIP dosyasını istediğiniz yere çıkarın
   - Örnek: `C:\deok-fatura-entegrasyon\`

2. **Kurulum Yap (Sadece İlk Kez):**
   - `portable-setup.bat` dosyasına çift tıklayın
   - Kurulum otomatik tamamlanacak (2-5 dakika)

3. **Uygulamayı Başlat:**
   - `BASLA.bat` dosyasına çift tıklayın
   - Tarayıcı otomatik açılacak

4. **Giriş Yap:**
   - GİB kullanıcı kodunuzu girin
   - GİB şifrenizi girin
   - "Giriş Yap" butonuna tıklayın

### SONRAKI KULLANIMLAR:

1. `BASLA.bat` dosyasına çift tıklayın
2. Tarayıcıda açılan sayfada giriş yapın
3. İşiniz bitince pencereyi kapatın

---

## ⚠️ Önemli Notlar

### Güvenlik:
- ✅ Tüm veriler yerel bilgisayarda
- ✅ İnternet sadece GİB bağlantısı için
- ✅ Şifreler session'da saklanır (geçici)

### Performans:
- 💾 Minimum 4GB RAM önerilir
- 💽 Minimum 500MB disk alanı
- 🌐 Stabil internet bağlantısı

### Uyumluluk:
- ✅ Windows 10/11 (64-bit)
- ✅ Windows Server 2016+
- ❌ Windows 7/8 (test edilmedi)
- ❌ 32-bit sistemler

### Sorun Giderme:

**"PHP bulunamadı" Hatası:**
- `portable/php/php.exe` dosyası var mı kontrol edin
- Yoksa PHP'yi tekrar indirip çıkarın

**"Node.js bulunamadı" Hatası:**
- `portable/node/node.exe` dosyası var mı kontrol edin
- Yoksa Node.js'i tekrar indirip çıkarın

**"Port kullanımda" Hatası:**
- Başka bir uygulama 8000 portunu kullanıyor
- BASLA.bat otomatik alternatif port deneyecek (8080, 8888)

**PDF Oluşturulmuyor:**
- `html2pdf/node_modules` klasörü var mı kontrol edin
- Yoksa `portable-setup.bat` tekrar çalıştırın

**Mail Gönderilmiyor:**
- SMTP ayarlarını kontrol edin
- "SMTP Ayarları" butonundan test edin

---

## 📦 Dosya Boyutları (Yaklaşık)

```
PHP (portable):           ~30 MB
Node.js (portable):       ~50 MB
Composer:                 ~2 MB
PHP vendor/:              ~20 MB
node_modules/:            ~150 MB
Puppeteer Chrome:         ~150 MB
Uygulama dosyaları:       ~5 MB
─────────────────────────────────
TOPLAM:                   ~400 MB
```

---

## 🚀 Hızlı Başlangıç (Özet)

```
1. ZIP'i çıkar
2. portable-setup.bat (ilk kez)
3. BASLA.bat (her kullanımda)
4. Tarayıcıda giriş yap
5. Kullan!
```

---

## 📞 Destek

Sorun yaşarsanız:
1. `help.html` dosyasını açın
2. Konsol hatalarını kontrol edin (F12)
3. `stderr.log` dosyasını kontrol edin

---

**Hazırlayan:** E-Arşiv Fatura Sistemi Ekibi  
**Versiyon:** 1.0 Portable  
**Tarih:** 2025
