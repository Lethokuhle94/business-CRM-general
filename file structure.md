root/
├── assets/
│ ├── css/
│ │ └── style.css
│ ├── images/
│ │ ├── logos/
│ │ └── watermarks/
│ └── js/
│ ├── navigation.js
│ └── script.js
│
├── clients/
│ ├── add_notes.php
│ ├── create.php
│ ├── delete.php
│ ├── edit.php
│ ├── group.php
│ ├── list.php
│ ├── quick_create.php
│ └── view.php
│
├── database/
│ └── invoice_app.sql
│
├── documentation/
│
├── financial/
│ ├── delete_transaction.php
│ ├── get_categories.php
│ ├── list.php
│ ├── process_account.php
│ ├── process_category.php
│ ├── recycle_bin.php
│ └── transactions.php
│
├── invoices/
│ ├── create.php
│ ├── delete.php
│ ├── download.php
│ ├── list.php
│ ├── reports.php
│ └── view.php
│
├── lib/
│ └── tcpdf/
│ └── (tcpdf library files)
│
├── quotations/
│ ├── convert.php
│ ├── create.php
│ ├── list.php
│ ├── delete.php
│ ├── edit.php
│ ├── recycle_bin_quotations.php
│ ├── restore_quotation.php
│ └── view.php
│
├── recycle_bin/
│ └── recycle_bin.php
│
├── dashboard.php
├── filestructure.md
├── index.php
├── login.php
├── logout.php
├── README.md
├── register.php
└── settings.php


## Directory Descriptions

### Core Directories
- **assets/** - Contains all static assets
  - css/ - Stylesheets
  - images/ - Image resources
    - logos/ - Company/client logos
    - watermarks/ - Watermark images
  - js/ - JavaScript files
    - navigation.js - Navigation menu functionality
    - script.js - Main application scripts

### Feature Modules
- **clients/** - Customer management
  - CRUD operations (create, read, update, delete)
  - Group management and notes functionality

- **financial/** - Financial management
  - Transaction processing
  - Category management
  - Financial reports

- **invoices/** - Invoice management
  - Full invoice lifecycle (creation to download)
  - Reporting features

- **quotations/** - Quotation management
  - Quotation creation and conversion to invoices
  - Recycle bin functionality

### System Directories
- **database/** - Database schema and backups
  - invoice_app.sql - Database schema file

- **lib/** - Third-party libraries
  - tcpdf/ - PDF generation library

- **recycle_bin/** - Deleted items management
  - recycle_bin.php - Main recycle bin interface

### Root Files
- **dashboard.php** - Main application dashboard
- **index.php** - Landing page
- **auth/** - Authentication files
  - login.php
  - logout.php
  - register.php
- **settings.php** - Application settings
- **README.md** - Project documentation
- **filestructure.md** - This file structure documentation

This structure follows a modular organization with clear separation of concerns between different application features and system components.