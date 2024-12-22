CREATE DATABASE windpower_db;
USE windpower_db;

CREATE TABLE wind_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    wind_speed DECIMAL(5,2) NOT NULL,
    wind_direction INT NOT NULL,
    power_output DECIMAL(10,2) NOT NULL,
    timestamp DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP 
);