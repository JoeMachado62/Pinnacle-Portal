## Development Guidelines

This project follows a detailed design specification. All development MUST adhere to:

- [Project Design Document](./docs/DESIGN.md)

Before implementing any feature, consult the relevant section in the Design Document.


# Pinnacle Auto Finance Dealer Portal

A comprehensive dealer portal that enables small independent dealerships to access broker financing solutions through Pinnacle Auto Finance. The portal integrates with DealerTrack APIs to facilitate the complete broker deal lifecycle, from dealer registration to deal completion.

## Project Overview

This project implements a multi-tier architecture:

```
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
├── docs/                       # Project documentation
│   ├── design.md               # Detailed design document
│   ├── architechture.md        # Technical architecture details
│   ├── DealerTrack API Modules overviews.pdf  # API documentation
│   └── Pinnacle Portal Design Mock Up screenshots.pdf  # UI mockups
├── package.json                # Root package.json
└── README.md                   # Project overview
```

## Technology Stack

- **Frontend**: React.js with TypeScript, Material UI, Redux
- **Backend**: Strapi CMS with custom plugins
- **Database**: PostgreSQL
- **Integration**: DealerTrack APIs for deal management
- **Authentication**: JWT-based with role-based access control
- **Deployment**: Cloud-based hosting with CI/CD pipeline

## Key Features

- **Dealer Management**: Registration, approval workflow, and profile management
- **Deal Submission**: Multi-step credit application forms with validation
- **Document Management**: Upload, signing, and tracking of deal documents
- **DealerTrack Integration**: Bidirectional API integration for deal processing
- **Communication System**: In-app messaging, email, and SMS notifications
- **Admin Interface**: Deal review, approval, and management tools
- **Analytics & Reporting**: Performance dashboards and custom reports

## Installation

1. Clone the repository:
   ```
   git clone <repository-url>
   ```
2. Navigate to the project directory:
   ```
   cd Pinnacle-Portal
   ```
3. Install dependencies:
   ```
   npm install
   ```

## Development

To start the development environment:
```
npm run dev
```

This will launch both the frontend and backend servers:
- Frontend: http://localhost:3000
- Backend: http://localhost:1337
- Admin: http://localhost:1337/admin

## Documentation

Comprehensive documentation is available in the `docs/` directory:

- [Design Document](./docs/design.md) - Detailed project design and user workflows
- [Architecture Document](./docs/architechture.md) - Technical architecture and implementation details
- DealerTrack API Documentation - Available as PDF in the docs directory
- UI Mockups - Available as PDF in the docs directory

## Contributing

Contributions are welcome! Please open an issue or submit a pull request for any enhancements or bug fixes.

## License

This project is licensed under the MIT License. See the LICENSE file for details.
