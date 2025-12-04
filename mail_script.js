// ==================== MAIL GÖNDERİM SİSTEMİ ====================

// SMTP Modal Elementleri
const smtpModal = document.getElementById('smtp-modal');
const smtpSettingsBtn = document.getElementById('smtp-settings-btn');
const closeSmtpModal = document.querySelector('.close-smtp-modal');
const smtpForm = document.getElementById('smtp-form');
const testSmtpBtn = document.getElementById('test-smtp-btn');
const smtpMessage = document.getElementById('smtp-message');

// Email Sending Modal Elementleri
const emailSendingModal = document.getElementById('email-sending-modal');
const sendEmailBtn = document.getElementById('send-email-btn');
const closeEmailModal = document.querySelector('.close-email-modal');

// SMTP Modal Açma/Kapama
smtpSettingsBtn.addEventListener('click', () => {
    smtpModal.style.display = 'flex';
    loadSmtpSettings();
});

closeSmtpModal.addEventListener('click', () => {
    smtpModal.style.display = 'none';
});

window.addEventListener('click', (event) => {
    if (event.target === smtpModal) {
        smtpModal.style.display = 'none';
    }
    if (event.target === emailSendingModal) {
        // Mail gönderimi sırasında modal kapatılmasın
    }
});

// SMTP Ayarlarını Yükle
async function loadSmtpSettings() {
    try {
        const response = await fetch(BACKEND_URL + '?path=get_smtp_settings');
        const data = await response.json();
        
        if (data.success && data.settings) {
            document.getElementById('smtp-host').value = data.settings.host || '';
            document.getElementById('smtp-port').value = data.settings.port || 587;
            document.getElementById('smtp-encryption').value = data.settings.encryption || 'tls';
            document.getElementById('smtp-username').value = data.settings.username || '';
            // Şifre güvenlik nedeniyle doldurulmaz
            document.getElementById('smtp-from-email').value = data.settings.from_email || '';
            document.getElementById('smtp-from-name').value = data.settings.from_name || 'E-Fatura Sistemi';
            document.getElementById('smtp-logo-url').value = data.settings.logo_url || '';
            document.getElementById('smtp-template-type').value = data.settings.template_type || 'default';
            document.getElementById('smtp-custom-message').value = data.settings.custom_message || '';
            
            showToast('SMTP ayarları yüklendi.', 'info');
        }
    } catch (error) {
        console.error('SMTP ayarları yüklenemedi:', error);
    }
}

// SMTP Bağlantısını Test Et
testSmtpBtn.addEventListener('click', async () => {
    const settings = {
        host: document.getElementById('smtp-host').value,
        port: parseInt(document.getElementById('smtp-port').value),
        encryption: document.getElementById('smtp-encryption').value,
        username: document.getElementById('smtp-username').value,
        password: document.getElementById('smtp-password').value,
        from_email: document.getElementById('smtp-from-email').value,
        from_name: document.getElementById('smtp-from-name').value
    };
    
    // Validasyon
    if (!settings.host || !settings.username || !settings.password || !settings.from_email) {
        showToast('Lütfen tüm zorunlu alanları doldurun!', 'warning');
        return;
    }
    
    testSmtpBtn.disabled = true;
    testSmtpBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Test Ediliyor...';
    
    try {
        const response = await fetch(BACKEND_URL + '?path=test_smtp', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(settings)
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast(data.message, 'success');
            smtpMessage.innerHTML = '<div style="color: var(--success-color); padding: 10px; background: #d4edda; border-radius: 5px; margin-top: 10px;">' +
                '<i class="fas fa-check-circle"></i> ' + data.message + '</div>';
        } else {
            showToast(data.message, 'error');
            smtpMessage.innerHTML = '<div style="color: var(--danger-color); padding: 10px; background: #f8d7da; border-radius: 5px; margin-top: 10px;">' +
                '<i class="fas fa-exclamation-circle"></i> ' + data.message + '</div>';
        }
    } catch (error) {
        showToast('Test sırasında hata oluştu!', 'error');
        smtpMessage.innerHTML = '<div style="color: var(--danger-color); padding: 10px; background: #f8d7da; border-radius: 5px; margin-top: 10px;">' +
            '<i class="fas fa-exclamation-circle"></i> Hata: ' + error.message + '</div>';
    } finally {
        testSmtpBtn.disabled = false;
        testSmtpBtn.innerHTML = '<i class="fas fa-vial"></i> Bağlantıyı Test Et';
    }
});

// SMTP Ayarlarını Kaydet
smtpForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const settings = {
        host: document.getElementById('smtp-host').value,
        port: parseInt(document.getElementById('smtp-port').value),
        encryption: document.getElementById('smtp-encryption').value,
        username: document.getElementById('smtp-username').value,
        password: document.getElementById('smtp-password').value,
        from_email: document.getElementById('smtp-from-email').value,
        from_name: document.getElementById('smtp-from-name').value,
        logo_url: document.getElementById('smtp-logo-url').value,
        template_type: document.getElementById('smtp-template-type').value,
        custom_message: document.getElementById('smtp-custom-message').value
    };
    
    try {
        const response = await fetch(BACKEND_URL + '?path=save_smtp_settings', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(settings)
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast(data.message, 'success');
            smtpMessage.innerHTML = '<div style="color: var(--success-color); padding: 10px; background: #d4edda; border-radius: 5px; margin-top: 10px;">' +
                '<i class="fas fa-check-circle"></i> ' + data.message + '</div>';
            
            // 2 saniye sonra modal'ı kapat
            setTimeout(() => {
                smtpModal.style.display = 'none';
                smtpMessage.innerHTML = '';
            }, 2000);
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        showToast('Ayarlar kaydedilemedi!', 'error');
    }
});

// Mail Gönderme Butonu
sendEmailBtn.addEventListener('click', async () => {
    const selectedInvoices = getSelectedInvoicesForEmail();
    
    if (selectedInvoices.length === 0) {
        showToast('Lütfen mail göndermek için fatura seçin!', 'warning');
        return;
    }
    
    // Uyarı: E-posta adresi backend'den fatura detayından çekilecek
    // Frontend'de kontrol yapmıyoruz çünkü liste görünümünde e-posta bilgisi olmayabilir
    showEmailSendingModal(selectedInvoices);
});

// Seçili Faturaları Al (E-posta için)
function getSelectedInvoicesForEmail() {
    const checkboxes = document.querySelectorAll('.invoice-checkbox:checked');
    const invoices = [];
    
    checkboxes.forEach(checkbox => {
        const row = checkbox.closest('tr');
        if (row) {
            const cells = row.cells;
            invoices.push({
                uuid: checkbox.dataset.uuid,
                belgeNumarasi: cells[1]?.textContent.trim() || 'N/A',
                faturaTarihi: cells[2]?.textContent.trim() || '',
                aliciUnvanAdSoyad: cells[4]?.textContent.trim() || '',
                aliciEmail: checkbox.dataset.email || '',
                toplamTutar: cells[6]?.textContent.trim() || '0'
            });
        }
    });
    
    return invoices;
}

// Mail Gönderme Modal'ını Göster
function showEmailSendingModal(invoices) {
    document.getElementById('email-invoice-count').textContent = invoices.length;
    
    // Seçilen faturaları listele (e-posta backend'den çekilecek)
    const selectedInvoicesContainer = document.getElementById('email-selected-invoices');
    selectedInvoicesContainer.innerHTML = invoices.map(inv => `
        <div style="padding: 8px; margin: 5px 0; background: white; border-radius: 4px; border-left: 3px solid var(--primary-color);">
            <div>
                <strong>${inv.belgeNumarasi}</strong> - ${inv.aliciUnvanAdSoyad}
            </div>
            <div style="color: var(--text-light-color); font-size: 0.85em; margin-top: 3px;">
                <i class="fas fa-info-circle"></i> E-posta adresi fatura detayından çekilecek
            </div>
        </div>
    `).join('');
    
    // Progress'i sıfırla
    document.getElementById('email-progress-fill').style.width = '0%';
    document.getElementById('email-progress-text').textContent = 'Hazırlanıyor...';
    document.getElementById('email-results').style.display = 'none';
    document.getElementById('email-message').innerHTML = '';
    
    // Modal'ı göster
    emailSendingModal.style.display = 'flex';
    
    // Mail göndermeyi başlat
    sendInvoiceEmails(invoices);
}

// Mail Gönderme İşlemi
async function sendInvoiceEmails(invoices) {
    try {
        document.getElementById('email-progress-text').textContent = 'Mailler gönderiliyor...';
        document.getElementById('email-progress-fill').style.width = '50%';
        
        const response = await fetch(BACKEND_URL + '?path=send_invoice_emails', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ invoices: invoices })
        });
        
        // Response'u text olarak al ve kontrol et
        const responseText = await response.text();
        console.log('Backend Response:', responseText);
        
        // JSON parse et
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (parseError) {
            console.error('JSON Parse Error:', parseError);
            console.error('Response Text:', responseText);
            throw new Error('Backend yanıtı geçersiz JSON formatında. Konsolu kontrol edin.');
        }
        
        document.getElementById('email-progress-fill').style.width = '100%';
        
        if (data.success) {
            document.getElementById('email-progress-text').innerHTML = 
                `<span style="color: var(--success-color);"><i class="fas fa-check-circle"></i> ${data.message}</span>`;
            
            showToast(`${data.sent} mail başarıyla gönderildi!`, 'success');
        } else {
            document.getElementById('email-progress-text').innerHTML = 
                `<span style="color: var(--danger-color);"><i class="fas fa-exclamation-circle"></i> ${data.message}</span>`;
            
            showToast('Mail gönderme hatası!', 'error');
        }
        
        // Detaylı sonuçları göster
        if (data.details && data.details.length > 0) {
            const resultsContainer = document.getElementById('email-results-list');
            resultsContainer.innerHTML = data.details.map(detail => {
                const icon = detail.success ? 
                    '<i class="fas fa-check-circle" style="color: var(--success-color);"></i>' : 
                    '<i class="fas fa-times-circle" style="color: var(--danger-color);"></i>';
                
                return `
                    <div style="padding: 8px; margin: 5px 0; background: ${detail.success ? '#d4edda' : '#f8d7da'}; border-radius: 4px;">
                        ${icon} <strong>${detail.belgeNumarasi}</strong> - ${detail.email || 'N/A'}<br>
                        <small style="color: #666;">${detail.message}</small>
                    </div>
                `;
            }).join('');
            
            document.getElementById('email-results').style.display = 'block';
        }
        
    } catch (error) {
        document.getElementById('email-progress-fill').style.width = '100%';
        document.getElementById('email-progress-fill').style.background = 'var(--danger-color)';
        document.getElementById('email-progress-text').innerHTML = 
            `<span style="color: var(--danger-color);"><i class="fas fa-exclamation-circle"></i> Hata: ${error.message}</span>`;
        
        showToast('Mail gönderme hatası: ' + error.message, 'error');
    }
}

// Email Modal Kapatma
closeEmailModal.addEventListener('click', () => {
    emailSendingModal.style.display = 'none';
    // Listeyi yenile
    document.getElementById('list-invoices-btn')?.click();
});


// Şablon Önizleme
document.getElementById('preview-template-btn').addEventListener('click', () => {
    const templateType = document.getElementById('smtp-template-type').value;
    const logoUrl = document.getElementById('smtp-logo-url').value;
    const customMessage = document.getElementById('smtp-custom-message').value;
    
    // Örnek fatura verisi
    const sampleData = {
        belgeNumarasi: 'ABC2024000001',
        faturaTarihi: '12/11/2025',
        aliciUnvanAdSoyad: 'Örnek Müşteri A.Ş.',
        toplamTutar: 1180.00,
        paraBirimi: 'TRY'
    };
    
    // Yeni pencerede önizleme
    const previewWindow = window.open('', 'Şablon Önizleme', 'width=800,height=600');
    
    // Backend'den şablon HTML'ini al
    fetch(BACKEND_URL + '?path=preview_template', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            template_type: templateType,
            logo_url: logoUrl,
            custom_message: customMessage,
            sample_data: sampleData
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success && data.html) {
            previewWindow.document.write(data.html);
            previewWindow.document.close();
        } else {
            previewWindow.document.write('<h3>Önizleme yüklenemedi</h3><p>' + (data.message || 'Hata oluştu') + '</p>');
            previewWindow.document.close();
        }
    })
    .catch(error => {
        previewWindow.document.write('<h3>Önizleme yüklenemedi</h3><p>' + error.message + '</p>');
        previewWindow.document.close();
    });
});


// ==================== İÇE/DIŞA AKTARMA ====================

// Dışa Aktar Butonu
document.getElementById('export-smtp-btn').addEventListener('click', async () => {
    try {
        const response = await fetch(BACKEND_URL + '?path=export_smtp_settings');
        const data = await response.json();
        
        if (data.success) {
            // JSON'u dosya olarak indir
            const jsonStr = JSON.stringify(data.data, null, 2);
            const blob = new Blob([jsonStr], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = data.filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
            
            showToast('SMTP ayarları başarıyla dışa aktarıldı!', 'success');
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        showToast('Dışa aktarma hatası: ' + error.message, 'error');
    }
});

// İçe Aktar Butonu
document.getElementById('import-smtp-btn').addEventListener('click', () => {
    document.getElementById('import-smtp-file').click();
});

// Dosya Seçildiğinde
document.getElementById('import-smtp-file').addEventListener('change', async (event) => {
    const file = event.target.files[0];
    if (!file) return;
    
    try {
        const reader = new FileReader();
        reader.onload = async (e) => {
            try {
                const jsonData = e.target.result;
                
                // JSON geçerliliğini kontrol et
                JSON.parse(jsonData);
                
                const response = await fetch(BACKEND_URL + '?path=import_smtp_settings', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ json_data: jsonData })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showToast('SMTP ayarları başarıyla içe aktarıldı!', 'success');
                    // Ayarları yeniden yükle
                    loadSmtpSettings();
                } else {
                    showToast(data.message, 'error');
                }
            } catch (error) {
                showToast('Geçersiz JSON dosyası: ' + error.message, 'error');
            }
        };
        reader.readAsText(file);
    } catch (error) {
        showToast('Dosya okuma hatası: ' + error.message, 'error');
    }
    
    // Input'u temizle (aynı dosya tekrar seçilebilsin)
    event.target.value = '';
});
