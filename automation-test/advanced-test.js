require('dotenv').config();
const puppeteer = require('puppeteer');
const fs = require('fs');
const path = require('path');
const fieldMapping = require('./field-mapping');

// Create screenshots directory if it doesn't exist
const screenshotsDir = path.join(__dirname, 'screenshots');
if (!fs.existsSync(screenshotsDir)) {
  fs.mkdirSync(screenshotsDir);
}

// Helper function to take screenshots
async function takeScreenshot(page, name) {
  await page.screenshot({ 
    path: path.join(screenshotsDir, `${name}-${new Date().toISOString().replace(/:/g, '-')}.png`),
    fullPage: true 
  });
  console.log(`Screenshot saved: ${name}`);
}

// Helper function to log page content for debugging
async function logPageContent(page, selector) {
  try {
    const content = await page.evaluate((sel) => {
      const element = document.querySelector(sel);
      return element ? element.textContent : 'Element not found';
    }, selector);
    console.log(`Content of ${selector}: ${content}`);
  } catch (error) {
    console.error(`Error getting content of ${selector}:`, error);
  }
}

// Helper function to fill a form field based on field mapping
async function fillFormField(page, fieldName, value) {
  const fieldConfig = fieldMapping[fieldName];
  if (!fieldConfig) {
    console.warn(`No field mapping found for ${fieldName}`);
    return false;
  }

  try {
    // Wait for the field to be available
    await page.waitForSelector(fieldConfig.selector, { timeout: 5000 })
      .catch(() => {
        throw new Error(`Field ${fieldName} (${fieldConfig.selector}) not found on the page`);
      });

    // Transform the value if a transform function is provided
    const transformedValue = fieldConfig.transform ? fieldConfig.transform(value) : value;

    // Fill the field based on its type
    switch (fieldConfig.type) {
      case 'input':
        await page.type(fieldConfig.selector, transformedValue.toString());
        break;
      case 'select':
        await page.select(fieldConfig.selector, transformedValue.toString());
        break;
      case 'radio':
        await page.click(`${fieldConfig.selector}[value="${transformedValue}"]`);
        break;
      case 'checkbox':
        if (transformedValue) {
          await page.click(fieldConfig.selector);
        }
        break;
      default:
        console.warn(`Unknown field type ${fieldConfig.type} for ${fieldName}`);
        return false;
    }

    console.log(`Filled field ${fieldName} with value ${value} (transformed: ${transformedValue})`);
    return true;
  } catch (error) {
    console.error(`Error filling field ${fieldName}:`, error.message);
    return false;
  }
}

// Helper function to extract data from a field based on field mapping
async function extractFieldData(page, fieldName) {
  const fieldConfig = fieldMapping[fieldName];
  if (!fieldConfig) {
    console.warn(`No field mapping found for ${fieldName}`);
    return null;
  }

  try {
    // Wait for the field to be available
    await page.waitForSelector(fieldConfig.selector, { timeout: 5000 })
      .catch(() => {
        throw new Error(`Field ${fieldName} (${fieldConfig.selector}) not found on the page`);
      });

    // Extract the value based on the field type
    let value;
    switch (fieldConfig.type) {
      case 'input':
        value = await page.evaluate((selector) => {
          return document.querySelector(selector).value;
        }, fieldConfig.selector);
        break;
      case 'select':
        value = await page.evaluate((selector) => {
          const select = document.querySelector(selector);
          return select.options[select.selectedIndex].value;
        }, fieldConfig.selector);
        break;
      case 'radio':
        value = await page.evaluate((selector) => {
          const radio = document.querySelector(`${selector}:checked`);
          return radio ? radio.value : null;
        }, fieldConfig.selector);
        break;
      case 'checkbox':
        value = await page.evaluate((selector) => {
          return document.querySelector(selector).checked;
        }, fieldConfig.selector);
        break;
      case 'text':
        value = await page.evaluate((selector) => {
          const element = document.querySelector(selector);
          return element ? element.textContent.trim() : null;
        }, fieldConfig.selector);
        break;
      default:
        console.warn(`Unknown field type ${fieldConfig.type} for ${fieldName}`);
        return null;
    }

    console.log(`Extracted field ${fieldName} with value ${value}`);
    return value;
  } catch (error) {
    console.error(`Error extracting field ${fieldName}:`, error.message);
    return null;
  }
}

// Sample credit application data (simulating data from WordPress CPT)
const sampleCreditApplication = {
  primary_borrower: {
    first_name: 'John',
    last_name: 'Doe',
    ssn: '123-45-6789',
    dob: '1980-01-15',
    email: 'john.doe@example.com',
    phone: '(555) 123-4567',
    address: '123 Main St',
    city: 'Anytown',
    state: 'CA',
    zip: '12345',
    residence_years: '3',
    residence_months: '6',
    employer: 'ACME Corporation',
    employment_years: '5',
    employment_months: '2',
    position: 'Software Engineer',
    income: '$6,500.00'
  },
  vehicle_data: {
    year: '2022',
    make: 'Toyota',
    model: 'Camry',
    trim: 'XLE',
    mileage: '15,000',
    vin: 'JT2BF22K1W0123456'
  },
  financial_data: {
    selling_price: '$25,000.00',
    down_payment: '$5,000.00',
    trade_value: '$8,000.00',
    trade_payoff: '$2,000.00',
    tax: '$1,500.00',
    warranty: '$1,200.00',
    gap: '$800.00',
    doc_fees: '$500.00',
    title_fees: '$300.00',
    registration_fees: '$200.00',
    amount_financed: '$18,500.00'
  }
};

// Main function
async function testDealerTrackAutomation() {
  console.log('Starting DealerTrack advanced automation test...');
  
  // Launch browser
  const browser = await puppeteer.launch({
    headless: process.env.HEADLESS === 'true',
    defaultViewport: null, // Use default viewport size
    args: ['--start-maximized', '--no-sandbox', '--disable-setuid-sandbox'],
    slowMo: 100 // Slow down operations by 100ms for visual debugging
  });
  
  const page = await browser.newPage();
  
  try {
    // Set default navigation timeout (30 seconds)
    page.setDefaultNavigationTimeout(30000);
    
    // Enable console logging from the browser
    page.on('console', msg => console.log('BROWSER CONSOLE:', msg.text()));
    
    // Step 1: Navigate to login page
    console.log(`Navigating to login page: ${process.env.DEALERTRACK_LOGIN_URL}`);
    await page.goto(process.env.DEALERTRACK_LOGIN_URL, { waitUntil: 'networkidle2' });
    await takeScreenshot(page, 'login-page');
    
    // Step 2: Fill in login credentials
    console.log('Filling in login credentials...');
    
    // Wait for login form elements to be available
    await page.waitForSelector('#username', { timeout: 10000 })
      .catch(() => {
        throw new Error('Username field not found. Login page structure may have changed.');
      });
    
    await page.waitForSelector('#password', { timeout: 5000 })
      .catch(() => {
        throw new Error('Password field not found. Login page structure may have changed.');
      });
    
    // Fill in username and password
    await page.type('#username', process.env.DEALERTRACK_USERNAME);
    await page.type('#password', process.env.DEALERTRACK_PASSWORD);
    await takeScreenshot(page, 'credentials-filled');
    
    // Step 3: Submit login form
    console.log('Submitting login form...');
    
    // Find and click the login button
    const loginButtonSelector = 'button[type="submit"], input[type="submit"], .login-button, #loginButton';
    await page.waitForSelector(loginButtonSelector, { timeout: 5000 })
      .catch(() => {
        throw new Error('Login button not found. Login page structure may have changed.');
      });
    
    await Promise.all([
      page.click(loginButtonSelector),
      page.waitForNavigation({ waitUntil: 'networkidle2', timeout: 60000 })
        .catch(error => {
          console.error('Navigation after login timed out or failed:', error);
          return page; // Continue execution even if navigation times out
        })
    ]);
    
    await takeScreenshot(page, 'after-login');
    
    // Step 4: Check if login was successful
    console.log('Checking login status...');
    
    // Look for elements that would indicate successful login
    const isLoggedIn = await page.evaluate(() => {
      // Check for common dashboard elements or error messages
      const errorElement = document.querySelector('.error-message, .alert-danger');
      if (errorElement) {
        return { success: false, message: errorElement.textContent.trim() };
      }
      
      // Check for elements that would indicate successful login
      const dashboardElement = document.querySelector('.dashboard, .welcome-message, .user-info');
      if (dashboardElement) {
        return { success: true, message: 'Successfully logged in' };
      }
      
      // If neither error nor dashboard elements found, check page title or URL
      return { 
        success: document.title.includes('Dashboard') || window.location.href.includes('dashboard'),
        message: 'Login status determined by page title or URL'
      };
    });
    
    if (isLoggedIn.success) {
      console.log('Login successful:', isLoggedIn.message);
    } else {
      console.error('Login failed:', isLoggedIn.message);
      await takeScreenshot(page, 'login-failed');
      throw new Error('Login failed. Check credentials or DealerTrack login page structure.');
    }
    
    // Step 5: Navigate to credit application form
    console.log('Navigating to credit application form...');
    
    // This is a placeholder - you'll need to determine the actual navigation path
    // to the credit application form in DealerTrack
    try {
      // Example: Click on "New Application" link or menu item
      await page.waitForSelector('a[href*="new-application"], .new-application-button', { timeout: 10000 });
      await page.click('a[href*="new-application"], .new-application-button');
      await page.waitForNavigation({ waitUntil: 'networkidle2' });
      await takeScreenshot(page, 'credit-application-form');
    } catch (error) {
      console.warn('Could not automatically navigate to credit application form:', error.message);
      console.log('Attempting to navigate directly to the application URL...');
      
      // Fallback: Try to navigate directly to the application URL
      await page.goto(`${process.env.DEALERTRACK_APP_URL}/new-application`, { waitUntil: 'networkidle2' })
        .catch(error => {
          console.error('Failed to navigate to credit application form:', error);
          throw new Error('Could not access credit application form. Navigation path may have changed.');
        });
      
      await takeScreenshot(page, 'credit-application-form-direct');
    }
    
    // Step 6: Fill out the credit application form using field mapping
    console.log('Filling out credit application form using field mapping...');
    
    // Fill primary borrower information
    console.log('Filling primary borrower information...');
    for (const [field, value] of Object.entries(sampleCreditApplication.primary_borrower)) {
      await fillFormField(page, `primary_borrower.${field}`, value);
    }
    
    await takeScreenshot(page, 'borrower-info-filled');
    
    // Check if we need to navigate to the next page/section
    try {
      const continueButtonSelector = fieldMapping.continueButton.selector;
      const continueButtonVisible = await page.evaluate((selector) => {
        const button = document.querySelector(selector);
        return button && button.offsetParent !== null; // Check if button is visible
      }, continueButtonSelector);
      
      if (continueButtonVisible) {
        console.log('Clicking continue button to proceed to next section...');
        await page.click(continueButtonSelector);
        await page.waitForNavigation({ waitUntil: 'networkidle2', timeout: 10000 })
          .catch(() => console.log('No navigation occurred after clicking continue button'));
        await takeScreenshot(page, 'after-continue-click');
      }
    } catch (error) {
      console.warn('Error checking/clicking continue button:', error.message);
    }
    
    // Fill vehicle information
    console.log('Filling vehicle information...');
    for (const [field, value] of Object.entries(sampleCreditApplication.vehicle_data)) {
      await fillFormField(page, `vehicle_data.${field}`, value);
    }
    
    await takeScreenshot(page, 'vehicle-info-filled');
    
    // Check if we need to navigate to the next page/section again
    try {
      const continueButtonSelector = fieldMapping.continueButton.selector;
      const continueButtonVisible = await page.evaluate((selector) => {
        const button = document.querySelector(selector);
        return button && button.offsetParent !== null; // Check if button is visible
      }, continueButtonSelector);
      
      if (continueButtonVisible) {
        console.log('Clicking continue button to proceed to next section...');
        await page.click(continueButtonSelector);
        await page.waitForNavigation({ waitUntil: 'networkidle2', timeout: 10000 })
          .catch(() => console.log('No navigation occurred after clicking continue button'));
        await takeScreenshot(page, 'after-continue-click-2');
      }
    } catch (error) {
      console.warn('Error checking/clicking continue button:', error.message);
    }
    
    // Fill financial information
    console.log('Filling financial information...');
    for (const [field, value] of Object.entries(sampleCreditApplication.financial_data)) {
      await fillFormField(page, `financial_data.${field}`, value);
    }
    
    await takeScreenshot(page, 'financial-info-filled');
    
    // Step 7: Read some information from the page
    console.log('Reading information from the page...');
    
    // Example: Read form title or section headers
    await logPageContent(page, 'h1');
    await logPageContent(page, 'h2');
    await logPageContent(page, '.form-title, .section-header');
    
    // Example: Read field labels
    const fieldLabels = await page.evaluate(() => {
      const labels = Array.from(document.querySelectorAll('label'));
      return labels.map(label => label.textContent.trim());
    });
    
    console.log('Field labels found on the page:', fieldLabels);
    
    // Step 8: Test form submission (optional - commented out to prevent actual submission)
    /*
    console.log('Testing form submission...');
    
    try {
      const submitButtonSelector = fieldMapping.submitButton.selector;
      await page.waitForSelector(submitButtonSelector, { timeout: 5000 });
      
      // Click submit button but don't wait for navigation to complete
      // This is just to test if the button is clickable
      await page.click(submitButtonSelector);
      
      console.log('Submit button clicked successfully');
      await takeScreenshot(page, 'after-submit-click');
      
      // Wait a moment to see any client-side validation errors
      await page.waitForTimeout(2000);
      await takeScreenshot(page, 'validation-check');
      
      // Try to extract the reference number if available
      const referenceNumber = await extractFieldData(page, 'referenceNumber');
      if (referenceNumber) {
        console.log('Application reference number:', referenceNumber);
      }
      
      // Try to extract the status if available
      const status = await extractFieldData(page, 'statusIndicator');
      if (status) {
        console.log('Application status:', status);
      }
      
    } catch (error) {
      console.warn('Could not test form submission:', error.message);
    }
    */
    
    console.log('DealerTrack advanced automation test completed successfully!');
    
  } catch (error) {
    console.error('Test failed:', error);
    await takeScreenshot(page, 'error-state');
  } finally {
    // Close the browser
    await browser.close();
    console.log('Browser closed. Test complete.');
  }
}

// Run the test
testDealerTrackAutomation().catch(error => {
  console.error('Unhandled error in test:', error);
  process.exit(1);
});
