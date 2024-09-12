# Smartphone Scraper

A PHP-based web scraper for extracting smartphone product data from the MagpieHQ website. This project uses the Symfony DomCrawler for HTML parsing and the Guzzle HTTP client for making network requests. The extracted data is saved in a JSON format.

## Prerequisites

Ensure you have the following installed:

- **PHP**: Version 7.4 or higher
- **Composer**: Dependency manager for PHP. [Install Composer](https://getcomposer.org).

## Setup

1. **Clone the Repository**

   git clone https://github.com/pankaj1-dev/smartphone-scraper.git
   cd smartphone-scraper

2. **Install Dependencies**

Make sure you have Composer installed. If not, you can download it from getcomposer.org.
Install the required PHP packages by running:

   composer install

3. **Configure PHP Environment**

Ensure your PHP environment allows a script execution time long enough to scrape multiple pages. The script sets a time limit of 300 seconds for execution.

    **Running the Scraper**

    1. Run the Scraper

   After installing the dependencies, you can run the scraper by executing:
   
      php scraper.php 

    2. Check Output

   Once the scraper has completed its run, you can find the extracted data in output.json. This file will be created in the root directory of the project.

     3. File Structure  

     scraper.php: The main PHP file containing the Scrape class and the scraping logic.
     output.json: The file where the scraped data is saved.
