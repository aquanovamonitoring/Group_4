<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';

$latestReading = null;
$deviceStatus = 'OFFLINE';
$errorMessage = '';

try {
    $pdo = getDBConnection();

    $stmt = $pdo->query(
        'SELECT device_id, soil_moisture, water_level, pump_status, recorded_at
         FROM water_monitoring
         ORDER BY recorded_at DESC
         LIMIT 1'
    );
    $latestReading = $stmt->fetch();

    if ($latestReading) {
        $thresholdMinutes = 5;
        $lastSeen = new DateTime($latestReading['recorded_at']);
        $now = new DateTime();
        $diffMinutes = ($now->getTimestamp() - $lastSeen->getTimestamp()) / 60;

        $deviceStatus = $diffMinutes <= $thresholdMinutes ? 'ONLINE' : 'OFFLINE';
    }
} catch (Throwable $e) {
    $errorMessage = 'Unable to load monitoring data.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Water Monitoring — AquaNova</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .monitoring-container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 28px 24px;
        }

        .monitoring-header {
            margin-bottom: 28px;
        }

        .monitoring-header h1 {
            font-size: 1.6rem;
            font-weight: 700;
            color: var(--color-text-primary);
            letter-spacing: -0.3px;
        }

        .monitoring-header p {
            font-size: 0.9rem;
            color: var(--color-text-muted);
            margin-top: 4px;
        }

        .monitoring-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
        }

        @media (max-width: 640px) {
            .monitoring-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            .monitoring-container {
                padding: 16px 16px;
            }
        }

        .monitoring-card {
            background: var(--color-bg-card);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-card);
            padding: 24px;
            transition: all var(--transition);
            animation: cardFadeIn 0.5s ease both;
        }

        .monitoring-card:hover {
            box-shadow: var(--shadow-card);
            border-color: var(--color-surface-light);
        }

        .monitoring-card.full-width {
            grid-column: 1 / -1;
        }

        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .card-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1rem;
            font-weight: 600;
            color: var(--color-text-primary);
        }

        .card-title-icon {
            width: 20px;
            height: 20px;
            color: var(--color-water);
        }

        .device-badge {
            font-size: 0.75rem;
            padding: 4px 12px;
            border-radius: 20px;
            background: var(--color-surface);
            color: var(--color-text-secondary);
            border: 1px solid var(--color-border);
            white-space: nowrap;
        }

        .metric-value {
            font-size: 3rem;
            font-weight: 800;
            color: var(--color-text-primary);
            line-height: 1;
            font-variant-numeric: tabular-nums;
            text-align: center;
            margin: 12px 0;
        }

        .metric-unit {
            font-size: 1rem;
            color: var(--color-text-muted);
            font-weight: 500;
            display: block;
            text-align: center;
            margin-top: 2px;
        }

        .progress-track {
            width: 100%;
            height: 12px;
            background: var(--color-surface);
            border-radius: 12px;
            overflow: hidden;
            margin: 16px 0 12px;
        }

        .progress-fill {
            height: 100%;
            border-radius: 12px;
            background: linear-gradient(90deg, var(--color-warning), var(--color-success), var(--color-water));
            transition: width 1s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .progress-labels {
            display: flex;
            justify-content: space-between;
            font-size: 0.7rem;
            color: var(--color-text-muted);
        }

        .pump-status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .pump-status.on {
            background: rgba(16, 185, 129, 0.15);
            color: var(--color-success);
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .pump-status.off {
            background: rgba(239, 68, 68, 0.1);
            color: var(--color-danger);
            border: 1px solid rgba(239, 68, 68, 0.25);
        }

        .status-dot-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
        }

        .status-dot-indicator.online {
            background: var(--color-success);
            box-shadow: 0 0 8px rgba(16, 185, 129, 0.6);
        }

        .status-dot-indicator.offline {
            background: var(--color-danger);
        }

        .last-update {
            font-size: 0.8rem;
            color: var(--color-text-muted);
            text-align: center;
            margin-top: 8px;
        }

        .device-status-card {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            padding: 18px 24px;
        }

        .device-status-label {
            font-size: 1rem;
            font-weight: 600;
            color: var(--color-text-secondary);
        }

        .device-status-value {
            font-size: 1.2rem;
            font-weight: 700;
        }

        .device-status-value.online {
            color: var(--color-success);
        }

        .device-status-value.offline {
            color: var(--color-danger);
        }

        .error-message {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.25);
            border-radius: var(--radius-card);
            padding: 16px 20px;
            color: var(--color-danger);
            font-size: 0.9rem;
            margin-bottom: 20px;
        }

        .no-data {
            text-align: center;
            color: var(--color-text-muted);
            padding: 40px 20px;
        }

        @keyframes cardFadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .monitoring-card:nth-child(1) { animation-delay: 0s; }
        .monitoring-card:nth-child(2) { animation-delay: 0.1s; }
        .monitoring-card:nth-child(3) { animation-delay: 0.2s; }
        .monitoring-card:nth-child(4) { animation-delay: 0.3s; }
        .monitoring-card:nth-child(5) { animation-delay: 0.4s; }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <svg class="logo-icon" viewBox="0 0 40 40" fill="none">
                        <path d="M20 4C20 4 12 12 12 20C12 24.4 15.6 28 20 28C24.4 28 28 24.4 28 20C28 12 20 4 20 4Z" fill="var(--color-primary-light)" opacity="0.3"/>
                        <path d="M20 8C20 8 15 13.5 15 19C15 21.8 17.2 24 20 24C22.8 24 25 21.8 25 19C25 13.5 20 8 20 8Z" fill="var(--color-primary-light)"/>
                        <circle cx="20" cy="19" r="2" fill="var(--color-primary-dark)"/>
                        <path d="M20 28V36M16 32H24" stroke="var(--color-primary-light)" stroke-width="2" stroke-linecap="round"/>
                        <path d="M10 12L6 8M30 12L34 8" stroke="var(--color-primary-light)" stroke-width="1.5" stroke-linecap="round" opacity="0.6"/>
                    </svg>
                    <span class="logo-text">Aqua<span class="logo-highlight">Nova</span></span>
                </div>
                <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 18l6-6-6-6"/>
                    </svg>
                </button>
            </div>

            <nav class="sidebar-nav">
                <a href="index.html" class="nav-item" data-section="dashboard">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7" rx="1"/>
                        <rect x="14" y="3" width="7" height="7" rx="1"/>
                        <rect x="3" y="14" width="7" height="7" rx="1"/>
                        <rect x="14" y="14" width="7" height="7" rx="1"/>
                    </svg>
                    <span>Dashboard</span>
                </a>
                <a href="index.html" class="nav-item" data-section="soil-moisture">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z" stroke-linejoin="round"/>
                    </svg>
                    <span>Soil Moisture</span>
                </a>
                <a href="index.html" class="nav-item" data-section="irrigation">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M2 12h20M12 2v20M6 6l12 12M18 6L6 18" stroke-linecap="round"/>
                        <path d="M12 6a6 6 0 0 1 6 6" stroke-linecap="round"/>
                    </svg>
                    <span>Irrigation Control</span>
                </a>
                <a href="index.html" class="nav-item" data-section="reports">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="16" y1="13" x2="8" y2="13"/>
                        <line x1="16" y1="17" x2="8" y2="17"/>
                    </svg>
                    <span>Reports</span>
                </a>
                <a href="monitoring.php" class="nav-item active" data-section="water-monitoring">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z" stroke-linejoin="round"/>
                    </svg>
                    <span>Water Monitoring</span>
                </a>
                <a href="index.html" class="nav-item" data-section="settings">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"/>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                    </svg>
                    <span>Settings</span>
                </a>
            </nav>

            <div class="sidebar-footer">
                <div class="connection-status">
                    <span class="status-dot online"></span>
                    <span class="status-text">ESP-01 Ready</span>
                </div>
            </div>
        </aside>

        <main class="main-content" id="mainContent">
            <header class="header">
                <div class="header-left">
                    <button class="hamburger" id="hamburger" aria-label="Menu">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="3" y1="12" x2="21" y2="12"/>
                            <line x1="3" y1="6" x2="21" y2="6"/>
                            <line x1="3" y1="18" x2="21" y2="18"/>
                        </svg>
                    </button>
                    <div>
                        <h1 class="page-title">Water Monitoring</h1>
                        <p class="page-subtitle">Real-time sensor readings from ESP-01</p>
                    </div>
                </div>
                <div class="header-right">
                    <button class="theme-toggle" id="themeToggle" aria-label="Toggle theme" title="Toggle Light/Dark Mode">
                        <svg class="theme-icon-sun" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="5"/>
                            <line x1="12" y1="1" x2="12" y2="3"/>
                            <line x1="12" y1="21" x2="12" y2="23"/>
                            <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/>
                            <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
                            <line x1="1" y1="12" x2="3" y2="12"/>
                            <line x1="21" y1="12" x2="23" y2="12"/>
                            <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/>
                            <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
                        </svg>
                        <svg class="theme-icon-moon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                        </svg>
                    </button>
                    <div class="header-time" id="headerTime">--:-- --</div>
                    <div class="header-date" id="headerDate">---</div>
                </div>
            </header>

            <div class="monitoring-container">
                <?php if ($errorMessage): ?>
                    <div class="error-message"><?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>

                <div class="monitoring-header">
                    <h1>Water Monitoring</h1>
                    <p>Live readings from your ESP-01 water-level and soil-moisture sensors</p>
                </div>

                <?php if (!$latestReading): ?>
                    <div class="monitoring-card">
                        <div class="no-data">No sensor data available yet. The ESP-01 has not reported any readings.</div>
                    </div>
                <?php else: ?>
                    <div class="monitoring-grid">
                        <div class="monitoring-card">
                            <div class="card-header">
                                <div class="card-title">
                                    <svg class="card-title-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z" stroke-linejoin="round"/>
                                    </svg>
                                    Soil Moisture
                                </div>
                                <span class="device-badge">Sensor 1</span>
                            </div>
                            <div class="metric-value">
                                <?php echo (int)$latestReading['soil_moisture']; ?>%
                            </div>
                            <div class="progress-track">
                                <div class="progress-fill" style="width: <?php echo (int)$latestReading['soil_moisture']; ?>%"></div>
                            </div>
                            <div class="progress-labels">
                                <span>Dry</span>
                                <span>Moist</span>
                                <span>Wet</span>
                            </div>
                        </div>

                        <div class="monitoring-card">
                            <div class="card-header">
                                <div class="card-title">
                                    <svg class="card-title-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M12 2C12 2 7 8 7 13a5 5 0 0 0 10 0c0-5-5-11-5-11z" stroke-linejoin="round"/>
                                    </svg>
                                    Water Level
                                </div>
                                <span class="device-badge">Tank</span>
                            </div>
                            <div class="metric-value">
                                <?php echo (int)$latestReading['water_level']; ?>%
                            </div>
                            <div class="progress-track">
                                <div class="progress-fill" style="width: <?php echo (int)$latestReading['water_level']; ?>%"></div>
                            </div>
                            <div class="progress-labels">
                                <span>Empty</span>
                                <span>Half</span>
                                <span>Full</span>
                            </div>
                        </div>

                        <div class="monitoring-card">
                            <div class="card-header">
                                <div class="card-title">
                                    <svg class="card-title-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M2 12h20M12 2v20" stroke-linecap="round"/>
                                        <path d="M6 6l12 12M18 6L6 18" stroke-linecap="round"/>
                                    </svg>
                                    Pump Status
                                </div>
                            </div>
                            <div style="text-align:center; margin-top:8px;">
                                <span class="pump-status <?php echo strtolower($latestReading['pump_status']); ?>">
                                    <?php echo htmlspecialchars($latestReading['pump_status'], ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </div>
                        </div>

                        <div class="monitoring-card">
                            <div class="card-header">
                                <div class="card-title">
                                    <svg class="card-title-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="2" y="6" width="20" height="12" rx="2"/>
                                        <circle cx="8" cy="12" r="1" fill="currentColor"/>
                                        <circle cx="16" cy="12" r="1" fill="currentColor"/>
                                        <path d="M10 12h4" stroke-linecap="round"/>
                                    </svg>
                                    Device
                                </div>
                                <span class="device-badge">ESP-01</span>
                            </div>
                            <div class="device-status-card">
                                <span class="status-dot-indicator <?php echo strtolower($deviceStatus); ?>"></span>
                                <span class="device-status-label">Device:</span>
                                <span class="device-status-value <?php echo strtolower($deviceStatus); ?>">
                                    <?php echo htmlspecialchars($deviceStatus, ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </div>
                            <div class="last-update">
                                Last Update: <?php echo htmlspecialchars($latestReading['recorded_at'], ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="js/script.js"></script>
    <script>
        (function () {
            var themeToggle = document.getElementById('themeToggle');
            if (themeToggle) {
                themeToggle.addEventListener('click', function () {
                    var html = document.documentElement;
                    var current = html.getAttribute('data-theme');
                    html.setAttribute('data-theme', current === 'light' ? 'dark' : 'light');
                });
            }

            var sidebarToggle = document.getElementById('sidebarToggle');
            var sidebar = document.getElementById('sidebar');
            if (sidebarToggle && sidebar) {
                sidebarToggle.addEventListener('click', function () {
                    sidebar.classList.toggle('open');
                });
            }

            var hamburger = document.getElementById('hamburger');
            if (hamburger && sidebar) {
                hamburger.addEventListener('click', function () {
                    sidebar.classList.toggle('open');
                });
            }
        })();
    </script>
</body>
</html>
