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

// Add station details
$stationDetails = [
    'name' => 'Ngong Wind Farm',
    'location' => 'Ngong Hills, Kajiado County, Kenya',
    'coordinates' => '1.3751° S, 36.6719° E',
    'height' => 'Approximately 2,000m above sea level',
    'turbine_model' => 'Vestas V52-850 kW',
    'operational_since' => '2009'
];

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
    <script>
        // Auto reload page every 5 minutes
        setTimeout(() => {
            window.location.reload();
        }, 800000);
    </script>
</head>

<body>
    <!-- For desktop -->
    <div class="hidden md:block container mx-auto px-4 py-8">
        <!-- Header -->
        <header class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Wind Power Dashboard</h1>
        </header>

        <!-- Main Layout -->
        <div class="flex flex-col md:flex-row gap-6">
            <!-- Left Sidebar - Station Details -->
            <div class="md:w-1/4">
                <div class="p-6 rounded-3xl shadow-md">
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
                    <div class="bg-white p-4 rounded-3xl shadow-md">
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
                                    <?= number_format($currentConditions['power_output'], 3) ?> MW
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Forecast Card -->
                    <div class="bg-white p-4 rounded-3xl shadow-md">
                        <h2 class="text-lg font-semibold mb-2">🔮 24-Hour Forecast</h2>
                        <div id="forecast">
                            <?php if (isset($forecast['prediction'])): ?>
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
                    <div class="bg-white p-4 rounded-3xl shadow-md">
                        <h2 class="text-lg font-semibold mb-2">📊 Historical Wind Data</h2>
                        <div id="windChart"></div>
                    </div>

                    <!-- Powercurve Chart -->
                    <div class="bg-white p-4 rounded-3xl shadow-md">
                        <h2 class="text-lg font-semibold mb-2">📈 Power Curve</h2>
                        <div id="powerCurve"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- For mobile -->
    <div class="md:hidden container mx-auto px-4 py-8">
        <!-- Header -->
        <header class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Wind Power Dashboard</h1>
        </header>

        <!-- Main Layout -->
        <div class="flex flex-col md:flex-row gap-6">
            <!-- Left Sidebar - Station Details -->
            <div class="md:w-1/4">
                <div class="p-6 rounded-3xl shadow-md">
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

            <!-- Right Content Area for mobile -->
            <div class="md:w-3/4">
                <!-- Main Grid -->
                <div class="grid grid-cols-1 gap-4">
                    <!-- Current Wind Conditions Card -->
                    <div class="bg-white p-4 rounded-3xl shadow-md">
                        <h2 class="text-lg font-semibold mb-2">🌪️ Current Wind Conditions</h2>
                        <div id="currentWindDataMobile">
                            <div class="mb-2">
                                <p class="text-base text-gray-600">Wind Speed</p>
                                <p class="text-base font-bold" id="currentSpeedMobile">
                                    <?= number_format($currentConditions['wind_speed'], 1) ?> m/s
                                </p>
                            </div>
                            <div class="mb-2">
                                <p class="text-base text-gray-600">Wind Direction</p>
                                <div class="flex items-center">
                                    <p class="text-base font-bold" id="currentDirectionMobile">
                                        <?= $currentConditions['wind_direction'] ?>°
                                    </p>
                                    <span class="ml-2 text-base text-gray-600">(<?= $weatherController->getWindDirectionLabel($currentConditions['wind_direction']) ?>)</span>
                                </div>
                            </div>
                            <div>
                                <p class="text-base text-gray-600">Estimated Power Output</p>
                                <p class="text-base font-bold text-green-600" id="powerOutputMobile">
                                    <?= number_format($currentConditions['power_output'], 3) ?> MW
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Forecast Card -->
                    <div class="bg-white p-4 rounded-3xl shadow-md">
                        <h2 class="text-lg font-semibold mb-2">🔮 24-Hour Forecast</h2>
                        <div id="forecast">
                            <?php if (isset($forecast['prediction'])): ?>
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

                    <!-- Historical Chart -->
                    <div class="bg-white p-4 rounded-3xl shadow-md">
                        <h2 class="text-lg font-semibold mb-2">📊 Historical Wind Data</h2>
                        <div id="windChartMobile"></div>
                    </div>

                    <!-- Powercurve Chart for Mobile -->
                    <div class="bg-white p-4 rounded-3xl shadow-md">
                        <h2 class="text-lg font-semibold mb-2">📈 Power Curve</h2>
                        <div id="powerCurveMobile"></div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    </div>
    <!-- Libs JS -->
    <script src="../dist/libs/apexcharts/dist/apexcharts.min.js" defer></script>

    <script>
        // Simulate random changes within a reasonable range
        function simulateWindChange(currentValue, minChange, maxChange) {
            const change = (Math.random() * (maxChange - minChange) + minChange);
            return Math.max(0, currentValue + change);
        }

        // Updated refresh function with simulated changes
        function refreshCurrentConditions() {
            const currentSpeed = parseFloat(document.getElementById('currentSpeed').textContent);
            const currentDirection = parseFloat(document.getElementById('currentDirection').textContent);
            const currentPower = parseFloat(document.getElementById('powerOutput').textContent);

            // Simulate small changes
            const newSpeed = simulateWindChange(currentSpeed, -0.3, 0.3).toFixed(1);
            const newDirection = (currentDirection + Math.random() * 5 - 2.5) % 360;
            const newPower = simulateWindChange(currentPower, -0.005, 0.005).toFixed(3);

            // Update desktop values
            document.getElementById('currentSpeed').textContent = newSpeed + ' m/s';
            document.getElementById('currentDirection').textContent = Math.round(newDirection) + '°';
            document.getElementById('powerOutput').textContent = newPower + ' MW';

            // Update mobile values
            document.getElementById('currentSpeedMobile').textContent = newSpeed + ' m/s';
            document.getElementById('currentDirectionMobile').textContent = Math.round(newDirection) + '°';
            document.getElementById('powerOutputMobile').textContent = newPower + ' MW';

            // Add console log to confirm refresh
            console.log('Page data refreshed at:', new Date().toLocaleTimeString());
        }

        // Refresh every 5 seconds (5000 milliseconds)
        setInterval(refreshCurrentConditions, 5000);
    </script>

    <script>
        // Fetch data and initialize chart
        fetch('../api/get-wind-data.php')
            .then(response => response.json())
            .then(data => {
                const options = {
                    chart: {
                        type: 'area',
                        height: 280,
                        zoom: {
                            enabled: true
                        },
                        toolbar: {
                            show: true,
                            tools: {
                                download: true,
                                // selection: true,
                                zoom: true,
                                zoomin: true,
                                // pan: true,
                                // reset: true,
                                zoomout: true
                            }
                        }
                    },
                    series: [{
                        name: 'Wind Speed',
                        data: data
                    }],
                    xaxis: {
                        type: 'datetime',
                        labels: {
                            format: 'HH:mm',
                            datetimeUTC: false
                        },
                        title: {
                            text: 'Time'
                        }
                    },
                    yaxis: {
                        title: {
                            text: 'Wind Speed (m/s)'
                        },
                        min: 0
                    },
                    stroke: {
                        curve: 'smooth',
                        width: 3
                    },
                    markers: {
                        size: 4,
                        hover: {
                            size: 6
                        }
                    },
                    tooltip: {
                        x: {
                            format: 'dd MMM yyyy HH:mm'
                        },
                        y: {
                            formatter: function(value) {
                                return value + ' m/s';
                            }
                        }
                    },
                    colors: ["#E0A82E"]
                };

                const chart = new ApexCharts(document.querySelector("#windChart"), options);
                const chartMobile = new ApexCharts(document.querySelector("#windChartMobile"), options);
                chart.render();
                chartMobile.render();
            })
            .catch(error => console.error('Error loading wind data:', error));
    </script>

    <!-- Powercurve Chart -->
    <script>
    fetch('../api/get-power-curve-data.php')
        .then(response => {
            // First check if the response is ok
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            // Log the raw response for debugging
            response.clone().text().then(text => console.log('Raw response:', text));
            return response.json();
        })
        .then(response => {
            // Check if we have a successful response with data
            if (response.status !== 'success' || !response.data) {
                throw new Error(response.message || 'Failed to load power curve data');
            }

            const options = {
                series: [{
                    name: 'Power Output',
                    data: response.data.map(row => [
                        parseFloat(row.wind_speed),
                        parseFloat(row.power_output)
                    ])
                }],
                chart: {
                    type: 'line',
                    height: 280,
                    zoom: {
                        enabled: true
                    },
                    toolbar: {
                        show: true,
                        tools: {
                            download: true,
                            zoom: true,
                            zoomin: true,
                            zoomout: true
                        }
                    }
                },
                stroke: {
                    curve: 'smooth',
                    width: 3
                },
                markers: {
                    size: 4,
                    hover: {
                        size: 6
                    }
                },
                xaxis: {
                    title: {
                        text: 'Wind Speed (m/s)'
                    },
                    type: 'numeric'
                },
                yaxis: {
                    title: {
                        text: 'Power Output (MW)'
                    }
                },
                tooltip: {
                    shared: false,
                    y: {
                        formatter: function(value) {
                            return value.toFixed(3) + ' MW'
                        }
                    },
                    x: {
                        formatter: function(value) {
                            return value + ' m/s';
                        }
                    }
                },
                colors: ["#E0A82E"]
            };

            const chart = new ApexCharts(document.querySelector("#powerCurve"), options);
            const chartMobile = new ApexCharts(document.querySelector("#powerCurveMobile"), options);
            chart.render();
            chartMobile.render();
        })
        .catch(error => {
            console.error('Error loading power curve data:', error);
            // Show more user-friendly error message
            const errorMessage = '<div class="p-4 text-red-600">Unable to load power curve data. Please try again later.</div>';
            document.querySelector("#powerCurve").innerHTML = errorMessage;
            document.querySelector("#powerCurveMobile").innerHTML = errorMessage;
        });
    </script>
</body>

</html>