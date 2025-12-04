# 📖 E-Arşiv Fatura Yönetim Sistemi - Kullanım Kılavuzu

<div align="center">

**Detaylı kullanım talimatları ve özellik açıklamaları**

[Ana Sayfa](README.md) • [Kurulum Kılavuzu](KURULUM.md)

</div>

---

## 📋 İçindekiler

- [Başlangıç](#-başlangıç)
- [Giriş ve Çıkış](#-giriş-ve-çıkış)
- [Fatura İşlemleri](#-fatura-işlemleri)
- [Toplu İşlemler](#-toplu-işlemler)
- [Mail Sistemi](#-mail-sistemi)
- [Raporlama](#-raporlama)
- [İpuçları ve Püf Noktaları](#-ipuçları-ve-püf-noktaları)
- [Sık Sorulan Sorular](#-sık-sorulan-sorular)

---

## 🚀 Başlangıç

### İlk Kullanım

1. **Uygulamayı Başlatın**
   ```batch
   # Portable sürüm için
   BASLA.bat
   
   # Manuel kurulum için
   php -S localhost:8000
   ```

2. **Tarayıcıda Açın**
   - Portable: Otomatik açılır
   - Manuel: `http://localhost:8000`

3. **Giriş Yapın**
   - GİB kullanıcı kodunuzu girin
   - GİB şifrenizi girin
   - "Giriş Yap" butonuna tıklayın

---

## 🔐 Giriş ve Çıkış

### Giriş Yapma

<table>
<tr>
<td width="50%">

**Adımlar:**

1. Kullanıcı kodu alanına GİB kullanıcı kodunuzu girin
2. Şifre alanına GİB şifrenizi girin
3. "Giriş Yap" butonuna tıklayın
4. Başarılı giriş sonrası ana panel açılır

</td>
<td width="50%">

**Önemli Notlar:**

⚠️ GİB'e kayıtlı bilgilerinizi kullanın

⚠️ Şifreniz sadece session'da tutulur

⚠️ 10 hatalı denemeden sonra 10 dakika beklemeniz gerekir

⚠️ Oturum 20 dakika inaktivite sonrası sona erer

</td>
</tr>
</table>

### Çıkış Yapma

1. Sağ üst köşedeki "Çıkış Yap" butonuna tıklayın
2. Oturumunuz güvenli şekilde sonlandırılır
3. Tüm session verileri temizlenir

---

## 📄 Fatura İşlemleri

### Faturaları Listeleme

#### Temel Listeleme

1. **Tarih Aralığı Seçin**
   - Başlangıç tarihi
   - Bitiş tarihi

2. **Durum Filtresi** (İsteğe bağlı)
   - Tüm Durumlar
   - Onaylanmış
   - İptal Edilmiş
   - Taslak

3. **Arama** (İsteğe bağlı)
   - Fatura numarası
   - Alıcı adı

4. **"Filtrele"** butonuna tıklayın

#### Fatura Tablosu

Tabloda görünen bilgiler:

| Sütun | Açıklama |
|-------|----------|
| **Seç** | Toplu işlemler için seçim kutusu |
| **Fatura No** | Benzersiz fatura numarası |
| **Tarih** | Fatura tarihi |
| **Alıcı** | Alıcı adı/unvanı |
| **Tutar** | Toplam tutar (KDV dahil) |
| **Durum** | Onaylanmış / Taslak / İptal |
| **İşlemler** | Eylem butonları |

### Yeni Fatura Oluşturma

#### Adım 1: Formu Açın

"Yeni Fatura Oluştur" butonuna tıklayın

#### Adım 2: Temel Bilgileri Doldurun

**Fatura Temel Bilgileri:**

```
📅 Fatura Tarihi: Otomatik (bugün) veya manuel seçin
⏰ Fatura Saati: Otomatik (şimdi) veya manuel seçin
💱 Para Birimi: TRY, USD, EUR, GBP, vb.
📊 Döviz Kuru: Otomatik çekilir (değiştirilebilir)
📋 Fatura Tipi: Satış / İade
```

**Otomatik Döviz Kuru:**
- TRY dışındaki para birimleri için TCMB'den otomatik çekilir
- Manuel olarak değiştirebilirsiniz

#### Adım 3: Alıcı Bilgileri

**Otomatik Mükellef Bilgisi Çekme:**

1. VKN/TCKN girin (10 veya 11 haneli)
2. "Mükellef Bilgilerini Getir" butonuna tıklayın
3. Bilgiler otomatik doldurulur

**Manuel Girişler:**

```
🆔 VKN/TCKN: Zorunlu (10-11 hane)
🏢 Unvan: Şirket unvanı (opsiyonel)
👤 Adı: Zorunlu
👤 Soyadı: Zorunlu
🏛️ Vergi Dairesi: Opsiyonel
📍 Adres: Detaylı adres bilgileri
🌍 Ülke: Varsayılan "Türkiye"
```

**İletişim Bilgileri (Opsiyonel):**

```
📞 Telefon
📠 Faks
📧 E-posta: Mail göndermek için gerekli
```

#### Adım 4: Fatura Kalemleri

**Kalem Ekleme:**

1. "Kalem Ekle" butonuna tıklayın
2. Her kalem için:

```
📦 Mal/Hizmet: Ürün veya hizmet adı
🔢 Miktar: Adet, kg, vb.
📏 Birim: Adet, Kg, Litre, vb.
💰 Birim Fiyat: KDV hariç fiyat
💸 KDV Oranı: %0, %1, %8, %10, %20
💵 Toplam: Otomatik hesaplanır
```

**Kalem Silme:**

Her kalemin yanındaki "🗑️" butonuna tıklayın

**Toplamlar:**

Otomatik hesaplanır:
- Ara Toplam (KDV hariç)
- Toplam KDV
- Genel Toplam (KDV dahil)

#### Adım 5: Ek Bilgiler (Opsiyonel)

**Sipariş & İrsaliye:**

```
📋 Sipariş Numarası
📅 Sipariş Tarihi
📋 İrsaliye Numarası
📅 İrsaliye Tarihi
```

**ÖKC Fiş Bilgileri:**

```
🧾 Fiş No
📅 Fiş Tarihi
⏰ Fiş Saati
📋 Fiş Tipi
📊 Z Rapor No
🔢 OKC Seri No
```

**Notlar:**

Fatura için özel notlar ekleyebilirsiniz

#### Adım 6: Kaydet

"Taslak Oluştur" butonuna tıklayın

**Sonuç:**
- ✅ Fatura taslak olarak oluşturulur
- ✅ Fatura listesinde görünür
- ✅ SMS ile onaylanabilir

### Fatura Düzenleme

**Sadece taslak faturalar düzenlenebilir!**

1. Fatura listesinde "✏️ Düzenle" butonuna tıklayın
2. Formu düzenleyin
3. "Güncelle" butonuna tıklayın

### Fatura Onaylama (SMS)

#### Tek Fatura Onaylama

1. Fatura listesinde "✔️ Onayla" butonuna tıklayın
2. SMS onay penceresi açılır
3. GİB'den gelen 6 haneli SMS şifresini girin
4. "İmzala ve Onayla" butonuna tıklayın

**Önemli:**
- ⚠️ Bu işlem geri alınamaz!
- ⚠️ Mali olarak bağlayıcıdır
- ⚠️ SMS şifresi 3 dakika geçerlidir

#### Toplu SMS Onaylama

1. Onaylamak istediğiniz faturaları seçin (checkbox)
2. "Seçilenleri Toplu Onayla (SMS)" butonuna tıklayın
3. Seçilen faturalar listelenir
4. "SMS Şifresi Gönder" butonuna tıklayın
5. GİB'den gelen 6 haneli SMS şifresini girin
6. "Seçilen Faturaları Toplu Onayla" butonuna tıklayın

**Avantajlar:**
- ⚡ Tek SMS ile birden fazla fatura
- ⚡ Zaman tasarrufu
- ⚡ İlerleme çubuğu ile takip

### Fatura İptali

**Sadece onaylanmış faturalar iptal edilebilir!**

1. Fatura listesinde "❌ İptal Et" butonuna tıklayın
2. Onay mesajını kabul edin
3. Fatura iptal edilir

**Önemli:**
- ⚠️ İptal edilen faturalar GİB'e bildirilir
- ⚠️ İptal işlemi geri alınamaz

### Fatura İndirme

#### Tek Fatura İndirme

1. Fatura listesinde "📥 İndir" butonuna tıklayın
2. PDF dosyası indirilir

#### Toplu İndirme

1. İndirmek istediğiniz faturaları seçin
2. "Seçilenleri İndir (ZIP)" butonuna tıklayın
3. Tüm faturalar ZIP dosyası olarak indirilir

---

## 📊 Toplu İşlemler

### Toplu Fatura Yükleme (Excel)

#### Adım 1: Excel Şablonunu İndirin

1. "Toplu Fatura Yükle" butonuna tıklayın
2. "Excel Şablonu İndir" butonuna tıklayın
3. Şablon dosyası indirilir

#### Adım 2: Excel Şablonunu Doldurun

**Şablon Sütunları:**

| Sütun | Açıklama | Zorunlu | Örnek |
|-------|----------|---------|-------|
| **faturaTarihi** | Fatura tarihi | ✅ | 15/01/2025 |
| **aliciVknTckn** | VKN veya TCKN | ✅ | 1234567890 |
| **aliciUnvan** | Şirket unvanı | ❌ | ABC Ltd. Şti. |
| **aliciAdi** | Alıcı adı | ✅ | Ahmet |
| **aliciSoyadi** | Alıcı soyadı | ✅ | Yılmaz |
| **aliciVergiDairesi** | Vergi dairesi | ❌ | Kadıköy |
| **aliciAdres** | Adres | ❌ | Bağdat Cad. No:123 |
| **aliciMahalle** | Mahalle/İlçe | ❌ | Kadıköy |
| **aliciSehir** | Şehir | ❌ | İstanbul |
| **aliciUlke** | Ülke | ❌ | Türkiye |
| **aliciPostaKodu** | Posta kodu | ❌ | 34710 |
| **aliciTel** | Telefon | ❌ | 0216 123 45 67 |
| **aliciEposta** | E-posta | ❌ | ahmet@example.com |
| **malHizmet** | Ürün/Hizmet adı | ✅ | Web Tasarım Hizmeti |
| **miktar** | Miktar | ✅ | 1 |
| **birim** | Birim | ✅ | Adet |
| **birimFiyat** | Birim fiyat (KDV hariç) | ✅ | 1000.00 |
| **kdvOrani** | KDV oranı | ✅ | 20 |
| **paraBirimi** | Para birimi | ❌ | TRY |
| **not** | Fatura notu | ❌ | Ödeme 7 gün içinde |

**Önemli Notlar:**

- 📅 **Tarih Formatı:** GG/AA/YYYY (örn: 15/01/2025)
- 🔢 **Sayı Formatı:** Nokta kullanın (örn: 1000.50)
- 📋 **Birden Fazla Kalem:** Her satır bir fatura kalemi
- 🆔 **Aynı VKN:** Aynı VKN'ye birden fazla kalem eklenebilir

**Örnek Satır:**

```
15/01/2025 | 1234567890 | ABC Ltd. | Ahmet | Yılmaz | Kadıköy | Bağdat Cad. | Kadıköy | İstanbul | Türkiye | 34710 | 0216 123 45 67 | ahmet@example.com | Web Tasarım | 1 | Adet | 1000.00 | 20 | TRY | Ödeme 7 gün içinde
```

#### Adım 3: Excel Dosyasını Yükleyin

1. "Excel Dosyası Seç" butonuna tıklayın
2. Doldurduğunuz Excel dosyasını seçin
3. Dosya önizlemesi görünür

#### Adım 4: Önizleme ve Kontrol

- ✅ Kaç fatura oluşturulacağı gösterilir
- ✅ Her faturanın detayları listelenir
- ✅ Hatalı satırlar kırmızı ile işaretlenir

#### Adım 5: Faturaları Oluşturun

1. "Faturaları Oluştur" butonuna tıklayın
2. İlerleme çubuğu ile takip edin
3. Tamamlandığında sonuç raporu görünür

**Sonuç Raporu:**

```
✅ Başarılı: 45 fatura
❌ Hatalı: 2 fatura
⏱️ Süre: 2 dakika 15 saniye
```

**Özellikler:**

- 🚀 **Otomatik Mükellef Bilgisi:** VKN/TCKN'den otomatik çekilir
- 🚀 **Otomatik Döviz Kuru:** TCMB'den otomatik çekilir
- 🚀 **Hata Toleransı:** Hatalı satırlar atlanır
- 🚀 **İlerleme Takibi:** Gerçek zamanlı ilerleme

---

## 📧 Mail Sistemi

### SMTP Ayarları

#### Adım 1: SMTP Ayarları Penceresini Açın

"SMTP Ayarları" butonuna tıklayın

#### Adım 2: Sunucu Bilgilerini Girin

**Outlook/Office365 için:**

```
SMTP Sunucu: smtp.office365.com
Port: 587
Şifreleme: TLS
E-posta: email@domain.com
Şifre: mail şifreniz
```

**Gmail için:**

```
SMTP Sunucu: smtp.gmail.com
Port: 587
Şifreleme: TLS
E-posta: email@gmail.com
Şifre: Uygulama Şifresi (16 haneli)
```

**Yandex için:**

```
SMTP Sunucu: smtp.yandex.com
Port: 465
Şifreleme: SSL
E-posta: email@yandex.com
Şifre: mail şifreniz
```

#### Adım 3: Gönderen Bilgileri

```
Gönderen E-posta: email@domain.com
Gönderen Adı: Firma Adınız
```

#### Adım 4: Mail Şablonu Seçin

**3 Farklı Şablon:**

1. **Profesyonel (Varsayılan)**
   - Kurumsal görünüm
   - Logo desteği
   - Detaylı bilgiler

2. **Modern (Minimalist)**
   - Sade tasarım
   - Mobil uyumlu
   - Hızlı yükleme

3. **Klasik (Resmi)**
   - Geleneksel format
   - Resmi dil
   - Standart düzen

#### Adım 5: Logo ve Özel Mesaj (Opsiyonel)

```
Firma Logo URL: https://example.com/logo.png
Özel Mesaj: Ödeme 7 gün içinde yapılmalıdır.
```

#### Adım 6: Test ve Kaydet

1. "Bağlantıyı Test Et" butonuna tıklayın
2. Test başarılıysa "Ayarları Kaydet" butonuna tıklayın

**Test Sonuçları:**

```
✅ Bağlantı başarılı
✅ Kimlik doğrulama başarılı
✅ Test maili gönderildi
```

### Mail Gönderme

#### Tek Fatura Maili

1. Fatura listesinde "📧 Mail Gönder" butonuna tıklayın
2. Mail otomatik gönderilir
3. Sonuç bildirimi görünür

**Mail İçeriği:**

- 📎 PDF fatura eki
- 📋 Fatura detayları
- 🏢 Firma logosu (varsa)
- 📝 Özel mesaj (varsa)

#### Toplu Mail Gönderme

1. Mail göndermek istediğiniz faturaları seçin
2. "Seçilenlere Mail Gönder" butonuna tıklayın
3. İlerleme penceresi açılır
4. Her fatura için:
   - PDF oluşturulur
   - Mail gönderilir
   - Sonuç kaydedilir

**İlerleme Takibi:**

```
📊 İlerleme: 15/50 (%30)
⏱️ Geçen Süre: 1 dakika 30 saniye
⏱️ Tahmini Kalan: 3 dakika 30 saniye
```

**Sonuç Raporu:**

```
✅ Başarılı: 48 mail
❌ Hatalı: 2 mail
⚠️ Uyarı: 3 mail (e-posta adresi yok)
```

### Mail Şablonu Önizleme

1. SMTP Ayarları penceresinde
2. "Şablonu Önizle" butonuna tıklayın
3. Örnek mail görünür

---

## 📈 Raporlama

### Excel Raporu Oluşturma

#### Adım 1: Rapor Penceresini Açın

"Rapor Oluştur" butonuna tıklayın

#### Adım 2: Tarih Aralığı Seçin

```
Başlangıç Tarihi: 01/01/2025
Bitiş Tarihi: 31/01/2025
```

#### Adım 3: Önizleme (Opsiyonel)

"Önizleme" butonuna tıklayın

**Önizleme Bilgileri:**

```
📊 Toplam Fatura: 125
💰 Toplam Tutar: 125,450.00 TL
📈 Ortalama Fatura: 1,003.60 TL
📅 En Yüksek Gün: 15/01/2025 (15 fatura)
```

#### Adım 4: Rapor Oluştur

"Excel Raporu Oluştur" butonuna tıklayın

**Excel İçeriği:**

| Sütun | Açıklama |
|-------|----------|
| Fatura No | Benzersiz numara |
| Tarih | Fatura tarihi |
| Alıcı VKN/TCKN | Kimlik numarası |
| Alıcı Adı | Tam ad/unvan |
| Mal/Hizmet Toplamı | KDV hariç |
| KDV Tutarı | Toplam KDV |
| Genel Toplam | KDV dahil |
| Para Birimi | TRY, USD, vb. |
| Durum | Onaylanmış/İptal |

**Ek Özellikler:**

- 📊 Özet sayfa (ilk sayfa)
- 📈 Grafik ve istatistikler
- 💰 Toplam hesaplamalar
- 🎨 Renkli formatlar

---

## 💡 İpuçları ve Püf Noktaları

### Hızlı Kullanım

#### Klavye Kısayolları

```
Ctrl + S: Fatura kaydet
Ctrl + N: Yeni fatura
Ctrl + F: Arama
Esc: Modal kapat
```

#### Form Ayarları

**Ayarları Dışa Aktar:**

1. Fatura formunda "Ayarları Dışa Aktar" butonuna tıklayın
2. JSON dosyası indirilir
3. Gönderen bilgileri, varsayılan değerler kaydedilir

**Ayarları İçe Aktar:**

1. "Ayarları İçe Aktar" butonuna tıklayın
2. Daha önce dışa aktardığınız JSON dosyasını seçin
3. Form otomatik doldurulur

**Avantajlar:**

- ⚡ Tekrar eden bilgileri kaydedin
- ⚡ Farklı müşteriler için profiller
- ⚡ Zaman tasarrufu

### Performans İpuçları

#### PDF Oluşturma

- 📄 Tek seferde en fazla 50 PDF oluşturun
- 📄 Büyük toplu işlemleri parçalara bölün
- 📄 PDF oluşturma ~2-3 saniye sürer

#### Mail Gönderme

- 📧 Tek seferde en fazla 100 mail gönderin
- 📧 SMTP sunucunuzun limitlerini kontrol edin
- 📧 Her mail ~5-7 saniye sürer

#### Excel Yükleme

- 📊 Tek seferde en fazla 500 satır yükleyin
- 📊 Büyük dosyaları parçalara bölün
- 📊 Hatalı satırları önceden kontrol edin

### Güvenlik İpuçları

#### Şifre Güvenliği

- 🔒 Güçlü şifreler kullanın
- 🔒 Şifreleri paylaşmayın
- 🔒 Düzenli olarak değiştirin

#### Oturum Güvenliği

- 🛡️ İşiniz bitince çıkış yapın
- 🛡️ Bilgisayarınızı kilitli tutun
- 🛡️ Genel ağlarda dikkatli olun

#### Veri Yedekleme

- 💾 Düzenli yedek alın
- 💾 SMTP ayarlarını dışa aktarın
- 💾 Önemli faturaları indirin

---

## ❓ Sık Sorulan Sorular

### Genel Sorular

**S: Faturalar nerede saklanıyor?**

C: Tüm faturalar GİB sunucularında saklanır. Uygulama sadece arayüz görevi görür.

**S: İnternet olmadan çalışır mı?**

C: Hayır, GİB bağlantısı için internet gereklidir.

**S: Birden fazla kullanıcı kullanabilir mi?**

C: Evet, her kullanıcı kendi GİB bilgileriyle giriş yapabilir.

### Fatura Soruları

**S: Taslak faturayı düzenleyebilir miyim?**

C: Evet, sadece taslak faturalar düzenlenebilir. Onaylanmış faturalar düzenlenemez.

**S: Onaylanmış faturayı iptal edebilir miyim?**

C: Evet, ancak bu işlem geri alınamaz ve GİB'e bildirilir.

**S: Fatura numarası nasıl belirleniyor?**

C: Fatura numarası GİB tarafından otomatik olarak atanır.

**S: Döviz kuru nereden çekiliyor?**

C: TCMB (Türkiye Cumhuriyet Merkez Bankası) günlük kurlarından otomatik çekilir.

### Mail Soruları

**S: Gmail ile mail gönderemiyorum?**

C: Gmail için "Uygulama Şifresi" kullanmanız gerekir. Normal şifre çalışmaz.

**S: Mail gönderimi ne kadar sürer?**

C: Her fatura için ortalama 5-7 saniye sürer (PDF oluşturma dahil).

**S: Toplu mail gönderirken hata olursa?**

C: Hatalı mailler atlanır ve sonuç raporunda gösterilir. Diğer mailler gönderilmeye devam eder.

### Teknik Sorular

**S: Hangi tarayıcıları destekliyor?**

C: Chrome, Firefox, Edge ve Safari'nin güncel sürümleri desteklenir.

**S: Mobil cihazlarda çalışır mı?**

C: Arayüz responsive'dir ancak masaüstü kullanımı önerilir.

**S: Veritabanı kullanıyor mu?**

C: Hayır, tüm veriler GİB API'si üzerinden çekilir.

---

## 🎓 Video Eğitimler

### Başlangıç Seviyesi

1. **İlk Kurulum ve Giriş** (5 dk)
2. **İlk Faturanızı Oluşturun** (10 dk)
3. **SMTP Ayarları ve Mail Gönderme** (8 dk)

### Orta Seviye

4. **Toplu Fatura Yükleme** (12 dk)
5. **Toplu SMS Onaylama** (7 dk)
6. **Excel Raporları** (6 dk)

### İleri Seviye

7. **Form Ayarları ve Optimizasyon** (10 dk)
8. **Sorun Giderme** (15 dk)
9. **Güvenlik ve Yedekleme** (8 dk)

---

## 📞 Destek

### Yardım Kaynakları

1. **Yardım Sayfası:** Uygulamada "Yardım" butonuna tıklayın
2. **Dokümantasyon:** [README.md](README.md) ve [KURULUM.md](KURULUM.md)
3. **GitHub Issues:** [Sorun bildirin](https://github.com/denizZz009/deok-fatura-entegrasyon/issues)
4. **Discussions:** [Tartışmalara katılın](https://github.com/denizZz009/deok-fatura-entegrasyon/discussions)

### Sorun Bildirme

Sorun bildirirken lütfen şunları ekleyin:

- 🖥️ İşletim sistemi ve sürümü
- 🌐 Tarayıcı ve sürümü
- 📝 Hata mesajı (varsa)
- 📸 Ekran görüntüsü (varsa)
- 🔄 Hatayı tekrarlama adımları

---

<div align="center">

**Başarılar! 🎉**

[Ana Sayfa](README.md) • [Kurulum Kılavuzu](KURULUM.md)

**⭐ Projeyi beğendiyseniz yıldız vermeyi unutmayın!**

</div>
