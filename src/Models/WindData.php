<?php
class WindData {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getCurrentData() {
        $stmt = $this->db->prepare("SELECT * FROM wind_data WHERE timestamp >= NOW() - INTERVAL 1 HOUR ORDER BY timestamp DESC LIMIT 1");
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function saveData($speed, $direction, $power_output) {
        $stmt = $this->db->prepare("INSERT INTO wind_data (wind_speed, wind_direction, power_output, timestamp) VALUES (?, ?, ?, NOW()");
        return $stmt->execute([$speed, $direction, $power_output]);
    }

    public function getHistoricalData($days = 30) {
        $stmt = $this->db->prepare("SELECT * FROM wind_data WHERE timestamp >= NOW() - INTERVAL ? DAY ORDER BY timestamp ASC");
        $stmt->execute([$days]);
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}