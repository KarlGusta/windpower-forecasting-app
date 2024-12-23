<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Initialize error reporting for development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load configuration
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Controllers/WeatherController.php';
require_once __DIR__ . '/../src/Models/WindData.php';
require_once __DIR__ . '/../src/Services/OpenAIService.php';

// Add this after the require statements
use Controllers\WeatherController;

// Initialize controller
$weatherController = new \Controllers\WeatherController();

// Get data for the dashboard
$currentConditions = $weatherController->getCurrentConditions();
$forecast = $weatherController->getForecast();
$historicalData = $weatherController->getHistoricalData();
// Limit historical data to last 8 hours
$historicalData = array_slice($historicalData, -8);

// Format timestamps to show only time
foreach ($historicalData as &$data) {
    if (isset($data['timestamp'])) {
        $datetime = new DateTime($data['timestamp']);
       $data['formatted_time'] = $datetime->format('H:i'); // 24-hour format (e.g. "14:30")
    }
}
unset($data); // Unset reference after foreach

// Add station details
$stationDetails = [
    'name' => 'Lake Turkana Wind Power (LTWP)',
    'location' => 'Loiyangalani, Marsabit County, Kenya',
    'coordinates' => '2.5072° N, 36.7256° E',
    'height' => '450m above sea level',
    'turbine_model' => 'Vestas V52-850 kW',
    'operational_since' => '2018'
];

?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Wind Power Dashboard</title>
        <!-- Add Rubik font -->
        <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
        <!-- Add ApexCharts -->
        <!-- <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script> -->
        <style>
            body {
                font-family: 'Rubik', sans-serif;
                background-color: #E6E6E6;
            }
        </style>
    </head>
    <body>
        <div class="container mx-auto px-4 py-8">
            <!-- Header -->
            <header class="mb-8">
                <h1 class="text-3xl font-bold text-gray-800">Wind Power Dashboard</h1>
            </header>

            <!-- Main Layout -->
            <div class="flex flex-col md:flex-row gap-6">
                <!-- Left Sidebar - Station Details -->
                <div class="md:w-1/4">
                    <div class="p-6 rounded-lg shadow-md">
                        <h2 class="text-xl font-semibold mb-4">🏭 Station Details</h2>
                        <div class="space-y-4">
                            <div>
                                <p class="text-base text-gray-600">Station Name</p>
                                <p class="text-base font-medium"><?= $stationDetails['name'] ?></p>
                            </div>
                            <div>
                                <p class="text-base text-gray-600">Location</p>
                                <p class="text-base font-medium"><?= $stationDetails['location'] ?></p>
                            </div>
                            <div>
                                <p class="text-base text-gray-600">Coordinates</p>
                                <p class="text-base font-medium"><?= $stationDetails['coordinates'] ?></p>
                            </div>
                            <div>
                                <p class="text-base text-gray-600">Height</p>
                                <p class="text-base font-medium"><?= $stationDetails['height'] ?></p>
                            </div>
                            <div>
                                <p class="text-base text-gray-600">Turbine Model</p>
                                <p class="text-base font-medium"><?= $stationDetails['turbine_model'] ?></p>
                            </div>
                            <div>
                                <p class="text-base text-gray-600">Operational Since</p>
                                <p class="text-base font-medium"><?= $stationDetails['operational_since'] ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Content Area -->
                <div class="md:w-3/4">
                    <!-- Main Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Current Wind Conditions Card -->
                        <div class="bg-white p-4 rounded-lg shadow-md">
                            <h2 class="text-lg font-semibold mb-2">🌪️ Current Wind Conditions</h2>
                            <div id="currentWindData">
                                <div class="mb-2">
                                    <p class="text-base text-gray-600">Wind Speed</p>
                                    <p class="text-base font-bold" id="currentSpeed">
                                        <?= number_format($currentConditions['wind_speed'], 1) ?> m/s
                                    </p>
                                </div>
                                <div class="mb-2">
                                    <p class="text-base text-gray-600">Wind Direction</p>
                                    <div class="flex items-center">
                                        <p class="text-base font-bold" id="currentDirection">
                                            <?= $currentConditions['wind_direction'] ?>°
                                        </p>
                                        <span class="ml-2 text-base text-gray-600">(<?= $weatherController->getWindDirectionLabel($currentConditions['wind_direction']) ?>)</span>
                                    </div>
                                </div>
                                <div>
                                    <p class="text-base text-gray-600">Estimated Power Output</p>
                                    <p class="text-base font-bold text-green-600" id="powerOutput">
                                        <?= number_format($currentConditions['power_output'], 2) ?> kW
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Forecast Card -->
                        <div class="bg-white p-4 rounded-lg shadow-md">
                            <h2 class="text-lg font-semibold mb-2">🔮 24-Hour Forecast</h2>
                            <div id="forecast">
                                <?php if(isset($forecast['prediction'])): ?>
                                    <div class="mb-2">
                                        <p class="text-base text-gray-600">Prediction</p>
                                        <p class="text-base font-bold"><?= $forecast['prediction'] ?></p>
                                    </div>
                                    <div>
                                        <p class="text-base text-gray-600">Confidence</p>
                                        <p class="text-base font-bold"><?= number_format($forecast['confidence'] ?? 0, 1) ?>%</p>
                                    </div>
                                <?php else: ?>
                                    <p class="text-base">Forecast data unavailable</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Historical Chart Card -->
                        <div class="bg-white p-4 rounded-lg shadow-md">
                            <h2 class="text-lg font-semibold mb-2">📊 Historical Wind Data</h2>
                            <div id="windChart"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Libs JS -->
    <script src="../dist/libs/apexcharts/dist/apexcharts.min.js" defer></script>

        <!-- Include Chart.js first -->
        <!-- <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> -->
        
        <!-- Initialize Dashboard -->
        <script>
            // Pass PHP data to JavaScript (limited to last 8 hours)
            const historicalData = <?= json_encode($historicalData) ?>;
        </script>
        <script src="assets/js/dashboard.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                new Dashboard(historicalData);
            });
        </script>
    </body>
</html>
