<?php
namespace Services;

use Exception;

class WeatherService {
    private $baseUrl = 'https://api.open-meteo.com/v1/forecast';
    private $windData;

    public function __construct() {
        $this->windData = new \Models\WindData();
    }

    public function generateForecast($latitude = 2.5072, $longitude = 36.7256) { // Default to London coordinates 
        $url = $this->baseUrl . "?latitude={$latitude}&longitude={$longitude}" . "&hourly=windspeed_10m,winddirection_10m,temperature_2m" . "&current=windspeed_10m,winddirection_10m,temperature_2m" . "&forecast_days=2";

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            throw new Exception("Failed to fetch forecast: " . $err);
        }

        $data = json_decode($response, true);
        return $this->formatForecast($data);
    }

    private function formatForecast($data) {
        if(!isset($data['hourly'])) {
            return [
                'prediction' => 'Forecast data currently unavailable',
                'confidence' => 0 
            ];
        }

        // Get current conditions
        $currentWind = isset($data['current']) ? [
            'speed' => $data['current']['windspeed_10m'],
            'direction' => $data['current']['winddirection_10m'],
            'temperature' => $data['current']['temperature_2m']
        ] : null;

        // Calculate power output and save current wind data to database
        if ($currentWind) {
            $powerOutput = $this->calculatePowerOutput($currentWind['speed']);
            $this->windData->saveData($currentWind['speed'], $currentWind['direction'], $powerOutput);
        }

        // Get next 24 hours of data
        $hours = 24;
        $windSpeeds = array_slice($data['hourly']['windspeed_10m'], 0, $hours);
        $windDirections = array_slice($data['hourly']['winddirection_10m'], 0, $hours);

        // Calculate averages
        $avgWindSpeed = array_sum($windSpeeds) / count($windSpeeds);
        $maxWindSpeed = max($windSpeeds);
        $minWindSpeed = min($windSpeeds);

        // Generate HTML with the new structure
        $prediction = sprintf(
            '<div id="currentWindData">
                <div class="mb-4">
                    <p class="text-base text-gray-600">Average Wind Speed</p>
                    <p class="text-base font-bold">%.1f m/s</p>
                </div>
                <div class="mb-4">
                    <p class="text-base text-gray-600">Maximum Wind Speed</p>
                    <p class="text-base font-bold">%.1f m/s</p>
                </div>
                <div class="mb-4">
                    <p class="text-base text-gray-600">Minimum Wind Speed</p>
                    <p class="text-base font-bold">%.1f m/s</p>
                </div>
                <div>
                    <p class="text-base text-gray-600">Wind Conditions</p>
                    <p class="text-base font-bold">%s</p>
                </div>
            </div>',
            $avgWindSpeed,
            $maxWindSpeed,
            $minWindSpeed,
            $this->getWindDescription($avgWindSpeed)
        );

        return [
            'prediction' => $prediction,
            'confidence' => 90,
            'current' => $currentWind,
            'hourly_data' => array_map(function($speed, $direction) {
                return [
                    'speed' => $speed,
                    'direction' => $direction 
                ];
            }, $windSpeeds, $windDirections)
        ];
    }

    private function getWindDescription($speed) {
        if ($speed < 5.5) {
            return "Light winds expected. Power generation may be reduced.";
        } elseif ($speed < 8) {
            return "Moderate winds providing steady power generation.";
        } elseif ($speed < 11) {
            return "Good wind conditions for optimal power generation.";
        } else {
            return "Strong winds expected. High power generation potential.";
        }
    }

    private function calculatePowerOutput($windSpeed) {
        // Basic power output calculation using the power curve
        // P = 0.5 * ρ * A * v³ * Cp
        // where ρ is air density (≈ 1.225 kg/m³), A is swept area, v is wind speed, Cp is power coefficient
        
        $airDensity = 1.225; // kg/m³
        $rotorRadius = 50; // meters (adjust based on your turbine size)
        $sweptArea = pi() * pow($rotorRadius, 2);
        $powerCoefficient = 0.4; // typical value between 0.35-0.45

        $powerOutput = 0.5 * $airDensity * $sweptArea * pow($windSpeed, 3) * $powerCoefficient;
        
        // Convert to megawatts and round to 2 decimal places
        return round($powerOutput / 1000000, 2);
    }
}