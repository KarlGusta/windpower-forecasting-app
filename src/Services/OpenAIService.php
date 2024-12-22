<?php
class OpenAIService {
    private $api_key;
    private $model;

    public function __construct() {
        $config = require __DIR__ . '/../../config/config.php';
        $this->api_key = $config['openai']['api_key'];
        $this->model = $config['openai']['model'];
    }

    public function generateForecast($historical_data) {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.openai.com/v1/chat/completions',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->api_key
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a wind power prediction expert.'
                    ],
                    [
                        'role' => 'user',
                        'content' => "Based on this historical wind data, provide a 24-hour forecast: " . json_encode($historical_data)                        
                    ]
                ]                                
            ]) 
                    ]);

                    $response = curl_exec($curl);
                    curl_close($curl);

                    return json_decode($response, true);
    }
}