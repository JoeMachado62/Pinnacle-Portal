// index.js
const express = require('express');
const bodyParser = require('body-parser');
const config = require('./src/config'); // Main config (config.json)
const logger = require('./src/logger');
const jobProcessor = require('./src/jobProcessor');
const browserManager = require('./src/browserManager');

const app = express();
app.use(bodyParser.json());

function apiKeyAuth(req, res, next) {
    const providedApiKey = req.header('X-Paf-WpJob-Api-Key');
    if (!providedApiKey || providedApiKey !== config.local_server.wp_dispatch_api_key) {
        logger.warn(`Unauthorized API access attempt to /new-job-notification. IP: ${req.ip}. Key Provided: ${providedApiKey ? '******' : 'MISSING'}`);
        return res.status(403).json({ success: false, message: 'Forbidden: Invalid or missing API Key' });
    }
    next();
}

// API endpoint for WordPress to notify of a new job
// WordPress (action-scheduler-jobs.php) sends a payload like:
// { job_id, job_type, data: { credit_application_id, deal_id, dt_reference_number } }
app.post('/new-job-notification', apiKeyAuth, async (req, res) => {
    const jobPayload = req.body; 
    logger.info(`Received new job notification from WordPress for job ID: ${jobPayload.job_id}, Type: ${jobPayload.job_type}`);

    // Validate that the payload has the expected structure (job_id, job_type, and the 'data' object)
    if (!jobPayload || !jobPayload.job_id || !jobPayload.job_type || typeof jobPayload.data !== 'object' || jobPayload.data === null) {
        logger.error('Invalid job payload received from WordPress notification. Missing job_id, job_type, or job.data object.', jobPayload);
        return res.status(400).json({ success: false, message: 'Invalid job payload structure' });
    }

    // Asynchronously process the job. jobProcessor.processSingleJob expects this payload structure.
    jobProcessor.processSingleJob(jobPayload)
        .then(() => logger.info(`Direct processing for notified job ${jobPayload.job_id} handled by processSingleJob.`))
        .catch(err => logger.error(`Unhandled error from async processSingleJob for notified job ${jobPayload.job_id}: ${err.message}`));

    res.status(202).json({ success: true, message: 'Job notification received and processing initiated.' });
});

let isPollingCycleActive = false; // Renamed for clarity

const originalCheckForPendingJobs = jobProcessor.checkForPendingJobs;
jobProcessor.checkForPendingJobs = async () => {
    isPollingCycleActive = true;
    try {
        await originalCheckForPendingJobs();
    } finally {
        isPollingCycleActive = false;
    }
};

app.get('/health', async (req, res) => {
    let browserStatus = 'disconnected';
    let browserVersion = 'N/A';
    let connectedBrowserInstance; // Renamed for clarity

    try {
        connectedBrowserInstance = browserManager.getBrowserInstanceForHealthCheck();
        if (connectedBrowserInstance && connectedBrowserInstance.isConnected()) {
            browserStatus = 'connected';
            browserVersion = await connectedBrowserInstance.version();
        } else {
            logger.info('[Health Check] Browser not connected or instance is null, attempting to (re)connect...');
            await browserManager.connectToBrowser(); // This will throw if it fails critically
            connectedBrowserInstance = browserManager.getBrowserInstanceForHealthCheck();
            if (connectedBrowserInstance && connectedBrowserInstance.isConnected()) {
                browserStatus = 'connected (re-established for health check)';
                browserVersion = await connectedBrowserInstance.version();
            } else {
                browserStatus = 'disconnected (failed to connect for health check or instance still null)';
            }
        }
    } catch (e) {
        logger.error(`[Health Check] Error checking browser status: ${e.message}`);
        browserStatus = `error during health check: ${e.message.substring(0,150)}`;
    }

    res.status(200).json({
        status: 'ok',
        message: 'Pinnacle DT Client is running.',
        timestamp: new Date().toISOString(),
        browser: {
            status: browserStatus,
            version: browserVersion,
            configured_endpoint: config.puppeteer.browser_ws_endpoint
        },
        job_processor_status: {
            is_polling_cycle_active: isPollingCycleActive, // Updated flag name
            // is_direct_processing_active: jobProcessor.getDirectProcessingState() // If you add such a state tracker
        }
    });
});

async function main() {
    logger.info('Starting Pinnacle Auto Finance DealerTrack Client (Refactored)...');
    
    try {
        await jobProcessor.initialize(); 
        logger.info('Client initialized. Starting job polling.');

        // Initial check for jobs from polling
        jobProcessor.checkForPendingJobs();

        // Periodically poll for pending jobs
        setInterval(() => {
            jobProcessor.checkForPendingJobs();
        }, config.job_processing.poll_interval_ms || 60000);

        app.listen(config.local_server.port, () => {
            logger.info(`Local client server listening on port ${config.local_server.port}`);
            logger.info(`Awaiting jobs from WordPress at ${config.wordpress_api.base_url} (via webhook or polling)`);
            logger.info(`Puppeteer configured to connect to: ${config.puppeteer.browser_ws_endpoint}`);
            logger.info(`Ensure Chrome/Firefox is running with --remote-debugging-port=${config.puppeteer.browser_ws_endpoint.split(':')[2]} and you are logged into DealerTrack.`);
        });

    } catch (initializationError) {
        logger.error(`CRITICAL: Client initialization failed: ${initializationError.message}. Exiting.`);
        logger.error(initializationError.stack);
        process.exit(1); 
    }
}

main().catch(error => {
    logger.error(`Unhandled critical error in main execution: ${error.message}`);
    logger.error(error.stack);
    process.exit(1);
});

async function shutdown(signal) {
    logger.info(`${signal} received. Shutting down client gracefully...`);
    try {
        // Give any ongoing job a moment to try and finish or log, then disconnect.
        // A more sophisticated shutdown would involve signaling the jobProcessor to stop accepting new work.
        await new Promise(resolve => setTimeout(resolve, 2000)); 
        await browserManager.closeBrowser(); // This disconnects, doesn't close the user's browser
    } catch (e) {
        logger.error(`Error during browser disconnect on shutdown: ${e.message}`);
    }
    logger.info('Client shutdown complete.');
    process.exit(0);
}

process.on('SIGINT', () => shutdown('SIGINT'));
process.on('SIGTERM', () => shutdown('SIGTERM'));