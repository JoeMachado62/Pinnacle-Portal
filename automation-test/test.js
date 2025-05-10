require('dotenv').config();
const puppeteer = require('puppeteer');
const fs = require('fs');
const path = require('path');

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

// Main function
async function testDealerTrackAutomation() {
  console.log('Starting DealerTrack automation test...');
  
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
    
    // Step 6: Fill out some fields in the credit application form
    console.log('Filling out credit application form...');
    
    // These selectors are placeholders - you'll need to determine the actual selectors
    // for the credit application form in DealerTrack
    const formFields = [
      { selector: 'input[name="firstName"], #firstName', value: 'John' },
      { selector: 'input[name="lastName"], #lastName', value: 'Doe' },
      { selector: 'input[name="email"], #email', value: 'john.doe@example.com' },
      { selector: 'input[name="phone"], #phone', value: '555-123-4567' },
      { selector: 'input[name="address"], #address', value: '123 Main St' },
      { selector: 'input[name="city"], #city', value: 'Anytown' },
      { selector: 'select[name="state"], #state', value: 'CA' },
      { selector: 'input[name="zip"], #zip', value: '12345' }
    ];
    
    // Try to fill out each field
    for (const field of formFields) {
      try {
        await page.waitForSelector(field.selector, { timeout: 5000 });
        
        if (field.selector.includes('select')) {
          await page.select(field.selector, field.value);
        } else {
          await page.type(field.selector, field.value);
        }
        
        console.log(`Filled field ${field.selector} with value ${field.value}`);
      } catch (error) {
        console.warn(`Could not fill field ${field.selector}:`, error.message);
      }
    }
    
    await takeScreenshot(page, 'form-filled');
    
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
      const submitButtonSelector = 'button[type="submit"], input[type="submit"], .submit-button';
      await page.waitForSelector(submitButtonSelector, { timeout: 5000 });
      
      // Click submit button but don't wait for navigation to complete
      // This is just to test if the button is clickable
      await page.click(submitButtonSelector);
      
      console.log('Submit button clicked successfully');
      await takeScreenshot(page, 'after-submit-click');
      
      // Wait a moment to see any client-side validation errors
      await page.waitForTimeout(2000);
      await takeScreenshot(page, 'validation-check');
      
    } catch (error) {
      console.warn('Could not test form submission:', error.message);
    }
    */
    
    console.log('DealerTrack automation test completed successfully!');
    
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
