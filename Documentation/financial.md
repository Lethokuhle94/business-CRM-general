financial/
├── list.php              # Main financial dashboard
├── process_transaction.php # Handles transaction CRUD operations
├── process_account.php    # Handles account CRUD operations
├── (future) view.php      # Detailed transaction/account views
└── (future) reports.php   # Financial reporting

Table Details
financial_accounts

Stores all monetary accounts (bank, cash, etc.)

Linked to users and transactions

Tracks current balance automatically

transactions

Records all money movements (income/expense/transfer)

Requires associated account and optional category

Automatically updates account balances

transaction_categories

User-defined classification system

Type (income/expense) determines usage

Default categories provided system-wide

budgets

Spending targets by category and period

Enables financial planning features

Core Functionality
1. Accounts Management
Purpose: Track all money storage locations

Key Features:

Multiple account types supported

Real-time balance tracking

Transaction history per account

2. Transaction Recording
Workflow:

Select account

Choose transaction type

Enter amount and details

System updates balances automatically

Special Cases:

Transfers between accounts require special handling

Recurring transactions (future implementation)

3. Financial Overview
Dashboard Shows:

30-day income/expense summary

Account balances at a glance

Recent transaction activity

Net worth calculation

User Guide
For Business Owners
Setup:

Add all financial accounts first

Create custom categories matching your business

Daily Use:

Record transactions as they occur

Reconcile accounts monthly

Use reports for tax preparation

Best Practices:

Be consistent with categories

Record transactions promptly

Review dashboard weekly

For Developers
Extension Points:

process_transaction.php handles all transaction logic

Balance updates are transactional

API-ready structure for future mobile apps

Important Constraints:

All financial operations require logged-in user

Balances should never be updated directly

Audit trail maintained via transaction records