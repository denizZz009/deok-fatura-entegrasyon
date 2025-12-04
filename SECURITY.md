# 🔒 Güvenlik Politikası

## 🛡️ Desteklenen Sürümler

Aşağıdaki sürümler güvenlik güncellemeleri almaktadır:

| Sürüm | Destekleniyor |
| ------- | ------------------ |
| 1.0.x   | ✅ Evet |
| < 1.0   | ❌ Hayır |

---

## 🚨 Güvenlik Açığı Bildirme

### Lütfen Güvenlik Açıklarını Herkese Açık Olarak Bildirmeyin!

Güvenlik açığı bulduysanız, lütfen **herkese açık issue açmayın**. Bunun yerine:

### Bildirme Yöntemi

1. **GitHub Security Advisory** kullanın:
   - [Security Advisory Oluştur](https://github.com/denizZz009/deok-fatura-entegrasyon/security/advisories/new)

2. **Veya e-posta gönderin:**
   - E-posta: security@example.com
   - Konu: [SECURITY] E-Arşiv Fatura - Güvenlik Açığı

### Bildirimde Bulunması Gerekenler

Lütfen aşağıdaki bilgileri ekleyin:

- 📝 **Açıklama:** Güvenlik açığının detaylı açıklaması
- 🔄 **Tekrarlama Adımları:** Açığı nasıl tekrarlayabiliriz?
- 💥 **Etki:** Potansiyel etki nedir?
- 🎯 **Etkilenen Sürümler:** Hangi sürümler etkileniyor?
- 🛠️ **Önerilen Çözüm:** Varsa çözüm öneriniz
- 📸 **Ekran Görüntüleri:** Varsa ekleyin

### Örnek Rapor

```markdown
**Güvenlik Açığı Türü:** SQL Injection

**Açıklama:**
backend/index.php dosyasında kullanıcı girdisi sanitize edilmeden 
SQL sorgusunda kullanılıyor.

**Tekrarlama Adımları:**
1. Login sayfasına git
2. Kullanıcı adı alanına: admin' OR '1'='1
3. Şifre alanına: herhangi bir şey
4. Giriş yap

**Etki:**
Yetkisiz erişim, veri sızıntısı

**Etkilenen Sürümler:**
1.0.0 - 1.0.5

**Önerilen Çözüm:**
Prepared statements kullanın
```

---

## ⏱️ Yanıt Süresi

Güvenlik raporlarına yanıt süremiz:

| Öncelik | İlk Yanıt | Düzeltme |
|---------|-----------|----------|
| **Kritik** | 24 saat | 7 gün |
| **Yüksek** | 48 saat | 14 gün |
| **Orta** | 5 gün | 30 gün |
| **Düşük** | 7 gün | 60 gün |

---

## 🔐 Güvenlik En İyi Uygulamaları

### Kullanıcılar İçin

#### 1. Güçlü Şifreler

```
✅ İyi Şifre: K@r1ş1k_Şifre_2025!
❌ Kötü Şifre: 123456
```

**Öneriler:**
- En az 12 karakter
- Büyük/küçük harf karışımı
- Sayı ve özel karakter
- Sözlükte olmayan kelimeler

#### 2. Oturum Güvenliği

```
✅ İşiniz bitince çıkış yapın
✅ Bilgisayarınızı kilitli tutun
✅ Genel ağlarda dikkatli olun
❌ Şifrenizi paylaşmayın
❌ Şifrenizi tarayıcıya kaydetmeyin
```

#### 3. Güncellemeler

```
✅ Düzenli olarak güncelleyin
✅ Güvenlik yamalarını hemen uygulayın
✅ Bağımlılıkları güncel tutun
```

#### 4. Veri Yedekleme

```
✅ Düzenli yedek alın
✅ Yedekleri güvenli yerde saklayın
✅ Yedekleri test edin
```

### Geliştiriciler İçin

#### 1. Input Validation

```php
// ❌ Kötü
$vkn = $_GET['vkn'];
$query = "SELECT * FROM users WHERE vkn = '$vkn'";

// ✅ İyi
$vkn = filter_var($_GET['vkn'], FILTER_SANITIZE_STRING);
if (!preg_match('/^\d{10,11}$/', $vkn)) {
    throw new Exception('Geçersiz VKN/TCKN');
}
$stmt = $pdo->prepare("SELECT * FROM users WHERE vkn = ?");
$stmt->execute([$vkn]);
```

#### 2. Output Encoding

```php
// ❌ Kötü
echo $user_input;

// ✅ İyi
echo htmlspecialchars($user_input, ENT_QUOTES, 'UTF-8');
```

#### 3. Session Güvenliği

```php
// ✅ İyi
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');

session_start();

// IP ve User-Agent kontrolü
if (isset($_SESSION['ip']) && $_SESSION['ip'] !== $_SERVER['REMOTE_ADDR']) {
    session_destroy();
    die('Güvenlik ihlali!');
}
```

#### 4. CSRF Koruması

```php
// Token oluştur
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Token kontrol et
if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    die('CSRF token geçersiz!');
}
```

#### 5. Rate Limiting

```php
// Brute force koruması
function is_brute_forced() {
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = [];
    }
    
    $now = time();
    $_SESSION['login_attempts'] = array_filter(
        $_SESSION['login_attempts'],
        fn($t) => $now - $t < 600 // 10 dakika
    );
    
    return count($_SESSION['login_attempts']) >= 5;
}
```

#### 6. Güvenli Dosya Yükleme

```php
// ❌ Kötü
move_uploaded_file($_FILES['file']['tmp_name'], 'uploads/' . $_FILES['file']['name']);

// ✅ İyi
$allowed = ['jpg', 'jpeg', 'png', 'pdf'];
$ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));

if (!in_array($ext, $allowed)) {
    die('Geçersiz dosya türü!');
}

$filename = bin2hex(random_bytes(16)) . '.' . $ext;
move_uploaded_file($_FILES['file']['tmp_name'], 'uploads/' . $filename);
```

#### 7. SQL Injection Koruması

```php
// ✅ Prepared Statements kullanın
$stmt = $pdo->prepare("SELECT * FROM invoices WHERE uuid = ?");
$stmt->execute([$uuid]);

// ✅ PDO kullanın
$pdo = new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => false
]);
```

#### 8. XSS Koruması

```javascript
// ❌ Kötü
element.innerHTML = userInput;

// ✅ İyi
element.textContent = userInput;

// Veya
const escapeHtml = (text) => {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
};
```

---

## 🔍 Güvenlik Kontrol Listesi

### Deployment Öncesi

- [ ] Tüm bağımlılıklar güncel mi?
- [ ] Güvenlik açıkları tarandı mı?
- [ ] HTTPS kullanılıyor mu?
- [ ] Hata mesajları gizlendi mi?
- [ ] Debug modu kapalı mı?
- [ ] Güvenli session ayarları yapıldı mı?
- [ ] CSRF koruması var mı?
- [ ] Rate limiting var mı?
- [ ] Input validation yapılıyor mu?
- [ ] Output encoding yapılıyor mu?

### Düzenli Kontroller

- [ ] Bağımlılıklar güncellendi mi? (Haftalık)
- [ ] Log dosyaları kontrol edildi mi? (Günlük)
- [ ] Güvenlik yamaları uygulandı mı? (Hemen)
- [ ] Yedekler alındı mı? (Günlük)
- [ ] Güvenlik taraması yapıldı mı? (Aylık)

---

## 🛠️ Güvenlik Araçları

### PHP Güvenlik Taraması

```bash
# Composer bağımlılık kontrolü
composer audit

# PHP Security Checker
composer require --dev enlightn/security-checker
./vendor/bin/security-checker security:check composer.lock
```

### JavaScript Güvenlik Taraması

```bash
# NPM audit
npm audit

# Yarn audit
yarn audit

# Snyk
npm install -g snyk
snyk test
```

### Statik Kod Analizi

```bash
# PHPStan
composer require --dev phpstan/phpstan
./vendor/bin/phpstan analyse backend/

# Psalm
composer require --dev vimeo/psalm
./vendor/bin/psalm
```

---

## 📚 Güvenlik Kaynakları

### Önerilen Okumalar

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [PHP Security Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/PHP_Configuration_Cheat_Sheet.html)
- [Web Security Academy](https://portswigger.net/web-security)

### Güvenlik Standartları

- [CWE - Common Weakness Enumeration](https://cwe.mitre.org/)
- [CVE - Common Vulnerabilities and Exposures](https://cve.mitre.org/)
- [CVSS - Common Vulnerability Scoring System](https://www.first.org/cvss/)

---

## 🏆 Hall of Fame

Güvenlik açıklarını sorumlu bir şekilde bildiren kişilere teşekkür ederiz:

<!-- Güvenlik araştırmacıları buraya eklenecek -->

---

## 📞 İletişim

Güvenlik ile ilgili sorularınız için:

- **Security Advisory:** [GitHub Security](https://github.com/denizZz009/deok-fatura-entegrasyon/security)
- **E-posta:** security@example.com

---

<div align="center">

**Güvenliğiniz bizim önceliğimizdir! 🔒**

[Ana Sayfa](README.md)

</div>
