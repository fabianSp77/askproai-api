<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverDimension;
use Facebook\WebDriver\WebDriverExpectedCondition;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class CaptureUIScreenshots extends Command
{
    protected $signature = 'ui:capture-screenshots {--all : Capture all pages} {--page=* : Specific pages to capture}';
    protected $description = 'Capture screenshots of UI pages for testing and documentation';

    private $driver;
    private $baseUrl;
    private $screenshotDir;

    public function handle()
    {
        $this->baseUrl = config('app.url', 'http://localhost');
        $this->screenshotDir = storage_path('app/screenshots/' . date('Y-m-d_H-i-s'));
        
        if (!file_exists($this->screenshotDir)) {
            mkdir($this->screenshotDir, 0755, true);
        }

        try {
            $this->info('Starting UI screenshot capture...');
            $this->setupWebDriver();
            $this->login();
            
            $pages = $this->option('all') ? $this->getAllPages() : $this->option('page');
            
            foreach ($pages as $page) {
                $this->capturePageScreenshots($page);
            }
            
            $this->info("Screenshots saved to: {$this->screenshotDir}");
            
        } catch (\Exception $e) {
            $this->error('Screenshot capture failed: ' . $e->getMessage());
            Log::error('Screenshot capture error', ['error' => $e->getMessage()]);
        } finally {
            if ($this->driver) {
                $this->driver->quit();
            }
        }
    }

    private function setupWebDriver()
    {
        $options = new ChromeOptions();
        $options->addArguments([
            '--headless',
            '--no-sandbox',
            '--disable-dev-shm-usage',
            '--disable-gpu',
            '--window-size=1920,1080'
        ]);

        $capabilities = DesiredCapabilities::chrome();
        $capabilities->setCapability(ChromeOptions::CAPABILITY, $options);

        $this->driver = RemoteWebDriver::create(
            'http://localhost:4444/wd/hub',
            $capabilities,
            30000,
            30000
        );
    }

    private function login()
    {
        $this->info('Logging in...');
        
        // Get first admin user
        $admin = User::where('email', 'admin@askproai.de')->first();
        if (!$admin) {
            $admin = User::first();
        }

        $this->driver->get($this->baseUrl . '/admin/login');
        sleep(2);

        // Fill login form
        $this->driver->findElement(WebDriverBy::name('email'))->sendKeys($admin->email);
        $this->driver->findElement(WebDriverBy::name('password'))->sendKeys('password'); // Adjust as needed
        $this->driver->findElement(WebDriverBy::cssSelector('button[type="submit"]'))->click();

        // Wait for dashboard
        $this->driver->wait(10)->until(
            WebDriverExpectedCondition::urlContains('/admin')
        );
        
        sleep(2);
        $this->info('Logged in successfully');
    }

    private function getAllPages()
    {
        return [
            'company-integration-portal',
            'dashboard',
            'branches',
            'phone-numbers',
            'appointments',
            'customers',
            'calls'
        ];
    }

    private function capturePageScreenshots($page)
    {
        $this->info("Capturing screenshots for: $page");
        
        $url = $this->baseUrl . '/admin/' . $page;
        $this->driver->get($url);
        sleep(3); // Wait for page load

        // Capture desktop view
        $this->driver->manage()->window()->setSize(new WebDriverDimension(1920, 1080));
        $this->takeScreenshot($page . '_desktop');

        // Capture tablet view
        $this->driver->manage()->window()->setSize(new WebDriverDimension(768, 1024));
        $this->takeScreenshot($page . '_tablet');

        // Capture mobile view
        $this->driver->manage()->window()->setSize(new WebDriverDimension(375, 812));
        $this->takeScreenshot($page . '_mobile');

        // Capture specific interactions for Company Integration Portal
        if ($page === 'company-integration-portal') {
            $this->capturePortalInteractions();
        }
    }

    private function capturePortalInteractions()
    {
        $this->info('Capturing Company Integration Portal interactions...');
        
        // Reset to desktop size
        $this->driver->manage()->window()->setSize(new WebDriverDimension(1920, 1080));
        
        try {
            // Capture dropdown menu
            $dropdownButton = $this->driver->findElement(WebDriverBy::cssSelector('.branch-dropdown-trigger'));
            if ($dropdownButton) {
                $dropdownButton->click();
                sleep(1);
                $this->takeScreenshot('portal_dropdown_open');
                // Close dropdown
                $this->driver->findElement(WebDriverBy::tagName('body'))->click();
            }
        } catch (\Exception $e) {
            $this->warn('Could not capture dropdown: ' . $e->getMessage());
        }

        try {
            // Capture event type modal
            $eventTypeButton = $this->driver->findElement(WebDriverBy::cssSelector('button[wire\\:click*="manageBranchEventTypes"]'));
            if ($eventTypeButton) {
                $this->driver->executeScript("arguments[0].scrollIntoView(true);", [$eventTypeButton]);
                sleep(1);
                $eventTypeButton->click();
                sleep(2);
                $this->takeScreenshot('portal_event_type_modal');
                // Close modal
                $this->driver->findElement(WebDriverBy::cssSelector('button[x-on\\:click="open = false"]'))->click();
            }
        } catch (\Exception $e) {
            $this->warn('Could not capture event type modal: ' . $e->getMessage());
        }

        try {
            // Test inline editing
            $inlineEditButton = $this->driver->findElement(WebDriverBy::cssSelector('.inline-edit-button'));
            if ($inlineEditButton) {
                $inlineEditButton->click();
                sleep(1);
                $this->takeScreenshot('portal_inline_edit_active');
            }
        } catch (\Exception $e) {
            $this->warn('Could not capture inline edit: ' . $e->getMessage());
        }
    }

    private function takeScreenshot($name)
    {
        $filename = $this->screenshotDir . '/' . $name . '.png';
        $this->driver->takeScreenshot($filename);
        $this->info("Screenshot saved: $name.png");
        
        // Also capture full page screenshot
        $fullPageFilename = $this->screenshotDir . '/' . $name . '_full.png';
        $this->captureFullPageScreenshot($fullPageFilename);
    }

    private function captureFullPageScreenshot($filename)
    {
        try {
            // Get total height
            $totalHeight = $this->driver->executeScript("return document.body.scrollHeight");
            $viewportHeight = $this->driver->executeScript("return window.innerHeight");
            
            // Scroll and capture
            $currentPosition = 0;
            $screenshots = [];
            
            while ($currentPosition < $totalHeight) {
                $this->driver->executeScript("window.scrollTo(0, $currentPosition)");
                sleep(1);
                $tempFile = tempnam(sys_get_temp_dir(), 'screenshot_part_');
                $this->driver->takeScreenshot($tempFile);
                $screenshots[] = $tempFile;
                $currentPosition += $viewportHeight;
            }
            
            // Combine screenshots (simplified - in production use image manipulation library)
            if (count($screenshots) > 0) {
                copy($screenshots[0], $filename);
                foreach ($screenshots as $screenshot) {
                    unlink($screenshot);
                }
            }
        } catch (\Exception $e) {
            $this->warn('Could not capture full page screenshot: ' . $e->getMessage());
        }
    }
}