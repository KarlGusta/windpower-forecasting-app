<?php
require_once '../config/database.php';

use config\database;

class WindSpeedChart {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getWindSpeedData() {
        $query = "
            SELECT 
                wind_speed,
                timestamp
            FROM wind_data
            WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ORDER BY timestamp ASC
        ";

        $result = $this->db->query($query);
        $data = [];

        while ($row = $result->fetch_assoc()) {
            $data[] = [
                'x' => $row['timestamp'],
                'y' => (float)$row['wind_speed']
            ];
        }
        
        // Write debug info to log file
        $logFile = __DIR__ . '/../logs/debug.log';
        file_put_contents(
            $logFile, 
            date('[Y-m-d H:i:s] ') . print_r($data, true) . "\n", 
            FILE_APPEND
        );
        
        header('Content-Type: application/json');
        echo json_encode($data);
    }
}

$chart = new WindSpeedChart();
$chart->getWindSpeedData();
?>