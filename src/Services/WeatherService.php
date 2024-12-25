<?php

namespace Services;

use Exception;

class WeatherService
{
    private $baseUrl = 'https://api.open-meteo.com/v1/forecast';
    private $windData;

    public function __construct()
    {
        $this->windData = new \Models\WindData();
    }

    public function generateForecast($latitude = 2.5072, $longitude = 36.7256)
    { // Default to London coordinates 
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

    private function formatForecast($data)
    {
        if (!isset($data['hourly'])) {
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

        // Add small random variations to the averages (+-5%)
        $variation = function ($value) {
            return $value * (1 + (mt_rand(-50, 50) / 1000));
        };


        // Calculate averages
        $avgWindSpeed = $variation(array_sum($windSpeeds) / count($windSpeeds));
        $maxWindSpeed = $variation(max($windSpeeds));
        $minWindSpeed = $variation(min($windSpeeds));

        // Generate HTML with auto-refresh script
        $prediction = sprintf(
            '<div id="currentWindData">
                <div class="mb-4">
                    <p class="text-base text-gray-600">Average Wind Speed</p>
                    <p class="text-base font-bold" id="avgSpeed">%.1f m/s</p>
                </div>
                <div class="mb-4">
                    <p class="text-base text-gray-600">Maximum Wind Speed</p>
                    <p class="text-base font-bold" id="maxSpeed">%.1f m/s</p>
                </div>
                <div class="mb-4">
                    <p class="text-base text-gray-600">Minimum Wind Speed</p>
                    <p class="text-base font-bold" id="minSpeed">%.1f m/s</p>
                </div>
                <div>
                    <p class="text-base text-gray-600">Wind Conditions</p>
                    <p class="text-base font-bold" id="windDesc">%s</p>
                </div>
            </div>
            <script>
                function updateWindData() {
                    fetch(window.location.href, {
                        headers: {
                            "Accept": "application/json"
                        }
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.prediction) {
                                const parser = new DOMParser();
                                const doc = parser.parseFromString(data.prediction, "text/html");
                                document.getElementById("avgSpeed").textContent = doc.getElementById("avgSpeed").textContent;
                                document.getElementById("maxSpeed").textContent = doc.getElementById("maxSpeed").textContent;
                                document.getElementById("minSpeed").textContent = doc.getElementById("minSpeed").textContent;
                                document.getElementById("windDesc").textContent = doc.getElementById("windDesc").textContent;
                            }
                        })
                        .catch(error => console.error("Error:", error));
                }
                
                // Start updating when the document is ready
                document.addEventListener("DOMContentLoaded", function() {
                    // Update every 5 seconds
                    setInterval(updateWindData, 5000);
                });
            </script>',
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
            }, $windSpeeds, $windDirections),
            'wind_description' => $this->getWindDescription($avgWindSpeed) // Added for AJAX updates
        ];
    }

    private function getWindDescription($speed)
    {
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

    private function calculatePowerOutput($windSpeed)
    {
        // Constants
        $airDensity = 1.225; // kg/m³
        $rotorRadius = 50; // meters
        $sweptArea = pi() * pow($rotorRadius, 2);
        $powerCoefficient = 0.4;
        $numberOfTurbines = 122;
        $maxTurbineCapacity = 850; // kW

        // Calculate theoretical power output for one turbine (in kilowatts)
        $singleTurbinePower = (0.5 * $airDensity * $sweptArea * pow($windSpeed, 3) * $powerCoefficient) / 1000;

        // Limit to maximum capacity per turbine
        $actualTurbinePower = min($singleTurbinePower, $maxTurbineCapacity);

        // Calculate total farm output in megawatts (122 turbines)
        $totalPowerOutput = ($actualTurbinePower * $numberOfTurbines) / 1000;

        return round($totalPowerOutput, 2);
    }
}
