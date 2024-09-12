<?php

namespace App;

require 'vendor/autoload.php';

use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp\Client;

class Scrape
{
    private array $products = [];
    private Client $client;

    public function __construct()
    {
        $this->client = new Client();
        set_time_limit(300);
    }

    public function run(): void
    {
        $baseUrl = 'https://www.magpiehq.com/developer-challenge/smartphones';
        $totalPages = $this->getTotalPages($baseUrl);

        if ($totalPages === null) {
            echo "Failed to determine the number of pages.\n";
            return;
        }

        $this->scrapePages($baseUrl, 1, $totalPages);

        file_put_contents('output.json', json_encode($this->products, JSON_PRETTY_PRINT));
    }

    private function getTotalPages(string $baseUrl): ?int
    {
        $document = $this->fetchDocument($baseUrl);
        
        if ($document === null) {
            return null;
        }

        $crawler = new Crawler($document);
        $pageLinks = $crawler->filter('#pages a');
        $pageNumbers = $pageLinks->extract(['href']);

        $maxPageNumber = 0;
        foreach ($pageNumbers as $link) {
            if (preg_match('/page=(\d+)$/', $link, $matches)) {
                $pageNumber = (int) $matches[1];
                if ($pageNumber > $maxPageNumber) {
                    $maxPageNumber = $pageNumber;
                }
            }
        }

        return $maxPageNumber > 0 ? $maxPageNumber : null;
    }

    private function scrapePages(string $baseUrl, int $startPage, int $endPage): void
    {
        for ($currentPage = $startPage; $currentPage <= $endPage; $currentPage++) {
            $pageUrl = $currentPage === 1 ? $baseUrl : $baseUrl . '?page=' . $currentPage;
            $document = $this->fetchDocument($pageUrl);
            
            if ($document === null) {
                echo "Failed to fetch data from page $currentPage\n";
                continue; // Skip to next page
            }

            $this->parseDocument($document);
            sleep(1);
        }
    }

    private function fetchDocument(string $url): ?string
    {
        try {
            $response = $this->client->request('GET', $url);
            return $response->getBody()->getContents();
        } catch (\Exception $e) {
            echo "Error fetching document from $url: " . $e->getMessage() . "\n";
            return null;
        }
    }

    private function parseDocument(string $html): void
    {
        $crawler = new Crawler($html);
        $productsOnPage = $crawler->filter('.product')->each(function (Crawler $node) {
            
            $shippingTexts = $node->filter('.text-sm');
            $shippingText = $shippingTexts->count() > 1 ? $shippingTexts->eq(1)->text() : '';
            $shippingDate = $this->parseShippingDate($shippingText);

            return [
                'title' => $node->filter('.product-name')->text() . ' ' . $node->filter('.product-capacity')->text(),
                'price' => $this->parsePrice($node->filter('.text-lg')->text()),
                'imageUrl' => $this->parseImageUrl($node->filter('img')->attr('src')),
                'capacityMB' => $this->parseCapacity($node->filter('.product-capacity')->text()),
                'colour' => $this->parseColour($node->filter('[data-colour]')->attr('data-colour')),
                'availabilityText' => str_replace('Availability: ', '', trim($node->filter('.text-sm')->first()->text())),
                'isAvailable' => $this->parseAvailability($node->filter('.text-sm')->first()->text()),
                'shippingText' => $this->parseShippingText($shippingText),
                'shippingDate' => $shippingDate
            ];
        });
        
        $this->products = array_merge($this->products, $productsOnPage);
    }

    private function parsePrice(string $text): float
    {
        return (float) preg_replace('/[^0-9.]/', '', $text);
    }

    private function parseImageUrl(string $src): string
    {
        return 'https://www.magpiehq.com' . str_replace('../images/', '/images/', $src);
    }

    private function parseCapacity(string $text): int
    {
        $capacity = preg_replace('/[^0-9]/', '', $text);
        return (int) ($capacity * 1000); // Convert GB to MB
    }

    private function parseColour(string $colour): string
    {
        return strtolower($colour);
    }

    private function parseAvailability(string $text): bool
    {
        return stripos($text, 'In Stock') !== false;
    }

    private function parseShippingText(string $text): string
    {
        return !empty(trim($text)) ? trim($text) : '';
    }

    private function parseShippingDate(string $text): ?string
    {
        $patterns = ['/(\d{1,2} \w+ \d{4})/','/(\d{1,2}(?:st|nd|rd|th) \w+ \d{4})/','/(\d{4}-\d{2}-\d{2})/'];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $date = $matches[1];
                
                $formats = ['j M Y', 'jS M Y', 'Y-m-d'];

                foreach ($formats as $format) {
                    $parsedDate = \DateTime::createFromFormat($format, $date);
                    if ($parsedDate !== false) {
                        return $parsedDate->format('Y-m-d');
                    }
                }
            }
        }

        // Return null if no date is found
        return null;
    }
}