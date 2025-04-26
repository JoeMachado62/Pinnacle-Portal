Pinnacle Auto Finance Dealer Portal
A comprehensive dealer portal that enables small independent dealerships to access broker financing solutions through Pinnacle Auto Finance. The portal uses automated form filling technology to interact with DealerTrack's embedded forms, facilitating the complete broker deal lifecycle from dealer registration to deal completion.
Development Guidelines
This project follows a detailed design specification. All development MUST adhere to:

Project Design Document
Architecture Document

Before implementing any feature, consult the relevant section in the Design Document.
Project Overview
This project implements a multi-tier architecture:
Pinnacle-Portal
├── frontend/                   # React-based frontend
│   ├── src/                    # React components and logic
│   ├── public/                 # Static assets
│   └── package.json            # Frontend dependencies
├── backend/                    # Strapi CMS with custom plugins
│   ├── api/                    # API endpoints and controllers
│   ├── config/                 # Strapi configuration
│   ├── extensions/             # Custom Strapi extensions
│   └── package.json            # Backend dependencies
├── automation/                 # Form automation services
│   ├── scripts/                # Playwright automation scripts
│   ├── mapping/                # Field mapping definitions
│   ├── services/               # Job scheduling and processing
│   └── package.json            # Automation dependencies
├── hidden-form/                # DealerTrack embedded form page
│   ├── index.html              # Main embedded form container
│   └── dealertrack.js          # DealerTrack embedding utilities
├── docs/                       # Project documentation
│   ├── design.md               # Detailed design document
│   ├── architecture.md         # Technical architecture details
│   └── Pinnacle Portal Design Mock Up screenshots.pdf  # UI mockups
├── package.json                # Root package.json
└── README.md                   # Project overview
Technology Stack

Frontend: React.js with TypeScript, Material UI, Redux
Backend: Strapi CMS with custom plugins
Database: PostgreSQL
Automation: Seperate Chrome Extensions for browser integration for for form filing and data extraction.
Integration: DealerTrack embedded forms
Authentication: JWT-based with role-based access control
Deployment: Cloud-based hosting with CI/CD pipeline

Key Features

Dealer Management: Registration, approval workflow, and profile management
Deal Submission: Multi-step credit application forms with validation
Document Management: Upload, signing, and tracking of deal documents
Form Automation: Chrome Extension for bidirectional integration with DealerTrack forms
Communication System: In-app messaging, email, and SMS notifications
Admin Interface: Deal review, approval, and management tools
Analytics & Reporting: Performance dashboards and custom reports

Installation

Clone the repository:
git clone <repository-url>

Navigate to the project directory:
cd Pinnacle-Portal

Install dependencies:
npm install


Development
To start the development environment:
npm run dev
This will launch both the frontend and backend servers:

Frontend: http://168.231.71.194:3000
Backend: http://168.231.71.194:1337
Admin: http://168.231.71.194:1337/admin

Automation Development
For automation development and testing:
npm run extension:dev
This will start the Chrome Extension in development mode, allowing you to:

Test form integration scripts
Debug field mappings
Monitor integration processes

Form Automation Architecture
The system uses a Chrome Extension for bidirectional integration with DealerTrack embedded forms:

Custom Credit Application: Dealers fill out our custom credit application form
Data Storage: Application data is stored in our PostgreSQL database
Chrome Extension: Installed by dealers and finance admins to facilitate DealerTrack integration
Hidden Form Page: A dedicated page hosts the DealerTrack embedded form for integration
Bidirectional Data Flow: The extension both fills forms and captures data from DealerTrack

Key advantages of this approach:

No expensive API integration required
Bidirectional data flow between our system and DealerTrack
Avoids CORS issues and automation blocking mechanisms
Operates within the user's browser context for better performance
Flexibility to customize our own forms
Ability to adapt to DealerTrack form changes

Documentation
Comprehensive documentation is available in the docs/ directory:

Design Document - Detailed project design and user workflows
Architecture Document - Technical architecture and implementation details
UI Mockups - Available as PDF in the docs directory

Chrome Extension
The extension/ directory contains the Chrome Extension code for different DealerTrack integration scenarios:

form-filler.js - Fills out credit applications in DealerTrack
data-scraper.js - Captures data from DealerTrack forms and updates
document-handler.js - Manages document uploads and downloads

Contributing
Contributions are welcome! Please open an issue or submit a pull request for any enhancements or bug fixes.
When working on the Chrome Extension:

Always test changes in the development environment first
Update field mappings when DealerTrack form changes
Add comprehensive error handling
Document any UI changes observed in DealerTrack forms
Follow Chrome Extension best practices for security and performance

License
This project is licensed under the MIT License. See the LICENSE file for details.
