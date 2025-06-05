// src/formInteraction.js
const logger = require('./logger');

/**
 * Enhanced Form Interaction Module for DealerTrack Forms
 */
module.exports = {
    /**
     * Type text into a form field with error handling
     * @param {Page} page - Puppeteer page object
     * @param {string} selector - CSS selector for the field
     * @param {string} text - Text to type
     * @param {string} fieldName - Field name for logging
     * @param {Object} options - Additional options
     * @returns {Promise<boolean>} - True if successful
     */
    async typeIntoField(page, selector, text, fieldName, options = {}) {
        const { timeout = 10000, retries = 2, delay = 50 } = options;
        
        let attempts = 0;
        while (attempts <= retries) {
            try {
                await page.waitForSelector(selector, { timeout, visible: true });
                
                // Clear the field first if requested
                if (options.clearFirst) {
                    await page.evaluate((sel) => {
                        document.querySelector(sel).value = '';
                    }, selector);
                }
                
                await page.type(selector, String(text), { delay });
                logger.debug(`Typed "${text}" into ${fieldName} (${selector})`);
                return true;
            } catch (error) {
                attempts++;
                logger.warn(`Attempt ${attempts}/${retries+1} failed for ${fieldName} (${selector}): ${error.message}`);
                
                if (attempts > retries) {
                    if (options.required) {
                        throw new Error(`Failed to type into required field ${fieldName}: ${error.message}`);
                    } else {
                        logger.error(`Failed to type into ${fieldName} (${selector}): ${error.message}`);
                        return false;
                    }
                }
                
                // Wait before retry
                await page.waitForTimeout(500);
            }
        }
    },
    
    /**
     * Click on an element with error handling
     * @param {Page} page - Puppeteer page object
     * @param {string} selector - CSS selector for the element
     * @param {string} elementName - Element name for logging
     * @param {Object} options - Additional options
     * @returns {Promise<boolean>} - True if successful
     */
    async clickElement(page, selector, elementName, options = {}) {
        const { timeout = 10000, retries = 2, waitAfterClick = 0 } = options;
        
        let attempts = 0;
        while (attempts <= retries) {
            try {
                await page.waitForSelector(selector, { timeout, visible: true });
                await page.click(selector);
                logger.debug(`Clicked ${elementName} (${selector})`);
                
                if (waitAfterClick > 0) {
                    await page.waitForTimeout(waitAfterClick);
                }
                
                return true;
            } catch (error) {
                attempts++;
                logger.warn(`Attempt ${attempts}/${retries+1} failed to click ${elementName} (${selector}): ${error.message}`);
                
                if (attempts > retries) {
                    if (options.required) {
                        throw new Error(`Failed to click required element ${elementName}: ${error.message}`);
                    } else {
                        logger.error(`Failed to click ${elementName} (${selector}): ${error.message}`);
                        return false;
                    }
                }
                
                // Wait before retry
                await page.waitForTimeout(500);
            }
        }
    },
    
    /**
     * Select a dropdown option with error handling
     * @param {Page} page - Puppeteer page object
     * @param {string} selector - CSS selector for the dropdown
     * @param {string} value - Value to select
     * @param {string} fieldName - Field name for logging
     * @param {Object} options - Additional options
     * @returns {Promise<boolean>} - True if successful
     */
    async selectDropdownByValue(page, selector, value, fieldName, options = {}) {
        const { timeout = 10000, retries = 2, waitAfterSelect = 0 } = options;
        
        let attempts = 0;
        while (attempts <= retries) {
            try {
                await page.waitForSelector(selector, { timeout, visible: true });
                await page.select(selector, String(value));
                logger.debug(`Selected "${value}" for ${fieldName} (${selector})`);
                
                if (waitAfterSelect > 0) {
                    await page.waitForTimeout(waitAfterSelect);
                }
                
                return true;
            } catch (error) {
                attempts++;
                logger.warn(`Attempt ${attempts}/${retries+1} failed to select value for ${fieldName} (${selector}): ${error.message}`);
                
                if (attempts > retries) {
                    if (options.required) {
                        throw new Error(`Failed to select required value for ${fieldName}: ${error.message}`);
                    } else {
                        logger.error(`Failed to select value for ${fieldName} (${selector}): ${error.message}`);
                        return false;
                    }
                }
                
                // Wait before retry
               await page.waitForTimeout(500);
           }
       }
   },
   
   /**
    * Navigate through DealerTrack application pages with robust error handling
    * @param {Page} page - Puppeteer page object
    * @param {string} nextButtonSelector - Selector for the 'Next' button
    * @param {Object} options - Navigation options
    * @returns {Promise<boolean>} - True if successful
    */
   async navigateToNextPage(page, nextButtonSelector, options = {}) {
       const { timeout = 30000, retries = 2, waitAfterClick = 1000 } = options;
       
       let attempts = 0;
       while (attempts <= retries) {
           try {
               // Ensure button is visible and clickable
               await page.waitForSelector(nextButtonSelector, { timeout: 10000, visible: true });
               
               // Take screenshot before navigation attempt (for debugging)
               if (options.screenshotPrefix) {
                   await page.screenshot({ 
                       path: `./screenshots/${options.screenshotPrefix}_before_navigation_${attempts}.png`, 
                       fullPage: true 
                   });
               }
               
               // Save current URL to verify navigation
               const currentUrl = page.url();
               
               // Click the next button
               await page.click(nextButtonSelector);
               logger.info(`Clicked navigation button: ${nextButtonSelector}`);
               
               // Wait briefly for navigation to start
               await page.waitForTimeout(waitAfterClick);
               
               // Wait for navigation to complete with multiple success criteria
               try {
                   // First attempt with networkidle0 (more reliable but can time out)
                   await page.waitForNavigation({ 
                       waitUntil: 'networkidle0', 
                       timeout: timeout - waitAfterClick 
                   });
               } catch (navError) {
                   logger.warn(`Strict navigation wait failed (networkidle0): ${navError.message}`);
                   
                   // Check if we've actually navigated despite the timeout
                   const newUrl = page.url();
                   if (newUrl !== currentUrl) {
                       logger.info(`Navigation succeeded despite timeout. Old URL: ${currentUrl}, New URL: ${newUrl}`);
                       // Continue as successful
                   } else {
                       // Try with a more lenient condition
                       try {
                           await page.waitForNavigation({ 
                               waitUntil: 'load', 
                               timeout: 5000 
                           });
                       } catch (lenientNavError) {
                           // Still check URL change as load event might not fire
                           const finalUrl = page.url();
                           if (finalUrl !== currentUrl) {
                               logger.info(`Navigation succeeded with URL change only. Final URL: ${finalUrl}`);
                           } else {
                               throw new Error(`Navigation appears to have failed: page URL did not change`);
                           }
                       }
                   }
               }
               
               // Take screenshot after navigation (for debugging)
               if (options.screenshotPrefix) {
                   await page.screenshot({ 
                       path: `./screenshots/${options.screenshotPrefix}_after_navigation_success.png`, 
                       fullPage: true 
                   });
               }
               
               // Additional verification that page loaded correctly
               if (options.expectedElementSelector) {
                   await page.waitForSelector(options.expectedElementSelector, { 
                       timeout: 10000, 
                       visible: true 
                   });
                   logger.info(`Navigation confirmed successful - found expected element: ${options.expectedElementSelector}`);
               }
               
               logger.info(`Successfully navigated to next page: ${page.url()}`);
               return true;
               
           } catch (error) {
               attempts++;
               logger.warn(`Attempt ${attempts}/${retries+1} - Navigation failed: ${error.message}`);
               
               // Take error screenshot
               if (options.screenshotPrefix) {
                   await page.screenshot({ 
                       path: `./screenshots/${options.screenshotPrefix}_navigation_error_${attempts}.png`, 
                       fullPage: true 
                   });
               }
               
               if (attempts > retries) {
                   throw new Error(`Failed to navigate to next page after ${retries+1} attempts: ${error.message}`);
               }
               
               // Wait before retry
               await page.waitForTimeout(2000);
           }
       }
   },
   
   /**
    * Check if an element exists and is visible on the page
    * @param {Page} page - Puppeteer page object
    * @param {string} selector - CSS selector for the element
    * @param {Object} options - Options
    * @returns {Promise<boolean>} - True if element exists and is visible
    */
   async elementExists(page, selector, options = {}) {
       const { timeout = 5000 } = options;
       
       try {
           await page.waitForSelector(selector, { timeout, visible: true });
           return true;
       } catch (error) {
           return false;
       }
   },
   
   /**
    * Fill a form section based on field mapping configuration
    * @param {Page} page - Puppeteer page object
    * @param {Object} sectionMapping - Field mapping for the section
    * @param {Object} sectionData - Data for the section
    * @param {Object} options - Options
    * @returns {Promise<boolean>} - True if successful
    */
   async fillFormSection(page, sectionMapping, sectionData, options = {}) {
       const { jobId = 'unknown', sectionName = 'unknown' } = options;
       
       logger.info(`[Job ${jobId}] Filling ${sectionName} section`);
       
       // Check if this section should be processed based on condition
       if (sectionMapping.shouldProcess && !sectionMapping.shouldProcess(sectionData)) {
           logger.info(`[Job ${jobId}] Skipping ${sectionName} section based on condition`);
           return true;
       }
       
       // Process each field in the section
       for (const [fieldName, fieldConfig] of Object.entries(sectionMapping)) {
           // Skip special properties
           if (['shouldProcess', 'shouldShow', 'exists'].includes(fieldName)) {
               continue;
           }
           
           // Get the field value from section data
           const fieldValue = typeof fieldConfig.value === 'function' 
               ? fieldConfig.value(sectionData)
               : (fieldConfig.transform 
                   ? fieldConfig.transform(sectionData[fieldName]) 
                   : sectionData[fieldName]);
           
           // Skip empty/undefined values for non-required fields
           if ((fieldValue === undefined || fieldValue === null || fieldValue === '') && !fieldConfig.required) {
               logger.debug(`[Job ${jobId}] Skipping empty field: ${fieldName}`);
               continue;
           }
           
           // Check conditional selector (if field should appear based on other selections)
           if (fieldConfig.conditionalSelector) {
               let shouldProcess = true;
               
               if (typeof fieldConfig.condition === 'function') {
                   if (fieldConfig.condition.length > 1) {
                       // Condition function accepts data parameter
                       shouldProcess = await fieldConfig.condition(page, sectionData);
                   } else {
                       // Condition function only accepts page parameter
                       shouldProcess = await fieldConfig.condition(page);
                   }
               }
               
               if (!shouldProcess) {
                   logger.debug(`[Job ${jobId}] Skipping field ${fieldName} based on condition`);
                   continue;
               }
           }
           
           // Process field based on action type
           try {
               if (fieldConfig.action === 'click') {
                   // For radio buttons, checkboxes, etc.
                   if (fieldValue) {
                       await this.clickElement(page, fieldConfig.selector, `${sectionName}.${fieldName}`, {
                           required: fieldConfig.required,
                           waitAfterClick: fieldConfig.waitAfter ? 1000 : 0
                       });
                   }
               } else if (fieldConfig.selector.includes('select') || fieldConfig.isDropdown) {
                   // For dropdown selections
                   await this.selectDropdownByValue(page, fieldConfig.selector, fieldValue, `${sectionName}.${fieldName}`, {
                       required: fieldConfig.required,
                       waitAfterSelect: fieldConfig.waitAfter ? 1000 : 0
                   });
               } else {
                   // For text inputs
                   await this.typeIntoField(page, fieldConfig.selector, fieldValue, `${sectionName}.${fieldName}`, {
                       required: fieldConfig.required,
                       clearFirst: true,
                       delay: 30
                   });
               }
               
               // Handle post-action callback if defined
               if (fieldConfig.onChange) {
                   await fieldConfig.onChange(page, fieldValue);
               }
               
               // Add slight delay between fields to avoid overwhelming the form
               await page.waitForTimeout(100);
               
           } catch (error) {
               logger.error(`[Job ${jobId}] Error filling field ${sectionName}.${fieldName}: ${error.message}`);
               
               // Take error screenshot
               await page.screenshot({ 
                   path: `./screenshots/error_${jobId}_${sectionName}_${fieldName}.png`, 
                   fullPage: true 
               });
               
               // If field is required, throw error; otherwise continue
               if (fieldConfig.required) {
                   throw error;
               }
           }
       }
       
       logger.info(`[Job ${jobId}] Successfully filled ${sectionName} section`);
       return true;
   }
};