# InternetBS Registrar Module for FOSSBilling

**InternetBS Domain Registrar Adapter for FOSSBilling**

---

## Overview

This module integrates FOSSBilling with the Internet.bs domain registrar API, enabling domain registration, renewal, transfer, DNS management, URL/email forwarding, and more directly within FOSSBilling.

- **Compatibility:** PHP 8.3+
- **API Version:** InternetBS API (RESTful JSON API)
- **License:** Open-source (GPL3.0 License)

## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Features](#features)
- [Code Structure](#code-structure)
- [Security Considerations](#security-considerations)
- [Forking and Contribution](#forking-and-contribution)

---

## Requirements

- FOSSBilling (latest stable)
- PHP 8.3+
- Composer Dependencies:
  - `symfony/http-client`

---

## Installation

1. **Clone the repository** into your FOSSBilling modules directory:

```bash
git clone https://github.com/your-repo/internetbs-fossbilling.git
```

2. **Composer Dependencies:**

Ensure `symfony/http-client` is installed. If not, run:

```bash
composer require symfony/http-client
```

3. **Module Activation:**

- Log into your FOSSBilling admin panel.
- Go to `Extensions > Domain Registrars`
- Enable `InternetBS Registrar`.

---

## Configuration

In FOSSBilling admin panel (`Domain Registrars > InternetBS Registrar`), set the following fields:

- **API Key:** Your InternetBS API key.
- **API Password:** Your InternetBS API password.
- **Test Mode:** Enable for API testing without real-world domain registrations (uses InternetBS test endpoint).

---

## Features

- **Domain Operations:**
  - Domain Availability Checks
  - Domain Registrations
  - Domain Renewals
  - Domain Transfers
  - Trade Operations (.EU, .FR, etc.)
  - Registrar Lock Management
  - Private WHOIS Management

- **DNS Management:**
  - Add, Update, Remove DNS records (A, AAAA, CNAME, MX, SRV, TXT, NS)

- **Email and URL Forwarding:**
  - Create, update, remove forwarding rules

- **Host Management:**
  - Create, update, delete nameservers (child hosts)

- **Account Management:**
  - Retrieve account balance and prices
  - Default currency management

---

## Code Structure

### Main Adapter File

```php
src/library/Registrar/Adapter/InternetBs.php
```

### Key Methods

- `checkAvailability()`
- `registerDomain()`
- `renewDomain()`
- `transferDomain()`
- `enableRegistrarLock()`
- `disableRegistrarLock()`
- `enablePrivateWhois()`
- `disablePrivateWhois()`
- `dnsRecordAdd()`, `dnsRecordRemove()`, `dnsRecordUpdate()`
- `urlForwardAdd()`, `emailForwardAdd()`

### HTTP Client Usage

- Secure HTTPS communications with TLS verification
- Sensitive credentials passed via POST to avoid query logging exposure

```php
HttpClient::create([
    'base_uri' => $this->endpoint,
    'timeout' => 30,
    'verify_peer' => true,
    'verify_host' => true,
]);
```

---

## Security Considerations

- **HTTPS Communication:** API communication always over HTTPS.
- **Sensitive Data Protection:**
  - Credentials stored securely within FOSSBilling.
  - Never exposed in logs or URLs (credentials only passed in HTTP POST requests).
- **Parameter Whitelisting:**
  - All user inputs and API calls explicitly validated and sanitized.
- **Exception Handling:**
  - Sanitized exceptions thrown, no sensitive data leakage.
  - Custom error handling for predictable responses.
- **Rate Limiting:**
  - Built-in safeguards recommended to prevent API abuse.

---

## Forking and Contribution

Feel free to fork this repository and customize or extend functionality. Please follow best practices:

- **Clearly document changes**.
- **Ensure backward compatibility** where possible.
- **Adhere to existing security standards and coding patterns**.
- **Submit a pull request** for major changes or improvements.

### Pull Requests

- Clearly describe your contribution.
- Include unit tests (recommended).
- Adhere to PHP PSR standards.

### Reporting Issues

- Clearly describe the issue, including reproducible steps.
- Include expected and actual results.
- Specify the environment (PHP/FOSSBilling version).

---

## License

GPL License. Free to use, modify, distribute, and extend.

---

This README is designed to be thorough, eliminating any need for direct support. If you encounter issues, refer to the detailed information provided above to debug and resolve independently.
