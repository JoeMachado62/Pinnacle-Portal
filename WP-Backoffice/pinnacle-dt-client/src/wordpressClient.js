// src/wordpressClient.js
const axios = require('axios');
const config = require('./config'); // Main config (config.json)
const logger = require('./logger');

const wpApiClient = axios.create({
    baseURL: config.wordpress_api.base_url,
    timeout: 30000,
    headers: {
        'Content-Type': 'application/json',
        'X-Paf-Api-Secret': config.wordpress_api.shared_secret,
    }
});

async function fetchPendingJobs() {
    try {
        logger.info('Fetching pending jobs from WordPress...');
        // This endpoint in rest-api-endpoints.php currently returns:
        // [{ job_id, job_type, credit_application_id, deal_id, dt_reference_number }]
        const response = await wpApiClient.get('/jobs/pending');
        logger.debug(`Received ${response.data.length} pending jobs from polling.`);
        return response.data; // Array of job objects (flat structure)
    } catch (error) {
        logger.error(`Error fetching pending jobs: ${error.message}`);
        if (error.response) {
            logger.error(`WP API Response (fetchPendingJobs): ${error.response.status} - ${JSON.stringify(error.response.data)}`);
        }
        return [];
    }
}

async function updateJobStatus(jobId, status, details = {}) {
    try {
        logger.info(`Updating job ${jobId} status to ${status} with details: ${JSON.stringify(details)}`);
        const payload = { status, ...details }; // WP REST endpoint expects status and other details at top level
        const response = await wpApiClient.post(`/jobs/${jobId}/update`, payload);
        logger.info(`Job ${jobId} status updated successfully on WordPress.`);
        return response.data;
    } catch (error) {
        logger.error(`Error updating job ${jobId} status on WordPress: ${error.message}`);
         if (error.response) {
            logger.error(`WP API Response (updateJobStatus): ${error.response.status} - ${JSON.stringify(error.response.data)}`);
        }
        throw error;
    }
}

async function getApplicationDataForSubmission(appId) {
    try {
        logger.info(`Fetching application data for WP app_id: ${appId}`);
        // This WP endpoint returns { id, primary_borrower, co_borrower, vehicle_data, financial_data, ... }
        const response = await wpApiClient.get(`/credit-applications/${appId}/data_for_submission`);
        logger.debug(`Application data for ${appId} fetched successfully from WordPress.`);
        // Ensure sensitive data (SSN, DL) is decrypted by WordPress endpoint as per rest-api-endpoints.php
        return response.data;
    } catch (error) {
        logger.error(`Error fetching application data for WP app_id ${appId}: ${error.message}`);
        if (error.response) {
            logger.error(`WP API Response (getApplicationDataForSubmission): ${error.response.status} - ${JSON.stringify(error.response.data)}`);
        }
        throw error;
    }
}

async function createDealFromApp(appId, dtReferenceNumber) {
    try {
        logger.info(`Requesting WordPress to create deal for app_id: ${appId}, DT Ref: ${dtReferenceNumber}`);
        // WP endpoint /deals/create_from_app/{app_id} expects { dt_reference_number }
        const response = await wpApiClient.post(`/deals/create_from_app/${appId}`, {
            dt_reference_number: dtReferenceNumber,
        });
        logger.info(`WordPress created deal for app_id ${appId}. WP Deal ID: ${response.data.deal_id}`);
        return response.data;
    } catch (error) {
        logger.error(`Error requesting WordPress to create deal for app_id ${appId}: ${error.message}`);
        if (error.response) {
            logger.error(`WP API Response (createDealFromApp): ${error.response.status} - ${JSON.stringify(error.response.data)}`);
        }
        throw error;
    }
}

async function updateDealFromDealertrack(dealId, dtStatusKey, dtDealJacketData = {}, notes = '') {
    try {
        logger.info(`Requesting WordPress to update deal ${dealId} from DealerTrack. Status: ${dtStatusKey}`);
        // WP endpoint /deals/{deal_id}/update_from_dealertrack expects { dt_status_key, dt_deal_jacket_data, notes }
        const payload = {
            dt_status_key: dtStatusKey,
            dt_deal_jacket_data: dtDealJacketData,
            notes: notes,
        };
        const response = await wpApiClient.post(`/deals/${dealId}/update_from_dealertrack`, payload);
        logger.info(`WordPress updated deal ${dealId} successfully from DealerTrack data.`);
        return response.data;
    } catch (error) {
        logger.error(`Error requesting WordPress to update deal ${dealId} from DealerTrack: ${error.message}`);
        if (error.response) {
            logger.error(`WP API Response (updateDealFromDealertrack): ${error.response.status} - ${JSON.stringify(error.response.data)}`);
        }
        throw error;
    }
}

// getDealsForStatusCheck remains the same as it's not directly part of job processing flow modification.
async function getDealsForStatusCheck() {
    try {
        logger.info('Fetching deals from WordPress that require status check (client-initiated poll)...');
        const response = await wpApiClient.get('/deals/for_status_check');
        logger.debug(`Received ${response.data.length} deals for status check from WordPress.`);
        return response.data; 
    } catch (error) {
        logger.error(`Error fetching deals for status check from WordPress: ${error.message}`);
        if (error.response) {
            logger.error(`WP API Response (getDealsForStatusCheck): ${error.response.status} - ${JSON.stringify(error.response.data)}`);
        }
        return [];
    }
}

module.exports = {
    fetchPendingJobs,
    updateJobStatus,
    getApplicationDataForSubmission,
    createDealFromApp,
    updateDealFromDealertrack,
    getDealsForStatusCheck,
};