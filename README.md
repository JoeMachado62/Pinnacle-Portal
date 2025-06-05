Project Overview
Pinnacle Auto Finance Dealer Portal is a comprehensive system that enables independent dealerships to access broker financing solutions. The system combines:

The WordPress Portal (VPS-hosted): Manages dealer accounts, loan applications, and customer data.
The Local Automation Client (Dealership PC): Handles real-time interaction with DealerTrack through a native browser

This hybrid approach leverages the strengths of both environments: centralized data management on the VPS and reliable browser automation on a local machine with authenticated DealerTrack access.
System Architecture
VPS Components (WordPress)
Pinnacle-Portal/
├── frontend/                    # WordPress with Ultimate Member integration
│   ├── themes/                  # Custom theme with UM template overrides
│   ├── plugins/                 # Custom plugins for CPTs and form handling
│   └── wp-config.php            # WordPress configuration
├── api/                         # REST API endpoints
│   ├── job-status/              # Endpoints for job status updates
│   ├── application-queue/       # New application notification endpoint
│   ├── dealertrack-callback/    # Webhook receiver for status updates
│   └── auth/                    # Authentication middleware
├── data/                        # Database and file storage
│   ├── wp-content/uploads/      # Document storage
│   └── mysql/                   # Database
└── docs/                        # Project documentation
Local Machine Components
DealerTrack-Client/
├── src/
│   ├── browser/                 # Browser management
│   │   ├── session.js           # Session handler
│   │   └── navigation.js        # Page navigation utilities
│   ├── automation/              # DealerTrack interaction logic
│   │   ├── applications.js      # Credit application automation
│   │   ├── deals.js             # Deal management functions
│   │   └── mapping/             # Field mapping definitions
│   ├── api/                     # Communication with VPS
│   │   ├── client.js            # API client
│   │   └── sync.js              # Data synchronization
│   ├── queue/                   # Job processing
│   │   ├── manager.js           # Queue management
│   │   └── worker.js            # Job execution
│   └── utils/                   # Helper functions
├── config/                      # Configuration
├── logs/                        # Application logs
└── ui/                          # Simple admin interface
Key Technical Requirements
WordPress Portal (VPS)

Custom Post Types:

credit_application: Stores application data
dealer: Dealer information and credentials
deal: Deal tracking with status workflow
Implement custom meta fields via ACF or native meta


API Endpoints:

Application submission notification
Status update receiver
Document upload/download
Job queue management


User Management:

Ultimate Member integration
Role-based access (Admin, Dealer, Finance Manager)
Dealer onboarding workflow


Job Queue:

WP Action Scheduler integration
Job prioritization
Status tracking
Error handling and notifications



Local Automation Client

Browser Integration:

Connect to running Chrome instance with active DealerTrack session
Work with pre-authenticated browser (manual login by staff)
Monitor and maintain session status


Automation Functions:

Credit application submission
Deal status checking
Document retrieval
Data extraction


Communication:

Secure API interaction with WordPress
Real-time status updates
Error reporting
File transfer


Resilience:

Automatic recovery from errors
Screenshot capture for debugging
Detailed logging
Retry mechanisms



Data Flow

Application Submission:
Dealer → WordPress Form → Credit Application CPT → API Notification → 
Local Client → DealerTrack Submission → Status Update → WordPress

Status Updates:
Local Client → Periodic DealerTrack Check → Status Change Detected → 
API Update → WordPress CPT Update → Notification to Dealer

Document Management:
DealerTrack Document → Local Client Extraction → API Upload → 
WordPress Storage → Dealer Portal Access


Implementation Guide
WordPress Development
Custom Post Types
php// Register Credit Application CPT
function register_credit_application_cpt() {
    $args = [
        'public' => true,
        'label'  => 'Credit Applications',
        'supports' => ['title', 'editor', 'custom-fields'],
        'show_in_rest' => true,
        'capability_type' => 'post',
        'capabilities' => [
            'create_posts' => 'create_credit_applications',
            'edit_posts' => 'edit_credit_applications',
            // Additional capabilities...
        ],
        'map_meta_cap' => true
    ];
    register_post_type('credit_application', $args);
}
add_action('init', 'register_credit_application_cpt');
API Endpoints
php// Register API endpoints
add_action('rest_api_init', function() {
    // Get pending applications
    register_rest_route('pinnacle/v1', '/applications/pending', [
        'methods' => 'GET',
        'callback' => 'get_pending_applications',
        'permission_callback' => 'verify_api_access'
    ]);
    
    // Update application status
    register_rest_route('pinnacle/v1', '/applications/(?P<id>\d+)/status', [
        'methods' => 'POST',
        'callback' => 'update_application_status',
        'permission_callback' => 'verify_api_access'
    ]);
    
    // Additional endpoints...
});
Job Notification
php// Notify local client when new application is submitted
function notify_application_submitted($post_id, $post, $update) {
    if ($post->post_type !== 'credit_application' || $update) {
        return;
    }
    
    // Prepare application data
    $application_data = [
        'id' => $post_id,
        'customer' => get_post_meta($post_id, 'customer_info', true),
        'vehicle' => get_post_meta($post_id, 'vehicle_info', true),
        'financial' => get_post_meta($post_id, 'financial_info', true),
        // Additional fields...
    ];
    
    // Send notification to local client
    $client_endpoint = get_option('local_client_webhook');
    wp_remote_post($client_endpoint, [
        'body' => json_encode($application_data),
        'headers' => [
            'Content-Type' => 'application/json',
            'X-API-Key' => get_option('api_key')
        ]
    ]);
    
    // Log notification
    update_post_meta($post_id, 'notification_sent', current_time('mysql'));
}
add_action('wp_insert_post', 'notify_application_submitted', 10, 3);
Local Client Development
Browser Management
javascriptconst puppeteer = require('puppeteer');

// Connect to existing Chrome instance
async function connectToBrowser() {
  try {
    // Connect to Chrome DevTools Protocol endpoint
    const browser = await puppeteer.connect({
      browserURL: 'http://localhost:9222',
      defaultViewport: null
    });
    
    console.log('Connected to existing Chrome instance');
    
    // Verify DealerTrack is accessible
    const pages = await browser.pages();
    let dealerTrackPage = null;
    
    for (const page of pages) {
      const url = page.url();
      if (url.includes('dealertrack.com')) {
        dealerTrackPage = page;
        break;
      }
    }
    
    // If DealerTrack not open, navigate to it
    if (!dealerTrackPage) {
      console.log('DealerTrack not found in open tabs, creating new tab');
      dealerTrackPage = await browser.newPage();
      await dealerTrackPage.goto('https://us.dealertrack.com');
      
      // Alert user to authenticate
      console.log('Please log in to DealerTrack in the newly opened tab');
      
      // Implement method to detect when login is completed
      await waitForDealerTrackLogin(dealerTrackPage);
    }
    
    return { browser, dealerTrackPage };
  } catch (error) {
    console.error('Failed to connect to browser:', error);
    throw new Error('Browser connection failed. Ensure Chrome is running with remote debugging enabled.');
  }
}

// Start Chrome with debugging enabled (for reference)
function launchInstructions() {
  console.log(`
    Please launch Chrome with remote debugging enabled:
    
    Windows:
    "C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe" --remote-debugging-port=9222
    
    macOS:
    "/Applications/Google Chrome.app/Contents/MacOS/Google Chrome" --remote-debugging-port=9222
  `);
}

module.exports = { connectToBrowser, launchInstructions };
Credit Application Submission
javascriptconst { navigateTo, waitForLoad } = require('./navigation');
const { getFieldMappings } = require('./mapping');

// Submit credit application to DealerTrack
async function submitCreditApplication(page, applicationData) {
  try {
    console.log(`Submitting application for: ${applicationData.customer.name}`);
    
    // Navigate to new application form
    await navigateTo(page, 'newApplication');
    
    // Get field mappings
    const fieldMappings = getFieldMappings('creditApplication');
    
    // Fill customer information
    console.log('Filling customer information...');
    for (const [field, selector] of Object.entries(fieldMappings.customer)) {
      if (applicationData.customer[field]) {
        await page.type(selector, applicationData.customer[field]);
      }
    }
    
    // Fill vehicle information
    console.log('Filling vehicle information...');
    for (const [field, selector] of Object.entries(fieldMappings.vehicle)) {
      if (applicationData.vehicle[field]) {
        await page.type(selector, applicationData.vehicle[field]);
      }
    }
    
    // Fill financial information
    console.log('Filling financial information...');
    for (const [field, selector] of Object.entries(fieldMappings.financial)) {
      if (applicationData.financial[field]) {
        await page.type(selector, applicationData.financial[field]);
      }
    }
    
    // Take screenshot before submission
    await page.screenshot({ path: `./logs/application-${applicationData.id}-before-submit.png` });
    
    // Submit form
    console.log('Submitting application...');
    await page.click(fieldMappings.submitButton);
    await waitForLoad(page);
    
    // Capture DealerTrack reference number
    const referenceNumber = await page.evaluate((selector) => {
      const element = document.querySelector(selector);
      return element ? element.textContent.trim() : null;
    }, fieldMappings.referenceNumberSelector);
    
    console.log(`Application submitted successfully. Reference: ${referenceNumber}`);
    
    // Take screenshot after submission
    await page.screenshot({ path: `./logs/application-${applicationData.id}-after-submit.png` });
    
    return {
      success: true,
      referenceNumber,
      message: 'Application submitted successfully'
    };
  } catch (error) {
    console.error('Failed to submit application:', error);
    
    // Capture error state
    await page.screenshot({ path: `./logs/application-${applicationData.id}-error.png` });
    
    return {
      success: false,
      error: error.message,
      message: 'Failed to submit application'
    };
  }
}

module.exports = { submitCreditApplication };
API Communication
javascriptconst axios = require('axios');
const fs = require('fs');
const { getConfig } = require('../config');

class WordPressAPI {
  constructor() {
    const config = getConfig();
    this.baseURL = config.wordpressAPI.baseURL;
    this.apiKey = config.wordpressAPI.apiKey;
  }
  
  // Get pending applications
  async getPendingApplications() {
    try {
      const response = await axios.get(`${this.baseURL}/applications/pending`, {
        headers: {
          'X-API-Key': this.apiKey,
          'Content-Type': 'application/json'
        }
      });
      
      return response.data;
    } catch (error) {
      console.error('Failed to fetch pending applications:', error);
      throw error;
    }
  }
  
  // Update application status
  async updateApplicationStatus(applicationId, status, details = {}) {
    try {
      const response = await axios.post(
        `${this.baseURL}/applications/${applicationId}/status`,
        {
          status,
          details,
          timestamp: new Date().toISOString()
        },
        {
          headers: {
            'X-API-Key': this.apiKey,
            'Content-Type': 'application/json'
          }
        }
      );
      
      return response.data;
    } catch (error) {
      console.error(`Failed to update status for application ${applicationId}:`, error);
      throw error;
    }
  }
  
  // Upload document
  async uploadDocument(applicationId, filePath, documentType) {
    try {
      const fileContent = fs.readFileSync(filePath);
      const formData = new FormData();
      
      formData.append('file', fileContent);
      formData.append('application_id', applicationId);
      formData.append('document_type', documentType);
      
      const response = await axios.post(
        `${this.baseURL}/documents/upload`,
        formData,
        {
          headers: {
            'X-API-Key': this.apiKey,
            'Content-Type': 'multipart/form-data'
          }
        }
      );
      
      return response.data;
    } catch (error) {
      console.error(`Failed to upload document for application ${applicationId}:`, error);
      throw error;
    }
  }
}

module.exports = new WordPressAPI();
Job Processing
javascriptconst { submitCreditApplication } = require('../automation/applications');
const { checkApplicationStatus } = require('../automation/deals');
const api = require('../api/wordpress');
const { connectToBrowser } = require('../browser/session');

class JobProcessor {
  constructor() {
    this.running = false;
    this.browser = null;
    this.dealerTrackPage = null;
  }
  
  // Initialize browser connection
  async initialize() {
    try {
      const { browser, dealerTrackPage } = await connectToBrowser();
      this.browser = browser;
      this.dealerTrackPage = dealerTrackPage;
      this.running = true;
      
      console.log('Job processor initialized successfully');
      return true;
    } catch (error) {
      console.error('Failed to initialize job processor:', error);
      return false;
    }
  }
  
  // Process a new application job
  async processApplicationJob(applicationData) {
    try {
      console.log(`Processing application job: ${applicationData.id}`);
      
      // Update status to processing
      await api.updateApplicationStatus(applicationData.id, 'processing', {
        message: 'Application is being submitted to DealerTrack'
      });
      
      // Submit application
      const result = await submitCreditApplication(
        this.dealerTrackPage, 
        applicationData
      );
      
      // Update status based on result
      if (result.success) {
        await api.updateApplicationStatus(applicationData.id, 'submitted', {
          dealertrack_reference: result.referenceNumber,
          message: 'Application submitted to DealerTrack successfully'
        });
      } else {
        await api.updateApplicationStatus(applicationData.id, 'failed', {
          error: result.error,
          message: 'Failed to submit application to DealerTrack'
        });
      }
      
      return result;
    } catch (error) {
      console.error(`Failed to process application job ${applicationData.id}:`, error);
      
      // Update status to failed
      await api.updateApplicationStatus(applicationData.id, 'failed', {
        error: error.message,
        message: 'Application processing encountered an error'
      });
      
      return {
        success: false,
        error: error.message
      };
    }
  }
  
  // Check status of submitted applications
  async checkApplicationStatuses() {
    try {
      console.log('Checking statuses of submitted applications');
      
      // Get applications that need status check
      const applications = await api.getApplicationsForStatusCheck();
      
      for (const application of applications) {
        console.log(`Checking status for application: ${application.id}`);
        
        // Get current status from DealerTrack
        const statusResult = await checkApplicationStatus(
          this.dealerTrackPage,
          application.dealertrack_reference
        );
        
        // Update application status if changed
        if (statusResult.status !== application.status) {
          await api.updateApplicationStatus(application.id, statusResult.status, {
            dealertrack_status: statusResult.dealerTrackStatus,
            message: 'Status updated from DealerTrack'
          });
        }
      }
      
      return true;
    } catch (error) {
      console.error('Failed to check application statuses:', error);
      return false;
    }
  }
  
  // Stop job processor
  async stop() {
    try {
      this.running = false;
      console.log('Job processor stopped');
      return true;
    } catch (error) {
      console.error('Failed to stop job processor:', error);
      return false;
    }
  }
}

module.exports = new JobProcessor();
Main Application
javascriptconst express = require('express');
const bodyParser = require('body-parser');
const jobProcessor = require('./queue/processor');
const { getConfig } = require('./config');

// Initialize Express app
const app = express();
app.use(bodyParser.json());

// Config
const config = getConfig();
const PORT = config.server.port || 3000;

// Initialize job processor
(async () => {
  await jobProcessor.initialize();
  
  // Start periodic status checks
  setInterval(() => {
    jobProcessor.checkApplicationStatuses();
  }, config.statusCheck.interval || 300000); // Default: 5 minutes
})();

// API endpoint for new application notification
app.post('/api/applications', async (req, res) => {
  try {
    const applicationData = req.body;
    
    // Validate request
    if (!applicationData || !applicationData.id) {
      return res.status(400).json({
        success: false,
        message: 'Invalid application data'
      });
    }
    
    // Process application asynchronously
    jobProcessor.processApplicationJob(applicationData)
      .then(result => {
        console.log(`Application ${applicationData.id} processed:`, result);
      })
      .catch(error => {
        console.error(`Error processing application ${applicationData.id}:`, error);
      });
    
    // Respond immediately
    return res.status(202).json({
      success: true,
      message: 'Application queued for processing'
    });
  } catch (error) {
    console.error('API error:', error);
    return res.status(500).json({
      success: false,
      message: 'Server error'
    });
  }
});

// Health check endpoint
app.get('/health', (req, res) => {
  res.status(200).json({
    status: 'ok',
    browser: jobProcessor.browser ? 'connected' : 'disconnected'
  });
});

// Start server
app.listen(PORT, () => {
  console.log(`Local client running on port ${PORT}`);
});
Setup Instructions
VPS (WordPress) Setup

Set up WordPress with required plugins:

Ultimate Member (user management)
Advanced Custom Fields (or similar for custom fields)
Custom plugin for CPTs and API endpoints


Configure custom post types and fields
Add API endpoints for communication with local client
Set up secure authentication for API access

Local Machine Setup

Install Node.js and dependencies:
bashnpm install puppeteer express axios winston

Configure Chrome for remote debugging:
bash# Windows (create shortcut with these parameters)
"C:\Program Files\Google\Chrome\Application\chrome.exe" --remote-debugging-port=9222

# macOS
"/Applications/Google Chrome.app/Contents/MacOS/Google Chrome" --remote-debugging-port=9222

Configure the local client:
bash# Create config.json with appropriate settings
{
  "wordpressAPI": {
    "baseURL": "https://your-wordpress-site.com/wp-json/pinnacle/v1",
    "apiKey": "your-api-key"
  },
  "server": {
    "port": 3000
  },
  "statusCheck": {
    "interval": 300000
  }
}

Start the local client:
bashnode index.js

Log in to DealerTrack in the Chrome browser

Deployment Checklist
WordPress (VPS)

 Set up WordPress with required plugins
 Configure custom post types and fields
 Add REST API endpoints
 Set up secure authentication
 Configure HTTPS
 Set up regular backups
 Configure error logging

Local Client

 Install Node.js and dependencies
 Configure Chrome shortcut with remote debugging
 Set up local client configuration
 Configure startup script to run on boot
 Set up error logging and alerting
 Configure firewall to allow API communication
 Test end-to-end workflow

Security Considerations

API Security:

Use API keys or JWT for authentication
Implement IP whitelisting
Use HTTPS for all communications


Data Protection:

Encrypt sensitive data
Implement proper data retention policies
Consider regulatory requirements (GLBA, CCPA)


Access Control:

Strong passwords for DealerTrack
Role-based access in WordPress
Regular credential rotation


Infrastructure:

Secure VPS configuration
Regular updates and patches
Firewall configuration



Maintenance Procedures

Regular Updates:

WordPress core and plugin updates
Node.js and package updates
Chrome browser updates


Monitoring:

API health checks
Error log monitoring
Performance metrics


Backup Procedures:

Daily WordPress database backups
Configuration backups
Disaster recovery plan


Troubleshooting:

Error logging
Screenshot capture
Session recording for debugging



This implementation provides a robust and maintainable solution that balances the strengths of both environments: the rich user interface and data management capabilities of WordPress on the VPS, combined with the reliability of browser automation on a local machine with direct access to DealerTrack.