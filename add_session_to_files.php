<?php
// Directory to scan
$directory = __DIR__;

// Session include code to add
$session_code = <<<'EOD'
<?php
require_once 'includes/session.php';
require_once 'config/db_connect.php';
require_once 'includes/audit_log.php';

// Require login
requireLogin();

// Log page access
logActivity($pdo, "Accessed page", basename(__FILE__, '.php'));

EOD;

// Function to process each PHP file
function processPhpFile($file, $session_code) {
    // Skip certain files
    $skip_files = [
        'login.php',
        'logout.php',
        'includes/session.php',
        'includes/audit_log.php',
        'add_session_to_files.php'
    ];
    
    foreach ($skip_files as $skip) {
        if (strpos($file, $skip) !== false) {
            echo "Skipping $file\n";
            return;
        }
    }
    
    // Read file content
    $content = file_get_contents($file);
    
    // Check if file already has session include
    if (strpos($content, "require_once 'includes/session.php'") !== false) {
        echo "File $file already has session include\n";
        return;
    }
    
    // Find the first PHP opening tag
    $pos = strpos($content, '<?php');
    
    if ($pos !== false) {
        // Replace the opening PHP tag with our session code
        $new_content = substr_replace($content, $session_code, $pos, 5);
        
        // Write the modified content back to the file
        file_put_contents($file, $new_content);
        echo "Updated $file\n";
    } else {
        echo "No PHP opening tag found in $file\n";
    }
}

// Recursively scan directory for PHP files
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        processPhpFile($file->getPathname(), $session_code);
    }
}

echo "Done!\n";
?> 