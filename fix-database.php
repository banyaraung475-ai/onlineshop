<?php
require_once __DIR__ . '/includes/config.php';

// Create database connection with error handling
try {
    $db = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $db->exec("USE " . DB_NAME);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

function executeSqlScript(PDO $db, string $filePath): array {
    $content = @file_get_contents($filePath);
    if ($content === false) {
        return ["Unable to read SQL file: {$filePath}"];
    }

    $errors = [];
    $delimiter = ';';
    $statement = '';
    $lines = preg_split('/\R/', $content);

    foreach ($lines as $line) {
        $trimmed = trim($line);

        if ($trimmed === '' || strpos($trimmed, '--') === 0 || strpos($trimmed, '#') === 0) {
            continue;
        }

        if (stripos($trimmed, 'DELIMITER ') === 0) {
            $delimiter = trim(substr($trimmed, 10));
            if ($delimiter === '') {
                $delimiter = ';';
            }
            continue;
        }

        $statement .= $line . "\n";
        $statementTrimmed = rtrim($statement);

        if (substr($statementTrimmed, -strlen($delimiter)) === $delimiter) {
            $sql = trim(substr($statementTrimmed, 0, -strlen($delimiter)));
            $statement = '';

            if ($sql === '') {
                continue;
            }

            try {
                $db->exec($sql);
            } catch (PDOException $e) {
                $errors[] = $e->getMessage() . " | SQL: " . substr($sql, 0, 200);
            }
        }
    }

    return $errors;
}

$schemaPath = __DIR__ . '/database.sql';
$errors = executeSqlScript($db, $schemaPath);
$success = empty($errors);

echo "<!DOCTYPE html>
<html lang=\"en\">
<head>
    <meta charset=\"UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <title>Database Fix - OnlineShop</title>
    <link href=\"https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css\" rel=\"stylesheet\">
    <style>
        .status-card { border-left: 4px solid #007bff; }
        .status-success { border-left-color: #28a745; }
    </style>
</head>
<body class=\"bg-light\">
    <div class=\"container py-5\">
        <div class=\"row justify-content-center\">
            <div class=\"col-md-8\">
                <div class=\"card shadow\">
                    <div class=\"card-header bg-primary text-white\">
                        <h4 class=\"mb-0\"><i class=\"fas fa-database me-2\"></i>Database Fix Tool</h4>
                    </div>
                    <div class=\"card-body\">
                        <h5>" . ($success ? "Database Fix Completed" : "Database Fix Completed With Errors") . "</h5>
                        <p>" . ($success ? "The database schema was applied successfully." : "The schema was applied with one or more errors. See details below.") . "</p>";

if (!$success) {
    echo "<div class=\"alert alert-warning\"><strong>Errors:</strong><ul class=\"mb-0\">";
    foreach ($errors as $error) {
        echo "<li>" . htmlspecialchars($error, ENT_QUOTES, 'UTF-8') . "</li>";
    }
    echo "</ul></div>";
}

echo "
                        
                        <div class=\"text-center mt-4\">
                            <a href=\"final-verification.php\" class=\"btn btn-primary btn-lg me-2\">
                                <i class=\"fas fa-check me-2\"></i>Verify System
                            </a>
                            <a href=\"index.php\" class=\"btn btn-success btn-lg\">
                                <i class=\"fas fa-home me-2\"></i>Visit Website
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src=\"https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js\"></script>
</body>
</html>";
