/* ==========================================
   AquaNova — Smart Agriculture Dashboard
   Main JavaScript
   ========================================== */

'use strict';

class AquaNovaDashboard {
    constructor() {
        // State
        this.moisture = 65;
        this.irrigationOn = false;
        this.irrigationDuration = 0;
        this.irrigationWaterUsed = 0;
        this.irrigationTimer = null;
        this.serialMessages = [];
        this.arduinoConnected = true;

        // DOM Elements
        this.elements = {
            sidebar: document.getElementById('sidebar'),
            hamburger: document.getElementById('hamburger'),
            sidebarToggle: document.getElementById('sidebarToggle'),
            navItems: document.querySelectorAll('.nav-item'),
            moistureValue: document.getElementById('moistureValue'),
            moistureStatus: document.getElementById('moistureStatus'),
            moistureBar: document.getElementById('moistureBar'),
            gaugeProgress: document.getElementById('gaugeProgress'),
            gaugeGlow: document.getElementById('gaugeGlow'),
            summaryMoisture: document.getElementById('summaryMoisture'),
            summaryTrend: document.getElementById('summaryTrend'),
            summaryIrrigation: document.getElementById('summaryIrrigation'),
            summaryIrrigationStatus: document.getElementById('summaryIrrigationStatus'),
            summaryWaterUsage: document.getElementById('summaryWaterUsage'),
            summarySystem: document.getElementById('summarySystem'),
            irrigationToggle: document.getElementById('irrigationToggle'),
            irrigationBtn: document.getElementById('irrigationBtn'),
            btnText: document.getElementById('btnText'),
            toggleLabel: document.getElementById('toggleLabel'),
            irrigationDuration: document.getElementById('irrigationDuration'),
            irrigationWaterUsed: document.getElementById('irrigationWaterUsed'),
            serialOutput: document.getElementById('serialOutput'),
            headerTime: document.getElementById('headerTime'),
            headerDate: document.getElementById('headerDate'),
            sprinklerAnim: document.getElementById('sprinklerAnim'),
            waterDrops: document.getElementById('waterDrops'),
            sprayArcs: document.getElementById('sprayArcs'),
            sensorBadge: document.getElementById('sensorBadge'),
            ledTX: document.getElementById('ledTX'),
            ledRX: document.getElementById('ledRX'),
            themeToggle: document.getElementById('themeToggle'),
        };

        this.init();
    }

    init() {
        this.setupTheme();
        this.setupNavigation();
        this.setupSidebar();
        this.setupIrrigation();
        this.startMoistureSimulation();
        this.startClock();
        this.startSerialMonitor();
        this.startLEDBlink();
        this.updateGauge(this.moisture);
        this.updateUI();
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

    // --- Sidebar ---
    setupSidebar() {
        this.elements.hamburger.addEventListener('click', () => {
            this.elements.sidebar.classList.toggle('open');
            this.createOverlay();
        });

        this.elements.sidebarToggle.addEventListener('click', () => {
            this.elements.sidebar.classList.remove('open');
            this.removeOverlay();
        });

        // Close sidebar on overlay click
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('sidebar-overlay')) {
                this.elements.sidebar.classList.remove('open');
                this.removeOverlay();
            }
        });
    }

    createOverlay() {
        let overlay = document.querySelector('.sidebar-overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'sidebar-overlay';
            document.body.appendChild(overlay);
            requestAnimationFrame(() => overlay.classList.add('show'));
        }
    }

    removeOverlay() {
        const overlay = document.querySelector('.sidebar-overlay');
        if (overlay) {
            overlay.classList.remove('show');
            setTimeout(() => overlay.remove(), 200);
        }
    }

    // --- Navigation ---
    setupNavigation() {
        this.elements.navItems.forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                this.elements.navItems.forEach(n => n.classList.remove('active'));
                item.classList.add('active');

                const section = item.dataset.section;
                this.handleNavigation(section);
            });
        });
    }

    handleNavigation(section) {
        // Scroll to relevant section or simulate page navigation
        const content = document.getElementById('dashboardContent');
        content.style.opacity = '0';
        content.style.transform = 'translateY(10px)';

        setTimeout(() => {
            // Update active section visual feedback
            this.elements.sensorBadge.textContent =
                section === 'soil-moisture' ? 'Soil Moisture 1' :
                section === 'irrigation' ? 'Sprinkler System' :
                section === 'reports' ? 'Reports View' :
                section === 'settings' ? 'Settings' :
                'Soil Moisture 1';

            content.style.opacity = '1';
            content.style.transform = 'translateY(0)';
        }, 200);
    }

    // --- Gauge ---
    updateGauge(value) {
        const circumference = 2 * Math.PI * 85; // ~534.07
        const offset = circumference - (value / 100) * circumference;

        this.elements.gaugeProgress.style.strokeDashoffset = offset;
        this.elements.gaugeGlow.style.strokeDashoffset = offset;

        // Update glow color based on value
        let glowColor = '#34d399';
        if (value < 40) glowColor = '#f59e0b';
        else if (value > 75) glowColor = '#60a5fa';

        this.elements.gaugeGlow.style.stroke = glowColor;
        this.elements.gaugeProgress.style.filter = `drop-shadow(0 0 8px ${glowColor}40)`;

        // Update gauge value color
        this.elements.moistureValue.style.color =
            value < 40 ? 'var(--color-warning)' :
            value > 75 ? 'var(--color-water)' :
            'var(--color-success)';
    }

    // --- Moisture Simulation ---
    startMoistureSimulation() {
        const updateMoisture = () => {
            // Simulate moisture fluctuation with realistic patterns
            const delta = (Math.random() - 0.5) * 3;

            // If irrigation is on, moisture increases
            if (this.irrigationOn) {
                this.moisture = Math.min(95, this.moisture + 0.8 + Math.random() * 1.2);
            } else {
                // Natural slow decrease over time (evaporation)
                this.moisture = Math.max(15, this.moisture - 0.05 + delta * 0.3);
            }

            this.moisture = Math.round(Math.max(0, Math.min(100, this.moisture)));
            this.updateUI();

            // Simulate serial data
            this.addSerialLine(`> Soil Moisture: ${this.moisture}%`, 'data');
        };

        // Update every 2-3 seconds for realistic feel
        const scheduleUpdate = () => {
            const delay = 2000 + Math.random() * 1000;
            setTimeout(() => {
                updateMoisture();
                scheduleUpdate();
            }, delay);
        };

        scheduleUpdate();
    }

    // --- UI Update ---
    updateUI() {
        const value = this.moisture;
        const status = value < 40 ? 'Dry' : value > 75 ? 'Wet' : 'Moist';
        const statusClass = value < 40 ? 'status-dry' : value > 75 ? 'status-wet' : 'status-moist';

        // Gauge
        this.elements.moistureValue.textContent = value;
        this.elements.moistureStatus.textContent = status;
        this.elements.moistureStatus.className = `gauge-status ${statusClass}`;
        this.elements.moistureBar.style.width = value + '%';

        // Summary cards
        this.elements.summaryMoisture.textContent = value + '%';
        this.elements.summaryTrend.textContent = `● ${status}`;
        this.elements.summaryTrend.className = `card-trend ${statusClass}`;

        // Update gauge animation
        this.updateGauge(value);
    }

    // --- Irrigation ---
    setupIrrigation() {
        // Toggle switch
        this.elements.irrigationToggle.addEventListener('change', () => {
            this.toggleIrrigation(this.elements.irrigationToggle.checked);
        });

        // Button
        this.elements.irrigationBtn.addEventListener('click', () => {
            this.toggleIrrigation(!this.irrigationOn);
        });
    }

    toggleIrrigation(on) {
        this.irrigationOn = on;
        this.elements.irrigationToggle.checked = on;

        // Update UI
        this.elements.toggleLabel.textContent = on ? 'Irrigation ON' : 'Irrigation OFF';
        this.elements.btnText.textContent = on ? 'Turn OFF' : 'Turn ON';
        this.elements.summaryIrrigation.textContent = on ? 'ON' : 'OFF';
        this.elements.summaryIrrigationStatus.textContent = on ? '● Active' : '● Idle';

        // Button styling
        this.elements.irrigationBtn.className = `irrigation-btn${on ? '' : ' off'}`;

        // Sprinkler animation
        this.elements.waterDrops.style.opacity = on ? '1' : '0';
        this.elements.sprayArcs.style.opacity = on ? '1' : '0';

        // Serial message
        this.addSerialLine(on
            ? '> Irrigation: TURNED ON'
            : '> Irrigation: TURNED OFF', 'info');

        // LED blink
        this.blinkLED('tx');

        // Timer tracking
        if (on) {
            this.startIrrigationTimer();
            this.addSerialLine('> Water pump: ACTIVE', 'data');
        } else {
            this.stopIrrigationTimer();
            this.addSerialLine('> Water pump: STOPPED', 'data');
        }
    }

    startIrrigationTimer() {
        this.irrigationDuration = 0;
        this.irrigationWaterUsed = 0;

        this.irrigationTimer = setInterval(() => {
            this.irrigationDuration++;
            // Simulate 0.5L per second flow rate
            this.irrigationWaterUsed = Math.round((this.irrigationDuration * 0.5) * 10) / 10;

            const mins = Math.floor(this.irrigationDuration / 60);
            const secs = this.irrigationDuration % 60;
            this.elements.irrigationDuration.textContent =
                `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
            this.elements.irrigationWaterUsed.textContent = `${this.irrigationWaterUsed.toFixed(1)} L`;
            this.elements.summaryWaterUsage.textContent = `${this.irrigationWaterUsed.toFixed(1)} L`;
        }, 1000);
    }

    stopIrrigationTimer() {
        if (this.irrigationTimer) {
            clearInterval(this.irrigationTimer);
            this.irrigationTimer = null;
        }
    }

    // --- Clock ---
    startClock() {
        const update = () => {
            const now = new Date();
            let hours = now.getHours();
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12 || 12;

            this.elements.headerTime.textContent = `${hours}:${minutes} ${ampm}`;

            const options = { weekday: 'short', month: 'short', day: 'numeric' };
            this.elements.headerDate.textContent = now.toLocaleDateString('en-US', options);
        };

        update();
        setInterval(update, 10000);
    }

    // --- Serial Monitor ---
    startSerialMonitor() {
        this.addSerialLine('AquaNova v1.0 — Smart Irrigation System', 'welcome');
        this.addSerialLine('Arduino Uno connected on COM3', 'info');

        setTimeout(() => {
            this.addSerialLine('> Initializing sensors...', 'data');
            setTimeout(() => {
                this.addSerialLine('> Soil Moisture Sensor 1: Ready', 'data');
                setTimeout(() => {
                    this.addSerialLine('> System ready.', 'data');
                }, 800);
            }, 1000);
        }, 500);
    }

    addSerialLine(text, type = 'data') {
        const line = document.createElement('div');
        line.className = `serial-line ${type}`;
        line.textContent = text;
        this.elements.serialOutput.appendChild(line);
        this.elements.serialOutput.scrollTop = this.elements.serialOutput.scrollHeight;

        // Keep last 50 lines only
        while (this.elements.serialOutput.children.length > 50) {
            this.elements.serialOutput.removeChild(this.elements.serialOutput.firstChild);
        }
    }

    // --- LED Blink ---
    startLEDBlink() {
        setInterval(() => {
            if (this.arduinoConnected && Math.random() > 0.7) {
                this.blinkLED('rx');
            }
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
    new AquaNovaDashboard();
});

