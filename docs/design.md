# PINNACLE PORTAL DESIGN DOCUMENT

## 1. Executive Summary
This document outlines the design for a dealer portal that will enable small independent dealerships to access broker financing solutions through Pinnacle Auto Finance. The portal will utilize a Chrome Extension for bidirectional integration with DealerTrack's embedded forms, facilitating the complete broker deal lifecycle from dealer registration to deal completion.

The system will provide a seamless experience for dealers while ensuring Pinnacle maintains control over the approval process and communication with lenders. The Chrome Extension approach enables both form filling and data scraping capabilities, creating a robust integration between our portal and DealerTrack without requiring expensive API licenses. The portal will cater to the specific needs of independent dealerships that may not have direct access to multiple lenders.

## 2. System Overview

### 2.1 Project Goals
- Create a comprehensive dealer portal that facilitates the entire broker financing workflow
- Implement secure bidirectional integration with DealerTrack iframe credit app and F&I DealerTrack credit module via Chrome Extensions
- Provide real-time status updates and notifications to dealers
- Build an admin interface for Pinnacle staff to review and process deals
- Design a scalable solution that can accommodate future growth and additional features

### 2.2 Key Stakeholders
- Independent dealerships (primary users)
- Pinnacle Auto Finance staff (administrators)
- End customers (vehicle buyers, indirectly affected)
- DealerTrack (embedded form provider)

## 3. User Workflows

### 3.1 Dealer Registration & Authentication

#### Registration Process
- Dealer completes registration through Strapi portal
- Admin reviews and approves dealer application
- Dealer receives credentials and accesses dashboard

#### Authentication Flow
- Secure login process with option for multi-factor authentication
- Role-based access for dealership staff
- Session management with appropriate timeouts

### 3.2 Deal Submission & Processing

#### Credit Application Workflow
- Dealer submits credit application through Pinnacle portal
- System validates application completeness, updates portal DB, e
- **Chrome Extension fills DealerTrack embedded form with application data** Note that said application is embedden in hidden iframe with same URL as our custom form so that URL specfic controls from DealerTrack side don't cause issues. 
- Once Chrome Exntension submits appliation syste notifies admins via email.
- Deal enters "Pending Review" status in dealer's pipeline
- Finance Admin reviews and structures application
- **Admin triggers Chrome Extension pulling updates from  DealerTrack Deal Jacket view and post information to Dealers pipeline view for status updates and decisions**
- Finance Admin reviews decision and customizes terms if needed
- Approval is published to dealer with terms and conditions

#### Vehicle Acquisition & Documentation
- Dealer submits Wholesale Bill of Sale to Pinnacle
- System validates dealer credentials and documentation
- **Chrome Extension adds vehicle to inventory through DealerTrack embedded form**
- Deal documents are generated and made available to dealer
- Dealer obtains signatures and uploads documents
- Admin reviews documents and confirms completeness
- Final approval is issued for vehicle delivery

#### Deal Completion
- Dealer delivers vehicle to customer
- Final paperwork is mailed to Pinnacle
- Title work is processed and payment issued to dealer
- Deal status changes to "Complete" in the system

## 4. Communication System Design

### 4.1 Omni-Channel Communication System

#### Conversation Management
- Centralized conversation log tracking all interactions
- Communication methods including in-app messaging, email, and SMS
- All communications timestamped and associated with specific deals
- AI agents monitoring communication patterns and triggering follow-ups

#### Notification Framework
- Event-driven architecture using Strapi Cloud Functions
- Push notifications for mobile users
- Email notifications for critical status changes
- SMS notifications for urgent actions required

### 4.2 AI-Assisted Communication

#### Follow-up Generation
- AI-generated follow-up messages based on deal status
- Customized messaging based on dealer history and preferences
- Proactive notification of missing documents or information

#### Communication Analytics
- Tracking of response times and effectiveness
- Identification of common questions and issues
- Optimization of communication strategies

## 5. User Interface Design

### 5.1 Dealer Portal Interface

#### Dashboard & Pipeline View
- Real-time deal status dashboard
- Filtering and search capabilities
- Status-based visualization
- Action buttons for common tasks
- Performance metrics and insights

#### Deal Submission Interface
- Multi-step form with progress indication
- Validation and error prevention
- Document upload capabilities
- Draft saving functionality
- Mobile-responsive design

#### Document Management Interface
- Document categorization and organization
- Status indicators for document processing
- Signature request and tracking
- Document version history
- Thumbnail previews

### 5.2 Admin Interface

#### Deal Management Console
- Comprehensive overview of all deals
- Advanced filtering and sorting
- Bulk action capabilities
- Decision customization interface
- Dealer performance metrics
- **Chrome Extension integration monitoring and management controls**

#### Dealer Management System
- Dealer onboarding workflow
- Account management tools
- Performance tracking and reporting
- Communication history
- Compliance monitoring

#### Reporting Dashboard
- Business intelligence visualizations
- Deal funnel analytics
- Conversion rate tracking
- Finance product penetration
- Regulatory compliance reporting
- **Chrome Extension integration performance metrics**

## 6. Mobile Responsiveness
- Progressive Web App Features
- Offline functionality for field use
- Push notification integration
- Camera access for document scanning
- Touch-optimized interface
- Responsive design for all device sizes

## 7. Implementation Plan

### 7.1 Phase 1: Foundation (10 Steps)

#### Steps 1-2: Infrastructure & Authentication
- Strapi project setup
- Authentication system implementation
- Security configuration
- Basic UI framework

#### Steps 3-4: Dealer Management & Profiles
- Dealer registration process
- Admin approval workflow
- User role management
- Profile management

#### Steps 5-6: Credit Application Forms
- Custom credit application form development
- Form validation and progression
- Database schema implementation
- Draft saving functionality

#### Steps 7-8: Communication System
- Conversation log implementation
- Notification system development
- Email integration
- SMS capabilities

#### Steps 9-10: Document Management
- Document upload system
- Document categorization
- Storage integration
- Preview generation

### 7.2 Phase 2: Chrome Extension Implementation (6 Steps)

#### Steps 11-12: Hidden Form Page Setup
- DealerTrack embedding implementation
- Form field identification and mapping
- Session management
- Form submission detection

#### Steps 13-14: Chrome Extension Development
- Extension architecture and framework setup
- Content script for form interaction
- Background script for API communication
- Field mapping implementation
- Form filling and data scraping logic
- Error handling and recovery

#### Steps 15-16: Integration System
- API endpoints for extension communication
- Status tracking and synchronization
- Retry mechanisms
- Manual intervention tools
- Extension distribution and update system

### 7.3 Phase 3: AI & Advanced Features (4 Steps)

#### Steps 17-18: AI Integration
- Google AI Studio integration
- Follow-up message generation
- Document analysis assistance
- Communication optimization

#### Steps 19-20: Analytics & Reporting
- Dashboard development
- Reporting system implementation
- Performance metrics
- Export capabilities
- Automation performance analysis

### 7.4 Phase 4: Testing & Deployment (2 Steps)

#### Steps 21-22: Testing & Optimization
- End-to-end testing
- Automation reliability testing
- Performance optimization
- Security validation
- User acceptance testing

## 8. Key Business Requirements

### 8.1 Dealer Management
- Requirement 1: Support for multi-tier dealer hierarchy
- Requirement 2: Customizable approval workflows for new dealers
- Requirement 3: Performance tracking and incentive management
- Requirement 4: Compliance documentation and verification

### 8.2 Deal Management
- Requirement 1: Support for multiple deal types (new, used, lease, finance)
- Requirement 2: Customizable approval workflows based on deal parameters
- Requirement 3: Document verification and storage
- Requirement 4: Reliable Chrome Extension integration with DealerTrack forms
- Requirement 5: Bidirectional data flow between Pinnacle Portal and DealerTrack

### 8.3 Reporting and Analytics
- Requirement 1: Real-time dashboards for management overview
- Requirement 2: Detailed dealer performance reports
- Requirement 3: Deal pipeline analysis and forecasting
- Requirement 4: Automation performance and reliability metrics

## 9. Future Enhancements

### 9.1 Potential Future Features
- Mobile application for dealers
- Enhanced AI for deal structuring recommendations
- Advanced form field detection for DealerTrack updates
- Customer-facing portal for end buyers
- Advanced document recognition and processing
- Integration with accounting systems
- Chrome Extension enhancements:
  - Support for additional browsers (Edge, Firefox)
  - Offline capabilities for form filling
  - Enhanced data scraping capabilities
  - Automated screenshot capture for audit trails

### 9.2 Expansion Considerations
- Support for multiple finance portals beyond DealerTrack
- International market support
- Additional vehicle types and financing models
- White-label solutions for larger dealer groups
- API integration when economically feasible

## 10. Conclusion
This design document provides a comprehensive blueprint for implementing the Pinnacle Auto Finance Dealer Portal with Chrome Extension integration for DealerTrack forms. The phased approach allows for incremental delivery of value while managing technical complexity. The system is designed to streamline the broker finance process for independent dealerships while maintaining Pinnacle's control over the approval and communication processes.

By implementing our custom credit application forms and using a Chrome Extension for bidirectional integration with DealerTrack embedded forms, we provide a cost-effective solution that doesn't require expensive API licenses. This approach offers several key advantages:

1. **Bidirectional Data Flow**: The Chrome Extension can both fill forms with our data and scrape updated information from DealerTrack
2. **Browser-Based Integration**: Avoids CORS issues and automation blocking mechanisms by operating within the user's browser context
3. **Enhanced User Experience**: Provides a seamless experience for dealers while maintaining full control over the process
4. **Technical Flexibility**: Can adapt more easily to DealerTrack form changes through extension updates

The Chrome Extension approach does require dealers to install the extension as part of their onboarding process, but the benefits in terms of reliability, performance, and bidirectional data capabilities make this a worthwhile tradeoff.
