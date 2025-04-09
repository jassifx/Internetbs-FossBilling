# Internet.bs Registrar Module for FossBilling

This module integrates the Internet.bs Domain Registrar API with FossBilling, enabling you to manage domain registrations directly from FossBilling. The plugin is written in PHP 8.3 and leverages the full set of Internet.bs API features – including domain availability checks, registrations, updates, transfers, nameserver management, DNS record handling, URL/email forwarding, and account operations.

> **Note:** This module is open source and available for the community. Contributions, issue reports, and feature requests are welcome!

## Features

- **Configurable API Credentials and Test Mode**  
  Administrators can set the Internet.bs API key, API password, and choose whether to use the test environment via the FossBilling web UI.

- **Domain Operations**  
  - Domain Availability Check
  - Domain Registration
  - Domain Update
  - Domain Info Retrieval
  - Registry Status Check
  - Domain Count
  - Domain Restoration
  - Domain Listing

- **Domain Transfer Management**  
  - Initiate Domain Transfer
  - Transfer Retry
  - Transfer Cancel
  - Resend Initial Authorization Email
  - Transfer History

- **Nameserver (Host) Management**  
  - Create, Update, Retrieve, and Delete nameservers

- **Domain Forwarding**  
  - URL Forward Add, Update, Remove, and List  
  - Email Forward Add, Update, Remove, and List

- **DNS Record Management**  
  - DNS Record Add, Update, Remove, and List  
  - Supports DNSSEC records (DS / DNSKEY) for enhanced domain security

- **Account-Related Operations**  
  - Check Account Balance
  - Get/Set Default Currency
  - Retrieve Price Lists
  - Get/Set Account Configuration

## Directory & File Placement in FossBilling

Place this module in the FossBilling registrar modules directory. For example, you can put the file as:

