// src/logger.js
const winston = require('winston');
const config = require('./config'); // Loads config using the function

const { combine, timestamp, printf, colorize } = winston.format;

const myFormat = printf(({ level, message, timestamp, service }) => {
  return `${timestamp} [${service || 'App'}] ${level}: ${message}`;
});

const transports = [
    new winston.transports.Console({
        format: combine(
            colorize(),
            timestamp({ format: 'YYYY-MM-DD HH:mm:ss' }),
            myFormat
        ),
        level: config.logging.level || 'info',
    })
];

if (config.logging.log_to_file) {
    transports.push(
        new winston.transports.File({
            filename: config.logging.log_file_path || 'client.log',
            format: combine(
                timestamp({ format: 'YYYY-MM-DD HH:mm:ss' }),
                myFormat
            ),
            level: config.logging.level || 'info',
            maxsize: 5242880, // 5MB
            maxFiles: 5,
        })
    );
}

const logger = winston.createLogger({
    transports: transports,
});

module.exports = logger;