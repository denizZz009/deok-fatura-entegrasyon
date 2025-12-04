# 🤝 Katkıda Bulunma Rehberi

E-Arşiv Fatura Yönetim Sistemi'ne katkıda bulunmak istediğiniz için teşekkür ederiz! Bu rehber, projeye nasıl katkıda bulunabileceğinizi açıklar.

## 📋 İçindekiler

- [Davranış Kuralları](#-davranış-kuralları)
- [Nasıl Katkıda Bulunabilirim?](#-nasıl-katkıda-bulunabilirim)
- [Geliştirme Süreci](#-geliştirme-süreci)
- [Kod Standartları](#-kod-standartları)
- [Commit Mesajları](#-commit-mesajları)
- [Pull Request Süreci](#-pull-request-süreci)

---

## 📜 Davranış Kuralları

### Taahhütlerimiz

Açık ve misafirperver bir ortam yaratmak için, katkıda bulunanlar ve sürdürücüler olarak, projemize ve topluluğumuza katılımı herkes için taciz içermeyen bir deneyim haline getirmeyi taahhüt ediyoruz.

### Standartlarımız

**Olumlu davranış örnekleri:**

- ✅ Nazik ve saygılı dil kullanmak
- ✅ Farklı bakış açılarına ve deneyimlere saygı göstermek
- ✅ Yapıcı eleştiriyi nezaketle kabul etmek
- ✅ Topluluk için en iyisine odaklanmak
- ✅ Diğer topluluk üyelerine empati göstermek

**Kabul edilemez davranış örnekleri:**

- ❌ Cinselleştirilmiş dil veya imge kullanımı
- ❌ Trolleme, hakaret veya aşağılayıcı yorumlar
- ❌ Kişisel veya politik saldırılar
- ❌ Açık veya örtülü taciz
- ❌ Başkalarının özel bilgilerini izinsiz yayınlamak

---

## 🎯 Nasıl Katkıda Bulunabilirim?

### Hata Bildirme

Hata bulduğunuzda:

1. **Mevcut issue'ları kontrol edin** - Belki daha önce bildirilmiştir
2. **Yeni issue açın** - Detaylı bilgi verin
3. **Şablon kullanın** - Issue şablonunu doldurun

**Hata raporu şablonu:**

```markdown
**Hata Açıklaması**
Hatanın net ve kısa bir açıklaması.

**Tekrarlama Adımları**
1. '...' sayfasına git
2. '...' butonuna tıkla
3. Aşağı kaydır
4. Hatayı gör

**Beklenen Davranış**
Ne olmasını bekliyordunuz?

**Ekran Görüntüleri**
Varsa ekran görüntüleri ekleyin.

**Ortam:**
 - İşletim Sistemi: [örn. Windows 10]
 - Tarayıcı: [örn. Chrome 120]
 - Versiyon: [örn. 1.0.0]

**Ek Bilgi**
Başka bir bağlam veya bilgi.
```

### Özellik Önerme

Yeni özellik önerisi için:

1. **Mevcut önerileri kontrol edin**
2. **Yeni issue açın** - "Feature Request" etiketi ile
3. **Detaylı açıklama yapın**

**Özellik önerisi şablonu:**

```markdown
**Özellik İsteği**
Özelliğin net ve kısa bir açıklaması.

**Sorun**
Bu özellik hangi sorunu çözüyor?

**Çözüm Önerisi**
Nasıl çözülmesini istersiniz?

**Alternatifler**
Düşündüğünüz alternatif çözümler.

**Ek Bilgi**
Başka bir bağlam, ekran görüntüsü vb.
```

### Dokümantasyon İyileştirme

Dokümantasyon her zaman iyileştirilebilir:

- 📝 Yazım hatalarını düzeltin
- 📝 Eksik bilgileri ekleyin
- 📝 Örnekleri iyileştirin
- 📝 Çeviriler ekleyin

### Kod Katkısı

Kod katkısı yapmak için:

1. **Issue seçin** veya yeni bir tane açın
2. **Fork edin** ve geliştirin
3. **Test edin**
4. **Pull Request açın**

---

## 🔧 Geliştirme Süreci

### 1. Projeyi Fork Edin

```bash
# GitHub'da "Fork" butonuna tıklayın
# Sonra kendi fork'unuzu klonlayın
git clone https://github.com/KULLANICI_ADINIZ/deok-fatura-entegrasyon.git
cd deok-fatura-entegrasyon
```

### 2. Upstream Ekleyin

```bash
git remote add upstream https://github.com/ORIJINAL_SAHIP/deok-fatura-entegrasyon.git
git fetch upstream
```

### 3. Branch Oluşturun

```bash
# Feature için
git checkout -b feature/harika-ozellik

# Bugfix için
git checkout -b fix/hata-duzeltmesi

# Dokümantasyon için
git checkout -b docs/dokuman-guncelleme
```

### 4. Geliştirme Yapın

```bash
# Bağımlılıkları yükleyin
composer install
cd backend && composer install && cd ..
cd html2pdf && npm install && cd ..

# Geliştirme sunucusunu başlatın
php -S localhost:8000
```

### 5. Test Edin

```bash
# PHP syntax kontrolü
find . -name "*.php" -exec php -l {} \;

# JavaScript syntax kontrolü
npm run lint

# Manuel test yapın
# - Tüm özellikleri test edin
# - Farklı tarayıcılarda test edin
# - Hata durumlarını test edin
```

### 6. Commit Edin

```bash
git add .
git commit -m "feat: harika özellik eklendi"
```

### 7. Push Edin

```bash
git push origin feature/harika-ozellik
```

### 8. Pull Request Açın

GitHub'da fork'unuza gidin ve "Pull Request" butonuna tıklayın.

---

## 📝 Kod Standartları

### PHP Standartları

**PSR-12 Standartlarını Takip Edin:**

```php
<?php

namespace App\Services;

use Exception;

class InvoiceService
{
    private $gib;
    
    public function __construct(Gib $gib)
    {
        $this->gib = $gib;
    }
    
    public function createInvoice(array $data): array
    {
        try {
            // İşlem yap
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
```

**Önemli Kurallar:**

- ✅ 4 boşluk girinti (tab değil)
- ✅ Açılış süslü parantez aynı satırda
- ✅ Kapanış süslü parantez yeni satırda
- ✅ Türkçe yorum satırları
- ✅ Anlamlı değişken isimleri

### JavaScript Standartları

**ES6+ Standartlarını Kullanın:**

```javascript
// Fonksiyon tanımlama
const createInvoice = async (data) => {
    try {
        const response = await fetch('/api/create', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        return await response.json();
    } catch (error) {
        console.error('Hata:', error);
        return { success: false, message: error.message };
    }
};

// Class kullanımı
class InvoiceManager {
    constructor() {
        this.invoices = [];
    }
    
    addInvoice(invoice) {
        this.invoices.push(invoice);
    }
}
```

**Önemli Kurallar:**

- ✅ `const` ve `let` kullanın (`var` değil)
- ✅ Arrow function kullanın
- ✅ Template literals kullanın
- ✅ Async/await kullanın
- ✅ Türkçe yorum satırları

### CSS Standartları

**BEM Metodolojisi:**

```css
/* Block */
.invoice-form {
    padding: 20px;
}

/* Element */
.invoice-form__input {
    width: 100%;
    padding: 10px;
}

/* Modifier */
.invoice-form__input--error {
    border-color: red;
}
```

**Önemli Kurallar:**

- ✅ BEM isimlendirme
- ✅ CSS değişkenleri kullanın
- ✅ Mobile-first yaklaşım
- ✅ Flexbox/Grid kullanın

### Yorum Satırları

**İyi Yorum Örnekleri:**

```php
// Mükellef bilgilerini GİB'den çek
$data = $gib->getRecipientData($vkn);

// Döviz kurunu TCMB'den al
$rate = $this->getTCMBRate($currency);

/**
 * Fatura oluşturur ve GİB'e gönderir
 * 
 * @param array $data Fatura verileri
 * @return array Sonuç ['success' => bool, 'uuid' => string]
 * @throws Exception GİB bağlantı hatası
 */
public function createInvoice(array $data): array
{
    // ...
}
```

---

## 💬 Commit Mesajları

### Conventional Commits

**Format:**

```
<tip>(<kapsam>): <kısa açıklama>

<detaylı açıklama>

<footer>
```

**Tipler:**

- `feat`: Yeni özellik
- `fix`: Hata düzeltme
- `docs`: Dokümantasyon
- `style`: Kod formatı (işlevsellik değişmez)
- `refactor`: Kod yeniden yapılandırma
- `perf`: Performans iyileştirme
- `test`: Test ekleme/düzeltme
- `chore`: Bakım işleri

**Örnekler:**

```bash
# Yeni özellik
git commit -m "feat(mail): toplu mail gönderme eklendi"

# Hata düzeltme
git commit -m "fix(pdf): PDF oluşturma hatası düzeltildi"

# Dokümantasyon
git commit -m "docs(readme): kurulum adımları güncellendi"

# Detaylı commit
git commit -m "feat(invoice): Excel toplu yükleme eklendi

- Excel şablonu oluşturuldu
- Otomatik mükellef bilgisi çekme
- Hata toleransı eklendi
- İlerleme çubuğu eklendi

Closes #123"
```

---

## 🔄 Pull Request Süreci

### PR Açmadan Önce

**Kontrol Listesi:**

- [ ] Kod standartlarına uygun mu?
- [ ] Testler geçiyor mu?
- [ ] Dokümantasyon güncellendi mi?
- [ ] Commit mesajları düzgün mü?
- [ ] Conflict yok mu?

### PR Şablonu

```markdown
## Değişiklik Türü

- [ ] Hata düzeltme (breaking change olmayan)
- [ ] Yeni özellik (breaking change olmayan)
- [ ] Breaking change (mevcut özellikleri etkiler)
- [ ] Dokümantasyon güncellemesi

## Açıklama

Bu PR'ın amacı nedir?

## İlgili Issue

Fixes #(issue numarası)

## Test Edildi mi?

- [ ] Evet, manuel test yapıldı
- [ ] Evet, otomatik testler eklendi
- [ ] Hayır, test gerekmedi

## Ekran Görüntüleri

Varsa ekran görüntüleri ekleyin.

## Checklist

- [ ] Kod standartlarına uygun
- [ ] Dokümantasyon güncellendi
- [ ] Testler geçiyor
- [ ] Commit mesajları düzgün
```

### Review Süreci

1. **Otomatik Kontroller**
   - Syntax kontrolü
   - Linting
   - Test çalıştırma

2. **Manuel Review**
   - Kod kalitesi
   - Güvenlik
   - Performans
   - Dokümantasyon

3. **Değişiklik İstekleri**
   - Geri bildirim
   - Düzeltme önerileri
   - Tartışma

4. **Onay ve Merge**
   - En az 1 onay gerekli
   - Tüm kontroller geçmeli
   - Conflict çözülmeli

---

## 🏆 Katkıda Bulunanlar

Tüm katkıda bulunanlara teşekkür ederiz! 🎉

Katkıda bulunanlar listesi için [Contributors](https://github.com/denizZz009/deok-fatura-entegrasyon/graphs/contributors) sayfasına bakın.

---

## 📞 İletişim

Sorularınız için:

- **GitHub Issues:** [Sorun bildirin](https://github.com/denizZz009/deok-fatura-entegrasyon/issues)
- **GitHub Discussions:** [Tartışmalara katılın](https://github.com/denizZz009/deok-fatura-entegrasyon/discussions)

---

<div align="center">

**Katkılarınız için teşekkürler! ❤️**

[Ana Sayfa](README.md) • [Kurulum](KURULUM.md) • [Kullanım](KULLANIM.md)

</div>
