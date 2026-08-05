/* ==========================================
   AquaNova — Control Panel View
   CP JavaScript
   ========================================== */

'use strict';

class AquaNovaCP {
    constructor() {
        // Shared state with main dashboard via localStorage
        this.moisture = parseInt(localStorage.getItem('aquanova_moisture')) || 65;
        this.irrigationOn = localStorage.getItem('aquanova_irrigation') === 'true';
        this.irrigationDuration = parseInt(localStorage.getItem('aquanova_duration')) || 0;
        this.irrigationWaterUsed = parseFloat(localStorage.getItem('aquanova_water')) || 0;
        this.irrigationTimer = null;

        this.elements = {
            moistureValue: document.getElementById('cpMoistureValue'),
            moistureStatus: document.getElementById('cpMoistureStatus'),
            moistureBar: document.getElementById('cpMoistureBar'),
            gaugeProgress: document.getElementById('cpGaugeProgress'),
            gaugeGlow: document.getElementById('cpGaugeGlow'),
            toggle: document.getElementById('cpIrrigationToggle'),
            btn: document.getElementById('cpIrrigationBtn'),
            btnText: document.getElementById('cpBtnText'),
            toggleLabel: document.getElementById('cpToggleLabel'),
            duration: document.getElementById('cpDuration'),
            waterUsed: document.getElementById('cpWaterUsed'),
            waterToday: document.getElementById('cpWaterToday'),
            lastReading: document.getElementById('cpLastReading'),
            serialOutput: document.getElementById('cpSerialOutput'),
            waterDrops: document.getElementById('cpWaterDrops'),
            ledTX: document.querySelector('.cp-led.led-tx'),
            ledRX: document.querySelector('.cp-led.led-rx'),
            themeToggle: document.getElementById('themeToggle'),
        };

        this.init();
    }

    init() {
        this.setupTheme();
        this.setupIrrigation();
        this.startMoistureSimulation();
        this.updateUI();
        this.syncWithDashboard();
        this.startLEDBlink();

        // Load irrigation state from localStorage
        if (this.irrigationOn) {
            this.elements.toggle.checked = true;
            this.elements.btn.className = 'cp-irrigation-btn';
            this.elements.btnText.textContent = 'Turn OFF';
            this.elements.toggleLabel.textContent = 'Irrigation ON';
            this.elements.waterDrops.style.opacity = '1';
            this.startIrrigationTimer();
        }

        // Update last reading time
        this.updateLastReading();
    }

    syncWithDashboard() {
        // Sync moisture every 2 seconds from localStorage
        setInterval(() => {
            const storedMoisture = localStorage.getItem('aquanova_moisture');
            if (storedMoisture) {
                this.moisture = parseInt(storedMoisture);
                this.updateUI();
                this.updateLastReading();
            }

            const storedIrrigation = localStorage.getItem('aquanova_irrigation');
            if (storedIrrigation === 'true' && !this.irrigationOn) {
                this.irrigationOn = true;
                this.elements.toggle.checked = true;
                this.elements.btn.className = 'cp-irrigation-btn';
                this.elements.btnText.textContent = 'Turn OFF';
                this.elements.toggleLabel.textContent = 'Irrigation ON';
                this.elements.waterDrops.style.opacity = '1';
                if (!this.irrigationTimer) this.startIrrigationTimer();
            } else if (storedIrrigation === 'false' && this.irrigationOn) {
                this.irrigationOn = false;
                this.elements.toggle.checked = false;
                this.elements.btn.className = 'cp-irrigation-btn off';
                this.elements.btnText.textContent = 'Turn ON';
                this.elements.toggleLabel.textContent = 'Irrigation OFF';
                this.elements.waterDrops.style.opacity = '0';
                this.stopIrrigationTimer();
            }
        }, 2000);
    }

    // --- Gauge ---
    updateGauge(value) {
        const circumference = 2 * Math.PI * 85;
        const offset = circumference - (value / 100) * circumference;

        this.elements.gaugeProgress.style.strokeDashoffset = offset;
        this.elements.gaugeGlow.style.strokeDashoffset = offset;

        let glowColor = '#34d399';
        if (value < 40) glowColor = '#f59e0b';
        else if (value > 75) glowColor = '#60a5fa';

        this.elements.gaugeGlow.style.stroke = glowColor;
        this.elements.gaugeProgress.style.filter = `drop-shadow(0 0 8px ${glowColor}40)`;

        this.elements.moistureValue.style.color =
            value < 40 ? 'var(--color-warning)' :
            value > 75 ? 'var(--color-water)' :
            'var(--color-success)';
    }

    // --- Moisture Simulation ---
    startMoistureSimulation() {
        const updateMoisture = () => {
            const delta = (Math.random() - 0.5) * 3;

            if (this.irrigationOn) {
                this.moisture = Math.min(95, this.moisture + 0.8 + Math.random() * 1.2);
            } else {
                this.moisture = Math.max(15, this.moisture - 0.05 + delta * 0.3);
            }

            this.moisture = Math.round(Math.max(0, Math.min(100, this.moisture)));
            localStorage.setItem('aquanova_moisture', this.moisture);

            this.updateUI();
            this.updateLastReading();
            this.addSerialLine(`> Sensor reading: ${this.moisture}%`);
        };

        const scheduleUpdate = () => {
            const delay = 2000 + Math.random() * 1000;
            setTimeout(() => {
                updateMoisture();
                scheduleUpdate();
            }, delay);
        };

        scheduleUpdate();
    }

    // --- UI ---
    updateUI() {
        const value = this.moisture;
        const status = value < 40 ? 'Dry' : value > 75 ? 'Wet' : 'Moist';
        const statusClass = value < 40 ? 'status-dry' : value > 75 ? 'status-wet' : 'status-moist';

        this.elements.moistureValue.textContent = value;
        this.elements.moistureStatus.textContent = status;
        this.elements.moistureStatus.className = `cp-gauge-status ${statusClass}`;
        this.elements.moistureBar.style.width = value + '%';

        this.updateGauge(value);
    }

    updateLastReading() {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        this.elements.lastReading.textContent = `${hours}:${minutes}:${seconds}`;
    }

    // --- Theme ---
    setupTheme() {
        const savedTheme = localStorage.getItem('aquanova_theme');
        if (savedTheme) {
            document.documentElement.setAttribute('data-theme', savedTheme);
        } else if (window.matchMedia('(prefers-color-scheme: light)').matches) {
            document.documentElement.setAttribute('data-theme', 'light');
        }

        if (this.elements.themeToggle) {
            this.elements.themeToggle.addEventListener('click', () => {
                const current = document.documentElement.getAttribute('data-theme');
                const next = current === 'light' ? 'dark' : 'light';
                document.documentElement.setAttribute('data-theme', next);
                localStorage.setItem('aquanova_theme', next);
            });
        }
    }

    // --- Irrigation ---
    setupIrrigation() {
        this.elements.toggle.addEventListener('change', () => {
            this.toggleIrrigation(this.elements.toggle.checked);
        });

        this.elements.btn.addEventListener('click', () => {
            this.toggleIrrigation(!this.irrigationOn);
        });
    }

    toggleIrrigation(on) {
        this.irrigationOn = on;
        this.elements.toggle.checked = on;
        localStorage.setItem('aquanova_irrigation', on);

        this.elements.toggleLabel.textContent = on ? 'Irrigation ON' : 'Irrigation OFF';
        this.elements.btnText.textContent = on ? 'Turn OFF' : 'Turn ON';
        this.elements.btn.className = `cp-irrigation-btn${on ? '' : ' off'}`;
        this.elements.waterDrops.style.opacity = on ? '1' : '0';

        this.addSerialLine(on ? '> Irrigation: ON' : '> Irrigation: OFF');
        this.blinkLED('tx');

        if (on) {
            this.startIrrigationTimer();
            this.addSerialLine('> Pump: ACTIVE');
        } else {
            this.stopIrrigationTimer();
            this.addSerialLine('> Pump: STOPPED');

            // Reset counters when turned off
            setTimeout(() => {
                this.irrigationDuration = 0;
                this.irrigationWaterUsed = 0;
                this.elements.duration.textContent = '00:00';
                this.elements.waterUsed.textContent = '0.0 L';
                this.elements.waterToday.textContent = '0.0 L';
                localStorage.setItem('aquanova_duration', '0');
                localStorage.setItem('aquanova_water', '0');
            }, 500);
        }
    }

    startIrrigationTimer() {
        this.irrigationTimer = setInterval(() => {
            this.irrigationDuration++;
            this.irrigationWaterUsed = Math.round((this.irrigationDuration * 0.5) * 10) / 10;

            const mins = Math.floor(this.irrigationDuration / 60);
            const secs = this.irrigationDuration % 60;
            this.elements.duration.textContent =
                `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
            this.elements.waterUsed.textContent = `${this.irrigationWaterUsed.toFixed(1)} L`;
            this.elements.waterToday.textContent = `${this.irrigationWaterUsed.toFixed(1)} L`;

            localStorage.setItem('aquanova_duration', this.irrigationDuration);
            localStorage.setItem('aquanova_water', this.irrigationWaterUsed);
        }, 1000);
    }

    stopIrrigationTimer() {
        if (this.irrigationTimer) {
            clearInterval(this.irrigationTimer);
            this.irrigationTimer = null;
        }
    }

    // --- Serial ---
    addSerialLine(text) {
        const line = document.createElement('div');
        line.className = 'cp-serial-line';
        line.textContent = text;
        this.elements.serialOutput.appendChild(line);
        this.elements.serialOutput.scrollTop = this.elements.serialOutput.scrollHeight;

        while (this.elements.serialOutput.children.length > 30) {
            this.elements.serialOutput.removeChild(this.elements.serialOutput.firstChild);
        }
    }

    // --- LED ---
    startLEDBlink() {
        setInterval(() => {
            if (Math.random() > 0.7) this.blinkLED('rx');
        }, 3000);
    }

    blinkLED(type) {
        const led = type === 'tx' ? this.elements.ledTX : this.elements.ledRX;
        const cls = type === 'tx' ? 'active-tx' : 'active-rx';

        led.classList.add(cls);
        setTimeout(() => led.classList.remove(cls), 500);
    }
}

// --- Initialize ---
document.addEventListener('DOMContentLoaded', () => {
    new AquaNovaCP();
});

