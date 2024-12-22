<?php
class WeatherController {
    private $windData;
    private $openAI;

    public function __construct() {
        $this->windData = new WindData();
        $this->openAI = new OpenAIService();
    }

    public function getCurrentConditions() {
        return $this->windData->getCurrentData();
    }

    public function getForecast() {
        $historical = $this->windData->getHistoricalData(7);
        return $this->openAI->generateForecast($historical);
    }

    public function getHistoricalData($days = 30) {
        return $this->windData->getHistoricalData($days);
    }
}