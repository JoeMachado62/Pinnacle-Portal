Pinnacle Auto Finance Dealer Portal: Revised Architecture
1. System Architecture Overview
The Pinnacle Auto Finance Dealer Portal will be built using a multi-tier architecture that separates concerns while providing a scalable and maintainable system. This revised architecture adapts to the unavailability of direct DealerTrack API access by implementing server-side automation instead of client-side Chrome Extensions.

1.1 Architecture Layers
Presentation Layer
WordPress frontend with Ultimate Member integration
Custom dashboard views for dealers and administrators
Responsive design for mobile compatibility
Form-based interface for credit applications
Application Layer
WordPress core providing user management and content storage
Custom plugins for business logic and data management
Custom Post Types for structured data storage
REST API for service communication
Integration Layer
Server-side Puppeteer automation for DealerTrack integration
Field mapping and transformation
Bidirectional data flow between WordPress and DealerTrack
Job scheduling and monitoring
Data Layer
WordPress MySQL database
Custom tables for specific functionality where needed
WordPress media library for document storage
Caching for performance optimization
1.2 System Interaction Flow
┌─────────────┐     ┌─────────────┐     ┌──────────────┐     ┌─────────────┐
│  Dealer UI  │◄───►│ WordPress   │◄───►│ Puppeteer    │◄───►│ DealerTrack │
│  Admin UI   │     │ CPTs & API  │     │ Service      │     │ Interface   │
└─────────────┘     └─────────────┘     └──────────────┘     └─────────────┘
                           ▲                   ▲                    ▲
                           │                   │                    │
                           ▼                   │                    │
                    ┌─────────────┐           │                    │
                    │  WordPress  │◄──────────┘                    │
                    │  Database   │◄───────────────────────────────┘
                    └─────────────┘
2. Technical Stack
2.1 Frontend Technologies
CMS: WordPress
User Management: Ultimate Member
Form Handling: Custom form implementation
JavaScript Framework: jQuery
CSS Framework: Bootstrap or custom styling
HTTP Client: WordPress HTTP API or Fetch API
Testing: PHPUnit
2.2 Backend Technologies
CMS Core: WordPress
Custom Development: PHP 7.4+
API Framework: WordPress REST API
Authentication: WordPress authentication with JWT
Process Management: WP Action Scheduler
Testing: PHPUnit
2.3 Integration Technologies
Automation Engine: Puppeteer
Runtime Environment: Node.js
Browser Control: Headless Chrome
Data Transformation: Lodash
Error Handling: Custom retry and logging system
API Communication: Axios
2.4 Database
RDBMS: MySQL (WordPress default)
ORM: WordPress wpdb
Query Builder: WordPress query functions
Migration Tool: Custom migrations as needed
Backup Strategy: Automated daily backups
2.5 Infrastructure
Hosting: Cloud-based VPS (AWS, DigitalOcean, or similar)
Container Management: Docker and Docker Compose
Web Server: Nginx
CI/CD: GitHub Actions
Monitoring: Prometheus and Grafana
Logging: ELK Stack
3. WordPress Data Architecture
Instead of the original PostgreSQL schema design, we'll now use WordPress Custom Post Types and meta fields for data storage.

3.1 User Structure
Based on WordPress users with Ultimate Member extensions
Role-based permissions:
dealer: Dealership owners
dealer_staff: Staff members at dealerships
finance_admin: Pinnacle financial administrators
admin: System administrators
3.2 Custom Post Types
Dealers CPT
php
register_post_type('paf_dealer', [
    'public' => false,
    'show_ui' => true,
    'supports' => ['title', 'custom-fields'],
]);

// Meta fields
'address' => ['type' => 'text'],
'city' => ['type' => 'text'],
'state' => ['type' => 'text'],
'zip' => ['type' => 'text'],
'phone' => ['type' => 'text'],
'status' => ['type' => 'select', 'options' => ['pending', 'approved', 'suspended', 'inactive']],
'approval_date' => ['type' => 'date'],
'associated_user_id' => ['type' => 'number'],
Credit Applications CPT
php
register_post_type('paf_credit_app', [
    'public' => false,
    'show_ui' => true,
    'supports' => ['title', 'author', 'custom-fields'],
]);

// Meta fields
'status' => ['type' => 'select', 'options' => ['draft', 'pending_review', 'processing', 'approved', 'declined', 'completed']],
'primary_borrower' => ['type' => 'json'],
'vehicle_data' => ['type' => 'json'],
'financial_data' => ['type' => 'json'],
'submission_date' => ['type' => 'datetime'],
'last_processed_date' => ['type' => 'datetime'],
'processing_status' => ['type' => 'select', 'options' => ['pending', 'processing', 'submitted', 'error']],
'processing_notes' => ['type' => 'text'],
'dt_reference_number' => ['type' => 'text'],
'dt_status' => ['type' => 'text'],
Documents CPT
php
register_post_type('paf_document', [
    'public' => false,
    'show_ui' => true,
    'supports' => ['title', 'custom-fields'],
]);

// Meta fields
'credit_application_id' => ['type' => 'number'],
'document_type' => ['type' => 'select', 'options' => ['id', 'proof_of_income', 'proof_of_residence', 'vehicle_title', 'bill_of_sale']],
'status' => ['type' => 'select', 'options' => ['pending', 'approved', 'rejected']],
'file_id' => ['type' => 'number'],
'thumbnail_id' => ['type' => 'number'],
Additional CPTs
Communications
Application History
Automation Jobs
4. REST API Architecture
We'll extend the WordPress REST API to provide endpoints for the Puppeteer service and the frontend to interact with.

4.1 API Namespace
All custom endpoints will use the 'paf/v1' namespace.

4.2 Key Endpoints
GET    /wp-json/paf/v1/jobs/pending         # Get pending automation jobs
POST   /wp-json/paf/v1/jobs/:id/update      # Update job status
GET    /wp-json/paf/v1/applications/:id     # Get application details
POST   /wp-json/paf/v1/applications/:id     # Update application
GET    /wp-json/paf/v1/dealers              # List dealers
POST   /wp-json/paf/v1/dealers              # Create dealer
GET    /wp-json/paf/v1/dashboard            # Get dashboard data
POST   /wp-json/paf/v1/documents/upload     # Upload document
4.3 Authentication
API authentication will use WordPress application passwords or JWT authentication for secure communication between the Puppeteer service and WordPress.

5. Puppeteer Integration Architecture
Instead of the Chrome Extension, we'll use a server-side Puppeteer service to interact with DealerTrack.

5.1 Integration Architecture
Node.js Service: Runs in a Docker container
Job Queue: WP Action Scheduler for reliable job processing
Field Mapping: Configuration files define mappings between our data and DealerTrack fields
Error Handling: Comprehensive retry and logging mechanisms
Monitoring: Health checks and performance tracking
5.2 Integration Workflow
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│ Application │────►│ WordPress   │────►│ Job Queue   │
│  Submitted  │     │  CPT & Meta │     │ (WP Action) │
└─────────────┘     └─────────────┘     └─────────────┘
                                               │
                                               ▼
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│ WordPress   │◄────│ REST API    │◄────│ Puppeteer   │
│ Database    │     │ Updates     │     │ Service     │
└─────────────┘     └─────────────┘     └─────────────┘
                                               │
                                               ▼
                                        ┌─────────────┐
                                        │ DealerTrack │
                                        │ Interface   │
                                        └─────────────┘
5.3 Field Mapping Configuration
The system will maintain a comprehensive mapping between our CPT meta fields and DealerTrack form fields:

javascript
// Example field mapping configuration
const fieldMapping = {
  // Borrower Information
  'primary_borrower.first_name': '#firstName',
  'primary_borrower.last_name': '#lastName',
  'primary_borrower.ssn': '#ssn',
  
  // Vehicle Information
  'vehicle_data.year': '#vehicleYear',
  'vehicle_data.make': '#vehicleMake',
  'vehicle_data.model': '#vehicleModel',
  'vehicle_data.vin': '#vehicleVIN',
  
  // Financial Information
  'financial_data.selling_price': '#sellingPrice',
  
  // ... additional field mappings
};
5.4 Error Handling Strategy
Comprehensive logging of all automation steps
Screenshot capture at point of failure
Retry mechanism with exponential backoff
Admin notifications for persistent failures
Manual intervention capabilities
5.5 Puppeteer Service Implementation
javascript
// Example Puppeteer implementation (simplified)
async function processApplication(appId) {
  try {
    // Fetch application data from WordPress API
    const appData = await fetchApplicationData(appId);
    
    // Launch browser
    const browser = await puppeteer.launch({
      headless: true,
      args: ['--no-sandbox']
    });
    
    const page = await browser.newPage();
    
    // Login to DealerTrack
    await page.goto('https://dealertrack.com/login');
    await page.type('#username', process.env.DT_USERNAME);
    await page.type('#password', process.env.DT_PASSWORD);
    await page.click('button[type="submit"]');
    await page.waitForNavigation();
    
    // Navigate to credit application form
    await page.goto('https://dealertrack.com/credit-app');
    
    // Fill form fields based on mapping
    for (const [fieldKey, selector] of Object.entries(fieldMapping)) {
      const value = getNestedValue(appData, fieldKey);
      if (value) {
        await page.type(selector, value);
      }
    }
    
    // Submit the form
    await page.click('#submitButton');
    await page.waitForNavigation();
    
    // Extract reference number
    const referenceNumber = await page.$eval('#referenceNumber', el => el.textContent);
    
    // Update application status via WordPress API
    await updateApplicationStatus(appId, {
      status: 'submitted',
      dt_reference: referenceNumber
    });
    
    await browser.close();
    return { success: true, referenceNumber };
  } catch (error) {
    console.error(`Error processing application ${appId}:`, error);
    
    // Update status to error
    await updateApplicationStatus(appId, {
      status: 'error',
      error: error.message
    });
    
    return { success: false, error: error.message };
  }
}
6. Security Architecture
6.1 Authentication & Authorization
WordPress user authentication
Role-based access control
Application passwords for API access
JWT tokens for service communication
IP-based restrictions for admin access
6.2 Data Protection
TLS/SSL encryption for all communications
Encryption at rest for sensitive fields
PII handling according to regulations
Proper data sanitization and validation
Regular security audits
6.3 API Security
Authentication for all API endpoints
Rate limiting
Input validation
CORS configuration
Audit logging
6.4 Database Security
Prepared statements to prevent SQL injection
Limited database user privileges
Regular backups with encryption
Secure handling of connection credentials
6.5 Automation Security
Credentials stored as encrypted environment variables
Secure communication between services
Headless browser execution in isolated environment
Proper session handling and cleanup
7. Performance Optimization
7.1 WordPress Optimization
Database query optimization
Object caching
Page caching where appropriate
CDN for static assets
Image optimization
7.2 API Performance
Response caching for appropriate endpoints
Pagination for large data sets
Query optimization
Data compression
7.3 Frontend Performance
Asset minification and bundling
Lazy loading of components
Optimized JavaScript and CSS
Image optimization
7.4 Automation Performance
Job batching
Resource monitoring
Performance metrics tracking
Parallel processing where possible
8. Monitoring & Maintenance
8.1 Application Monitoring
Real-time error tracking
Performance dashboards
User experience monitoring
Custom metrics for business KPIs
8.2 Database Monitoring
Query performance analysis
Storage utilization tracking
Backup verification
Index optimization
8.3 Infrastructure Monitoring
Server resource utilization
Network performance
Container health
Security monitoring
8.4 Automation Monitoring
Job success/failure rates
Processing time metrics
Error pattern analysis
Automated alerts for systemic issues
9. Deployment Strategy
9.1 Environments
Development environment
Staging environment
Production environment
9.2 CI/CD Pipeline
┌─────────┐     ┌─────────┐     ┌─────────┐     ┌─────────┐
│  Code   │────►│  Build  │────►│  Test   │────►│ Deploy  │
│ Commit  │     │ Process │     │  Suite  │     │         │
└─────────┘     └─────────┘     └─────────┘     └─────────┘
Automated builds on commit
Unit and integration testing
Security scanning
Deployment automation
Rollback capability
9.3 Docker Deployment
yaml
# Example docker-compose.yml (simplified)
version: '3'

services:
  wordpress:
    image: wordpress:latest
    ports:
      - "8080:80"
    environment:
      WORDPRESS_DB_HOST: db
      WORDPRESS_DB_USER: wordpress
      WORDPRESS_DB_PASSWORD: wordpress
      WORDPRESS_DB_NAME: wordpress
    volumes:
      - ./wp-content:/var/www/html/wp-content
    depends_on:
      - db

  db:
    image: mysql:5.7
    volumes:
      - db_data:/var/lib/mysql
    environment:
      MYSQL_ROOT_PASSWORD: rootpassword
      MYSQL_DATABASE: wordpress
      MYSQL_USER: wordpress
      MYSQL_PASSWORD: wordpress

  puppeteer:
    build: ./automation
    environment:
      - NODE_ENV=production
      - WP_API_URL=http://wordpress/wp-json
      - DT_USERNAME=${DT_USERNAME}
      - DT_PASSWORD=${DT_PASSWORD}
    volumes:
      - ./automation:/app
    depends_on:
      - wordpress

volumes:
  db_data:
10. Technical Challenges & Mitigations
10.1 Puppeteer Integration Challenges
Challenge: DealerTrack form structure changes

Mitigation: Regular monitoring and field mapping updates
Challenge: Authentication and session management

Mitigation: Implement robust login and session detection logic
Challenge: Error handling during form submission

Mitigation: Comprehensive error handling and retry mechanisms
Challenge: Performance and resource utilization

Mitigation: Optimize browser instances and implement job queueing
10.2 WordPress Performance Challenges
Challenge: Scaling with high transaction volume

Mitigation: Implement caching and optimize database queries
Challenge: Complex data relationships in WordPress

Mitigation: Carefully design CPT relationships and use optimized queries
Challenge: Security of sensitive data

Mitigation: Implement field-level encryption for sensitive data
10.3 Integration Stability Challenges
Challenge: Network reliability between services

Mitigation: Robust retry logic and circuit breakers
Challenge: Maintaining consistent state across systems

Mitigation: Transaction-like processing with rollback capabilities
Challenge: Monitoring and alerting

Mitigation: Comprehensive logging and alerting system
11. Conclusion
This revised architecture document provides a comprehensive technical blueprint for implementing the Pinnacle Auto Finance Dealer Portal using WordPress and server-side Puppeteer automation. This approach offers several advantages over the original Strapi + Chrome Extension design:

Simplified Architecture: Using WordPress for both user management and data storage reduces complexity
Improved User Experience: No need for dealers to install browser extensions
Centralized Management: All automation runs on the server under our control
Streamlined Authentication: Single DealerTrack admin account simplifies credentials management
Enhanced Reliability: Server-side automation is more consistent and easier to monitor
The system maintains the same core functionality while adapting to the constraints of not having direct DealerTrack API access. By leveraging WordPress's mature ecosystem and combining it with modern automation techniques, we create a solution that is both powerful and maintainable.

