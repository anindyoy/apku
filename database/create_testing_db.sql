-- Create testing database
CREATE DATABASE IF NOT EXISTS apku_testing;

-- Grant privileges (adjust as needed for your setup)
GRANT ALL PRIVILEGES ON apku_testing.* TO 'root'@'localhost';
GRANT ALL PRIVILEGES ON apku_testing.* TO 'root'@'127.0.0.1';

FLUSH PRIVILEGES;