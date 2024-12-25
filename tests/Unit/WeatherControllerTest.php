<?php
namespace Tests\Controllers;

require_once __DIR__ . '/../../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use Controllers\WeatherController;

class WeatherControllerTest extends TestCase
{
    private $weatherController;

    protected function setUp(): void
    {
        $this->weatherController = new WeatherController();
    }

    /**
     * @dataProvider windSpeedProvider
     */
    public function testCalculatePowerOutput($windSpeed, $expectedPowerRange)
    {
        $power = $this->weatherController->calculatePowerOutput($windSpeed);
        
        // Check if power output is within expected range
        $this->assertGreaterThanOrEqual($expectedPowerRange[0], $power);
        $this->assertLessThanOrEqual($expectedPowerRange[1], $power);
    }

    public function windSpeedProvider()
    {
        return [
            'No wind' => [
                0,
                [0, 0.1] // Expect near-zero output
            ],
            'Low wind' => [
                5,
                [5, 7] // Expected range for 5 m/s wind
            ],
            'Optimal wind' => [
                12,
                [103.7, 103.8] // All turbines at max capacity (103.7 MW = 0.85 MW * 122)
            ],
            'High wind' => [
                25,
                [103.7, 103.8] // Should be capped at max capacity
            ]
        ];
    }

    public function testCalculatePowerOutputNegativeWind()
    {
        $power = $this->weatherController->calculatePowerOutput(-5);
        $this->assertEquals(0, $power, 'Negative wind speeds should return 0 power');
    }
}
