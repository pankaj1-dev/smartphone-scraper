<?php

require 'vendor/autoload.php';

use App\Scrape;

$scrape = new Scrape();
$scrape->run();

echo "Scraping completed. Check output.json for results.";
