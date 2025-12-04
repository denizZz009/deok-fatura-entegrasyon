# 🧾 E-Arşiv Fatura Yönetim Sistemi

<div align="center">

![Version](https://img.shields.io/badge/versiyon-1.0.0-blue.svg)
![PHP](https://img.shields.io/badge/PHP-8.1+-777BB4?logo=php&logoColor=white)
![Node.js](https://img.shields.io/badge/Node.js-18+-339933?logo=node.js&logoColor=white)
![License](https://img.shields.io/badge/lisans-MIT-green.svg)
![Platform](https://img.shields.io/badge/platform-Windows%2010%2F11-0078D6?logo=windows&logoColor=white)

**GİB e-Arşiv fatura sistemi için geliştirilmiş, modern ve kullanıcı dostu masaüstü uygulaması**

[Özellikler](#-özellikler) • [Kurulum](KURULUM.md) • [Kullanım](KULLANIM.md) • [Ekran Görüntüleri](#-ekran-görüntüleri) • [Katkıda Bulunma](#-katkıda-bulunma)

</div>

---

## 📋 İçindekiler

- [Özellikler](#-özellikler)
- [Hızlı Başlangıç](#-hızlı-başlangıç)
- [Teknoloji Stack](#-teknoloji-stack)
- [Sistem Gereksinimleri](#-sistem-gereksinimleri)
- [Kurulum](#-kurulum)
- [Kullanım](#-kullanım)
- [Önemli Notlar](#-önemli-notlar)
- [Sorun Giderme](#-sorun-giderme)
- [Katkıda Bulunma](#-katkıda-bulunma)
- [Lisans](#-lisans)
- [İletişim](#-iletişim)

---

## ✨ Özellikler

### 🎯 Temel Özellikler

- ✅ **Portable Yapı** - Kurulum gerektirmez, USB'den çalışır
- ✅ **Kolay Kullanım** - Tek tıkla başlatma ve sezgisel arayüz
- ✅ **GİB Entegrasyonu** - Resmi e-Arşiv API ile tam entegrasyon
- ✅ **Güvenli Oturum** - Session güvenliği ve brute force koruması

### 📄 Fatura İşlemleri

- � **Fatura Oluşturma** - Detaylı form ile kolay fatura oluşturma
- 📊 **Toplu Yükleme** - Excel ile toplu fatura yükleme
- 🔍 **Listeleme & Filtreleme** - Gelişmiş arama ve filtreleme
- ✏️ **Düzenleme** - Taslak faturaları düzenleme
- ❌ **İptal** - Onaylanmış faturaları iptal etme
- ✔️ **SMS Onaylama** - Tek veya toplu SMS onaylama

### 📧 Mail Sistemi

- 📨 **Otomatik Mail Gönderimi** - PDF eki ile otomatik mail
- 🎨 **Özelleştirilebilir Şablonlar** - 3 farklı profesyonel şablon
- 🖼️ **Logo Desteği** - Firma logonuzu ekleyin
- 📝 **Özel Mesajlar** - Kişiselleştirilmiş mail içeriği
- 🔧 **SMTP Yapılandırması** - Outlook, Gmail, Yandex desteği

### 📊 Raporlama

- 📈 **Excel Raporları** - Detaylı fatura raporları
- 📅 **Tarih Aralığı** - Özelleştirilebilir tarih filtreleme
- 💰 **Finansal Özet** - Toplam tutar ve vergi hesaplamaları
- � **Dlışa Aktarma** - Excel formatında indirme

### 🔐 Güvenlik

- 🛡️ **Session Güvenliği** - IP ve User-Agent kontrolü
- 🚫 **Brute Force Koruması** - Otomatik engelleme sistemi
- ⏱️ **Oturum Zaman Aşımı** - 20 dakika inaktivite koruması
- 🔒 **Güvenli Veri Saklama** - Yerel ve güvenli depolama

---

## 🚀 Hızlı Başlangıç

### Portable Sürüm (Önerilen)

```batch
# 1. Otomatik indirme ve kurulum
otomatik-indir.bat

# 2. Kurulumu tamamlama
portable-setup.bat

# 3. Uygulamayı başlatma
BASLA.bat
```

### Manuel Kurulum

Detaylı kurulum talimatları için [KURULUM.md](KURULUM.md) dosyasına bakın.

---

## 🛠️ Teknoloji Stack

### Backend
- **PHP 8.1+** - Ana backend dili
- **mlevent/fatura** - GİB e-Arşiv API entegrasyonu
- **PHPMailer 7.0+** - Mail gönderimi
- **PhpSpreadsheet 1.29+** - Excel işlemleri
- **DomPDF 3.1+** - PDF oluşturma (alternatif)

### Frontend
- **Vanilla JavaScript** - Saf JavaScript, framework yok
- **HTML5 & CSS3** - Modern web standartları
- **Font Awesome 6** - İkon kütüphanesi

### PDF Oluşturma
- **Node.js 18+** - JavaScript runtime
- **Puppeteer** - Headless Chrome ile PDF oluşturma

### Diğer
- **Composer** - PHP paket yöneticisi
- **npm** - Node.js paket yöneticisi

---

## 💻 Sistem Gereksinimleri

### Minimum Gereksinimler

| Bileşen | Gereksinim |
|---------|-----------|
| **İşletim Sistemi** | Windows 10/11 (64-bit) |
| **RAM** | 4GB (önerilen) |
| **Disk Alanı** | 500MB boş alan |
| **İnternet** | GİB bağlantısı için gerekli |
| **Tarayıcı** | Chrome, Firefox, Edge (güncel) |

### Yazılım Gereksinimleri

**Portable Sürüm:** Hiçbir ek yazılım gerekmez!

**Manuel Kurulum:**
- PHP 8.1 veya üzeri
- Composer (PHP paket yöneticisi)
- Node.js 18 veya üzeri
- npm (Node.js ile birlikte gelir)

---

## 📦 Kurulum

### Portable Kurulum (Önerilen)

1. **Projeyi İndirin**
   ```bash
   git clone https://github.com/denizZz009/deok-fatura-entegrasyon.git
   cd deok-fatura-entegrasyon
   ```

2. **Otomatik Kurulum**
   ```batch
   # PHP ve Node.js'i otomatik indir
   otomatik-indir.bat
   
   # Bağımlılıkları yükle
   portable-setup.bat
   ```

3. **Başlatın**
   ```batch
   BASLA.bat
   ```

### Manuel Kurulum

Detaylı manuel kurulum adımları için [KURULUM.md](KURULUM.md) dosyasına bakın.

---

## 📖 Kullanım

### Temel Kullanım

1. **Giriş Yapma**
   - `BASLA.bat` dosyasını çalıştırın
   - Tarayıcıda açılan sayfada GİB bilgilerinizle giriş yapın

2. **Fatura Oluşturma**
   - "Yeni Fatura Oluştur" butonuna tıklayın
   - Formu doldurun
   - "Taslak Oluştur" ile kaydedin

3. **Toplu Fatura Yükleme**
   - "Toplu Fatura Yükle" butonuna tıklayın
   - Excel şablonunu indirin ve doldurun
   - Dosyayı yükleyin ve işleyin

4. **Mail Gönderme**
   - SMTP ayarlarını yapılandırın
   - Faturaları seçin
   - "Seçilenlere Mail Gönder" butonuna tıklayın

Detaylı kullanım kılavuzu için [KULLANIM.md](KULLANIM.md) dosyasına bakın.

---

## 📁 Proje Yapısı

```
deok-fatura-entegrasyon/
├── 📂 backend/                 # Backend PHP dosyaları
│   ├── index.php              # Ana API endpoint
│   ├── mail_functions.php     # Mail işlemleri
│   ├── mail_templates.php     # Mail şablonları
│   ├── optimized_functions.php # Optimize edilmiş fonksiyonlar
│   ├── composer.json          # PHP bağımlılıkları
│   └── vendor/                # PHP paketleri
├── 📂 html2pdf/               # PDF oluşturma sistemi
│   ├── convert-to-pdf.js      # Puppeteer script
│   ├── package.json           # Node.js bağımlılıkları
│   └── node_modules/          # Node.js paketleri
├── 📂 portable/               # Portable bileşenler
│   ├── php/                   # PHP runtime
│   ├── node/                  # Node.js runtime
│   └── composer/              # Composer
├── 📂 vendor/                 # Ana PHP paketleri
├── 📄 index.html              # Ana sayfa
├── 📄 script.js               # Ana JavaScript
├── 📄 mail_script.js          # Mail JavaScript
├── 📄 style.css               # Stil dosyası
├── 📄 help.html               # Yardım sayfası
├── 📄 composer.json           # Ana PHP bağımlılıkları
├── 🚀 BASLA.bat              # Başlatıcı script
├── 🔧 portable-setup.bat     # Kurulum script
├── 📥 otomatik-indir.bat     # Otomatik indirme
├── 📖 README.md              # Bu dosya
├── 📖 KURULUM.md             # Kurulum kılavuzu
└── 📖 KULLANIM.md            # Kullanım kılavuzu
```

---

## ⚠️ Önemli Notlar

### Güvenlik

- 🔒 **Şifre Güvenliği:** GİB şifreniz sadece session'da tutulur, hiçbir yere kaydedilmez
- 🛡️ **Yerel Depolama:** Tüm veriler yerel bilgisayarınızda saklanır
- 🌐 **İnternet Kullanımı:** Sadece GİB bağlantısı için internet gereklidir
- 🚫 **Veri Paylaşımı:** Hiçbir veri üçüncü taraflarla paylaşılmaz

### Performans

- ⏱️ **PDF Oluşturma:** Her PDF ~2-3 saniye sürer
- 📧 **Mail Gönderimi:** Her fatura ~5-7 saniye sürer
- 📊 **Toplu İşlemler:** Büyük miktarlarda batch işleme önerilir

### Hosting

- 🖥️ **Shared Hosting:** Puppeteer çalışmayabilir
- ☁️ **VPS/Dedicated:** Önerilir
- 🔄 **Alternatif:** DomPDF veya üçüncü parti PDF API kullanılabilir

---

## 🐛 Sorun Giderme

### Sık Karşılaşılan Sorunlar

<details>
<summary><b>Uygulama açılmıyor</b></summary>

```batch
# Çözüm 1: Kurulumu yeniden yapın
portable-setup.bat

# Çözüm 2: Port değiştirin
php -S localhost:8080
```
</details>

<details>
<summary><b>PDF oluşturulmuyor</b></summary>

```batch
# html2pdf klasöründe:
cd html2pdf
npm install
npx puppeteer browsers install chrome
cd ..
```
</details>

<details>
<summary><b>Mail gönderilmiyor</b></summary>

- SMTP ayarlarını kontrol edin
- Gmail için "Uygulama Şifresi" kullanın
- "Bağlantıyı Test Et" butonunu kullanın
</details>

<details>
<summary><b>Session süresi doldu hatası</b></summary>

- Oturum 20 dakika inaktivite sonrası sona erer
- Tekrar giriş yapın
- Uzun işlemler için oturumu aktif tutun
</details>

Daha fazla sorun giderme için [KURULUM.md](KURULUM.md) dosyasına bakın.

---

## 🤝 Katkıda Bulunma

Katkılarınızı bekliyoruz! Projeye katkıda bulunmak için:

1. **Fork** edin
2. **Feature branch** oluşturun (`git checkout -b feature/harika-ozellik`)
3. **Commit** edin (`git commit -m 'Harika özellik eklendi'`)
4. **Push** edin (`git push origin feature/harika-ozellik`)
5. **Pull Request** açın

### Katkı Kuralları

- ✅ Kod standartlarına uyun
- ✅ Yorum satırları ekleyin
- ✅ Test edin
- ✅ Dokümantasyon güncelleyin

---

## 📄 Lisans

Bu proje MIT lisansı altında lisanslanmıştır. Detaylar için [LICENSE](LICENSE) dosyasına bakın.

---

## 🙏 Teşekkürler

Bu proje aşağıdaki açık kaynak projeleri kullanmaktadır:

- [mlevent/fatura](https://github.com/mlevent/fatura) - GİB e-Arşiv API entegrasyonu
- [PHPMailer](https://github.com/PHPMailer/PHPMailer) - Mail gönderimi
- [PhpSpreadsheet](https://github.com/PHPOffice/PhpSpreadsheet) - Excel işlemleri
- [Puppeteer](https://pptr.dev/) - PDF oluşturma
- [Font Awesome](https://fontawesome.com/) - İkonlar

---

## 📞 İletişim

- **GitHub Issues:** [Sorun bildirin](https://github.com/denizZz009/deok-fatura-entegrasyon/issues)
- **Discussions:** [Tartışmalara katılın](https://github.com/denizZz009/deok-fatura-entegrasyon/discussions)

---

## 📊 İstatistikler

<div align="center">

![GitHub stars](https://img.shields.io/github/stars/denizZz009/deok-fatura-entegrasyon?style=social)
![GitHub forks](https://img.shields.io/github/forks/denizZz009/deok-fatura-entegrasyon?style=social)
![GitHub watchers](https://img.shields.io/github/watchers/denizZz009/deok-fatura-entegrasyon?style=social)

</div>

---

<div align="center">

**⭐ Projeyi beğendiyseniz yıldız vermeyi unutmayın!**

Made by deokyazilim.com

</div>
