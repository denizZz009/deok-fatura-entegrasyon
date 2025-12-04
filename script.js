// Dinamik backend URL - hem localhost hem hosting'de çalışır
const BACKEND_URL = (() => {
    const currentPath = window.location.pathname;
    const basePath = currentPath.substring(0, currentPath.lastIndexOf('/') + 1);
    return basePath + 'backend/index.php';
})();

// Toast Notification Function
function showToast(message, type = 'info') {
    const toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        return;
    }

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerText = message;

    toastContainer.appendChild(toast);

    // Show toast
    setTimeout(() => {
        toast.classList.add('show');
    }, 100);

    // Hide and remove toast after a few seconds
    setTimeout(() => {
        toast.classList.remove('show');
        toast.classList.add('hide');
        toast.addEventListener('transitionend', () => toast.remove());
    }, 3000);
}

document.addEventListener('DOMContentLoaded', () => {

    // --- MODAL AND FORM LOGIC ---
    const invoiceModal = document.getElementById('invoice-modal');
    const smsModal = document.getElementById('sms-modal');
    const createInvoiceBtn = document.getElementById('create-invoice-btn');
    const closeInvoiceBtn = invoiceModal.querySelector('.close-btn');
    const closeSmsBtn = smsModal.querySelector('.close-sms-btn');
    const addItemBtn = document.getElementById('add-item-btn');
    const invoiceItemsContainer = document.getElementById('invoice-items');
    const invoiceForm = document.getElementById('invoice-form');
    const smsForm = document.getElementById('sms-form');
    const getRecipientInfoBtn = document.getElementById('get-recipient-info-btn');
    const invoiceMessage = document.getElementById('invoice-message');
    const smsMessage = document.getElementById('sms-message');
    const invoiceListContainer = document.getElementById('invoice-list');

    // Export/Import Buttons
    const exportSettingsBtn = document.getElementById('export-settings-btn');
    const importSettingsBtn = document.getElementById('import-settings-btn');
    const importSettingsInput = document.getElementById('import-settings-input');

    // Bulk Upload Elements
    const bulkUploadBtn = document.getElementById('bulk-upload-btn');
    const bulkUploadSection = document.getElementById('bulk-upload-section');
    const downloadTemplateBtn = document.getElementById('download-template-btn');
    const selectExcelBtn = document.getElementById('select-excel-btn');
    const excelFileInput = document.getElementById('excel-file-input');
    const excelPreview = document.getElementById('excel-preview');
    const processExcelBtn = document.getElementById('process-excel-btn');
    const cancelExcelBtn = document.getElementById('cancel-excel-btn');

    // --- Event Listeners ---
    // Login button event listener
    document.getElementById('login-btn').addEventListener('click', async () => {
        const usercode = document.getElementById('usercode').value;
        const password = document.getElementById('password').value;
        const loginMessageElement = document.getElementById('login-message');

        try {
            const response = await fetch(BACKEND_URL + '?path=login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ usercode, password })
            });
            const data = await response.json();

            if (data.success) {
                document.getElementById('login-section').style.display = 'none';
                document.getElementById('panel-section').style.display = 'block';
                showToast(data.message || 'Giriş başarılı!', 'success');

                // Panel buttons are already handled by DOMContentLoaded, no need to re-attach here.
                // The filter button listener is moved outside this block to prevent multiple attachments.




            } else {
                showToast(data.message || 'Giriş başarısız!', 'error');
            }
        } catch (error) {
            showToast('Giriş sırasında bir hata oluştu. Lütfen tekrar deneyin.', 'error');
        }
    });

    // Help button event listener
    document.getElementById('help-btn').addEventListener('click', () => {
        window.open('help.html', '_blank', 'width=1200,height=800,scrollbars=yes,resizable=yes');
    });

    // Logout button event listener
    document.getElementById('logout-btn').addEventListener('click', () => {
        fetch(BACKEND_URL + '?path=logout', { method: 'POST' })
            .then(res => res.json())
            .then(() => {
                document.getElementById('login-section').style.display = 'block';
                document.getElementById('panel-section').style.display = 'none';
                document.getElementById('invoice-list').innerHTML = '';
                document.getElementById('progress').innerHTML = '';
                document.getElementById('usercode').value = '';
                document.getElementById('password').value = '';
            });
    });

    createInvoiceBtn.addEventListener('click', () => {
        invoiceForm.reset();
        invoiceItemsContainer.innerHTML = '';

        // Edit modunu temizle
        delete invoiceForm.dataset.editUuid;
        delete invoiceForm.dataset.editMode;

        // Submit butonunu yeni fatura moduna çevir
        const submitBtn = document.getElementById('submit-invoice-btn');
        submitBtn.innerHTML = '<i class="fas fa-file-invoice"></i> Taslak Oluştur';

        updateTotals();
        addInvoiceItem();
        showToast('Yeni fatura formu hazırlandı.', 'info');
        invoiceModal.style.display = 'flex';
        // Set current date and time as default
        const now = new Date();
        document.getElementById('inv-date').value = now.toISOString().substring(0, 10);
        document.getElementById('inv-time').value = now.toTimeString().substring(0, 5);
    });

    closeInvoiceBtn.addEventListener('click', () => invoiceModal.style.display = 'none');
    closeSmsBtn.addEventListener('click', () => smsModal.style.display = 'none');
    window.addEventListener('click', (event) => {
        if (event.target == invoiceModal) invoiceModal.style.display = 'none';
        if (event.target == smsModal) smsModal.style.display = 'none';
    });

    document.querySelectorAll('.toggle-section').forEach(button => {
        button.addEventListener('click', () => {
            const content = button.parentElement.nextElementSibling;
            content.classList.toggle('collapsed');
            button.textContent = content.classList.contains('collapsed') ? '+' : '-';
        });
    });

    addItemBtn.addEventListener('click', addInvoiceItem);

    getRecipientInfoBtn.addEventListener('click', () => {
        const vknTckn = document.getElementById('recipient-vkn').value;
        if (!vknTckn) return showToast('Lütfen VKN/TCKN girin.', 'warning');

        // Modal'ın açık olup olmadığını kontrol et
        const modal = document.getElementById('invoice-modal');
        if (!modal || modal.style.display === 'none') {
            showToast('Önce fatura oluşturma modalını açın!', 'warning');
            return;
        }

        showToast('Alıcı bilgileri getiriliyor...', 'info');
        fetch(`${BACKEND_URL}?path=get_recipient_info&vknTckn=${vknTckn}`)
            .then(res => res.json())
            .then(data => {
                if (data.success && data.data) {
                    // Gelen veriyi test et

                    // Her alanı tek tek doldur ve debug yap
                    const fields = [
                        { id: 'recipient-unvan', value: data.data.unvan || data.data.aliciUnvan || '', label: 'Unvan' },
                        { id: 'recipient-adi', value: data.data.adi || data.data.aliciAdi || data.data.ad || '', label: 'Adı' },
                        { id: 'recipient-soyadi', value: data.data.soyadi || data.data.aliciSoyadi || data.data.soyad || '', label: 'Soyadı' },
                        { id: 'recipient-vergi-dairesi', value: data.data.vergiDairesi || '', label: 'Vergi Dairesi' },
                        { id: 'recipient-address', value: data.data.adres || '', label: 'Adres' },
                        { id: 'recipient-mahalle', value: data.data.mahalleSemtIlce || data.data.mahalle || '', label: 'Mahalle' },
                        { id: 'recipient-sehir', value: data.data.sehir || '', label: 'Şehir' },
                        { id: 'recipient-ulke', value: data.data.ulke || 'Türkiye', label: 'Ülke' },
                        { id: 'recipient-posta-kodu', value: data.data.postaKodu || '', label: 'Posta Kodu' },
                        { id: 'recipient-tel', value: data.data.tel || data.data.telefon || '', label: 'Telefon' },
                        { id: 'recipient-email', value: data.data.eposta || '', label: 'E-posta' }
                    ];

                    // Direkt değer atama

                    // Adı doldur
                    const adiElement = document.getElementById('recipient-adi');
                    if (adiElement) {
                        adiElement.value = data.data.adi || '';
                    }

                    // Soyadı doldur
                    const soyadiElement = document.getElementById('recipient-soyadi');
                    if (soyadiElement) {
                        soyadiElement.value = data.data.soyadi || '';
                    }

                    // Vergi dairesi doldur
                    const vergiDairesiElement = document.getElementById('recipient-vergi-dairesi');
                    if (vergiDairesiElement) {
                        vergiDairesiElement.value = data.data.vergiDairesi || '';
                    }

                    // Diğer alanları da doldur
                    fields.forEach(field => {
                        const element = document.getElementById(field.id);
                        if (element) {
                            element.value = field.value;
                        }
                    });

                    // VKN ise (10 haneli) isim/soyisim zorunluluğunu kaldır
                    if (vknTckn.length === 10) {
                        document.getElementById('recipient-adi').removeAttribute('required');
                        document.getElementById('recipient-soyadi').removeAttribute('required');
                        document.getElementById('recipient-adi').classList.remove('required-field');
                        document.getElementById('recipient-soyadi').classList.remove('required-field');
                        showToast('Şirket bilgileri başarıyla getirildi!', 'success');
                    } else {
                        // TCKN ise (11 haneli) isim/soyisim zorunlu
                        document.getElementById('recipient-adi').setAttribute('required', 'required');
                        document.getElementById('recipient-soyadi').setAttribute('required', 'required');
                        showToast('Kişi bilgileri başarıyla getirildi!', 'success');
                    }
                } else {
                    showToast(`Hata: ${data.message}`, 'error');
                }
            })
            .catch(error => {
                showToast('Bir hata oluştu.', 'error');
            });
    });

    invoiceForm.addEventListener('submit', handleInvoiceFormSubmit);

    smsForm.addEventListener('submit', handleSmsFormSubmit);

    invoiceListContainer.addEventListener('click', (e) => {
        const targetButton = e.target.closest('button');
        if (!targetButton) return;

        const uuid = targetButton.dataset.uuid;
        const status = targetButton.dataset.status; // Get status
        if (targetButton.classList.contains('sign-btn')) {
            handleSignButtonClick(uuid);
        } else if (targetButton.classList.contains('delete-btn')) {
            handleDeleteButtonClick(uuid);
        } else if (targetButton.classList.contains('edit-btn')) {
            handleEditButtonClick(uuid);
        } else if (targetButton.classList.contains('view-btn')) {
            handleViewButtonClick(uuid, status); // Pass status to function
        }
    });

    exportSettingsBtn.addEventListener('click', exportInvoiceSettings);
    importSettingsBtn.addEventListener('click', () => importSettingsInput.click());
    importSettingsInput.addEventListener('change', importInvoiceSettings);

    // Para birimi değişikliğini dinle
    document.getElementById('inv-currency').addEventListener('change', handleCurrencyChange);

    // Bulk upload event listeners
    bulkUploadBtn.addEventListener('click', () => {
        const isVisible = bulkUploadSection.style.display !== 'none';
        bulkUploadSection.style.display = isVisible ? 'none' : 'block';
        bulkUploadBtn.innerHTML = isVisible ?
            '<i class="fas fa-file-excel"></i> Toplu Fatura Yükle' :
            '<i class="fas fa-times"></i> Kapat';
    });

    downloadTemplateBtn.addEventListener('click', () => {
        window.open(BACKEND_URL + '?path=download_excel_template', '_blank');
        showToast('Excel şablonu indiriliyor...', 'info');
    });

    selectExcelBtn.addEventListener('click', () => {
        excelFileInput.click();
    });

    excelFileInput.addEventListener('change', handleExcelFileSelect);
    processExcelBtn.addEventListener('click', handleExcelProcess);
    cancelExcelBtn.addEventListener('click', () => {
        excelPreview.style.display = 'none';
        excelFileInput.value = '';
    });

    // VKN/TCKN değişikliğini dinle
    document.getElementById('recipient-vkn').addEventListener('input', (e) => {
        const vknTckn = e.target.value;
        const adiField = document.getElementById('recipient-adi');
        const soyadiField = document.getElementById('recipient-soyadi');

        if (vknTckn.length === 10) {
            // VKN - şirket
            adiField.removeAttribute('required');
            soyadiField.removeAttribute('required');
            adiField.classList.remove('required-field');
            soyadiField.classList.remove('required-field');
        } else if (vknTckn.length === 11) {
            // TCKN - kişi
            adiField.setAttribute('required', 'required');
            soyadiField.setAttribute('required', 'required');
        }
    });

    // --- Rapor Sistemi Başlat ---
    initializeReportSystem();

    // --- Toplu SMS Onay Sistemi Başlat ---
    initializeBulkSmsSystem();

    // --- Toplu SMS Onay Sistemi ---
    function initializeBulkSmsSystem() {
        const bulkSmsModal = document.getElementById('bulk-sms-modal');
        const bulkSmsApproveBtn = document.getElementById('bulk-sms-approve-btn');
        const closeBulkSmsBtn = bulkSmsModal.querySelector('.close-bulk-sms-btn');
        const bulkSendSmsBtn = document.getElementById('bulk-send-sms-btn');
        const bulkSmsForm = document.getElementById('bulk-sms-form');

        let selectedInvoices = [];
        let bulkSmsTimer = null;

        // Toplu onay butonuna tıklama
        bulkSmsApproveBtn.addEventListener('click', () => {
            selectedInvoices = getSelectedInvoices();
            if (selectedInvoices.length === 0) {
                showToast('Lütfen onaylanacak faturaları seçin.', 'warning');
                return;
            }

            // Debug: Seçilen faturaların durumlarını kontrol et
            console.log('Seçilen faturalar:', selectedInvoices.map(inv => ({
                belgeNumarasi: inv.belgeNumarasi,
                status: inv.status,
                statusType: typeof inv.status
            })));

            // Geçici olarak durum kontrolünü devre dışı bırak - tüm seçilen faturaları onaylamaya çalış
            console.log('Durum kontrolü devre dışı - tüm seçilen faturalar onaylanmaya çalışılacak');

            // Sadece onaylanabilir faturaları filtrele (farklı durum formatlarını kontrol et)
            const draftInvoices = selectedInvoices.filter(inv => {
                const status = inv.status?.toString().toLowerCase().trim();
                // Onaylanmış veya silinmiş faturaları hariç tut
                return !status.includes('onaylandı') &&
                    !status.includes('onaylandi') &&
                    !status.includes('silinmiş') &&
                    !status.includes('silinmis') &&
                    !status.includes('iptal');
            });

            if (draftInvoices.length === 0) {
                // Daha detaylı hata mesajı
                const statusList = selectedInvoices.map(inv => inv.status).join(', ');
                showToast(`Seçilen faturalar onaylanabilir durumda değil. Mevcut durumlar: ${statusList}`, 'warning');
                return;
            }

            if (draftInvoices.length !== selectedInvoices.length) {
                showToast(`${selectedInvoices.length} fatura seçildi, ${draftInvoices.length} tanesi onaylanmaya çalışılacak.`, 'info');
            }

            selectedInvoices = draftInvoices;
            showBulkSmsModal();
        });

        // Modal kapatma
        closeBulkSmsBtn.addEventListener('click', () => {
            closeBulkSmsModal();
        });

        // Modal dışına tıklama
        window.addEventListener('click', (event) => {
            if (event.target === bulkSmsModal) {
                closeBulkSmsModal();
            }
        });

        // SMS gönder butonu
        bulkSendSmsBtn.addEventListener('click', async () => {
            try {
                bulkSendSmsBtn.disabled = true;
                bulkSendSmsBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> SMS Gönderiliyor...';

                const response = await fetch(BACKEND_URL + '?path=bulk_sms_start', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        invoices: selectedInvoices.map(inv => ({
                            uuid: inv.uuid,
                            belgeNumarasi: inv.belgeNumarasi,
                            aliciVknTckn: inv.aliciVknTckn,
                            aliciUnvanAdSoyad: inv.aliciUnvanAdSoyad,
                            ettn: inv.ettn
                        }))
                    })
                });

                const data = await response.json();

                if (data.success) {
                    document.getElementById('bulk-sms-oid').value = data.oid;
                    showBulkSmsStep2();
                    startBulkSmsTimer();
                    showToast('SMS şifresi gönderildi!', 'success');
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                showToast('SMS gönderme hatası: ' + error.message, 'error');
            } finally {
                bulkSendSmsBtn.disabled = false;
                bulkSendSmsBtn.innerHTML = '<i class="fas fa-sms"></i> SMS Şifresi Gönder';
            }
        });

        // SMS onay formu
        bulkSmsForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const smsCode = document.getElementById('bulk-sms-code').value;
            const oid = document.getElementById('bulk-sms-oid').value;

            if (!smsCode || smsCode.length !== 6) {
                showToast('Lütfen 6 haneli SMS şifresini girin.', 'warning');
                return;
            }

            try {
                const submitBtn = document.getElementById('submit-bulk-sms-btn');
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Onaylanıyor...';

                showBulkSmsProgress();

                const response = await fetch(BACKEND_URL + '?path=bulk_sms_verify', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        smsCode: smsCode,
                        oid: oid,
                        invoices: selectedInvoices
                    })
                });

                const data = await response.json();

                if (data.success) {
                    updateBulkSmsProgress(100, `${data.successCount} fatura başarıyla onaylandı!`);
                    showToast(`Toplu onay tamamlandı! ${data.successCount} fatura onaylandı.`, 'success');

                    // 3 saniye sonra modal'ı kapat ve listeyi yenile
                    setTimeout(() => {
                        closeBulkSmsModal();
                        document.getElementById('list-invoices-btn').click();
                    }, 3000);
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                updateBulkSmsProgress(0, 'Hata: ' + error.message);
                showToast('Toplu onay hatası: ' + error.message, 'error');
            } finally {
                const submitBtn = document.getElementById('submit-bulk-sms-btn');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-signature"></i> Seçilen Faturaları Toplu Onayla';

                if (bulkSmsTimer) {
                    clearInterval(bulkSmsTimer);
                    bulkSmsTimer = null;
                }
            }
        });

        function getSelectedInvoices() {
            const checkboxes = document.querySelectorAll('.invoice-checkbox:checked');
            const invoices = [];

            checkboxes.forEach(checkbox => {
                const row = checkbox.closest('tr');
                if (row) {
                    invoices.push({
                        uuid: checkbox.dataset.uuid,
                        belgeNumarasi: row.cells[1].textContent.trim(),
                        aliciVknTckn: checkbox.dataset.aliciVknTckn || '',
                        aliciUnvanAdSoyad: row.cells[4].textContent.trim(), // Alıcı 4. sütunda
                        status: row.cells[3].textContent.trim(), // Durum 3. sütunda
                        ettn: checkbox.dataset.ettn || ''
                    });
                }
            });

            return invoices;
        }

        function showBulkSmsModal() {
            // Seçilen faturaları göster
            document.getElementById('selected-invoice-count').textContent = selectedInvoices.length;

            const selectedInvoicesContainer = document.getElementById('bulk-selected-invoices');
            selectedInvoicesContainer.innerHTML = selectedInvoices.map(inv => `
                <div class="selected-invoice-item">
                    <div class="invoice-info">
                        <strong>${inv.belgeNumarasi}</strong> - ${inv.aliciUnvanAdSoyad}
                        <div class="invoice-status">Durum: ${inv.status}</div>
                    </div>
                </div>
            `).join('');

            // Modal'ı göster
            document.getElementById('bulk-sms-step-1').style.display = 'block';
            document.getElementById('bulk-sms-step-2').style.display = 'none';
            document.getElementById('bulk-sms-progress').style.display = 'none';
            document.getElementById('bulk-sms-message').innerHTML = '';

            bulkSmsModal.style.display = 'flex';
        }

        function showBulkSmsStep2() {
            document.getElementById('bulk-sms-step-1').style.display = 'none';
            document.getElementById('bulk-sms-step-2').style.display = 'block';
            document.getElementById('bulk-sms-code').focus();
        }

        function startBulkSmsTimer() {
            let timeLeft = 180;
            const timerElement = document.getElementById('bulk-timer-count');

            bulkSmsTimer = setInterval(() => {
                timeLeft--;
                timerElement.textContent = timeLeft;

                if (timeLeft <= 0) {
                    clearInterval(bulkSmsTimer);
                    bulkSmsTimer = null;

                    document.getElementById('bulk-sms-code').disabled = true;
                    document.getElementById('submit-bulk-sms-btn').disabled = true;

                    showToast('SMS süresi doldu. Lütfen tekrar deneyin.', 'error');
                }
            }, 1000);
        }

        function showBulkSmsProgress() {
            document.getElementById('bulk-sms-progress').style.display = 'block';
            updateBulkSmsProgress(50, 'Faturalar onaylanıyor...');
        }

        function updateBulkSmsProgress(percentage, message) {
            document.getElementById('bulk-sms-progress-fill').style.width = percentage + '%';
            document.getElementById('bulk-sms-progress-text').innerHTML = message;
        }

        function closeBulkSmsModal() {
            bulkSmsModal.style.display = 'none';
            selectedInvoices = [];

            if (bulkSmsTimer) {
                clearInterval(bulkSmsTimer);
                bulkSmsTimer = null;
            }

            // Form'u temizle
            document.getElementById('bulk-sms-code').value = '';
            document.getElementById('bulk-sms-code').disabled = false;
            document.getElementById('submit-bulk-sms-btn').disabled = false;
            document.getElementById('bulk-timer-count').textContent = '180';
        }
    }

    // --- Functions ---

    function handleExcelFileSelect(event) {
        const file = event.target.files[0];
        if (!file) return;

        if (!file.name.match(/\.(xlsx|xls)$/)) {
            showToast('Lütfen geçerli bir Excel dosyası seçin (.xlsx veya .xls)', 'error');
            return;
        }

        // Dosya önizlemesi göster
        document.getElementById('excel-data-preview').innerHTML = `
            <div style="padding: 15px; background: #f8f9fa; border-radius: 5px;">
                <strong>Seçilen Dosya:</strong> ${file.name}<br>
                <strong>Boyut:</strong> ${(file.size / 1024).toFixed(2)} KB<br>
                <strong>Tip:</strong> ${file.type || 'Excel dosyası'}
            </div>
            <div style="margin-top: 15px; padding: 10px; background: #e3f2fd; border-radius: 5px; border-left: 4px solid #2196f3;">
                <strong><i class="fas fa-info-circle"></i> Önemli Özellikler:</strong><br>
                <small>• <strong>A kolonu:</strong> Fatura Tarihi (<strong>gg/aa/yyyy formatında</strong> - örn: 30/09/2025) - Boş bırakılırsa bugünün tarihi kullanılır<br>
                • <strong>B kolonu:</strong> Fatura Saati (<strong>SS:DD formatında</strong> - örn: 14:30) - Boş bırakılırsa şimdiki saat kullanılır<br>
                • <strong>C kolonu:</strong> VKN/TCKN - Doldurulursa alıcı bilgileri otomatik çekilir<br>
                • <strong>D-N kolonları:</strong> Alıcı bilgileri - VKN/TCKN varsa otomatik doldurulur<br>
                • <strong>Akıllı Ünvan:</strong> TCKN (11 haneli) için ünvan otomatik boş bırakılır, VKN (10 haneli) için doldurulur<br>
                • <strong>Desteklenen Tarih Formatları:</strong> dd/mm/yyyy, dd.mm.yyyy, dd-mm-yyyy, yyyy-mm-dd</small>
            </div>
            <p style="margin-top: 10px; color: #666;">
                <i class="fas fa-info-circle"></i> 
                Dosya yüklendikten sonra her satır bir fatura taslağı olarak oluşturulacaktır.
            </p>
        `;
        excelPreview.style.display = 'block';
    }

    function handleExcelProcess() {
        const file = excelFileInput.files[0];
        if (!file) {
            showToast('Lütfen önce bir Excel dosyası seçin.', 'warning');
            return;
        }

        const formData = new FormData();
        formData.append('excel_file', file);

        // Progress göster
        const bulkProgress = document.getElementById('bulk-progress');
        const progressFill = document.getElementById('bulk-progress-fill');
        const progressText = document.getElementById('bulk-progress-text');

        bulkProgress.style.display = 'block';
        processExcelBtn.disabled = true;
        progressText.textContent = 'Excel dosyası işleniyor...';
        progressFill.style.width = '50%';

        fetch(BACKEND_URL + '?path=process_excel_upload', {
            method: 'POST',
            body: formData
        })
            .then(res => res.json())
            .then(data => {
                progressFill.style.width = '100%';

                if (data.success) {
                    let successMessage = `
                    <div style="color: var(--success-color);">
                        <i class="fas fa-check-circle"></i> ${data.message}
                    </div>
                `;

                    // Otomatik çekilen mükellef bilgisi varsa göster
                    if (data.recipientCacheCount && data.recipientCacheCount > 0) {
                        successMessage += `
                        <div style="margin-top: 8px; padding: 8px; background: #e8f5e8; border-radius: 4px; font-size: 0.9em;">
                            <i class="fas fa-user-check" style="color: #28a745;"></i> 
                            <strong>${data.recipientCacheCount}</strong> farklı mükellef bilgisi otomatik olarak çekildi ve dolduruldu!
                        </div>
                    `;
                    }

                    progressText.innerHTML = successMessage;

                    if (data.errors && data.errors.length > 0) {
                        progressText.innerHTML += `
                        <details style="margin-top: 10px;">
                            <summary style="cursor: pointer; color: var(--warning-color);">
                                <i class="fas fa-exclamation-triangle"></i> Hatalar (${data.errors.length})
                            </summary>
                            <ul style="margin-top: 5px; text-align: left;">
                                ${data.errors.map(error => `<li>${error}</li>`).join('')}
                            </ul>
                        </details>
                    `;
                    }

                    let toastMessage = `${data.successCount} fatura başarıyla oluşturuldu!`;
                    if (data.recipientCacheCount > 0) {
                        toastMessage += ` (${data.recipientCacheCount} mükellef bilgisi otomatik çekildi)`;
                    }
                    showToast(toastMessage, 'success');

                    // 3 saniye sonra listeyi yenile ve alanı kapat
                    setTimeout(() => {
                        document.getElementById('list-invoices-btn').click();
                        bulkUploadSection.style.display = 'none';
                        bulkUploadBtn.innerHTML = '<i class="fas fa-file-excel"></i> Toplu Fatura Yükle';
                        excelPreview.style.display = 'none';
                        bulkProgress.style.display = 'none';
                        excelFileInput.value = '';
                    }, 3000);

                } else {
                    progressText.innerHTML = `
                    <div style="color: var(--danger-color);">
                        <i class="fas fa-times-circle"></i> ${data.message}
                    </div>
                `;
                    showToast('Excel işleme başarısız: ' + data.message, 'error');
                }
            })
            .catch(error => {
                progressText.innerHTML = `
                <div style="color: var(--danger-color);">
                    <i class="fas fa-times-circle"></i> İşlem sırasında bir hata oluştu.
                </div>
            `;
                showToast('Excel işleme sırasında bir hata oluştu.', 'error');
            })
            .finally(() => {
                processExcelBtn.disabled = false;
            });
    }

    function handleEditButtonClick(uuid) {
        showToast('Fatura düzenleme için yükleniyor...', 'info');

        fetch(BACKEND_URL + '?path=get_invoice_for_edit&uuid=' + uuid)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Modalı aç ve formu doldur
                    populateEditForm(data.data, uuid);
                    invoiceModal.style.display = 'flex';
                    showToast('Fatura düzenleme için hazırlandı.', 'success');
                } else {
                    showToast('Hata: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showToast('Fatura düzenlenirken bir hata oluştu.', 'error');
            });
    }

    function populateEditForm(invoiceData, uuid) {
        // Formu temizle
        invoiceForm.reset();
        invoiceItemsContainer.innerHTML = '';

        // UUID ve belge numarasını sakla (güncelleme için gerekli)
        invoiceForm.dataset.editUuid = uuid;
        invoiceForm.dataset.editMode = 'true';
        invoiceForm.dataset.belgeNumarasi = invoiceData.belgeNumarasi || '';

        // Temel fatura bilgileri - Tarih
        if (invoiceData.faturaTarihi) {
            // Tarih formatı: "21/07/2025" -> "2025-07-21"
            const dateParts = invoiceData.faturaTarihi.split('/');
            if (dateParts.length === 3) {
                document.getElementById('inv-date').value = `${dateParts[2]}-${dateParts[1]}-${dateParts[0]}`;
            }
        }

        // Temel fatura bilgileri - Saat
        if (invoiceData.saat) {
            // Saat formatı: "19:10:00" -> "19:10"
            const timeParts = invoiceData.saat.split(':');
            if (timeParts.length >= 2) {
                document.getElementById('inv-time').value = `${timeParts[0]}:${timeParts[1]}`;
            }
        }

        // Para birimi ve kur bilgileri
        document.getElementById('inv-currency').value = invoiceData.paraBirimi || 'TRY';
        document.getElementById('inv-exchange-rate').value = invoiceData.dovzTLkur || invoiceData.dovizKuru || '1.0000';

        // Fatura tipi - "SATIS" -> "Satis"
        let faturaTipi = 'Satis';
        if (invoiceData.faturaTipi) {
            faturaTipi = invoiceData.faturaTipi.toLowerCase();
            faturaTipi = faturaTipi.charAt(0).toUpperCase() + faturaTipi.slice(1);
        }
        document.getElementById('inv-type').value = faturaTipi;

        // Alıcı bilgileri - VKN/TCKN
        document.getElementById('recipient-vkn').value = invoiceData.vknTckn || '';
        document.getElementById('recipient-adi').value = invoiceData.aliciAdi || '';
        document.getElementById('recipient-soyadi').value = invoiceData.aliciSoyadi || '';
        document.getElementById('recipient-unvan').value = invoiceData.aliciUnvan || '';

        // Diğer alıcı bilgileri
        document.getElementById('recipient-vergi-dairesi').value = invoiceData.vergiDairesi || '';
        document.getElementById('recipient-address').value = invoiceData.bulvarcaddesokak || '';
        document.getElementById('recipient-mahalle').value = invoiceData.mahalleSemtIlce || '';
        document.getElementById('recipient-sehir').value = invoiceData.sehir || '';
        document.getElementById('recipient-ulke').value = invoiceData.ulke || 'Türkiye';
        document.getElementById('recipient-posta-kodu').value = invoiceData.postaKodu || '';
        document.getElementById('recipient-tel').value = invoiceData.tel || '';
        document.getElementById('recipient-fax').value = invoiceData.fax || '';
        document.getElementById('recipient-email').value = invoiceData.eposta || '';

        // Bina bilgileri
        document.getElementById('recipient-bina-adi').value = invoiceData.binaAdi || '';
        document.getElementById('recipient-bina-no').value = invoiceData.binaNo || '';
        document.getElementById('recipient-kapi-no').value = invoiceData.kapiNo || '';
        document.getElementById('recipient-kasaba-koy').value = invoiceData.kasabaKoy || '';

        // Sipariş ve İrsaliye bilgileri
        document.getElementById('inv-siparis-numarasi').value = invoiceData.siparisNumarasi || '';
        document.getElementById('inv-siparis-tarihi').value = invoiceData.siparisTarihi || '';
        document.getElementById('inv-irsaliye-numarasi').value = invoiceData.irsaliyeNumarasi || '';
        document.getElementById('inv-irsaliye-tarihi').value = invoiceData.irsaliyeTarihi || '';

        // ÖKC Fiş bilgileri
        document.getElementById('inv-fis-no').value = invoiceData.fisNo || '';
        document.getElementById('inv-fis-tarihi').value = invoiceData.fisTarihi || '';
        document.getElementById('inv-fis-saati').value = invoiceData.fisSaati || '';
        document.getElementById('inv-fis-tipi').value = invoiceData.fisTipi || '';
        document.getElementById('inv-z-rapor-no').value = invoiceData.zRaporNo || '';
        document.getElementById('inv-okc-seri-no').value = invoiceData.okcSeriNo || '';

        // Notlar
        document.getElementById('inv-notes').value = invoiceData.not || '';

        // Fatura kalemlerini ekle
        if (invoiceData.malHizmetTable && invoiceData.malHizmetTable.length > 0) {
            invoiceData.malHizmetTable.forEach(item => {
                addInvoiceItem({
                    malHizmet: item.malHizmet || '',
                    miktar: item.miktar || 1,
                    birimFiyat: item.birimFiyat || 0,
                    kdvOrani: item.kdvOrani || 20
                }, false);
            });
        } else {
            // En az bir kalem ekle
            addInvoiceItem();
        }

        // Toplamları güncelle
        updateTotals();

        // Submit butonunu güncelle
        const submitBtn = document.getElementById('submit-invoice-btn');
        submitBtn.innerHTML = '<i class="fas fa-save"></i> Faturayı Güncelle';
    }

    function addInvoiceItem(item = null, triggerUpdate = true) {
        const itemRow = document.createElement('div');
        itemRow.className = 'item-row';

        const name = item ? item.malHizmet || '' : '';
        const qty = item ? item.miktar || 1 : 1;
        const price = item ? item.birimFiyat || '' : '';
        const tax = item ? item.kdvOrani || 20 : 20;

        itemRow.innerHTML = `
            <input type="text" class="item-name required-field" placeholder="Ürün/Hizmet Adı (Zorunlu)" value="${name}" required>
            <input type="number" class="item-qty required-field" placeholder="Miktar (Zorunlu)" value="${qty}" step="any" required>
            <input type="number" class="item-price required-field" placeholder="Birim Fiyat (Zorunlu)" value="${price}" step="any" required>
            <input type="number" class="item-tax required-field" placeholder="KDV (%) (Zorunlu)" value="${tax}" required>
            <span class="item-total">0.00</span>
            <button type="button" class="remove-item-btn btn-danger"><i class="fas fa-trash"></i></button>
        `;
        invoiceItemsContainer.appendChild(itemRow);
        itemRow.querySelector('.remove-item-btn').addEventListener('click', () => {
            itemRow.remove();
            updateTotals();
        });
        itemRow.querySelectorAll('input').forEach(input => input.addEventListener('input', updateTotals));

        if (triggerUpdate) {
            updateTotals();
        }
    }

    // Tutarı Türkçe yazıya çeviren fonksiyon
    function numberToTurkishText(number) {
        const ones = ['', 'Bir', 'İki', 'Üç', 'Dört', 'Beş', 'Altı', 'Yedi', 'Sekiz', 'Dokuz'];
        const tens = ['', 'On', 'Yirmi', 'Otuz', 'Kırk', 'Elli', 'Altmış', 'Yetmiş', 'Seksen', 'Doksan'];
        const hundreds = ['', 'Yüz', 'İkiYüz', 'ÜçYüz', 'DörtYüz', 'BeşYüz', 'AltıYüz', 'YediYüz', 'SekizYüz', 'DokuzYüz'];

        function convertGroup(num) {
            if (num === 0) return '';

            let result = '';

            // Yüzler
            const hundred = Math.floor(num / 100);
            if (hundred > 0) {
                result += hundreds[hundred];
            }

            // Onlar ve birler
            const remainder = num % 100;
            if (remainder > 0) {
                if (remainder < 10) {
                    result += ones[remainder];
                } else if (remainder < 20) {
                    if (remainder === 10) result += 'On';
                    else if (remainder === 11) result += 'OnBir';
                    else if (remainder === 12) result += 'Onİki';
                    else if (remainder === 13) result += 'OnÜç';
                    else if (remainder === 14) result += 'OnDört';
                    else if (remainder === 15) result += 'OnBeş';
                    else if (remainder === 16) result += 'OnAltı';
                    else if (remainder === 17) result += 'OnYedi';
                    else if (remainder === 18) result += 'OnSekiz';
                    else if (remainder === 19) result += 'OnDokuz';
                } else {
                    const ten = Math.floor(remainder / 10);
                    const one = remainder % 10;
                    result += tens[ten];
                    if (one > 0) result += ones[one];
                }
            }

            return result;
        }

        if (number === 0) return 'Sıfır';

        const integerPart = Math.floor(number);
        const decimalPart = Math.round((number - integerPart) * 100);

        let result = '';

        // Trilyonlar
        const trillions = Math.floor(integerPart / 1000000000000);
        if (trillions > 0) {
            result += convertGroup(trillions) + 'Trilyon';
        }

        // Milyarlar
        const billions = Math.floor((integerPart % 1000000000000) / 1000000000);
        if (billions > 0) {
            result += convertGroup(billions) + 'Milyar';
        }

        // Milyonlar
        const millions = Math.floor((integerPart % 1000000000) / 1000000);
        if (millions > 0) {
            result += convertGroup(millions) + 'Milyon';
        }

        // Binler
        const thousands = Math.floor((integerPart % 1000000) / 1000);
        if (thousands > 0) {
            if (thousands === 1) {
                result += 'Bin';
            } else {
                result += convertGroup(thousands) + 'Bin';
            }
        }

        // Yüzler ve altı
        const remainder = integerPart % 1000;
        if (remainder > 0) {
            result += convertGroup(remainder);
        }

        // Para birimi
        result += 'TürkLirası';

        // Kuruş
        if (decimalPart > 0) {
            result += 've' + convertGroup(decimalPart) + 'Kuruş';
        }

        return result;
    }

    function updateTotals() {
        let subtotal = 0, totalTax = 0;
        invoiceItemsContainer.querySelectorAll('.item-row').forEach(row => {
            const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
            const price = parseFloat(row.querySelector('.item-price').value) || 0;
            const taxRate = parseFloat(row.querySelector('.item-tax').value) || 0;
            const itemSubtotal = qty * price;
            const itemTax = itemSubtotal * (taxRate / 100);
            row.querySelector('.item-total').textContent = (itemSubtotal + itemTax).toFixed(2);
            subtotal += itemSubtotal;
            totalTax += itemTax;
        });
        document.getElementById('subtotal').textContent = subtotal.toFixed(2);
        document.getElementById('total-tax').textContent = totalTax.toFixed(2);
        const grandTotal = subtotal + totalTax;
        document.getElementById('grand-total').textContent = grandTotal.toFixed(2);

        // Tutarı Türkçe yazıya çevirip notlar kısmına ekle
        const turkishText = numberToTurkishText(grandTotal);
        const notesElement = document.getElementById('inv-notes');

        if (notesElement) {
            const currentNotes = notesElement.value;

            // Eğer notlar kısmında zaten "Yalnız:" varsa, onu güncelle
            if (currentNotes.includes('Yalnız:')) {
                const lines = currentNotes.split('\n');
                const newLines = lines.map(line => {
                    if (line.trim().startsWith('Yalnız:')) {
                        return `Yalnız: ${turkishText}`;
                    }
                    return line;
                });
                notesElement.value = newLines.join('\n');
            } else {
                // Yeni satır ekle
                const newNote = currentNotes ? `${currentNotes}\nYalnız: ${turkishText}` : `Yalnız: ${turkishText}`;
                notesElement.value = newNote;
            }
        }
    }

    function getInvoiceFormData(includeDateTime = true, includeNotes = true) {
        const data = {
            tarih: document.getElementById('inv-date').value,
            saat: document.getElementById('inv-time').value,
            paraBirimi: document.getElementById('inv-currency').value,
            dovizKuru: document.getElementById('inv-exchange-rate').value,
            faturaTipi: document.getElementById('inv-type').value,
            vknTckn: document.getElementById('recipient-vkn').value,
            vergiDairesi: document.getElementById('recipient-vergi-dairesi').value,
            aliciUnvan: document.getElementById('recipient-unvan').value,
            aliciAdi: document.getElementById('recipient-adi').value,
            aliciSoyadi: document.getElementById('recipient-soyadi').value,
            mahalleSemtIlce: document.getElementById('recipient-mahalle').value,
            sehir: document.getElementById('recipient-sehir').value,
            ulke: document.getElementById('recipient-ulke').value,
            adres: document.getElementById('recipient-address').value,
            siparisNumarasi: document.getElementById('inv-siparis-numarasi').value,
            siparisTarihi: document.getElementById('inv-siparis-tarihi').value,
            irsaliyeNumarasi: document.getElementById('inv-irsaliye-numarasi').value,
            irsaliyeTarihi: document.getElementById('inv-irsaliye-tarihi').value,
            fisNo: document.getElementById('inv-fis-no').value,
            fisTarihi: document.getElementById('inv-fis-tarihi').value,
            fisSaati: document.getElementById('inv-fis-saati').value,
            fisTipi: document.getElementById('inv-fis-tipi').value,
            zRaporNo: document.getElementById('inv-z-rapor-no').value,
            okcSeriNo: document.getElementById('inv-okc-seri-no').value,
            binaAdi: document.getElementById('recipient-bina-adi').value,
            binaNo: document.getElementById('recipient-bina-no').value,
            kapiNo: document.getElementById('recipient-kapi-no').value,
            kasabaKoy: document.getElementById('recipient-kasaba-koy').value,
            postaKodu: document.getElementById('recipient-posta-kodu').value,
            tel: document.getElementById('recipient-tel').value,
            fax: document.getElementById('recipient-fax').value,
            eposta: document.getElementById('recipient-email').value,
            items: Array.from(document.querySelectorAll('.item-row')).map(row => ({
                malHizmet: row.querySelector('.item-name').value,
                miktar: parseFloat(row.querySelector('.item-qty').value),
                birimFiyat: parseFloat(row.querySelector('.item-price').value),
                kdvOrani: parseInt(row.querySelector('.item-tax').value)
            }))
        };

        // Notlar kısmını sadece fatura gönderirken dahil et
        if (includeNotes) {
            data.notlar = document.getElementById('inv-notes').value;
        }

        if (!includeDateTime) {
            delete data.tarih;
            delete data.saat;
        }
        return data;
    }

    function handleInvoiceFormSubmit(e) {
        e.preventDefault();

        // --- Form Validation ---
        let isValid = true;
        const requiredFields = invoiceForm.querySelectorAll('[required]');

        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                isValid = false;
                field.classList.add('required-field'); // Ensure class is added if empty
                // Find the corresponding label or placeholder to create a meaningful message
                const fieldName = field.placeholder || field.id;
                showToast(`${fieldName} alanı zorunludur.`, 'error');
            }
        });

        if (!isValid) {
            showToast('Lütfen tüm zorunlu alanları doldurun.', 'error');
            return; // Stop submission if validation fails
        }

        const submitBtn = document.getElementById('submit-invoice-btn');
        submitBtn.disabled = true;

        // Güncelleme modu kontrolü
        const isEditMode = invoiceForm.dataset.editMode === 'true';
        const editUuid = invoiceForm.dataset.editUuid;

        if (isEditMode) {
            showToast('Fatura güncelleniyor...', 'info');
        } else {
            showToast('Fatura taslağı oluşturuluyor...', 'info');
        }

        invoiceMessage.dataset.type = ''; // Clear previous status

        const invoiceData = getInvoiceFormData(true, true); // Include date, time and notes for submission

        // Güncelleme modunda UUID ve belge numarası ekle
        if (isEditMode && editUuid) {
            invoiceData.uuid = editUuid;
            invoiceData.belgeNumarasi = invoiceForm.dataset.belgeNumarasi || '';
        }

        fetch(BACKEND_URL + '?path=create_invoice', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(invoiceData)
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const isEditMode = invoiceForm.dataset.editMode === 'true';
                    const successMessage = isEditMode ? 'Başarılı! Fatura güncellendi.' : 'Başarılı! Fatura taslağı oluşturuldu.';
                    showToast(successMessage, 'success');
                    setTimeout(() => {
                        invoiceModal.style.display = 'none';
                        // Edit modunu temizle
                        delete invoiceForm.dataset.editUuid;
                        delete invoiceForm.dataset.editMode;
                        // Submit butonunu eski haline getir
                        const submitBtn = document.getElementById('submit-invoice-btn');
                        submitBtn.innerHTML = '<i class="fas fa-file-invoice"></i> Taslak Oluştur';
                        document.getElementById('list-invoices-btn').click();
                    }, 1500);
                } else {
                    showToast(`Hata: ${data.message}`, 'error');
                }
            })
            .catch(() => {
                showToast('İstek gönderilirken bir hata oluştu.', 'error');
            })
            .finally(() => submitBtn.disabled = false);
    }

    function exportInvoiceSettings() {
        const settings = getInvoiceFormData(false, false); // Exclude date, time and notes
        const dataStr = JSON.stringify(settings, null, 2);
        const blob = new Blob([dataStr], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'fatura_ayarlari.json';
        document.body.appendChild(a);
        a.click();
        // Add a small delay before removing and revoking to ensure download starts
        setTimeout(() => {
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }, 100); // 100ms delay
        showToast('Ayarlar başarıyla dışa aktarıldı.', 'success');
    }

    function importInvoiceSettings(event) {
        const file = event.target.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = (e) => {
            try {
                const importedSettings = JSON.parse(e.target.result);

                invoiceItemsContainer.innerHTML = '';

                for (const key in importedSettings) {
                    if (key === 'tarih' || key === 'saat' || key === 'notlar') continue;

                    if (key === 'items') {
                        if (Array.isArray(importedSettings.items)) {
                            importedSettings.items.forEach(item => {
                                addInvoiceItem(item, false);
                            });
                        }
                    } else {
                        const element = document.getElementById(getHtmlElementId(key));
                        if (element) {
                            element.value = importedSettings[key];
                        }
                    }
                }
                updateTotals();
                showToast('Ayarlar başarıyla içe aktarıldı.', 'success');
            } catch (error) {
                showToast('Hata: Geçersiz JSON dosyası veya formatı.', 'error');
            }
        };
        reader.readAsText(file);
    }

    // TCMB XML servisinden Efektif Alış Kuru çeken fonksiyon (Yurt dışı faturalar için)
    async function fetchExchangeRate(currency) {
        try {
            // Desteklenen para birimleri listesi
            const supportedCurrencies = [
                'USD', 'EUR', 'AUD', 'DKK', 'GBP', 'CHF', 'SEK',
                'CAD', 'KWD', 'NOK', 'SAR', 'JPY'
            ];

            // Para birimi destekleniyor mu kontrol et
            if (!supportedCurrencies.includes(currency)) {
                throw new Error(`Desteklenmeyen para birimi: ${currency}. Desteklenen: ${supportedCurrencies.join(', ')}`);
            }

            const url = `${BACKEND_URL}?path=tcmb_proxy&currency=${currency}`;

            // Backend proxy üzerinden TCMB Efektif Alış Kurunu al
            const response = await fetch(url);

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();

            if (data.success) {
                return data.rate;
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            throw error;
        }
    }

    // Para birimi değiştiğinde kur bilgisini otomatik doldur
    function handleCurrencyChange() {
        const currencySelect = document.getElementById('inv-currency');
        const exchangeRateInput = document.getElementById('inv-exchange-rate');

        if (currencySelect.value !== 'TRY') {
            // TCMB'den kur bilgisini çek
            fetchExchangeRate(currencySelect.value).then(rate => {
                if (rate) {
                    exchangeRateInput.value = rate.toFixed(4);
                    showToast(`${currencySelect.value} Efektif Alış Kuru otomatik dolduruldu: ${rate.toFixed(4)}`, 'success');
                } else {
                    showToast('Efektif Alış Kuru alınamadı!', 'error');
                }
            }).catch(error => {
                showToast('Efektif Alış Kuru alınamadı: ' + error.message, 'error');
            });
        } else {
            exchangeRateInput.value = '1.0000';
        }
    }

    // Helper to map JSON keys to HTML element IDs
    function getHtmlElementId(jsonKey) {
        const map = {
            tarih: 'inv-date',
            saat: 'inv-time',
            paraBirimi: 'inv-currency',
            dovizKuru: 'inv-exchange-rate',
            faturaTipi: 'inv-type',
            vknTckn: 'recipient-vkn',
            vergiDairesi: 'recipient-vergi-dairesi',
            aliciUnvan: 'recipient-unvan',
            aliciAdi: 'recipient-adi',
            aliciSoyadi: 'recipient-soyadi',
            mahalleSemtIlce: 'recipient-mahalle',
            sehir: 'recipient-sehir',
            ulke: 'recipient-ulke',
            adres: 'recipient-address',
            siparisNumarasi: 'inv-siparis-numarasi',
            siparisTarihi: 'inv-siparis-tarihi',
            irsaliyeNumarasi: 'inv-irsaliye-numarasi',
            irsaliyeTarihi: 'inv-irsaliye-tarihi',
            fisNo: 'inv-fis-no',
            fisTarihi: 'inv-fis-tarihi',
            fisSaati: 'inv-fis-saati',
            fisTipi: 'inv-fis-tipi',
            zRaporNo: 'inv-z-rapor-no',
            okcSeriNo: 'inv-okc-seri-no',
            binaAdi: 'recipient-bina-adi',
            binaNo: 'recipient-bina-no',
            kapiNo: 'recipient-kapi-no',
            kasabaKoy: 'recipient-kasaba-koy',
            postaKodu: 'recipient-posta-kodu',
            tel: 'recipient-tel',
            fax: 'recipient-fax',
            eposta: 'recipient-email',
            notlar: 'inv-notes'
        };
        return map[jsonKey] || jsonKey; // Fallback to jsonKey if not in map
    }

    // --- EXISTING LOGIC (LOGIN, LIST, DOWNLOAD etc.) ---

    function renderInvoices(invoices) {
        const list = document.getElementById('invoice-list');
        if (!invoices || invoices.length === 0) {
            list.innerHTML = '<div class="card" style="text-align:center;">Fatura bulunamadı.</div>';
            document.getElementById('bulk-download-btn').style.display = 'none';
            document.getElementById('download-selected-btn').style.display = 'none';
            return;
        }
        let tableHtml = `<table class="table-invoices">
            <thead>
                <tr>
                    <th><input type="checkbox" id="select-all-invoices"></th>
                    <th>Fatura No</th>
                    <th>Tarih</th>
                    <th>Durum</th>
                    <th>Alıcı</th>
                    <!-- <th>Ürün/Hizmet</th> -->
                    <th>Tutar</th>
                    <th>KDV Oranı</th>
                    <th>İşlem</th>
                </tr>
            </thead>
            <tbody>`;
        invoices.forEach((inv) => {
            let actionButtons = '';
            // Always show view button
            actionButtons += `<button class="view-btn btn-info" data-uuid="${inv.uuid}" data-status="${inv.status}"><i class="fas fa-eye"></i></button>`;

            if (inv.status === 'Taslak') {
                actionButtons += `
                    <button class="edit-btn btn-secondary" data-uuid="${inv.uuid}" style="margin-left: 5px;"><i class="fas fa-edit"></i></button>
                    <button class="sign-btn btn-outline" data-uuid="${inv.uuid}" style="margin-left: 5px;"><i class="fas fa-signature"></i></button>
                    <button class="delete-btn btn-danger" data-uuid="${inv.uuid}" style="margin-left: 5px;"><i class="fas fa-trash"></i></button>
                `;
            } else if (inv.status === 'Silinmiş') {
                actionButtons += '<span class="status-badge status-silinmis" style="margin-left: 5px;">Silinmiş</span>';
            } else if (inv.status === 'İptal Edilmiş') {
                actionButtons += '<span class="status-badge status-iptal" style="margin-left: 5px;">İptal Edilmiş</span>';
            }
            tableHtml += `<tr data-uuid="${inv.uuid}">
                <td><input type="checkbox" class="invoice-checkbox" data-uuid="${inv.uuid}" data-alici-vkn-tckn="${inv.aliciVknTckn || inv.vknTckn || ''}" data-ettn="${inv.ettn || inv.uuid || ''}" data-email="${inv.aliciEmail || inv.email || ''}"></td>
                <td>${inv.no || 'Taslak'}</td>
                <td>${inv.date}</td>
                <td><span class="status-badge status-${inv.status.toLowerCase().replace(/\s+/g, '-')}">${inv.status}</span></td>
                <td>${inv.recipient || ''}</td>
                <!-- <td class="invoice-product">${inv.urunAdi || ''}</td> -->
                <td class="invoice-total">${inv.total ? formatCurrency(inv.total) : '<span style="color: #999;">Yükle</span>'}</td>
                <td class="invoice-kdv">${inv.kdvOrani ? inv.kdvOrani + '%' : ''}</td>
                <td class="action-cell">
                    ${actionButtons}
                </td>
            </tr>`;
        });
        tableHtml += '</tbody></table>';
        list.innerHTML = tableHtml;
        document.getElementById('bulk-download-btn').style.display = 'inline-flex';
        document.getElementById('send-email-btn').style.display = 'inline-flex';
        document.getElementById('download-selected-btn').style.display = 'inline-flex';
        document.getElementById('bulk-sms-approve-btn').style.display = 'inline-flex';

        // Tutarları Yükle butonunu göster
        const loadAmountsBtn = document.getElementById('load-amounts-btn');
        if (loadAmountsBtn) {
            loadAmountsBtn.style.display = 'inline-flex';
        }

        // Tümünü seç checkbox'ı için event listener
        const selectAllCheckbox = document.getElementById('select-all-invoices');
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function () {
                const checkboxes = document.querySelectorAll('.invoice-checkbox');
                checkboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
                updateBulkActionButtons();
            });
        }

        // Her checkbox için event listener
        const invoiceCheckboxes = document.querySelectorAll('.invoice-checkbox');
        invoiceCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function () {
                updateBulkActionButtons();

                // Tümünü seç checkbox'ını güncelle
                const allCheckboxes = document.querySelectorAll('.invoice-checkbox');
                const checkedCheckboxes = document.querySelectorAll('.invoice-checkbox:checked');
                const selectAllCheckbox = document.getElementById('select-all-invoices');

                if (selectAllCheckbox) {
                    selectAllCheckbox.checked = allCheckboxes.length === checkedCheckboxes.length;
                    selectAllCheckbox.indeterminate = checkedCheckboxes.length > 0 && checkedCheckboxes.length < allCheckboxes.length;
                }
            });
        });

        function updateBulkActionButtons() {
            const checkedCheckboxes = document.querySelectorAll('.invoice-checkbox:checked');
            const bulkSmsBtn = document.getElementById('bulk-sms-approve-btn');
            const sendEmailBtn = document.getElementById('send-email-btn');

            if (checkedCheckboxes.length > 0) {
                bulkSmsBtn.innerHTML = `<i class="fas fa-signature"></i> Seçilenleri Toplu Onayla (${checkedCheckboxes.length})`;
                if (sendEmailBtn) {
                    sendEmailBtn.innerHTML = `<i class="fas fa-envelope"></i> Seçilenlere Mail Gönder (${checkedCheckboxes.length})`;
                }
            } else {
                bulkSmsBtn.innerHTML = '<i class="fas fa-signature"></i> Seçilenleri Toplu Onayla (SMS)';
                if (sendEmailBtn) {
                    sendEmailBtn.innerHTML = '<i class="fas fa-envelope"></i> Seçilenlere Mail Gönder';
                }
            }
        }
        const selectAllInvoicesCheckbox = document.getElementById('select-all-invoices');
        if (selectAllInvoicesCheckbox) {
            selectAllInvoicesCheckbox.addEventListener('change', (e) => {
                list.querySelectorAll('.invoice-checkbox').forEach(cb => cb.checked = e.target.checked);
            });
        }
    }

    function getSelectedInvoiceUuids() {
        return Array.from(document.querySelectorAll('.invoice-checkbox:checked')).map(cb => cb.dataset.uuid);
    }

    function fetchInvoices(params) {
        fetch(`${BACKEND_URL}?${params.toString()}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    renderInvoices(data.invoices);
                } else {
                    showToast(data.message, 'error');
                }
            });
    }

    // Filter Invoices Button
    const filterInvoicesBtn = document.getElementById('filter-invoices-btn');
    if (filterInvoicesBtn) {
        filterInvoicesBtn.addEventListener('click', () => {
            const params = new URLSearchParams({
                path: 'list_invoices',
                start: document.getElementById('start-date').value,
                end: document.getElementById('end-date').value,
                status: document.getElementById('status-filter').value,
                search: document.getElementById('search-box').value
            });
            // If all filter fields are empty, fetch all invoices
            if (!params.get('start') && !params.get('end') && !params.get('status') && !params.get('search')) {
                params.delete('start');
                params.delete('end');
                params.delete('status');
                params.delete('search');
            }
            fetchInvoices(params);
        });
    }



    document.getElementById('bulk-download-btn').addEventListener('click', () => {
        const uuids = getSelectedInvoiceUuids();
        if (uuids.length === 0) return showToast('Lütfen fatura seçin.', 'warning');

        const bulkDownloadBtn = document.getElementById('bulk-download-btn');
        bulkDownloadBtn.disabled = true; // Disable button to prevent double clicks

        showToast('Toplu zip hazırlanıyor...', 'info');
        document.getElementById('progress').innerText = 'Toplu zip hazırlanıyor...';
        fetch(BACKEND_URL + '?path=bulk_download', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ uuids })
        })
            .then(async res => {
                if (!res.ok) {
                    const errorData = await res.json().catch(() => ({ message: 'Sunucu hatası.' }));
                    throw new Error(errorData.message || 'İndirme başarısız!');
                }
                const blob = await res.blob();
                if (!blob.type.includes('zip')) {
                    const text = await blob.text();
                    throw new Error(JSON.parse(text).message || 'Geçersiz dosya tipi.');
                }
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'faturalar.zip';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
                showToast('İndirme tamamlandı.', 'success');
            })
            .catch(err => showToast(`Hata: ${err.message}`, 'error'))
            .finally(() => {
                bulkDownloadBtn.disabled = false; // Re-enable button
            });
    });

    document.getElementById('download-selected-btn').addEventListener('click', async () => {
        const uuids = getSelectedInvoiceUuids();
        if (uuids.length === 0) return showToast('Lütfen fatura seçin.', 'warning');

        const downloadSelectedBtn = document.getElementById('download-selected-btn');
        downloadSelectedBtn.disabled = true; // Disable button

        showToast('Seçilen faturalar indiriliyor...', 'info');

        for (const uuid of uuids) {
            try {
                const link = document.createElement('a');
                link.href = `${BACKEND_URL}?path=download_zip&uuid=${uuid}`;
                link.download = `${uuid}.zip`;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                // Add a small delay to prevent browser from blocking multiple downloads
                await new Promise(resolve => setTimeout(resolve, 500));
            } catch (error) {
                showToast(`Fatura ${uuid} indirilirken bir hata oluştu.`, 'error');
            }
        }
        showToast('Seçilen faturaların indirilmesi tamamlandı.', 'success');
        downloadSelectedBtn.disabled = false; // Re-enable button
    });
});

function handleSignButtonClick(uuid) {
    showToast('SMS gönderme işlemi başlatılıyor...', 'info');
    fetch(BACKEND_URL + '?path=start_signing', { method: 'POST' })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                document.getElementById('sms-uuid').value = uuid;
                document.getElementById('sms-oid').value = data.oid;
                document.getElementById('sms-modal').style.display = 'flex';
                showToast('SMS gönderildi. Lütfen kodu girin.', 'success');
            } else {
                showToast(`Hata: ${data.message}`, 'error');
            }
        })
        .catch(() => showToast('Bir hata oluştu.', 'error'));
}

function handleSmsFormSubmit(e) {
    e.preventDefault();
    const uuid = document.getElementById('sms-uuid').value;
    const oid = document.getElementById('sms-oid').value;
    const code = document.getElementById('sms-code').value;
    const submitBtn = document.getElementById('submit-sms-btn');
    submitBtn.disabled = true;
    showToast('Fatura imzalanıyor...', 'info');

    fetch(BACKEND_URL + '?path=complete_signing', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ uuid, oid, code })
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showToast('Fatura başarıyla imzalandı!', 'success');
                setTimeout(() => {
                    document.getElementById('sms-modal').style.display = 'none';
                    document.getElementById('list-invoices-btn').click();
                }, 1500);
            } else {
                showToast(`Hata: ${data.message}`, 'error');
            }
        })
        .catch(() => showToast('Bir hata oluştu.', 'error'))
        .finally(() => submitBtn.disabled = false);
}

function handleDeleteButtonClick(uuid) {
    if (!confirm('Bu fatura taslağını kalıcı olarak silmek istediğinizden emin misiniz? Bu işlem geri alınamaz ve fatura taslağını sistemden tamamen kaldırır.')) {
        return;
    }

    showToast('Taslak siliniyor...', 'info');

    fetch(BACKEND_URL + '?path=delete_invoice', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ uuid })
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showToast('Taslak başarıyla silindi!', 'success');
                // After deletion, refresh the list by clicking the filter button
                const filterButton = document.getElementById('filter-invoices-btn');
                if (filterButton) {
                    filterButton.click();
                } else {
                    // Fallback if the filter button isn't there for some reason
                    document.getElementById('list-invoices-btn').click();
                }
            } else {
                showToast(`Hata: ${data.message}`, 'error');
            }
        })
        .catch(error => {
            showToast('Silme sırasında bir hata oluştu.', 'error');
        });
}

function handleViewButtonClick(uuid, status) {
    showToast('Fatura görüntüleniyor...', 'info');
    fetch(BACKEND_URL + '?path=view_invoice_html', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ uuid, status })
    })
        .then(res => res.text()) // Get response as text (HTML)
        .then(htmlContent => {
            const newWindow = window.open('', '_blank');
            newWindow.document.write(htmlContent);
            newWindow.document.close();
            showToast('Fatura başarıyla görüntülendi.', 'success');
        })
        .catch(error => {
            showToast('Fatura görüntülenirken bir hata oluştu.', 'error');
        });
}

// --- Rapor Sistemi Fonksiyonları ---

function initializeReportSystem() {
    // Rapor butonuna tıklama
    document.getElementById('report-btn').addEventListener('click', () => {
        document.getElementById('report-modal').style.display = 'flex';
        // Varsayılan tarihleri ayarla (son 30 gün)
        const today = new Date();
        const thirtyDaysAgo = new Date(today.getTime() - (30 * 24 * 60 * 60 * 1000));

        document.getElementById('report-start-date').value = thirtyDaysAgo.toISOString().split('T')[0];
        document.getElementById('report-end-date').value = today.toISOString().split('T')[0];
    });

    // Rapor modal kapatma
    document.querySelector('.close-report-btn').addEventListener('click', () => {
        document.getElementById('report-modal').style.display = 'none';
        document.getElementById('report-results').style.display = 'none';
        document.getElementById('report-message').textContent = '';
    });

    // Rapor formu gönderimi
    document.getElementById('report-form').addEventListener('submit', handleReportFormSubmit);

    // Önizleme butonu
    document.getElementById('preview-report-btn').addEventListener('click', handlePreviewReport);
}

function handleReportFormSubmit(e) {
    e.preventDefault();

    const startDate = document.getElementById('report-start-date').value;
    const endDate = document.getElementById('report-end-date').value;

    if (!startDate || !endDate) {
        showReportMessage('Lütfen başlangıç ve bitiş tarihlerini seçin.', 'error');
        return;
    }

    if (new Date(startDate) > new Date(endDate)) {
        showReportMessage('Başlangıç tarihi bitiş tarihinden büyük olamaz.', 'error');
        return;
    }

    generateReport(startDate, endDate, 'excel');
}

function handlePreviewReport() {
    const startDate = document.getElementById('report-start-date').value;
    const endDate = document.getElementById('report-end-date').value;

    if (!startDate || !endDate) {
        showReportMessage('Lütfen başlangıç ve bitiş tarihlerini seçin.', 'error');
        return;
    }

    if (new Date(startDate) > new Date(endDate)) {
        showReportMessage('Başlangıç tarihi bitiş tarihinden büyük olamaz.', 'error');
        return;
    }

    generateReport(startDate, endDate, 'json');
}

function generateReport(startDate, endDate, reportType) {
    const generateBtn = document.getElementById('generate-report-btn');
    const previewBtn = document.getElementById('preview-report-btn');

    generateBtn.disabled = true;
    previewBtn.disabled = true;

    const loadingText = reportType === 'excel' ? 'Excel raporu oluşturuluyor...' : 'Rapor önizleniyor...';
    showReportMessage(loadingText, 'info');

    fetch(BACKEND_URL + '?path=generate_report', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            startDate: startDate,
            endDate: endDate,
            reportType: reportType
        })
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (reportType === 'excel') {
                    // Excel dosyasını indir
                    downloadExcelFile(data.fileContent, data.filename);
                    showReportMessage(data.message, 'success');
                } else {
                    // JSON önizleme
                    displayReportPreview(data);
                    showReportMessage(data.message, 'success');
                }
            } else {
                showReportMessage(`Hata: ${data.message}`, 'error');
            }
        })
        .catch(error => {
            showReportMessage('Rapor oluşturulurken bir hata oluştu.', 'error');
        })
        .finally(() => {
            generateBtn.disabled = false;
            previewBtn.disabled = false;
        });
}

function downloadExcelFile(base64Content, filename) {
    const byteCharacters = atob(base64Content);
    const byteNumbers = new Array(byteCharacters.length);
    for (let i = 0; i < byteCharacters.length; i++) {
        byteNumbers[i] = byteCharacters.charCodeAt(i);
    }
    const byteArray = new Uint8Array(byteNumbers);
    const blob = new Blob([byteArray], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });

    const link = document.createElement('a');
    link.href = window.URL.createObjectURL(blob);
    link.download = filename;
    link.click();
    window.URL.revokeObjectURL(link.href);
}

function displayReportPreview(data) {
    const resultsDiv = document.getElementById('report-results');
    const statsDiv = document.getElementById('report-stats');
    const tableBody = document.getElementById('report-table-body');

    // İstatistikleri güncelle
    document.getElementById('total-invoices').textContent = data.summary.invoiceCount || 0;
    document.getElementById('total-mal-hizmet').textContent = formatCurrency(data.summary.totalMalHizmet || 0);
    document.getElementById('total-vergiler-dahil').textContent = formatCurrency(data.summary.totalVergilerDahil || 0);

    // Tabloyu temizle ve doldur
    tableBody.innerHTML = '';

    if (data.data && data.data.length > 0) {
        data.data.forEach(invoice => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${invoice.faturaNo || ''}</td>
                <td>${invoice.faturaTarihi || ''}</td>
                <td>${invoice.aliciUnvan || ''}</td>
                <td>${invoice.urunAdi || ''}</td>
                <td>${formatCurrency(invoice.malHizmetToplamTutari || 0)}</td>
                <td>${formatCurrency(invoice.vergilerDahilToplamTutar || 0)}</td>
                <td>${invoice.kdvOrani ? invoice.kdvOrani + '%' : ''}</td>
                <td>${invoice.paraBirimi || 'TRY'}</td>
            `;
            tableBody.appendChild(row);
        });
    } else {
        const row = document.createElement('tr');
        row.innerHTML = '<td colspan="6" style="text-align: center; padding: 20px;">Bu tarih aralığında onaylanmış fatura bulunamadı.</td>';
        tableBody.appendChild(row);
    }

    resultsDiv.style.display = 'block';
}

function showReportMessage(message, type = 'info') {
    const messageDiv = document.getElementById('report-message');
    messageDiv.textContent = message;
    messageDiv.setAttribute('data-type', type);
}

function formatCurrency(amount) {
    if (!amount || isNaN(amount) || amount <= 0) {
        return '';
    }
    return new Intl.NumberFormat('tr-TR', {
        style: 'currency',
        currency: 'TRY',
        minimumFractionDigits: 2
    }).format(amount);
}





// ============================================================================
// TUTARLARI YÜKLE BUTONU
// ============================================================================

document.getElementById('load-amounts-btn')?.addEventListener('click', async function () {
    const loadAmountsBtn = this;

    // Tablodaki tüm faturaların UUID'lerini topla
    const rows = document.querySelectorAll('.table-invoices tbody tr');
    const uuids = [];

    rows.forEach(row => {
        const uuid = row.dataset.uuid;
        if (uuid) {
            uuids.push(uuid);
        }
    });

    if (uuids.length === 0) {
        showToast('Yüklenecek fatura bulunamadı!', 'warning');
        return;
    }

    // Butonu devre dışı bırak
    loadAmountsBtn.disabled = true;

    // Progress göstergesi oluştur
    const progressDiv = document.getElementById('progress');
    progressDiv.innerHTML = `
        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 10px 0;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                <span><strong>Tutarlar Yükleniyor...</strong></span>
                <span id="amount-progress-text">0%</span>
            </div>
            <div style="background: #e0e0e0; border-radius: 10px; overflow: hidden; height: 20px;">
                <div id="amount-progress-bar" style="background: linear-gradient(90deg, #007bff, #0056b3); height: 100%; width: 0%; transition: width 0.3s;"></div>
            </div>
            <div style="margin-top: 5px; font-size: 0.9em; color: #666;">
                <span id="amount-progress-detail">Hazırlanıyor...</span>
            </div>
        </div>
    `;

    let totalProcessed = 0;
    let allAmounts = {};
    
    // Toplam batch sayısını hesapla
    const batchSize = 10;
    const totalBatches = Math.ceil(uuids.length / batchSize);

    try {
        // TÜM BATCH'LERİ PARALEL OLARAK BAŞLAT
        const batchPromises = [];
        
        for (let batchIndex = 0; batchIndex < totalBatches; batchIndex++) {
            const promise = fetch(BACKEND_URL + '?path=load_invoice_amounts', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    uuids: uuids,
                    batchIndex: batchIndex
                })
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message || 'Bilinmeyen hata');
                }
                
                // Bu batch'in tutarlarını birleştir
                allAmounts = { ...allAmounts, ...data.amounts };
                totalProcessed += data.processed;
                
                // Progress güncelle
                const progressBar = document.getElementById('amount-progress-bar');
                const progressText = document.getElementById('amount-progress-text');
                const progressDetail = document.getElementById('amount-progress-detail');
                
                if (progressBar && progressText && progressDetail) {
                    const currentProgress = Math.round((totalProcessed / uuids.length) * 100);
                    progressBar.style.width = currentProgress + '%';
                    progressText.textContent = currentProgress + '%';
                    progressDetail.textContent = `${totalProcessed} / ${uuids.length} fatura işlendi`;
                }
                
                // Tablodaki tutarları güncelle (her batch tamamlandıkça)
                rows.forEach(row => {
                    const uuid = row.dataset.uuid;
                    if (allAmounts[uuid]) {
                        const amountData = allAmounts[uuid];
                        
                        // Tutar kolonunu güncelle
                        const totalCell = row.querySelector('.invoice-total');
                        if (totalCell && amountData.total) {
                            totalCell.textContent = parseFloat(amountData.total).toFixed(2) + ' ₺';
                            totalCell.style.color = '#28a745';
                            totalCell.style.fontWeight = 'bold';
                        }
                        
                        // KDV kolonunu güncelle
                        const kdvCell = row.querySelector('.invoice-kdv');
                        if (kdvCell && amountData.kdvOrani) {
                            kdvCell.textContent = '%' + amountData.kdvOrani;
                        }
                    }
                });
                
                return data;
            });
            
            batchPromises.push(promise);
        }
        
        // TÜM BATCH'LERİN BİTMESİNİ BEKLE
        await Promise.all(batchPromises);

        // Başarı mesajı
        progressDiv.innerHTML = `
            <div style="background: #d4edda; padding: 15px; border-radius: 8px; margin: 10px 0; color: #155724;">
                <i class="fas fa-check-circle"></i> <strong>${totalProcessed} faturanın tutarı başarıyla yüklendi!</strong>
            </div>
        `;

        showToast(`${totalProcessed} faturanın tutarı yüklendi!`, 'success');

        // Butonu gizle
        loadAmountsBtn.style.display = 'none';

        // 3 saniye sonra progress mesajını temizle
        setTimeout(() => {
            progressDiv.innerHTML = '';
        }, 3000);

    } catch (error) {
        console.error('Tutarlar yüklenirken hata:', error);

        progressDiv.innerHTML = `
            <div style="background: #f8d7da; padding: 15px; border-radius: 8px; margin: 10px 0; color: #721c24;">
                <i class="fas fa-exclamation-circle"></i> <strong>Hata:</strong> ${error.message}
            </div>
        `;

        showToast('Tutarlar yüklenirken bir hata oluştu!', 'error');
        loadAmountsBtn.disabled = false;
        loadAmountsBtn.innerHTML = '<i class="fas fa-dollar-sign"></i> Tutarları Yükle';
    }
});
