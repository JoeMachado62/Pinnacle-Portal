// src/config.js
const fs = require('fs');
const path = require('path');

let config = null;

function loadConfig() {
    if (config) {
        return config;
    }

    const configPath = path.resolve(__dirname, '..', 'config.json'); // Points to config.json in project root
    try {
        if (fs.existsSync(configPath)) {
            const rawConfig = fs.readFileSync(configPath);
            config = JSON.parse(rawConfig);
            
            // Create screenshot and log directories if they don't exist
            if (config.puppeteer && config.puppeteer.screenshot_path) {
                const screenshotDir = path.resolve(__dirname, '..', config.puppeteer.screenshot_path);
                if (!fs.existsSync(screenshotDir)) {
                    fs.mkdirSync(screenshotDir, { recursive: true });
                }
            }
            if (config.logging && config.logging.log_to_file && config.logging.log_file_path) {
                const logDir = path.dirname(path.resolve(__dirname, '..', config.logging.log_file_path));
                 if (!fs.existsSync(logDir)) {
                    fs.mkdirSync(logDir, { recursive: true });
                }
            }

            return config;
        } else {
            console.error(`FATAL: Configuration file not found at ${configPath}. Please create it from config.example.json.`);
            process.exit(1);
        }
    } catch (error) {
        console.error(`FATAL: Error loading or parsing configuration file: ${error.message}`);
        process.exit(1);
    }
}

module.exports = loadConfig();