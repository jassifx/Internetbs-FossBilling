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

/path/to/fossbilling/modules/registrars/internetbs_registrar.php

ruby
Copy
Edit

If FossBilling supports separate module subfolders, you may create an `internetbs` folder within the registrars directory and place the module file there.

## Installation Instructions

1. **Download/Clone the Repository:**  
   Clone or download this repository to your local machine:
   git clone https://github.com/yourusername/internetbs-fossbilling-module.git

sql
Copy
Edit
2. **Copy the Module File:**  
Copy `internetbs_registrar.php` into the FossBilling registrar modules folder (e.g., `modules/registrars/`).

3. **Configure Module in FossBilling:**  
Log in to the FossBilling admin panel and navigate to the domain registrar modules configuration page. You should see an entry labeled **Internet.bs Registrar Module**.  
Enter your Internet.bs API credentials:
- **API Key:** Your Internet.bs API key (or test key if testing)
- **API Password:** Your Internet.bs API password
- **Test Mode:** Check the box if you want to use the test environment (uses `https://testapi.internet.bs/`)

4. **Save Settings:**  
Save your settings. FossBilling will now use these values for domain operations performed via the Internet.bs API.

## Usage

Once installed and configured, FossBilling will call the module functions to perform domain operations such as:

- **Domain Availability Check:**  
The module function `internetbs_CheckDomainAvailability()` checks if a domain is available by calling Internet.bs’s `/Domain/Check` endpoint.

- **Domain Registration:**  
When an order is placed, `internetbs_RegisterDomain()` maps order fields (e.g., firstname, lastname, email, etc.) to the Internet.bs required parameters and submits a registration request.

- **Domain Updates, Transfers, and More:**  
Additional wrapper functions (e.g., `internetbs_UpdateDomain()`, `internetbs_TransferInitiate()`) handle the remaining operations – all routed through the central API wrapper class.

- **Advanced Operations:**  
The module also covers nameserver (host) management, DNS record operations, URL/email forwarding, and account operations via dedicated functions.

## Code Structure

- **Configuration Function:**  
Defined in `internetbs_config()`, this function exposes the settings (API key, password, test mode) to FossBilling.

- **API Wrapper Class (`InternetBsApi`):**  
Encapsulates all API calls using a generic `apiCall()` method based on cURL. It supports both GET and POST and handles JSON and plain text responses.

- **FossBilling Wrapper Functions:**  
Each operation (check, register, update, transfer, host management, DNS, etc.) is wrapped in a function prefixed with `internetbs_`. These functions instantiate the API class with the configuration parameters and call the corresponding method.

## Customization & Extensibility

- **Parameter Mapping:**  
Adjust the mapping of FossBilling fields to Internet.bs contact fields in the functions (e.g., registration and update functions) if your installation uses custom field names.

- **Additional Operations:**  
If you require further functionality (or new API endpoints added by Internet.bs), add new methods to the `InternetBsApi` class and create corresponding FossBilling wrapper functions.

- **Error Handling & Logging:**  
The module uses exceptions and returns error messages for failures. Customize error handling/logging as needed to integrate with FossBilling’s logging system.

## Contributing

Contributions are welcome! If you find any bugs or would like to add more features, please submit a pull request or open an issue.

1. Fork this repository.
2. Create a new branch for your changes.
3. Commit your changes with a detailed message.
4. Push your branch and create a pull request.

## License

This project is licensed under the GNU General Public License v3.0.

## Support

If you have issues or need help with this module, please open an issue on GitHub or contact the maintainers.

---

Happy coding and enjoy integrating Internet.bs with FossBilling!
