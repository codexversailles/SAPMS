<?php
// Set content type to plain text for better readability
header('Content-Type: text/plain');

// Include database connection
require_once 'db_connect.php';

// Allow execution with a confirmation parameter for safety
$confirm = isset($_GET['confirm']) && $_GET['confirm'] === 'yes';

echo "==== CLASS DATA REPAIR TOOL ====\n\n";

if (!$confirm) {
    echo "This tool will check for and fix missing data in the classes table.\n";
    echo "To execute repairs, add ?confirm=yes to the URL.\n\n";
    echo "Running in diagnostic mode (no changes will be made)...\n\n";
}

try {
    // Check for classes with missing data
    $query = "SELECT c.*, t.full_name AS teacher_name
              FROM classes c
              JOIN teachers t ON c.teacher_id = t.id";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $issues = 0;
    $fixed = 0;
    
    echo "Found " . count($classes) . " classes in total.\n\n";
    
    // Loop through classes and check for issues
    foreach ($classes as $class) {
        $classId = $class['id'];
        $className = $class['class_name'];
        $hasIssues = false;
        $updates = [];
        
        echo "Checking class ID {$classId}: {$className}\n";
        
        // Check class_code
        if (empty($class['class_code'])) {
            $hasIssues = true;
            $issues++;
            echo "  - Missing class_code\n";
            
            // Generate a random 6-character code
            $newCode = strtoupper(substr(md5(uniqid()), 0, 6));
            $updates['class_code'] = $newCode;
            
            echo "    → Generated new code: {$newCode}\n";
        }
        
        // Check created_at
        if (empty($class['created_at'])) {
            $hasIssues = true;
            $issues++;
            echo "  - Missing created_at\n";
            
            // Set to current time
            $updates['created_at'] = date('Y-m-d H:i:s');
            
            echo "    → Set to current time: {$updates['created_at']}\n";
        }
        
        // Check updated_at
        if (empty($class['updated_at'])) {
            $hasIssues = true;
            $issues++;
            echo "  - Missing updated_at\n";
            
            // Set to current time
            $updates['updated_at'] = date('Y-m-d H:i:s');
            
            echo "    → Set to current time: {$updates['updated_at']}\n";
        }
        
        // Check is_active
        if (!isset($class['is_active'])) {
            $hasIssues = true;
            $issues++;
            echo "  - Missing is_active\n";
            
            // Set to active by default
            $updates['is_active'] = 1;
            
            echo "    → Set to active (1)\n";
        }
        
        // Apply fixes if confirmed
        if ($hasIssues && $confirm && !empty($updates)) {
            $updateParts = [];
            $updateValues = [];
            
            foreach ($updates as $field => $value) {
                $updateParts[] = "{$field} = ?";
                $updateValues[] = $value;
            }
            
            // Add class ID to update values
            $updateValues[] = $classId;
            
            $updateQuery = "UPDATE classes SET " . implode(', ', $updateParts) . " WHERE id = ?";
            $updateStmt = $pdo->prepare($updateQuery);
            
            if ($updateStmt->execute($updateValues)) {
                echo "  ✓ Fixed successfully!\n";
                $fixed++;
            } else {
                echo "  ✗ Failed to fix\n";
            }
        } elseif (!$hasIssues) {
            echo "  ✓ No issues found\n";
        }
        
        echo "\n";
    }
    
    // Summary
    echo "==== SUMMARY ====\n";
    echo "Total classes checked: " . count($classes) . "\n";
    echo "Classes with issues: {$issues}\n";
    
    if ($confirm) {
        echo "Classes fixed: {$fixed}\n";
    } else {
        echo "Run with ?confirm=yes to apply fixes.\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " on line " . $e->getLine() . "\n";
}
?> 