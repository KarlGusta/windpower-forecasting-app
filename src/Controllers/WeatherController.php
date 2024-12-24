<?php
namespace Controllers;

use Services\WeatherService;
use Models\WindData;

class WeatherController {
    private $windData;
    private $weatherService;

    public function __construct() {
        $this->windData = new WindData();
        $this->weatherService = new WeatherService();
    }

    public function getCurrentConditions() {
        $weatherService = new WeatherService();
        $forecast = $weatherService->generateForecast();
        
        // Extract current conditions from the forecast response
        $current = $forecast['current'] ?? [];
        
        return [
            'wind_speed' => $current['speed'] ?? 0,
            'wind_direction' => $current['direction'] ?? 0,
            'power_output' => $this->calculatePowerOutput($current['speed'] ?? 0)
        ];
    }

    private function calculatePowerOutput($windSpeed) {
        // Simplified power calculation
        $airDensity = 1.225; // kg/m³
        $sweptArea = 50; // m² (adjust based on your turbine)
        $efficiency = 0.35; // typical wind turbine efficiency
        $numberOfTurbines = 122; // total number of turbines in the wind farm
        $maxTurbineCapacity = 0.85; // Maximum capacity of 850kW = 0.85MW per turbine
        
        // Calculate power for a single turbine
        $singleTurbinePower = 0.5 * $airDensity * $sweptArea * pow($windSpeed, 3) * $efficiency / 1000; // Power in MW
        
        // Cap the power at the maximum turbine capacity
        $singleTurbinePower = min($singleTurbinePower, $maxTurbineCapacity);
        
        // Multiply by number of turbines to get total farm output
        return $singleTurbinePower * $numberOfTurbines;
    }

    public function getForecast() {
        // You can pass your location's coordinates here
        return $this->weatherService->generateForecast();
    }

    public function getHistoricalData() {
        // Query last 24 hours of data from database
        $historicalData = $this->windData->getLastDayReadings();
        
        // If no data is available, return empty array
        if (empty($historicalData)) {
            return [];
        }
        
        return array_map(function($reading) {
            return [
                'timestamp' => date('Y-m-d H:i:s', strtotime($reading['timestamp'])),
                'wind_speed' => (float)$reading['wind_speed'],
                'wind_direction' => (int)$reading['wind_direction'],
                'power_output' => $this->calculatePowerOutput($reading['wind_speed'])
            ];
        }, $historicalData);
    }

    public function getWindDirectionLabel($degrees) {
        $directions = [
            'N' => [337.5, 22.5],
            'NE' => [22.5, 67.5],
            'E' => [67.5, 112.5],
            'SE' => [112.5, 157.5],
            'S' => [157.5, 202.5],
            'SW' => [202.5, 247.5],
            'W' => [247.5, 292.5],
            'NW' => [292.5, 337.5]
        ];
        
        foreach ($directions as $dir => $range) {
            if ($degrees >= $range[0] && $degrees < $range[1]) {
                return $dir;
            }
        }
        return 'N'; // Default to North for 337.5-360 degrees
    }

    public function getMonthlyData() {
        // Generate 30 days of sample data
        $monthlyData = [];
        $currentTime = time();
        
        for ($i = 30; $i >= 0; $i--) {
            $timestamp = $currentTime - ($i * 86400); // Go back in time by days (86400 seconds per day)
            
            // Calculate daily averages
            $monthlyData[] = [
                'date' => date('Y-m-d', $timestamp),
                'avg_wind_speed' => round(rand(3, 12) + (rand(0, 100) / 100), 1), // Average wind speed between 3-12 m/s
                'max_wind_speed' => round(rand(10, 20) + (rand(0, 100) / 100), 1), // Max wind speed between 10-20 m/s
                'avg_power_output' => round(rand(100, 300) / 10, 1), // Average daily power output in kW
                'total_energy' => round(rand(2000, 7000) / 10, 1) // Total daily energy in kWh
            ];
        }
        
        return $monthlyData;
    }
}