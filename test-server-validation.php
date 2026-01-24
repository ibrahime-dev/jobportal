<?php
/**
 * Server-side Name Validation Test
 * Tests the PHP validation functions
 */

// Include the validation functions from api.php
function validateName($name, $field_name = 'Name') {
    // Trim whitespace
    $trimmed_name = trim($name);
    
    // Check if empty
    if (empty($trimmed_name)) {
        return [
            'valid' => false,
            'message' => $field_name . ' is required'
        ];
    }
    
    // Check for numbers (0-9)
    if (preg_match('/\d/', $trimmed_name)) {
        return [
            'valid' => false,
            'message' => $field_name . ' cannot contain numbers'
        ];
    }
    
    // Check for invalid special characters (allow only letters, spaces, hyphens, apostrophes)
    // Using Unicode-aware regex for international characters
    if (!preg_match('/^[\p{L}\s\-\']+$/u', $trimmed_name)) {
        return [
            'valid' => false,
            'message' => $field_name . ' can only contain letters, spaces, hyphens, and apostrophes'
        ];
    }
    
    // Check length (reasonable limits)
    if (mb_strlen($trimmed_name) > 50) {
        return [
            'valid' => false,
            'message' => $field_name . ' must be less than 50 characters'
        ];
    }
    
    // Check minimum length
    if (mb_strlen($trimmed_name) < 1) {
        return [
            'valid' => false,
            'message' => $field_name . ' must contain at least one character'
        ];
    }
    
    return [
        'valid' => true,
        'message' => ''
    ];
}

function normalizeName($name) {
    // Trim whitespace
    $normalized = trim($name);
    
    // Replace multiple spaces with single space
    $normalized = preg_replace('/\s+/', ' ', $normalized);
    
    // Capitalize first letter of each word (proper case)
    $normalized = mb_convert_case($normalized, MB_CASE_TITLE, 'UTF-8');
    
    return $normalized;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server-side Validation Test</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .test-case { margin: 10px 0; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        .valid { background-color: #d4edda; border-color: #c3e6cb; }
        .invalid { background-color: #f8d7da; border-color: #f5c6cb; }
        .test-input { font-weight: bold; }
        .result { margin-top: 5px; }
    </style>
</head>
<body>
    <h1>Server-side Name Validation Test Results</h1>
    
    <?php
    // Test cases
    $test_cases = [
        // Invalid cases (should fail)
        'John123' => 'Contains numbers',
        'Mary@Smith' => 'Contains @ symbol',
        'Bob$' => 'Contains $ symbol',
        'Jane#Doe' => 'Contains # symbol',
        '123John' => 'Starts with numbers',
        'Test!Name' => 'Contains exclamation mark',
        'User.Name' => 'Contains period',
        'Name+Plus' => 'Contains plus sign',
        '' => 'Empty string',
        '   ' => 'Only spaces',
        
        // Valid cases (should pass)
        'John' => 'Simple name',
        'Mary-Jane' => 'Hyphenated name',
        'O\'Connor' => 'Name with apostrophe',
        'José' => 'Name with accent',
        'Van Der Berg' => 'Multiple words',
        'Anne-Marie' => 'Hyphenated with accent',
        'D\'Angelo' => 'Apostrophe name',
        'Jean-Luc' => 'French hyphenated name',
        'María José' => 'Spanish compound name',
        'Al-Rahman' => 'Arabic-style name'
    ];
    
    echo "<h2>Test Results:</h2>";
    
    foreach ($test_cases as $test_name => $description) {
        $result = validateName($test_name, 'Test Name');
        $normalized = $result['valid'] ? normalizeName($test_name) : 'N/A';
        
        $css_class = $result['valid'] ? 'valid' : 'invalid';
        $status = $result['valid'] ? '✅ VALID' : '❌ INVALID';
        
        echo "<div class='test-case $css_class'>";
        echo "<div class='test-input'>Input: \"$test_name\" ($description)</div>";
        echo "<div class='result'>Status: $status</div>";
        if (!$result['valid']) {
            echo "<div class='result'>Error: {$result['message']}</div>";
        } else {
            echo "<div class='result'>Normalized: \"$normalized\"</div>";
        }
        echo "</div>";
    }
    ?>
    
    <h2>Summary</h2>
    <p>The server-side validation should:</p>
    <ul>
        <li>✅ Block names containing numbers (0-9)</li>
        <li>✅ Block names with special characters except spaces, hyphens, and apostrophes</li>
        <li>✅ Allow international characters (accented letters)</li>
        <li>✅ Allow hyphens and apostrophes</li>
        <li>✅ Normalize names (proper case, trim spaces)</li>
        <li>✅ Reject empty or whitespace-only names</li>
    </ul>
</body>
</html>