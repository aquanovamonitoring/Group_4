-- ==========================================
-- PhysIQ Water Monitoring — Table Schema
-- ==========================================

CREATE TABLE IF NOT EXISTS water_monitoring (
    id INT AUTO_INCREMENT PRIMARY KEY,
    device_id VARCHAR(50) NOT NULL,
    soil_moisture INT NULL,
    water_level INT NULL,
    pump_status ENUM('ON', 'OFF') DEFAULT 'OFF',
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_device (device_id),
    INDEX idx_recorded_at (recorded_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
