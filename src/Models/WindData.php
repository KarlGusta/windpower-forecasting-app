<?php
namespace Models;

use config\database;

class WindData {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getCurrentData() {
        $stmt = $this->db->prepare("SELECT * FROM wind_data WHERE timestamp >= NOW() - INTERVAL 1 HOUR ORDER BY timestamp DESC LIMIT 1");
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result ? $result : [
            'wind_speed' => 0,
            'wind_direction' => 0,
            'power_output' => 0,
            'timestamp' => null
        ];
    }

    public function saveData($speed, $direction, $power_output) {
        $stmt = $this->db->prepare("INSERT INTO wind_data (wind_speed, wind_direction, power_output, timestamp) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("ddd", $speed, $direction, $power_output);
        return $stmt->execute();
    }

    public function getHistoricalData($days = 30) {
        $stmt = $this->db->prepare("SELECT * FROM wind_data WHERE timestamp >= NOW() - INTERVAL ? DAY ORDER BY timestamp ASC");
        $stmt->bind_param("i", $days);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getLastDayReadings() {
        $stmt = $this->db->prepare("SELECT timestamp, wind_speed, wind_direction 
            FROM wind_data 
            WHERE timestamp >= NOW() - INTERVAL 24 HOUR 
            ORDER BY timestamp ASC");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
} 