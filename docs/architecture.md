1. System Architecture Overview
The Pinnacle Auto Finance Dealer Portal will be built using a multi-tier architecture that separates concerns while providing a scalable and maintainable system:
1.1 Architecture Layers
Presentation Layer

React-based frontend for dealers and administrators
Progressive Web App capabilities for mobile responsiveness
Component-based design for reusability

Application Layer

Strapi CMS providing API endpoints, authentication, and business logic
Custom Strapi plugins for specific business needs
Middleware for API Gateway functionality

Integration Layer

Chrome Extension for DealerTrack Integration (replacing direct DealerTrack API integration)
Form data mapping and transformation
Bidirectional data flow between Pinnacle Portal and DealerTrack
Hidden embedded form page for integration processing

Data Layer

PostgreSQL database for structured data
Strapi Storage for document management
Caching layer for performance optimization

1.2 System Interaction Flow
┌─────────────┐     ┌─────────────┐     ┌──────────────┐     ┌─────────────┐
│  Dealer UI  │◄───►│ Strapi API  │◄───►│ Chrome       │◄───►│ DealerTrack │
│  Admin UI   │     │ Controllers │     │ Extension    │     │ Embedded    │
└─────────────┘     └─────────────┘     └──────────────┘     │ Form        │
                           ▲                   ▲             └─────────────┘
                           │                   │                    ▲
                           ▼                   │                    │
                    ┌─────────────┐           │                    │
                    │ PostgreSQL  │◄──────────┘                    │
                    │  Database   │◄───────────────────────────────┘
                    └─────────────┘
2. Technical Stack
2.1 Frontend Technologies

Framework: React.js with TypeScript
State Management: Redux or Context API
UI Framework: Material UI or similar library
Form Handling: Formik with Yup validation
HTTP Client: Axios
Testing: Jest and React Testing Library
Build Tools: Webpack, Babel

2.2 Backend Technologies

CMS: Strapi with custom plugins
API Framework: Strapi's RESTful API with custom controllers
Authentication: JWT-based authentication
Server-side Language: Node.js
Process Management: PM2
Testing: Jest, Supertest

2.3 Integration Technologies

Chrome Extension: JavaScript-based browser extension
Browser Integration: Chrome Extension API
Data Transformation: Lodash
Error Handling: Custom retry and logging system

2.4 Database

RDBMS: PostgreSQL 13+
ORM: Strapi's built-in ORM (Bookshelf/Knex)
Migration Tool: Knex migrations
Backup Strategy: Automated daily backups with point-in-time recovery

2.5 Infrastructure

Hosting: Cloud-based (AWS, GCP, or Digital Ocean)
Container Management: Docker and Docker Compose
CI/CD: GitHub Actions or similar
Monitoring: Prometheus and Grafana
Logging: ELK Stack (Elasticsearch, Logstash, Kibana)
Load Balancing: Nginx

3. Database Schema Design
3.1 Core Tables
Users Table
sqlCREATE TABLE users (
  id SERIAL PRIMARY KEY,
  email VARCHAR(255) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role VARCHAR(50) NOT NULL,
  created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);
Dealers Table
sqlCREATE TABLE dealers (
  id SERIAL PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  address TEXT,
  city VARCHAR(100),
  state VARCHAR(50),
  zip VARCHAR(20),
  phone VARCHAR(50),
  status VARCHAR(50) NOT NULL,
  approval_date TIMESTAMP WITH TIME ZONE,
  dealertrack_dealer_id VARCHAR(100),
  created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);
Dealer Staff Table
sqlCREATE TABLE dealer_staff (
  id SERIAL PRIMARY KEY,
  dealer_id INTEGER REFERENCES dealers(id) ON DELETE CASCADE,
  user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
  position VARCHAR(100),
  permissions JSONB,
  created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);
Credit Applications Table
sqlCREATE TABLE credit_applications (
  id SERIAL PRIMARY KEY,
  dealer_id INTEGER REFERENCES dealers(id) ON DELETE CASCADE,
  status VARCHAR(50) NOT NULL,
  application_data JSONB NOT NULL,
  vehicle_data JSONB NOT NULL,
  financial_data JSONB NOT NULL,
  submission_date TIMESTAMP WITH TIME ZONE,
  last_processed_date TIMESTAMP WITH TIME ZONE,
  processing_status VARCHAR(50) DEFAULT 'pending',
  processing_notes TEXT,
  created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);
Documents Table
sqlCREATE TABLE documents (
  id SERIAL PRIMARY KEY,
  credit_application_id INTEGER REFERENCES credit_applications(id) ON DELETE CASCADE,
  name VARCHAR(255) NOT NULL,
  type VARCHAR(100) NOT NULL,
  status VARCHAR(50) NOT NULL,
  storage_path VARCHAR(255),
  thumbnail_path VARCHAR(255),
  created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);
Communications Table
sqlCREATE TABLE communications (
  id SERIAL PRIMARY KEY,
  credit_application_id INTEGER REFERENCES credit_applications(id) ON DELETE CASCADE,
  sender_id INTEGER REFERENCES users(id),
  sender_type VARCHAR(50) NOT NULL,
  message TEXT NOT NULL,
  attachments JSONB,
  read_status BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);
Application History Table
sqlCREATE TABLE application_history (
  id SERIAL PRIMARY KEY,
  credit_application_id INTEGER REFERENCES credit_applications(id) ON DELETE CASCADE,
  user_id INTEGER REFERENCES users(id),
  action VARCHAR(100) NOT NULL,
  details JSONB,
  created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);
Automation Jobs Table
sqlCREATE TABLE automation_jobs (
  id SERIAL PRIMARY KEY,
  credit_application_id INTEGER REFERENCES credit_applications(id) ON DELETE CASCADE,
  status VARCHAR(50) NOT NULL,
  scheduled_time TIMESTAMP WITH TIME ZONE,
  completed_time TIMESTAMP WITH TIME ZONE,
  attempt_count INTEGER DEFAULT 0,
  last_error TEXT,
  result_log TEXT,
  created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);
4. API Design
4.1 RESTful API Endpoints
Authentication APIs
POST   /api/auth/local             # Local authentication
POST   /api/auth/local/register    # Registration
POST   /api/auth/forgot-password   # Password reset request
POST   /api/auth/reset-password    # Password reset
Dealer Management APIs
GET    /api/dealers                # List dealers
POST   /api/dealers                # Create dealer
GET    /api/dealers/:id            # Get dealer details
PUT    /api/dealers/:id            # Update dealer
DELETE /api/dealers/:id            # Delete dealer
GET    /api/dealers/:id/staff      # List dealer staff
POST   /api/dealers/:id/staff      # Add staff member
GET    /api/dealers/:id/dashboard  # Get dashboard data
Credit Application Management APIs
GET    /api/credit-applications            # List applications
POST   /api/credit-applications            # Create application
GET    /api/credit-applications/:id        # Get application details
PUT    /api/credit-applications/:id        # Update application
DELETE /api/credit-applications/:id        # Delete application
POST   /api/credit-applications/:id/submit # Submit for processing
GET    /api/credit-applications/:id/status # Get processing status
POST   /api/credit-applications/:id/documents # Upload documents
GET    /api/credit-applications/:id/documents # List documents
GET    /api/credit-applications/:id/communications # Get communication history
POST   /api/credit-applications/:id/communications # Add message
Admin APIs
GET    /api/admin/credit-applications      # Administrative application list
POST   /api/admin/credit-applications/:id/review # Review application
POST   /api/admin/credit-applications/:id/process # Manually trigger processing
GET    /api/admin/dealers                  # Administrative dealer list
POST   /api/admin/dealers/:id/approve      # Approve dealer
GET    /api/admin/reports                  # Get reports
GET    /api/admin/automation-jobs          # List automation jobs
POST   /api/admin/automation-jobs/:id/retry # Retry failed job
5. Chrome Extension Integration Implementation
5.1 Integration Architecture
The Chrome Extension integration system will consist of several components:

Chrome Extension: Installed by dealers and finance admins to facilitate DealerTrack integration
Hidden Embedded Form Page: A page that embeds the DealerTrack form using their embedding suite
Form Data Mapping Layer: Transforms our application schema to match DealerTrack fields
API Endpoints: Allow the extension to retrieve and update data in our system
Error Handling & Retry System: Manages failures and provides visibility

5.2 Integration Workflow
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│ Application │────►│ Strapi API  │────►│  Hidden     │
│  Submitted  │     │  Endpoints  │     │  Form Page  │
└─────────────┘     └─────────────┘     └─────────────┘
                           │                   ▲
                           ▼                   │
                    ┌─────────────┐     ┌─────────────┐
                    │   Chrome    │◄───►│ DealerTrack │
                    │  Extension  │     │ Embedded    │
                    └─────────────┘     │ Form        │
                           ▲            └─────────────┘
                           │                   ▲
                           ▼                   │
                    ┌─────────────┐           │
                    │ PostgreSQL  │◄──────────┘
                    │  Database   │
                    └─────────────┘
5.3 Form Field Mapping
The system will maintain a comprehensive mapping between our database schema and DealerTrack form fields:
javascript// Example field mapping configuration
const fieldMapping = {
  // Applicant Information
  'borrower1_applicant': '#applicant',
  'borrower1_dob': '#dob',
  'borrower1_ssn': '#ssn',
  'borrower1_driversLicense': '#driversLicense',
  
  // Contact Information
  'borrower1_homePhone': '#homePhone',
  'borrower1_workPhone': '#workPhone',
  'borrower1_cellPhone': '#cellPhone',
  'borrower1_email': '#email',
  
  // Address Information
  'borrower1_currentAddress': '#currentAddress',
  
  // Vehicle Information
  'vehicleYear': '#vehicleYear',
  'vehicleMakeModel': '#vehicleMakeModel',
  'vehicleVIN': '#vehicleVIN',
  
  // Financial Information
  'sellingPrice': '#sellingPrice',
  
  // ... additional field mappings
};
5.4 Error Handling Strategy

Comprehensive logging of all automation steps
Screenshot capture at point of failure
Retry mechanism with exponential backoff
Admin notifications for persistent failures
Manual intervention capabilities

5.5 Chrome Extension Code Example
javascript// Example Chrome Extension content script (simplified)
// This script runs in the context of the hidden form page with the DealerTrack iframe

// Field mapping configuration
const fieldMapping = {
  // Applicant Information
  'borrower1_applicant': '#applicant',
  'borrower1_dob': '#dob',
  'borrower1_ssn': '#ssn',
  // ... additional field mappings
};

// Listen for messages from the extension background script
chrome.runtime.onMessage.addListener((message, sender, sendResponse) => {
  if (message.action === 'fillForm') {
    fillDealerTrackForm(message.applicationData)
      .then(result => sendResponse({ success: true, result }))
      .catch(error => sendResponse({ success: false, error: error.message }));
    return true; // Indicates async response
  } else if (message.action === 'scrapeData') {
    scrapeDealerTrackData()
      .then(data => sendResponse({ success: true, data }))
      .catch(error => sendResponse({ success: false, error: error.message }));
    return true; // Indicates async response
  }
});

// Function to fill the DealerTrack form
async function fillDealerTrackForm(applicationData) {
  try {
    // Get the iframe containing the DealerTrack form
    const iframe = document.querySelector('iframe[src*="dealertrack.com"]');
    if (!iframe) throw new Error('DealerTrack iframe not found');
    
    // Access the iframe content
    const iframeContent = iframe.contentDocument || iframe.contentWindow.document;
    
    // Fill form fields based on mapping
    for (const [fieldKey, selector] of Object.entries(fieldMapping)) {
      const value = getNestedValue(applicationData, fieldKey);
      if (value) {
        const element = iframeContent.querySelector(selector);
        if (element) {
          element.value = value;
          // Trigger change event to activate any listeners
          element.dispatchEvent(new Event('change', { bubbles: true }));
        }
      }
    }
    
    // Handle multi-step form process
    const continueButtons = iframeContent.querySelectorAll('button.continue-button');
    if (continueButtons.length > 0) {
      // Click through each continue button
      for (let i = 0; i < continueButtons.length; i++) {
        continueButtons[i].click();
        // Wait for next page to load
        await new Promise(resolve => setTimeout(resolve, 1000));
      }
    }
    
    // Submit the form
    const submitButton = iframeContent.querySelector('button[type="submit"]');
    if (submitButton) {
      submitButton.click();
      
      // Wait for confirmation
      return new Promise((resolve, reject) => {
        const checkConfirmation = setInterval(() => {
          const confirmationElement = iframeContent.querySelector('.confirmation-message');
          if (confirmationElement) {
            clearInterval(checkConfirmation);
            resolve('Form submitted successfully');
          }
        }, 500);
        
        // Timeout after 30 seconds
        setTimeout(() => {
          clearInterval(checkConfirmation);
          reject(new Error('Timeout waiting for form submission confirmation'));
        }, 30000);
      });
    }
    
    return 'Form filled successfully';
  } catch (error) {
    console.error('Error filling DealerTrack form:', error);
    throw error;
  }
}

// Function to scrape data from the DealerTrack form
async function scrapeDealerTrackData() {
  try {
    // Get the iframe containing the DealerTrack form
    const iframe = document.querySelector('iframe[src*="dealertrack.com"]');
    if (!iframe) throw new Error('DealerTrack iframe not found');
    
    // Access the iframe content
    const iframeContent = iframe.contentDocument || iframe.contentWindow.document;
    
    // Create a reverse mapping to extract data
    const reverseMapping = {};
    for (const [key, selector] of Object.entries(fieldMapping)) {
      reverseMapping[selector] = key;
    }
    
    // Extract data from the form
    const extractedData = {};
    for (const [selector, key] of Object.entries(reverseMapping)) {
      const element = iframeContent.querySelector(selector);
      if (element) {
        extractedData[key] = element.value;
      }
    }
    
    // Extract status information if available
    const statusElement = iframeContent.querySelector('.status-indicator');
    if (statusElement) {
      extractedData.status = statusElement.textContent.trim();
    }
    
    return extractedData;
  } catch (error) {
    console.error('Error scraping DealerTrack data:', error);
    throw error;
  }
}

// Helper function to get nested values from object
function getNestedValue(obj, path) {
  return path.split('.').reduce((prev, curr) => 
    prev && prev[curr] ? prev[curr] : null, obj
  );
}

// Background script (background.js)
chrome.runtime.onMessage.addListener((message, sender, sendResponse) => {
  if (message.action === 'processApplication') {
    processApplication(message.applicationId)
      .then(result => sendResponse({ success: true, result }))
      .catch(error => sendResponse({ success: false, error: error.message }));
    return true; // Indicates async response
  }
});

// Function to process an application
async function processApplication(applicationId) {
  try {
    // Fetch application data from our API
    const response = await fetch(`${API_BASE_URL}/api/credit-applications/${applicationId}`);
    if (!response.ok) throw new Error('Failed to fetch application data');
    
    const applicationData = await response.json();
    
    // Update processing status via API
    await fetch(`${API_BASE_URL}/api/credit-applications/${applicationId}/status`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ status: 'processing' })
    });
    
    // Open the hidden form page in a new tab
    const tab = await chrome.tabs.create({
      url: `${API_BASE_URL}/hidden-form?applicationId=${applicationId}`,
      active: false // Open in background
    });
    
    // Wait for page to load
    await new Promise(resolve => setTimeout(resolve, 2000));
    
    // Send message to content script to fill the form
    const result = await chrome.tabs.sendMessage(tab.id, {
      action: 'fillForm',
      applicationData
    });
    
    // Update application status based on result
    if (result.success) {
      await fetch(`${API_BASE_URL}/api/credit-applications/${applicationId}/status`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ status: 'submitted' })
      });
    } else {
      await fetch(`${API_BASE_URL}/api/credit-applications/${applicationId}/status`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
          status: 'error',
          error: result.error
        })
      });
    }
    
    // Close the tab
    await chrome.tabs.remove(tab.id);
    
    return result;
  } catch (error) {
    console.error(`Error processing application ${applicationId}:`, error);
    
    // Update application status to error
    await fetch(`${API_BASE_URL}/api/credit-applications/${applicationId}/status`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ 
        status: 'error',
        error: error.message
      })
    });
    
    throw error;
  }
}
6. Security Architecture
6.1 Authentication & Authorization

JWT-based authentication with short expiry
Refresh token rotation
Role-based access control (RBAC)
Permission-based action control
IP-based restrictions for admin access
Multi-factor authentication for sensitive operations

6.2 Data Protection

TLS/SSL encryption for all communications
Data encryption at rest for sensitive fields
PII handling according to regulations
Data minimization principles
Regular security audits and penetration testing

6.3 API Security

API key management with rotation policy
Rate limiting to prevent abuse
Input validation and sanitization
CORS configuration

6.4 Database Security

Connection pooling with limited privileges
Parameterized queries to prevent SQL injection
Regular security patching
Data encryption for sensitive fields
Database access logging and monitoring

6.5 Automation Security

Credentials stored in secure environment variables
Headless browser execution in isolated environment
Network isolation for automation services
Session timeout and automatic cleanup
Limited access permissions to automation services

7. Performance Optimization
7.1 Database Optimization

Proper indexing strategy
Query optimization and monitoring
Connection pooling configuration
Regular database maintenance
Partitioning for large tables

7.2 API Performance

Response caching where appropriate
Pagination for large data sets
Optimized query parameters
Request batching for multiple operations
API response compression

7.3 Frontend Performance

Code splitting for optimized loading
Asset optimization (minification, compression)
Lazy loading of components
Virtualization for large lists
Performance monitoring and analytics

7.4 Automation Performance

Job queue prioritization
Parallel processing capabilities
Resource monitoring and scaling
Scheduled execution during off-peak hours
Performance metrics tracking

8. Monitoring & Maintenance
8.1 Application Monitoring

Real-time error tracking and alerting
Performance monitoring dashboard
Automation job monitoring
User experience monitoring
Custom metrics for business KPIs

8.2 Database Monitoring

Query performance analysis
Storage utilization tracking
Index optimization
Backup verification
Connection pool monitoring

8.3 Infrastructure Monitoring

Server resource utilization
Network performance
Load balancer metrics
CDN performance
Security monitoring

8.4 Automation Monitoring

Job success/failure rates
Processing time metrics
Error pattern analysis
Resource utilization
Automated alerts for systemic issues

9. Deployment Strategy
9.1 Environments

Development environment for active development
Staging environment for testing
UAT environment for user acceptance testing
Production environment for live operations

9.2 CI/CD Pipeline
┌─────────┐     ┌─────────┐     ┌─────────┐     ┌─────────┐     ┌─────────┐
│  Code   │────►│  Build  │────►│  Test   │────►│ Staging │────►│   Prod  │
│ Commit  │     │ Process │     │  Suite  │     │ Deploy  │     │ Deploy  │
└─────────┘     └─────────┘     └─────────┘     └─────────┘     └─────────┘

Automated builds on commit
Unit and integration testing
Security scanning
Deployment automation
Rollback capability

9.3 Database Migration Strategy

Schema versioning
Automated migration scripts
Data migration tools
Rollback procedures
Zero-downtime migration where possible

10. Technical Challenges & Mitigations
10.1 Chrome Extension Integration Challenges

Challenge: DealerTrack form structure changes

Mitigation: Regular monitoring of form structure and quick adaptation of the Chrome Extension


Challenge: Chrome Extension distribution and updates

Mitigation: Implement auto-update mechanism and clear installation instructions


Challenge: Cross-browser compatibility

Mitigation: Focus on Chrome initially, with potential for Edge compatibility (Chromium-based)


Challenge: Handling validation errors during form submission

Mitigation: Comprehensive error handling and field-specific validation in the extension


Challenge: Session management and timeouts

Mitigation: Leverage browser session management and implement timeout detection



10.2 Performance Challenges

Challenge: Processing large volumes of applications

Mitigation: Queue-based processing with worker scaling


Challenge: Database performance with high transaction volume

Mitigation: Proper indexing, query optimization, and potential read replicas


Challenge: UI responsiveness with complex data

Mitigation: Pagination, virtualization, and optimized rendering



10.3 Scaling Challenges

Challenge: Handling increasing dealer count

Mitigation: Horizontal scaling with load balancing


Challenge: Database growth over time

Mitigation: Partitioning and archiving strategies


Challenge: Peak traffic handling

Mitigation: Auto-scaling configuration and caching strategies



11. Conclusion
This revised architecture document provides a comprehensive technical blueprint for implementing the Pinnacle Auto Finance Dealer Portal using a Chrome Extension integration approach instead of direct DealerTrack API integration. The architecture is designed with security, scalability, and maintainability as core principles.

By implementing our own credit application forms and using a Chrome Extension to interact with the embedded DealerTrack forms, we create a cost-effective solution that doesn't require expensive API licenses while still providing a seamless experience for dealers. The Chrome Extension approach offers several advantages over server-side automation:

1. Bidirectional data flow between our system and DealerTrack
2. Avoids CORS issues and automation blocking mechanisms
3. Operates within the user's browser context for better performance
4. Can handle the multi-step nature of DealerTrack forms more naturally

This approach introduces the requirement for dealers to install a Chrome Extension, but offers significant benefits in terms of reliability, performance, and flexibility. The development team should focus on creating a user-friendly extension with robust error handling and clear installation instructions to ensure a smooth user experience.
