class Dashboard {
    constructor() {
        this.chart = null;
        this.initializeCharts();
        this.setupUpdateIntervals();
    }

    async initializeCharts() {
        const data = await this.fetchHistoricalData();
        const ctx = document.getElementById('windChart').getContext('2d');

        this.chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.map(d => new Date(d.timestamp).toLocaleDateString()),
                datasets: [{
                    label: 'Wind Speed (m/s)',
                    data: data.map(d => d.wind_speed),
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true }
                }
            }
        }); 
    }

    async updateCurrentData() {
        try {
            const response = await fetch('/api/current-data.php');
            const data = await response.json();
            
            document.getElementById('currentSpeed').textContent = `${data.wind_speed} m/s`;
            document.getElementById('currentDirection').textContent = `${data.wind_direction}°`;
            document.getElementById('powerOutput').textContent = `${data.power_output} kW`;             
        } catch (error) {
            console.error('Error updating current data:', error);
        }
    }

    async updateForecast() {
        try {
            const response = await fetch('/api/forecast.php');
            const data = await response.json();

            document.getElementById('forecast').innerHTML = `
                <div class="forecast-content">
                    <h3>24-Hour Forecast</h3>
                    <p>${data.prediction}</p>
                    <small>Confidence: ${data.confidence}%</small>
                </div>
            `;
        } catch (error) {
            console.error('Error updating forecast:', error);
        }
    }

    async fetchHistoricalData() {
        const response = await fetch('/api/historical-data.php');
        return await response.json();
    }

    setupUpdateIntervals() {
        // Update current data every minute
        setInterval(() => this.updateCurrentData(), 60000);

        // Update forecast every hour
        setInterval(() => this.updateForecast(), 3600000);

        // Initial updates
        this.updateCurrentData();
        this.updateForecast();
    }
}

// Initialize dashboard when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new Dashboard();
});