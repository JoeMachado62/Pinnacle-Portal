// src/dealerTrackAutomator.js
const logger = require('./logger');
const { takeScreenshot } = require('./browserManager');
const dtAppConfig = require('../config/dealertrack'); // Path to the new config

// Import Page Objects (adjust paths if they are not in 'pages/' at root)
const CustomerSearchPage = require('../pages/CustomerSearchPage');
const ApplicationWizardPage = require('../pages/ApplicationWizardPage');
const AppStatusPage = require('../pages/AppStatusPage');
const DealJacketPage = require('../pages/DealJacketPage'); // If used for more details

async function submitCreditApplicationToDealerTrack(page, appData, jobId) {
    logger.info(`[Job ${jobId}] Starting credit application submission with Page Objects for app ID: ${appData.id}`);
    const customerSearchPage = new CustomerSearchPage(page, dtAppConfig);
    const applicationWizardPage = new ApplicationWizardPage(page, dtAppConfig, appData); // Pass appData here

    try {
        // 1. Navigate to start new Credit Application (via customer search)
        // This might involve going to F&I home then clicking a link, or direct to search page.
        // The CustomerSearchPage's startNewApplicationViaQuickLink handles this.
        await customerSearchPage.startNewApplicationViaQuickLink();
        
        // Optional: Search for customer to avoid duplicates (if this is the flow)
        // await customerSearchPage.enterNameAndSearch({
        //     firstName: appData.primary_borrower.personalInformation.applicantName.split(' ')[0],
        //     lastName: appData.primary_borrower.personalInformation.applicantName.substring(appData.primary_borrower.personalInformation.applicantName.indexOf(' ') + 1)
        //     // ssn: appData.primary_borrower.personalInformation.ssn // Decrypted SSN
        // });

        // if (await customerSearchPage.hasDuplicates()) {
        //     logger.warn(`[Job ${jobId}] Possible duplicate customer found for app ID ${appData.id}. current project scope is to proceed with new application.`);
        //     await customerSearchPage.proceedWithNewApplication(); // This would click "Continue with New"
        // }

        // 2. Fill out the application preferences (first screen of wizard)
        await applicationWizardPage.selectApplicationPreferences();
        await takeScreenshot(page, jobId, 'after_app_prefs');

        // 3. Fill main application form
        await applicationWizardPage.fillApplicantInformation();
        await applicationWizardPage.fillCoApplicantInformation(); // Skips if no co-app data
        await applicationWizardPage.fillVehicleAndFinancingInformation();
        await takeScreenshot(page, jobId, 'before_disclosures');

        // 4. Handle Disclosures & Final Submit
        await applicationWizardPage.handleDisclosuresAndSubmit();
        await takeScreenshot(page, jobId, 'after_final_submit_landed');

        // 5. Extract DealerTrack Reference Number
        const dtReferenceNumber = await applicationWizardPage.extractDealReferenceNumber();
        
        if (!dtReferenceNumber) {
             throw new Error('Critical: Could not extract DealerTrack Reference Number after submission.');
        }

        logger.info(`[Job ${jobId}] Credit application submission process completed. DT Ref: ${dtReferenceNumber}`);
        return { success: true, dtReferenceNumber };

    } catch (error) {
        logger.error(`[Job ${jobId}] FAILED during Page Object based submission for app ID ${appData.id}: ${error.message}`);
        logger.error(error.stack); // Log stack for better debugging
        await takeScreenshot(page, jobId, `submission_error_${error.constructor.name}`);
        return { success: false, errorMessage: error.message };
    }
}

async function checkApplicationStatusInDealerTrack(page, dtReferenceNumber, jobId) {
    logger.info(`[Job ${jobId}] Checking status with Page Objects for DealerTrack Ref: ${dtReferenceNumber}`);
    const appStatusPage = new AppStatusPage(page, dtAppConfig);
    // const dealJacketPage = new DealJacketPage(page, dtAppConfig); // If navigating to full jacket

    try {
        await appStatusPage.navigateTo();
        await appStatusPage.searchForApplication(dtReferenceNumber);
        await takeScreenshot(page, jobId, `status_results_${dtReferenceNumber}`);

        const rawDtStatusText = await appStatusPage.getFirstResultStatusText();

        // Basic status mapping (enhance in dtAppConfig.js or here)
        let dtStatusKey = 'processing'; // Default
        const lowerStatus = rawDtStatusText.toLowerCase();

        if (lowerStatus.includes('approved') && !lowerStatus.includes('conditional')) {
            dtStatusKey = 'final_approval';
        } else if (lowerStatus.includes('conditional approval') || lowerStatus.includes('conditionally approved')) {
            dtStatusKey = 'conditional_approval';
        } else if (lowerStatus.includes('declined')) {
            dtStatusKey = 'deal_declined';
        } else if (lowerStatus.includes('funded')) {
            dtStatusKey = 'deal_funded';
        } else if (lowerStatus.includes('submitted') || lowerStatus.includes('pending')) {
            dtStatusKey = 'deal_submitted'; // Or a more specific "processing_by_lender"
        }
        // ... more sophisticated mapping ...

        // For now, we only have the status from the table.
        // If more data is needed, you would navigate to the deal jacket:
        // if (await appStatusPage.navigateToDealJacketFromFirstResult()) {
        //     const dealJacketData = await dealJacketPage.getFullDealJacketData();
        //     return { success: true, dtStatusKey, dtDealJacketData: dealJacketData };
        // }

        const dtDealJacketData = { 
            dealerTrackReportedStatus: rawDtStatusText,
            lastChecked: new Date().toISOString()
        };

        logger.info(`[Job ${jobId}] Status check for ${dtReferenceNumber} complete. Mapped Status: ${dtStatusKey}, Raw: ${rawDtStatusText}`);
        return { success: true, dtStatusKey, dtDealJacketData };

    } catch (error) {
        logger.error(`[Job ${jobId}] Error checking status for DT Ref ${dtReferenceNumber} using Page Objects: ${error.message}`);
        logger.error(error.stack);
        await takeScreenshot(page, jobId, `status_check_error_${dtReferenceNumber}`);
        return { success: false, errorMessage: error.message };
    }
}

module.exports = {
    submitCreditApplicationToDealerTrack,
    checkApplicationStatusInDealerTrack,
};