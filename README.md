# InternetBS Registrar Adapter for FOSSBilling

**Enhanced Version - GPL-3.0 License**

---

## Overview

This enhanced module integrates InternetBS domain registrar services with FOSSBilling, providing robust domain registration, renewal, transfers, DNS management, and privacy protection capabilities.

This update improves upon the original adapter by addressing security, maintainability, performance, and API completeness, while preserving compatibility with existing implementations.

## Compatibility

- PHP 8.3+
- FOSSBilling
- Symfony HTTP Client

---

## Technical Improvements & Changes (Detailed Breakdown)

### 1. **Modern and Secure HTTP Client Implementation**

**Previously:** Disabled SSL verification (`verify_peer` and `verify_host` set to false), risking potential man-in-the-middle attacks.

**Enhanced:** Utilizes Symfony's HTTP Client with SSL/TLS verification (`verify_peer`, `verify_host` set to true), significantly increasing security.

### 2. **Explicit Configuration Management**

**Previously:** Supported only API key and password configuration.

**Enhanced:** Added "Test Mode" toggle in the admin configuration allowing safer API testing before production deployment.

### 3. **Secure API Request Handling**

**Previously:** API interactions used basic POST requests without structured validation.

**Enhanced:** All API interactions explicitly use secure POST requests with structured, comprehensive parameter validation and whitelisting.

### 4. **Advanced Exception Handling**

**Previously:** Minimal exception logging, risking unclear error states.

**Enhanced:** Implemented comprehensive and sanitized exceptions clearly communicating errors to administrators, without exposing sensitive information.

### 5. **Parameter Validation and Whitelisting**

**Previously:** Parameters were directly sent without explicit validation or sanitization.

**Enhanced:** Explicit validation and whitelisting ensure only validated parameters reach API calls, significantly reducing errors and security risks.

### 6. **Compatibility Preservation**

- Method signatures and names closely match the original adapter, ensuring seamless transitions.
- Functional parity maintained, preventing disruption to existing implementations.

### 7. **Expanded Functionality with Clearer Methodology**

Clearly defined methods for privacy protection and domain locking:
- `enablePrivacyProtection`
- `disablePrivacyProtection`
- `lock`
- `unlock`

### 8. **Code Maintainability and Standards Compliance**

- Adopted modern PHP (PSR-12) coding standards.
- Reorganized class and method structures for easier future maintenance and readability.

---

## Installation

1. **Clone the repository**

```bash
git clone https://github.com/your-repo/internetbs-fossbilling.git
```

2. **Install dependencies**

```bash
composer require symfony/http-client
```

3. **Activate the module in FOSSBilling:**

- Navigate to `Domain Registrars` within the admin panel.
- Enable `InternetBS Registrar`.

---

## Configuration

Set the following parameters from your FOSSBilling admin interface:

- **Internetbs API Key:** Your InternetBS API key.
- **Internetbs API Password:** Your InternetBS API password.
- **Test Mode:** Use the test API environment for safe pre-production testing.

---

## Functionality (Detailed Method Breakdown)

- `isDomainAvailable(Registrar_Domain $domain)`
  - Checks if a domain is available for registration.

- `registerDomain(Registrar_Domain $domain)`
  - Registers a new domain, sets nameservers, contact information, and handles specific TLD requirements.

- `renewDomain(Registrar_Domain $domain)`
  - Renews an existing domain for the specified registration period.

- `modifyNs(Registrar_Domain $domain)`
  - Modifies nameserver entries for existing domains.

- `enablePrivacyProtection(Registrar_Domain $domain)`
  - Activates WHOIS privacy protection for a domain.

- `disablePrivacyProtection(Registrar_Domain $domain)`
  - Deactivates WHOIS privacy protection.

- `lock(Registrar_Domain $domain)`
  - Enables registrar lock on a domain, preventing unauthorized transfers.

- `unlock(Registrar_Domain $domain)`
  - Disables registrar lock.

---

## Security Enhancements (Detailed)

- **TLS Verification:** Ensures secure, encrypted HTTPS connections to the InternetBS API.
- **Explicit POST requests:** Sensitive credentials and domain data transmitted securely.
- **Structured Error Management:** Errors are communicated clearly without risking sensitive data exposure.
- **Parameter Whitelisting:** Prevents injection attacks or misconfiguration by explicitly allowing only validated parameters.

---

## Contribution & Forking Guidelines

- Adhere strictly to PHP PSR-12 coding standards.
- Clearly document changes.
- Maintain backward compatibility wherever possible.
- Submit clear and descriptive pull requests.

---

## License

GNU General Public License v3.0 (GPL-3.0)

---

### Rationale & Benefits

This enhancement:
- Improves security drastically compared to the previous implementation.
- Ensures ease of maintenance and future enhancements.
- Clearly documents every change, ensuring that further contributors or users can manage, extend, or troubleshoot without additional support.

This detailed README is intentionally comprehensive to ensure users understand every technical detail clearly, reducing the need for additional support.
