# 📦 Portable Paketleme Rehberi

Bu rehber, uygulamayı başkalarına dağıtmak için nasıl paketleyeceğinizi açıklar.

## 🎯 Hedef

Kullanıcıların sadece ZIP'i çıkarıp `BASLA.bat` dosyasına tıklayarak kullanabileceği bir paket oluşturmak.

---

## 📋 Paketleme Adımları

### 1. Tam Kurulum Yap

```batch
1. otomatik-indir.bat çalıştır
2. portable-setup.bat çalıştır
3. BASLA.bat ile test et
```

### 2. Gereksiz Dosyaları Temizle

**Silinecekler:**
```
- .git/
- .vscode/
- .idea/
- *.log
- *.bak
- test/
- tests/
- composer.lock (opsiyonel)
```

**Tutulacaklar:**
```
✓ portable/php/
✓ portable/node/
✓ portable/composer/
✓ vendor/
✓ backend/vendor/
✓ html2pdf/node_modules/
✓ Tüm .bat dosyaları
✓ Tüm .md dosyaları
✓ index.html, script.js, style.css
```

### 3. Klasör Yapısını Kontrol Et

```
deok-fatura-entegrasyon-Portable/
├── portable/
│   ├── php/              ✓ (30 MB)
│   ├── node/             ✓ (50 MB)
│   ├── composer/         ✓ (2 MB)
│   └── downloads/        ✓ (boş klasör)
├── backend/
│   ├── vendor/           ✓ (20 MB)
│   ├── downloads/        ✓ (boş klasör)
│   └── *.php             ✓
├── html2pdf/
│   ├── node_modules/     ✓ (150 MB)
│   └── *.js, *.php       ✓
├── vendor/               ✓ (20 MB)
├── temp_invoices/        ✓ (boş klasör)
├── *.bat                 ✓ (tüm bat dosyaları)
├── *.md                  ✓ (tüm dokümantasyon)
├── index.html            ✓
├── script.js             ✓
├── style.css             ✓
└── help.html             ✓
```

### 4. README Dosyası Ekle

Ana klasöre `BASLANGIC.txt` ekle:

```text
═══════════════════════════════════════════════════════════════
  E-ARŞIV FATURA YÖNETİM SİSTEMİ - PORTABLE SÜRÜM
═══════════════════════════════════════════════════════════════

HIZLI BAŞLANGIÇ:

1. Bu klasörü istediğiniz yere çıkarın
2. BASLA.bat dosyasına çift tıklayın
3. Tarayıcıda açılan sayfada GİB bilgilerinizle giriş yapın

İLK KULLANIM:

Eğer uygulama açılmazsa:
1. MENU.bat dosyasını açın
2. "Sistem Kontrolü" seçeneğini seçin
3. Eksik bileşenleri kontrol edin

YARDIM:

- KULLANICI_KILAVUZU.md - Basit kullanım kılavuzu
- help.html - Detaylı yardım sayfası
- MENU.bat - Ana menü (tüm seçenekler)

GEREKSİNİMLER:

- Windows 10/11 (64-bit)
- 4GB RAM
- 500MB disk alanı
- İnternet (sadece GİB bağlantısı için)

═══════════════════════════════════════════════════════════════
```

### 5. ZIP Oluştur

**Yöntem 1: Windows Explorer**
```
1. Klasöre sağ tık
2. "Sıkıştırılmış (zip) klasöre gönder"
3. Dosya adı: deok-fatura-entegrasyon-Portable-v1.0.zip
```

**Yöntem 2: 7-Zip (Önerilen)**
```
1. 7-Zip ile sıkıştır
2. Sıkıştırma seviyesi: Normal
3. Format: ZIP
4. Dosya adı: deok-fatura-entegrasyon-Portable-v1.0.zip
```

---

## 📊 Paket Boyutları

### Sıkıştırılmamış
```
PHP:                30 MB
Node.js:            50 MB
Composer:           2 MB
PHP Paketleri:      20 MB
Node Paketleri:     150 MB
Puppeteer Chrome:   150 MB
Uygulama:           5 MB
─────────────────────────
TOPLAM:             ~400 MB
```

### Sıkıştırılmış (ZIP)
```
Beklenen boyut:     ~200-250 MB
```

---

## 🚀 Dağıtım Seçenekleri

### Seçenek 1: Tam Paket (Önerilen)

**İçerik:**
- ✅ PHP dahil
- ✅ Node.js dahil
- ✅ Tüm paketler dahil
- ✅ Tek tıkla çalışır

**Boyut:** ~250 MB (ZIP)

**Kullanıcı Adımları:**
```
1. ZIP'i çıkar
2. BASLA.bat'a tıkla
3. Kullan!
```

### Seçenek 2: Minimal Paket

**İçerik:**
- ❌ PHP yok
- ❌ Node.js yok
- ✅ Uygulama dosyaları
- ✅ Kurulum scriptleri

**Boyut:** ~5 MB (ZIP)

**Kullanıcı Adımları:**
```
1. ZIP'i çıkar
2. otomatik-indir.bat çalıştır
3. portable-setup.bat çalıştır
4. BASLA.bat'a tıkla
```

### Seçenek 3: Installer (Gelişmiş)

**Inno Setup ile:**
```
1. Inno Setup indir (https://jrsoftware.org/isinfo.php)
2. Setup scripti oluştur
3. Installer derle
4. .exe dosyası dağıt
```

**Avantajlar:**
- Profesyonel görünüm
- Başlat menüsüne ekleme
- Masaüstü kısayolu otomatik
- Kaldırma programı

---

## 📝 Kullanıcı Talimatları (Pakete Ekle)

### OKUBAŞI.txt

```text
═══════════════════════════════════════════════════════════════
  E-ARŞIV FATURA SİSTEMİ - KULLANIM TALİMATLARI
═══════════════════════════════════════════════════════════════

ADIM 1: ÇIKAR
─────────────
ZIP dosyasını istediğiniz klasöre çıkarın.
Örnek: C:\deok-fatura-entegrasyon\

ADIM 2: BAŞLAT
─────────────
BASLA.bat dosyasına çift tıklayın.
Tarayıcı otomatik açılacak.

ADIM 3: GİRİŞ YAP
─────────────
GİB kullanıcı kodunuz ve şifrenizle giriş yapın.

ADIM 4: KULLAN
─────────────
Faturalarınızı yönetin!

═══════════════════════════════════════════════════════════════

SORUN YAŞIYORSANIZ:

1. MENU.bat dosyasını açın
2. "Sistem Kontrolü" seçeneğini seçin
3. Eksik bileşenleri kontrol edin

Veya:

1. portable-setup.bat dosyasını çalıştırın
2. Kurulumun tamamlanmasını bekleyin
3. BASLA.bat ile tekrar deneyin

═══════════════════════════════════════════════════════════════

YARDIM:

- KULLANICI_KILAVUZU.md
- help.html
- MENU.bat

═══════════════════════════════════════════════════════════════
```

---

## ✅ Kontrol Listesi

Paketlemeden önce kontrol edin:

- [ ] PHP çalışıyor (`portable\php\php.exe -v`)
- [ ] Node.js çalışıyor (`portable\node\node.exe -v`)
- [ ] Composer yüklü (`portable\composer\composer.phar`)
- [ ] PHP paketleri yüklü (`vendor/` klasörü var)
- [ ] Node paketleri yüklü (`html2pdf/node_modules/` var)
- [ ] Puppeteer Chrome yüklü
- [ ] BASLA.bat çalışıyor
- [ ] Giriş yapılabiliyor
- [ ] Fatura oluşturulabiliyor
- [ ] PDF oluşturuluyor
- [ ] Mail gönderilebiliyor
- [ ] Tüm .bat dosyaları var
- [ ] Tüm .md dosyaları var
- [ ] OKUBAŞI.txt eklendi
- [ ] .gitignore kontrol edildi
- [ ] Log dosyaları temizlendi

---

## 🎁 Bonus: Otomatik Paketleme Scripti

`paketleme.bat` oluştur:

```batch
@echo off
echo Paketleme başlıyor...

REM Gereksiz dosyaları temizle
del /q *.log 2>nul
del /q *.bak 2>nul

REM ZIP oluştur (7-Zip gerekli)
"C:\Program Files\7-Zip\7z.exe" a -tzip deok-fatura-entegrasyon-Portable-v1.0.zip * -xr!.git -xr!.vscode -xr!.idea -xr!*.log

echo Paketleme tamamlandı!
pause
```

---

## 📤 Dağıtım Kanalları

1. **USB Bellek:** Doğrudan kopyala
2. **Ağ Paylaşımı:** Şirket içi paylaşım
3. **Cloud:** Google Drive, Dropbox, OneDrive
4. **FTP/SFTP:** Sunucu üzerinden
5. **Email:** Küçük paketler için (Minimal)

---

## 🔒 Güvenlik Notları

**Pakete EKLEMEYIN:**
- ❌ Gerçek GİB şifreleri
- ❌ SMTP şifreleri
- ❌ Gerçek fatura verileri
- ❌ Log dosyaları
- ❌ Session dosyaları

**Kullanıcıları Uyarın:**
- ⚠️ Şifreleri paylaşmayın
- ⚠️ Güvenilir kaynaklardan indirin
- ⚠️ Antivirüs taraması yapın

---

**Hazırlayan:** E-Arşiv Fatura Sistemi Ekibi  
**Versiyon:** 1.0  
**Tarih:** 2025
