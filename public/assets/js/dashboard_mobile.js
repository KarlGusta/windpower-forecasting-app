class DashboardMobile {
    constructor(historicalData) {
        this.historicalData = historicalData;
        this.initializeChart();
    }

    initializeChart() {
        // Process data for the chart
        const data = this.historicalData.map(data => ({
            x: new Date(data.timestamp),
            y: data.wind_speed
        }));

        const options = {
            chart: {
                type: "area",
                height: 240,
                parentHeightOffset: 0,
                toolbar: {
                    show: false,
                },
                animations: {
                    enabled: true
                },
            },
            dataLabels: {
                enabled: false,
            },
            fill: {
                opacity: .16,
                type: 'solid'
            },
            stroke: {
                width: 2,
                lineCap: "round",
                curve: "smooth",
            },
            series: [{
                name: "Wind Speed",
                data: data
            }],
            grid: {
                padding: {
                    top: -20,
                    right: 0,
                    left: -4,
                    bottom: -4
                },
                strokeDashArray: 4,
            },
            xaxis: {
                type: 'datetime',
                labels: {
                    formatter: function(value) {
                        return new Date(value).toLocaleString('en-US', { hour: 'numeric', hour12: true });
                    }
                }
            },
            yaxis: {
                labels: {
                    padding: 4,
                    formatter: function(value) {
                        return value.toFixed(1);
                    }
                },
            },
            colors: ["#E0A82E"],
            legend: {
                show: false,
            },
        };

        new ApexCharts(document.getElementById('windChartMobile'), options).render();
    }
}