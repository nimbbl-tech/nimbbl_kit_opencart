<?php
declare(strict_types=1);

/**
 * OpenCart Nimbbl Plugin - PHP 8+ Compatibility Checker
 * 
 * This script checks if your server environment is compatible with the PHP 8+ upgrade
 * and provides recommendations for the upgrade process.
 */

class NimbblPhp8CompatibilityChecker
{
    private array $requirements = [
        'php_version' => '8.0.0',
        'extensions' => [
            'curl',
            'json',
            'openssl',
            'mbstring'
        ],
        'functions' => [
            'hash_equals',
            'str_ends_with',
            'array_key_exists'
        ]
    ];

    private array $results = [];

    public function run(): void
    {
        echo "=== OpenCart Nimbbl Plugin - PHP 8+ Compatibility Check ===\n\n";
        
        $this->checkPhpVersion();
        $this->checkExtensions();
        $this->checkFunctions();
        $this->checkOpenCartCompatibility();
        $this->displayResults();
        $this->displayRecommendations();
    }

    private function checkPhpVersion(): void
    {
        $currentVersion = PHP_VERSION;
        $requiredVersion = $this->requirements['php_version'];
        
        $this->results['php_version'] = [
            'current' => $currentVersion,
            'required' => $requiredVersion,
            'status' => version_compare($currentVersion, $requiredVersion, '>='),
            'message' => "PHP Version: {$currentVersion} (Required: {$requiredVersion})"
        ];
    }

    private function checkExtensions(): void
    {
        $this->results['extensions'] = [];
        
        foreach ($this->requirements['extensions'] as $extension) {
            $loaded = extension_loaded($extension);
            $this->results['extensions'][$extension] = [
                'loaded' => $loaded,
                'message' => "Extension '{$extension}': " . ($loaded ? 'Loaded' : 'Not Loaded')
            ];
        }
    }

    private function checkFunctions(): void
    {
        $this->results['functions'] = [];
        
        foreach ($this->requirements['functions'] as $function) {
            $available = function_exists($function);
            $this->results['functions'][$function] = [
                'available' => $available,
                'message' => "Function '{$function}': " . ($available ? 'Available' : 'Not Available')
            ];
        }
    }

    private function checkOpenCartCompatibility(): void
    {
        // Check if we're in an OpenCart environment
        $opencartDetected = defined('DIR_APPLICATION') || defined('DIR_SYSTEM');
        
        $this->results['opencart'] = [
            'detected' => $opencartDetected,
            'message' => "OpenCart Environment: " . ($opencartDetected ? 'Detected' : 'Not Detected')
        ];
    }

    private function displayResults(): void
    {
        echo "=== COMPATIBILITY CHECK RESULTS ===\n\n";
        
        // PHP Version
        $phpResult = $this->results['php_version'];
        echo $phpResult['message'] . " - " . ($phpResult['status'] ? "✓ PASS" : "✗ FAIL") . "\n";
        
        // Extensions
        echo "\nExtensions:\n";
        foreach ($this->results['extensions'] as $extension => $result) {
            echo "  " . $result['message'] . " - " . ($result['loaded'] ? "✓ PASS" : "✗ FAIL") . "\n";
        }
        
        // Functions
        echo "\nFunctions:\n";
        foreach ($this->results['functions'] as $function => $result) {
            echo "  " . $result['message'] . " - " . ($result['available'] ? "✓ PASS" : "✗ FAIL") . "\n";
        }
        
        // OpenCart
        $opencartResult = $this->results['opencart'];
        echo "\n" . $opencartResult['message'] . " - " . ($opencartResult['detected'] ? "✓ PASS" : "⚠ WARNING") . "\n";
    }

    private function displayRecommendations(): void
    {
        echo "\n=== RECOMMENDATIONS ===\n\n";
        
        $allPassed = true;
        
        // Check PHP version
        if (!$this->results['php_version']['status']) {
            echo "❌ PHP Version Issue:\n";
            echo "   Your PHP version ({$this->results['php_version']['current']}) is below the required version ({$this->results['php_version']['required']}).\n";
            echo "   Please upgrade your PHP version to 8.0 or higher.\n\n";
            $allPassed = false;
        }
        
        // Check extensions
        $failedExtensions = [];
        foreach ($this->results['extensions'] as $extension => $result) {
            if (!$result['loaded']) {
                $failedExtensions[] = $extension;
            }
        }
        
        if (!empty($failedExtensions)) {
            echo "❌ Missing Extensions:\n";
            echo "   The following PHP extensions are required but not loaded:\n";
            foreach ($failedExtensions as $extension) {
                echo "   - {$extension}\n";
            }
            echo "   Please install and enable these extensions.\n\n";
            $allPassed = false;
        }
        
        // Check functions
        $failedFunctions = [];
        foreach ($this->results['functions'] as $function => $result) {
            if (!$result['available']) {
                $failedFunctions[] = $function;
            }
        }
        
        if (!empty($failedFunctions)) {
            echo "❌ Missing Functions:\n";
            echo "   The following PHP functions are required but not available:\n";
            foreach ($failedFunctions as $function) {
                echo "   - {$function}\n";
            }
            echo "   These functions should be available in PHP 8.0+.\n\n";
            $allPassed = false;
        }
        
        if ($allPassed) {
            echo "✅ Your server environment is compatible with the PHP 8+ upgrade!\n\n";
            echo "Next steps:\n";
            echo "1. Backup your current OpenCart installation\n";
            echo "2. Upload the upgraded plugin files\n";
            echo "3. Clear all caches (OpenCart cache, PHP OPcache)\n";
            echo "4. Test the payment functionality\n";
            echo "5. Monitor the logs for any issues\n\n";
        } else {
            echo "❌ Your server environment needs updates before upgrading to PHP 8+.\n";
            echo "Please address the issues above before proceeding with the upgrade.\n\n";
        }
        
        echo "=== UPGRADE NOTES ===\n\n";
        echo "• The upgraded plugin uses strict type checking (declare(strict_types=1))\n";
        echo "• All methods now have explicit type hints and return types\n";
        echo "• Array syntax has been modernized ([] instead of array())\n";
        echo "• Null coalescing operator (??) is used throughout\n";
        echo "• Error handling has been improved\n";
        echo "• Performance and security have been enhanced\n\n";
        
        echo "For detailed information, see: PHP8_UPGRADE_README.md\n";
    }
}

// Run the compatibility check
if (php_sapi_name() === 'cli') {
    $checker = new NimbblPhp8CompatibilityChecker();
    $checker->run();
} else {
    echo "This script should be run from the command line.\n";
    echo "Usage: php upgrade_to_php8.php\n";
} 