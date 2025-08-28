// آدرس backend PHP
const API_BASE = 'https://group2.storage.c2.liara.space/mahdi_sakhteman/api/';
const GET_DATA_URL = API_BASE + 'get_data.php';
const SAVE_DATA_URL = API_BASE + 'save_data.php';

let state = {
    units: [],
    settings: {
        calculationMode: 'manual',
        manualTiers: [],
        autoTiers: []
    },
    periodInfo: { name: '', date: '', totalBill: 0 },
    history: [] // تاریخچه محلی، چون backend جدول جداگانه‌ای برای دوره‌ها ندارد
};

// لود داده‌ها از backend
async function loadData() {
    try {
        const response = await fetch(GET_DATA_URL);
        const data = await response.json();
        state.units = data.units || [];
        state.settings = data.settings || state.settings;
        // periodInfo در backend هاردکد است، پس محلی مدیریت می‌کنیم یا اگر نیاز است، backend را گسترش دهید
        // برای حالا، از localStorage برای periodInfo و history استفاده می‌کنیم
        const localState = JSON.parse(localStorage.getItem('appState')) || {};
        state.periodInfo = localState.periodInfo || state.periodInfo;
        state.history = localState.history || [];
        document.getElementById('periodName').value = state.periodInfo.name || '';
        document.getElementById('billDate').value = state.periodInfo.date || '';
        document.getElementById('totalBill').value = state.periodInfo.totalBill || 0;
        renderUnitsTable();
        updateCalculationMode();
    } catch (error) {
        console.error('خطا در لود داده‌ها:', error);
    }
}

// ذخیره داده‌ها در backend و محلی
async function saveData() {
    try {
        const response = await fetch(SAVE_DATA_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                units: state.units,
                settings: state.settings
            })
        });
        const result = await response.json();
        if (result.success) {
            // ذخیره محلی برای periodInfo و history
            localStorage.setItem('appState', JSON.stringify({
                periodInfo: state.periodInfo,
                history: state.history
            }));
            alert('اطلاعات ذخیره شد.');
        } else {
            alert('خطا در ذخیره: ' + result.message);
        }
    } catch (error) {
        console.error('خطا در ذخیره داده‌ها:', error);
    }
}

// رندر جدول واحدها
function renderUnitsTable() {
    const tbody = document.querySelector('#unitsTable tbody');
    tbody.innerHTML = '';
    let totalAmount = 0;
    state.units.forEach((unit, index) => {
        const consumption = unit.current - unit.prev;
        const tier = calculateTier(consumption);
        const amount = calculateAmount(consumption);
        totalAmount += amount;
        const row = `
            <tr>
                <td>${unit.id}</td>
                <td><input type="text" value="${unit.name}" oninput="updateUnit(${index}, 'name', this.value)"></td>
                <td><input type="number" value="${unit.prev}" oninput="updateUnit(${index}, 'prev', this.value)"></td>
                <td><input type="number" value="${unit.current}" oninput="updateUnit(${index}, 'current', this.value)"></td>
                <td>${consumption.toFixed(2)}</td>
                <td>${tier}</td>
                <td>${amount.toFixed(0)}</td>
            </tr>
        `;
        tbody.innerHTML += row;
    });
    const difference = state.periodInfo.totalBill - totalAmount;
    document.getElementById('totalAmount').textContent = totalAmount.toFixed(0);
    document.getElementById('difference').textContent = difference.toFixed(0);
}

// بروزرسانی واحد
function updateUnit(index, field, value) {
    state.units[index][field] = field === 'name' ? value : parseFloat(value) || 0;
    renderUnitsTable();
}

// محاسبه پله مصرف
function calculateTier(consumption) {
    if (state.settings.calculationMode === 'manual') {
        let tier = 1;
        let cumulative = 0;
        for (let t of state.settings.manualTiers) {
            cumulative += t.max - (t.min || 0);
            if (consumption <= cumulative) return tier;
            tier++;
        }
        return tier;
    } else {
        return 'خودکار';
    }
}

// محاسبه مبلغ
function calculateAmount(consumption) {
    if (state.settings.calculationMode === 'manual') {
        let amount = 0;
        let remaining = consumption;
        for (let t of state.settings.manualTiers) {
            const tierMax = t.max - (t.min || 0);
            const tierConsumption = Math.min(remaining, tierMax);
            amount += tierConsumption * t.rate;
            remaining -= tierConsumption;
            if (remaining <= 0) break;
        }
        if (remaining > 0) {
            // اگر مصرف بیشتر از آخرین پلکان، با نرخ آخرین پلکان محاسبه شود
            amount += remaining * (state.settings.manualTiers[state.settings.manualTiers.length - 1]?.rate || 0);
        }
        return amount;
    } else {
        // حالت خودکار: تقسیم مبلغ کل بر اساس مصرف
        const totalConsumption = state.units.reduce((sum, u) => sum + (u.current - u.prev), 0);
        if (totalConsumption === 0) return 0;
        let baseRate = state.periodInfo.totalBill / totalConsumption;
        // اعمال ضرایب اگر تعریف شده
        let adjustedRate = baseRate;
        for (let t of state.settings.autoTiers) {
            if (consumption >= t.min && consumption < t.max) {
                adjustedRate *= t.rate;
                break;
            }
        }
        return consumption * adjustedRate;
    }
}

// مدیریت modalها
const settingsModal = document.getElementById('settingsModal');
const historyModal = document.getElementById('historyModal');
const closes = document.getElementsByClassName('close');

document.getElementById('settingsBtn').onclick = () => settingsModal.style.display = 'block';
document.getElementById('historyBtn').onclick = () => {
    renderHistory();
    historyModal.style.display = 'block';
};

for (let close of closes) {
    close.onclick = () => {
        settingsModal.style.display = 'none';
        historyModal.style.display = 'none';
    };
}

window.onclick = (event) => {
    if (event.target == settingsModal) settingsModal.style.display = 'none';
    if (event.target == historyModal) historyModal.style.display = 'none';
};

// مدیریت حالت محاسبه
const radios = document.querySelectorAll('input[name="calcMode"]');
radios.forEach(radio => {
    radio.onchange = () => {
        state.settings.calculationMode = radio.value;
        updateCalculationMode();
        renderUnitsTable();
    };
});

function updateCalculationMode() {
    const selected = document.querySelector(`input[name="calcMode"]:checked`).value;
    state.settings.calculationMode = selected;
    document.getElementById('manualTiersSection').style.display = selected === 'manual' ? 'block' : 'none';
    document.getElementById('autoTiersSection').style.display = selected === 'auto' ? 'block' : 'none';
    renderTiers('manual');
    renderTiers('auto');
}

// رندر پلکان‌ها
function renderTiers(mode) {
    const container = document.getElementById(`${mode}Tiers`);
    container.innerHTML = '';
    state.settings[`${mode}Tiers`].forEach((tier, index) => {
        const div = document.createElement('div');
        div.className = 'tier';
        div.innerHTML = `
            <label>از:</label><input type="number" value="${tier.min || 0}" oninput="updateTier('${mode}', ${index}, 'min', this.value)">
            <label>تا:</label><input type="number" value="${tier.max || 0}" oninput="updateTier('${mode}', ${index}, 'max', this.value)">
            <label>${mode === 'manual' ? 'تعرفه (تومان/مترمکعب)' : 'ضریب'}:</label><input type="number" value="${tier.rate || 0}" oninput="updateTier('${mode}', ${index}, 'rate', this.value)">
            <button onclick="removeTier('${mode}', ${index})">حذف</button>
        `;
        container.appendChild(div);
    });
}

// بروزرسانی پلکان
function updateTier(mode, index, field, value) {
    state.settings[`${mode}Tiers`][index][field] = parseFloat(value) || 0;
    renderUnitsTable();
}

// افزودن پلکان
document.getElementById('addManualTier').onclick = () => addTier('manual');
document.getElementById('addAutoTier').onclick = () => addTier('auto');

function addTier(mode) {
    state.settings[`${mode}Tiers`].push({ min: 0, max: 0, rate: 0 });
    renderTiers(mode);
}

// حذف پلکان
function removeTier(mode, index) {
    state.settings[`${mode}Tiers`].splice(index, 1);
    renderTiers(mode);
    renderUnitsTable();
}

// ذخیره تنظیمات
document.getElementById('saveSettings').onclick = async () => {
    await saveData();
    settingsModal.style.display = 'none';
    renderUnitsTable();
};

// افزودن واحد جدید (id را محلی مدیریت می‌کنیم، backend باید insert کند اما فعلی update است)
document.getElementById('addUnit').onclick = () => {
    const newId = state.units.length > 0 ? Math.max(...state.units.map(u => u.id)) + 1 : 1;
    state.units.push({ id: newId, name: '', prev: 0, current: 0 });
    renderUnitsTable();
    // توجه: برای insert واقعی، backend را گسترش دهید (اضافه کردن INSERT در save_data.php اگر id موجود نباشد)
};

// ذخیره دوره فعلی
document.getElementById('savePeriod').onclick = async () => {
    state.periodInfo = {
        name: document.getElementById('periodName').value,
        date: document.getElementById('billDate').value,
        totalBill: parseFloat(document.getElementById('totalBill').value) || 0
    };
    await saveData();
    state.history.push({ ...state.periodInfo, units: state.units.map(u => ({...u})) });
};

// شروع دوره جدید
document.getElementById('newPeriod').onclick = async () => {
    state.units.forEach(u => u.prev = u.current);
    state.periodInfo = { name: '', date: '', totalBill: 0 };
    document.getElementById('periodName').value = '';
    document.getElementById('billDate').value = '';
    document.getElementById('totalBill').value = 0;
    await saveData();
    renderUnitsTable();
};

// رندر تاریخچه
function renderHistory() {
    const list = document.getElementById('historyList');
    list.innerHTML = '';
    state.history.forEach((period, index) => {
        const li = document.createElement('li');
        li.innerHTML = `${period.name} - ${period.date} - مبلغ: ${period.totalBill} <button onclick="loadHistoryPeriod(${index})">بارگذاری</button>`;
        list.appendChild(li);
    });
}

// بارگذاری دوره از تاریخچه
function loadHistoryPeriod(index) {
    const period = state.history[index];
    state.periodInfo = { ...period };
    state.units = period.units.map(u => ({...u}));
    document.getElementById('periodName').value = period.name;
    document.getElementById('billDate').value = period.date;
    document.getElementById('totalBill').value = period.totalBill;
    renderUnitsTable();
    historyModal.style.display = 'none';
};

// خروجی PDF
document.getElementById('exportPDF').onclick = () => {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    doc.text('مدیریت قبض آب - ' + state.periodInfo.name, 10, 10);
    let y = 20;
    state.units.forEach(u => {
        const consumption = u.current - u.prev;
        const amount = calculateAmount(consumption);
        doc.text(`${u.name}: مصرف ${consumption} - مبلغ ${amount}`, 10, y);
        y += 10;
    });
    doc.save('bill.pdf');
};

// خروجی Excel
document.getElementById('exportExcel').onclick = () => {
    const data = state.units.map(u => {
        const consumption = u.current - u.prev;
        return {
            'شماره واحد': u.id,
            'نام واحد': u.name,
            'قرائت قبلی': u.prev,
            'قرائت فعلی': u.current,
            'مصرف': consumption,
            'مبلغ': calculateAmount(consumption)
        };
    });
    const ws = XLSX.utils.json_to_sheet(data);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Units');
    XLSX.writeFile(wb, 'bill.xlsx');
};

// ورود از Excel
document.getElementById('importExcel').onclick = () => {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = '.xlsx';
    input.onchange = (e) => {
        const file = e.target.files[0];
        const reader = new FileReader();
        reader.onload = (evt) => {
            const data = evt.target.result;
            const wb = XLSX.read(data, { type: 'binary' });
            const ws = wb.Sheets[wb.SheetNames[0]];
            const imported = XLSX.utils.sheet_to_json(ws);
            state.units = imported.map((row, idx) => ({
                id: row['شماره واحد'] || idx + 1,
                name: row['نام واحد'] || '',
                prev: row['قرائت قبلی'] || 0,
                current: row['قرائت فعلی'] || 0
            }));
            renderUnitsTable();
            saveData();
        };
        reader.readAsBinaryString(file);
    };
    input.click();
};

// بروزرسانی هنگام تغییر ورودی‌ها
document.getElementById('totalBill').oninput = (e) => {
    state.periodInfo.totalBill = parseFloat(e.target.value) || 0;
    renderUnitsTable();
};

// لود اولیه
loadData();