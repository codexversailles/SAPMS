<?php
/**
 * PHPMailer Setup Script
 * This script downloads PHPMailer from GitHub and sets it up in the project.
 */

// Directory where PHPMailer will be installed
$installDir = __DIR__ . '/PHPMailer';

// URL to the PHPMailer release to download
$phpmailerUrl = 'https://github.com/PHPMailer/PHPMailer/archive/refs/tags/v6.8.0.zip';
$zipFile = __DIR__ . '/phpmailer.zip';

echo "Setting up PHPMailer...\n";

// Create installation directory if it doesn't exist
if (!file_exists($installDir)) {
    echo "Creating directory: $installDir\n";
    mkdir($installDir, 0755, true);
}

// Download PHPMailer
echo "Downloading PHPMailer from $phpmailerUrl\n";
$zipContent = file_get_contents($phpmailerUrl);

if ($zipContent === false) {
    die("Failed to download PHPMailer. Please check your internet connection and try again.\n");
}

// Save zip file
file_put_contents($zipFile, $zipContent);
echo "Downloaded PHPMailer to $zipFile\n";

// Extract zip file
$zip = new ZipArchive;
if ($zip->open($zipFile) === true) {
    echo "Extracting PHPMailer...\n";
    $zip->extractTo(__DIR__);
    $zip->close();
    
    // Move files from extracted directory to our target directory
    $extractedDir = __DIR__ . '/PHPMailer-6.8.0';
    $srcDir = $extractedDir . '/src';
    
    // Create src directory
    if (!file_exists($installDir . '/src')) {
        mkdir($installDir . '/src', 0755, true);
    }
    
    // Copy src files
    echo "Copying PHPMailer files...\n";
    foreach (scandir($srcDir) as $file) {
        if ($file != '.' && $file != '..') {
            copy("$srcDir/$file", "$installDir/src/$file");
            echo "Copied $file\n";
        }
    }
    
    // Clean up
    echo "Cleaning up...\n";
    unlink($zipFile);
    
    function recursiveRemoveDir($dir) {
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            $path = "$dir/$file";
            is_dir($path) ? recursiveRemoveDir($path) : unlink($path);
        }
        return rmdir($dir);
    }
    
    recursiveRemoveDir($extractedDir);
    
    echo "PHPMailer has been successfully installed to $installDir\n";
} else {
    die("Failed to extract PHPMailer. The zip file may be corrupted.\n");
}

echo "Setup complete! PHPMailer is now installed and ready to use.\n";
echo "Remember to set your Gmail credentials in email_verification.php\n";
?> 