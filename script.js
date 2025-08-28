document.addEventListener('DOMContentLoaded', () => {

    // آدرس API که فایل‌های PHP روی آن قرار دارند
    const API_URL = 'https://group2.storage.c2.liara.space/mahdi_sakhteman/api/';

    // تمام عناصر صفحه که با آنها کار می‌کنیم
    const DOM = {
        tableBody: document.getElementById('table-body'),
        totalConsumption: document.getElementById('total-consumption'),
        totalCalculatedAmount: document.getElementById('total-calculated-amount'),
        differenceAmount: document.getElementById('difference-amount'),
        periodNameInput: document.getElementById('period-name'),
        billDateInput: document.getElementById('bill-date'),
        totalBillAmountInput: document.getElementById('total-bill-amount'),
        settingsModal: document.getElementById('settings-modal'),
        historyModal: document.getElementById('history-modal'),
        messageModal: document.getElementById('message-modal'),
        manualSettings: document.getElementById('manual-settings'),
        autoSettings: document.getElementById('auto-settings'),
        manualTiersContainer: document.getElementById('manual-tiers-container'),
        autoTiersContainer: document.getElementById('auto-tiers-container'),
        historyContainer: document.getElementById('history-container'),
        messageText: document.getElementById('message-text'),
        messageButtons: document.getElementById('message-buttons'),
        restoreInput: document.getElementById('restore-input'),
        actionsMenuBtn: document.getElementById('actions-menu-btn'),
        actionsMenu: document.getElementById('actions-menu'),
    };

    // متغیر اصلی برای نگهداری تمام داده‌های برنامه
    let state = {};

    // توابع کمکی برای کار با اعداد فارسی و انگلیسی
    const toEnglishDigits = str => String(str).replace(/[\u0660-\u0669\u06F0-\u06F9]/g, c => c.charCodeAt(0) & 0xf);
    const getRawNumber = formattedStr => parseFloat(toEnglishDigits(String(formattedStr)).replace(/[,٬]/g, '')) || 0;
    const formatNumber = num => isNaN(num) ? '۰' : Math.round(num).toLocaleString('fa-IR');

    // تابع نمایش پیام سفارشی
    function showMessage(text, buttons = [{ text: 'باشه', class: 'bg-blue-500', resolveValue: true }]) {
        return new Promise(resolve => {
            DOM.messageText.textContent = text;
            DOM.messageButtons.innerHTML = '';
            buttons.forEach(btnInfo => {
                const button = document.createElement('button');
                button.textContent = btnInfo.text;
                button.className = `text-white px-6 py-2 rounded-lg shadow hover:opacity-90 transition ${btnInfo.class}`;
                button.onclick = () => { DOM.messageModal.classList.add('hidden'); resolve(btnInfo.resolveValue); };
                DOM.messageButtons.appendChild(button);
            });
            DOM.messageModal.classList.remove('hidden');
        });
    }

    // ارتباط با سرور برای ذخیره اطلاعات
    async function saveState() {
        try {
            const response = await fetch(API_URL + 'save_data.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(state),
            });
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
        } catch (error) {
            console.error('Failed to save state to server:', error);
            showMessage('خطا در ذخیره اطلاعات در سرور. تغییرات شما ممکن است ذخیره نشود.');
        }
    }

    // ارتباط با سرور برای خواندن اطلاعات
    async function loadState() {
        try {
            const response = await fetch(API_URL + 'get_data.php');
            if (!response.ok) throw new Error('Network response was not ok');
            const serverState = await response.json();
            state = serverState;
        } catch (error) {
            console.error('Failed to load state from server:', error);
            showMessage('خطا در ارتباط با سرور. لطفا از اتصال اینترنت خود مطمئن شوید و صفحه را رفرش کنید.');
            // در صورت خطا، یک حالت پیش‌فرض خالی ایجاد می‌کنیم تا برنامه از کار نیفتد
            state = {
                units: Array.from({ length: 66 }, (_, i) => ({ id: i + 1, name: i === 64 ? 'عمومی' : (i === 65 ? 'تجاری' : `واحد ${i + 1}`), prev: 0, current: 0 })),
                periodInfo: { name: '', date: '', totalBill: 0 },
                settings: { calculationMode: 'manual', manualTiers: [], autoTiers: [] }
            };
        }
    }
    
    // (بقیه توابع بدون تغییر باقی می‌مانند)
    // ...

    async function initializeApp() {
        await loadState();
        setupEventListeners();
        render();
        jalaliDatepicker.startWatch({ 
            persianDigits: true,
            showTodayBtn: true,
            showEmptyBtn: true,
        });
    }

    initializeApp();
});
