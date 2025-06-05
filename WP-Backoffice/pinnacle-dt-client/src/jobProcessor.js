// src/jobProcessor.js
const logger = require('./logger');
const wordpressClient = require('./wordpressClient');
const browserManager = require('./browserManager');
const dealerTrackAutomator = require('./dealerTrackAutomator'); // Uses refactored methods
const globalAppConfig = require('./config'); // Main config.json loaded here

let isProcessing = false;
let mainBrowserPage = null;

async function initialize() {
    try {
        logger.info('Initializing Job Processor...');
        await browserManager.connectToBrowser();
        mainBrowserPage = await browserManager.getPage();
        
        const dtHomeSubstring = globalAppConfig.puppeteer.dealertrack_fni_home_url_substring;
        if (mainBrowserPage && mainBrowserPage.url && !mainBrowserPage.url().includes(dtHomeSubstring)) {
            logger.warn(`Initial page is not DealerTrack F&I Home (${mainBrowserPage.url()}). Please ensure logged in and on a relevant DT page.`);
            // Optionally navigate:
            // const dtConfig = require('../config/dealertrack'); // Load dtConfig for path
            // await mainBrowserPage.goto(dtConfig.baseUrl + dtConfig.paths.fniHome, { waitUntil: 'networkidle0' });
        }
        logger.info('Job Processor initialized successfully.');
        return true;
    } catch (error) {
        logger.error(`Failed to initialize Job Processor: ${error.message}`);
        return false;
    }
}

async function processSingleJob(job) { // job comes from WordPress (via /new-job-notification or polling)
    if (!job || !job.job_id || !job.job_type || !job.data) { // Check for job.data
        logger.error('Invalid job object received for processing. Missing job_id, job_type, or job.data.', job);
        // If job_id exists, try to mark it as failed in WP
        if (job && job.job_id) {
            try {
                await wordpressClient.updateJobStatus(job.job_id, 'failed', {
                    error_message: 'Invalid job payload structure received by local client.'
                });
            } catch (updateError) {
                logger.error(`CRITICAL: Failed to update invalid job ${job.job_id} status to FAILED: ${updateError.message}`);
            }
        }
        return;
    }
    logger.info(`Processing job ID: ${job.job_id}, Type: ${job.job_type}`);

    // Use job.data for specific IDs
    const jobData = job.data; // Contains credit_application_id, deal_id, dt_reference_number etc.

    try {
        await wordpressClient.updateJobStatus(job.job_id, 'processing_by_local_client', {
            result_log: 'Local client started processing job.'
        });

        if (!mainBrowserPage || mainBrowserPage.isClosed()) {
            logger.info('Main browser page is closed or not available, getting a new one.');
            await browserManager.connectToBrowser(); // Ensure connection
            mainBrowserPage = await browserManager.getPage();
        }
        
        let result;
        let updatePayload = {};

        switch (job.job_type) {
            case 'submit_credit_app_to_dealertrack':
                if (!jobData.credit_application_id) {
                    throw new Error('Missing credit_application_id in job.data for submit_credit_app_to_dealertrack job.');
                }
                // Fetch full appData from WordPress using the ID from jobData
                const appDataForSubmission = await wordpressClient.getApplicationDataForSubmission(jobData.credit_application_id);
                result = await dealerTrackAutomator.submitCreditApplicationToDealerTrack(mainBrowserPage, appDataForSubmission, job.job_id);
                
                if (result.success && result.dtReferenceNumber) {
                    // WordPress will create the deal and update credit_app status
                    await wordpressClient.createDealFromApp(jobData.credit_application_id, result.dtReferenceNumber);
                    updatePayload.dt_reference_number = result.dtReferenceNumber;
                    logger.info(`[Job ${job.job_id}] Successfully submitted app ${jobData.credit_application_id}, DT Ref: ${result.dtReferenceNumber}`);
                }
                break;

            case 'check_dealertrack_deal_status':
                if (!jobData.deal_id || !jobData.dt_reference_number) {
                    throw new Error('Missing deal_id or dt_reference_number in job.data for check_dealertrack_deal_status job.');
                }
                result = await dealerTrackAutomator.checkApplicationStatusInDealerTrack(mainBrowserPage, jobData.dt_reference_number, job.job_id);
                
                if (result.success && result.dtStatusKey) {
                    await wordpressClient.updateDealFromDealertrack(jobData.deal_id, result.dtStatusKey, result.dtDealJacketData, `Status check result: ${result.dtStatusKey}`);
                    updatePayload.dt_status_text = result.dtStatusKey;
                    logger.info(`[Job ${job.job_id}] Successfully checked status for deal ${jobData.deal_id} (DT Ref: ${jobData.dt_reference_number}). New status: ${result.dtStatusKey}`);
                }
                break;

            default:
                logger.warn(`Unknown job type: ${job.job_type} for job ID: ${job.job_id}`);
                result = { success: false, errorMessage: `Unknown job type: ${job.job_type}` };
        }

        if (result.success) {
            await wordpressClient.updateJobStatus(job.job_id, 'completed', {
                result_log: `Job completed successfully. ${JSON.stringify(updatePayload)}`,
                ...updatePayload
            });
        } else {
            // Error message should be set by the automator function
            throw new Error(result.errorMessage || 'Job processing failed for unspecified reasons.');
        }

    } catch (error) {
        logger.error(`Error processing job ${job.job_id} (${job.job_type}): ${error.message}`);
        logger.error(error.stack); // Log stack trace
        if (mainBrowserPage && !mainBrowserPage.isClosed()) {
            await browserManager.takeScreenshot(mainBrowserPage, job.job_id, `error_job_${job.job_type}`);
        } else {
            logger.warn(`Could not take screenshot for job ${job.job_id} as page was not available.`);
        }
        try {
            await wordpressClient.updateJobStatus(job.job_id, 'failed', {
                error_message: error.message || 'An unexpected error occurred during local client processing.',
            });
        } catch (updateError) {
            logger.error(`CRITICAL: Failed to update job ${job.job_id} status to FAILED on WordPress: ${updateError.message}`);
        }
    }
}

async function checkForPendingJobs() {
    if (isProcessing) {
        logger.info('Job processing is already in progress. Skipping new poll cycle.');
        return;
    }
    isProcessing = true;
    logger.info('Checking for pending jobs...');

    try {
        const pendingJobs = await wordpressClient.fetchPendingJobs(); // This fetches an array of job objects
        if (pendingJobs && pendingJobs.length > 0) {
            logger.info(`Found ${pendingJobs.length} pending job(s). Processing one by one.`);
            for (const job of pendingJobs) { // Each job here already includes the 'data' field from WP if structured correctly by WP REST API
                try {
                    await browserManager.connectToBrowser();
                    if (!mainBrowserPage || mainBrowserPage.isClosed()) {
                        mainBrowserPage = await browserManager.getPage();
                    }
                    const dtHomeSubstring = globalAppConfig.puppeteer.dealertrack_fni_home_url_substring;
                    if (mainBrowserPage && mainBrowserPage.url && !mainBrowserPage.url().includes(dtHomeSubstring)) {
                        logger.warn(`During job loop, page is not DealerTrack F&I Home (${mainBrowserPage.url()}). Attempting to navigate.`);
                        // const dtConfig = require('../config/dealertrack');
                        // await mainBrowserPage.goto(dtConfig.baseUrl + dtConfig.paths.fniHome, { waitUntil: 'networkidle0' })
                        //    .catch(navErr => logger.error(`Failed to re-navigate to DT Home: ${navErr.message}`));
                        // For now, let job proceed and potentially fail if page is wrong.
                    }
                } catch (browserError) {
                    logger.error(`Browser connection issue before processing job ${job.job_id}: ${browserError.message}`);
                    await wordpressClient.updateJobStatus(job.job_id, 'failed', {
                        error_message: `Browser connection failed before processing: ${browserError.message}`
                    }).catch(e => logger.error(`Failed to mark job ${job.job_id} as failed for browser error: ${e.message}`));
                    continue; 
                }
                
                // The `job` from `fetchPendingJobs` should already have the structure:
                // { job_id, job_type, credit_application_id, deal_id, dt_reference_number }
                // So, we need to wrap it in a 'data' field IF `processSingleJob` expects it that way.
                // Let's adjust `fetchPendingJobs` in `wordpressClient.js` OR adjust `processSingleJob` here.
                // The WP action scheduler sends payload with `data` key.
                // The WP API for `/jobs/pending` sends a flat structure.
                // For consistency, let's make `processSingleJob` always expect the `data` sub-object.
                // So, when polling, we'll reconstruct it.

                const jobPayloadForProcessor = {
                    job_id: job.job_id,
                    job_type: job.job_type,
                    data: { // Reconstruct the 'data' field here
                        credit_application_id: job.credit_application_id,
                        deal_id: job.deal_id,
                        dt_reference_number: job.dt_reference_number,
                        // Add any other fields from `job` that should be under `data`
                    }
                };
                await processSingleJob(jobPayloadForProcessor);
            }
        } else {
            logger.info('No pending jobs found from polling.');
        }
    } catch (error) {
        logger.error(`Error in checkForPendingJobs loop: ${error.message}`);
        logger.error(error.stack);
    } finally {
        isProcessing = false;
    }
}

module.exports = {
    initialize,
    checkForPendingJobs,
    processSingleJob,
};