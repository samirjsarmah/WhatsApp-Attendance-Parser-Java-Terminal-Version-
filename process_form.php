<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$db_host = 'localhost';
$db_name = 'student_db';
$db_username = 'root';
$db_password = '';

try {
    // Create PDO connection
    $pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_username,
        $db_password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    $errors = [];
    $success = '';

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $allData = json_decode($_POST['allData'], true);

        if (!$allData || !is_array($allData)) {
            $errors[] = "Invalid or missing data.";
        } else {
            $insertStmt = $pdo->prepare(
                "INSERT INTO students (rollno, name, semester, result) VALUES (?, ?, ?, ?)"
            );
            $checkStmt = $pdo->prepare("SELECT rollno FROM students WHERE rollno = ?");

            foreach ($allData as $student) {
                $rollno = (int)($student['roll'] ?? 0);
                $name = strtoupper(trim($student['name'] ?? ''));
                $semester = (int)($student['sem'] ?? 0);
                $result = ucfirst(strtolower(trim($student['result'] ?? '')));

                $hasError = false;

                if ($rollno < 1000 || $rollno > 9999) {
                    $errors[] = "Invalid Roll No: $rollno";
                    $hasError = true;
                }

                if (empty($name) || preg_match('/[0-9]/', $name)) {
                    $errors[] = "Invalid Name for Roll No: $rollno";
                    $hasError = true;
                }

                if ($semester < 1 || $semester > 8) {
                    $errors[] = "Invalid Semester for Roll No: $rollno";
                    $hasError = true;
                }

                if (!in_array(strtolower($result), ['pass', 'fail'])) {
                    $errors[] = "Invalid Result for Roll No: $rollno";
                    $hasError = true;
                }

                // Check duplicate
                $checkStmt->execute([$rollno]);
                if ($checkStmt->rowCount() > 0) {
                    $errors[] = "Duplicate Roll No: $rollno";
                    $hasError = true;
                }

                // Only insert if no errors for this student
                if (!$hasError) {
                    $insertStmt->execute([$rollno, $name, $semester, $result]);
                }
            }

            if (empty($errors)) {
                $success = "Student records added successfully!";
            }
        }
    }
} catch (PDOException $e) {
    $errors[] = "Connection failed: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Form Submission Result</title>
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background-color: #f5f5f5;
      color: #333;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      margin: 0;
      padding: 20px;
    }

    h2 {
      font-size: 28px;
      font-weight: 600;
      text-align: center;
      margin-bottom: 30px;
    }

    .result-container {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 25px;
    }

    .error, .success {
      background-color: white;
      border-radius: 10px;
      padding: 20px 30px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
      text-align: center;
      max-width: 600px;
    }

    .error h3 {
      color: #e53935;
      margin-top: 0;
    }

    .error ul {
      margin: 10px 0 0 20px;
      color: #b71c1c;
      text-align: left;
    }

    .success {
      color: #2e7d32;
      font-weight: bold;
      font-size: 16px;
    }

    a {
      background-color: #4CAF50;
      color: white;
      padding: 12px 24px;
      text-decoration: none;
      border-radius: 6px;
      font-weight: 600;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
      transition: background 0.3s ease;
    }

    a:hover {
      background-color: #45a049;
    }
  </style>
</head>
<body>

  <h2>Form Submission Result</h2>

  <div class="result-container">
    <?php if (!empty($errors)): ?>
      <div class="error">
        <h3>Error(s):</h3>
        <ul>
          <?php foreach ($errors as $error): ?>
            <li><?= htmlspecialchars($error) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php elseif (!empty($success)): ?>
      <div class="success">
        <p><?= htmlspecialchars($success) ?></p>
      </div>
    <?php endif; ?>

    <a href="index.html">Back to form</a>
  </div>

</body>
</html>
