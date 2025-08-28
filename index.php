<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

$errors = [];
$success = '';
$studentList = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawData = $_POST['allData'] ?? '';
    $studentList = json_decode($rawData, true);

    // Validate entire list
    if (!$studentList || !is_array($studentList) || count($studentList) === 0) {
        $errors[] = "No student data submitted.";
    } else {
        // Validate each student entry
        foreach ($studentList as $i => $student) {
            $rollno = (int)($student['roll'] ?? 0);
            $name = strtoupper(trim($student['name'] ?? ''));
            $semester = (int)($student['sem'] ?? 0);
            $result = strtolower(trim($student['result'] ?? ''));

            if ($rollno < 1000 || $rollno > 9999) {
                $errors[] = "Entry " . ($i + 1) . ": Invalid Roll No (must be 1000-9999).";
            }

            if (empty($name) || preg_match('/[0-9]/', $name)) {
                $errors[] = "Entry " . ($i + 1) . ": Invalid Name (no numbers allowed).";
            }

            if ($semester < 1 || $semester > 8) {
                $errors[] = "ENTRY " . ($i + 1) . ": Invalid Semester (must be 1-8).";
            }

            if (!in_array($result, ['pass', 'fail'])) {
                $errors[] = "ENTRY " . ($i + 1) . ": Result must be 'Pass' or 'Fail'.";
            }
        }

        // If no errors, insert into DB
        if (empty($errors)) {
            try {
                $pdo = new PDO("mysql:host=localhost;dbname=student_db;charset=utf8mb4", "root", "", [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]);

                $insertStmt = $pdo->prepare("INSERT INTO students (rollno, name, semester, result) VALUES (?, ?, ?, ?)");
                $checkStmt = $pdo->prepare("SELECT rollno FROM students WHERE rollno = ?");

                foreach ($studentList as $student) {
                    $rollno = (int)$student['roll'];
                    $name = strtoupper(trim($student['name']));
                    $semester = (int)$student['sem'];
                    $result = ucfirst(strtolower(trim($student['result'])));

                    // Check duplicate
                    $checkStmt->execute([$rollno]);
                    if ($checkStmt->rowCount() > 0) {
                        $errors[] = "Duplicate Roll No: $rollno";
                        continue;
                    }

                    $insertStmt->execute([$rollno, $name, $semester, $result]);
                }

                if (empty($errors)) {
                    $success = "Student records added successfully!";
                    // Clear the form data on success
                    $studentList = [];
                }
            } catch (PDOException $e) {
                $errors[] = "Database error: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Student Database System</title>
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      max-width: 1200px;
      margin: 0 auto;
      padding: 20px;
      background-color: #f5f5f5;
      color: #333;
    }

    .navbar {
      background-color: #2c2c2c;
      overflow: hidden;
      margin-bottom: 30px;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .navbar a {
      float: left;
      display: block;
      color: white;
      text-align: center;
      padding: 14px 24px;
      text-decoration: none;
      font-weight: 600;
      transition: background 0.3s ease;
    }

    .navbar a.active {
      background-color: #4CAF50;
    }

    .navbar a:hover {
      background-color: #3e3e3e;
    }

    h2 {
      margin-bottom: 20px;
      font-size: 28px;
      font-weight: 600;
      text-align: center;
    }

    .form-group {
      display: flex;
      gap: 20px;
      align-items: flex-end;
      margin-bottom: 30px;
      background: white;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
      overflow-x: auto;
    }

    .form-control {
      display: flex;
      flex-direction: column;
      flex: 1;
      min-width: 200px;
    }

    .form-control:last-child {
      flex: none;
    }

    label {
      font-weight: 600;
      margin-bottom: 4px;
    }

    .hint {
      font-size: 13px;
      color: #777;
      margin-bottom: 8px;
      white-space: nowrap;
    }

    input[type="text"],
    input[type="number"] {
      padding: 10px;
      border: 1px solid #ccc;
      border-radius: 6px;
      font-size: 15px;
      outline-color: #4CAF50;
    }

    button,
    input[type="submit"] {
      padding: 12px 20px;
      background-color: #4CAF50;
      border: none;
      color: white;
      border-radius: 6px;
      cursor: pointer;
      font-weight: bold;
      transition: background 0.3s ease;
    }

    button:hover,
    input[type="submit"]:hover {
      background-color: #45a049;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 30px;
      background-color: white;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
      border-radius: 8px;
      overflow: hidden;
    }

    th, td {
      border: 1px solid #ddd;
      padding: 12px;
      text-align: center;
      font-size: 14px;
    }

    th {
      background-color: #eee;
      font-weight: bold;
    }

    .action-btn {
      padding: 6px 12px;
      background-color: #f44336;
      border: none;
      color: white;
      border-radius: 4px;
      margin: 0 2px;
      cursor: pointer;
      font-size: 13px;
    }

    .action-btn.edit {
      background-color: #2196F3;
    }

    .action-btn:hover {
      opacity: 0.9;
    }

    marquee {
      margin-top: 30px;
      background-color: #fff3ca;
      color: #ff0303;
      padding: 12px;
      border: 1px solid #ffeeba;
      border-radius: 6px;
      font-weight: 600;
      box-shadow: 0 2px 6px rgba(0,0,0,0.05);
    }

    .error-box {
      background: #ffe6e6;
      border: 1px solid #ff4c4c;
      color: #a10000;
      padding: 15px 20px;
      border-radius: 8px;
      margin-bottom: 20px;
      font-weight: 600;
      max-width: 700px;
    }

    .success-box {
      background: #e6ffe6;
      border: 1px solid #4caf50;
      color: #2e7d32;
      padding: 15px 20px;
      border-radius: 8px;
      margin-bottom: 20px;
      font-weight: 600;
      max-width: 700px;
    }
  </style>
</head>
<body>

  <div class="navbar">
    <a href="index.php" class="active">Entry</a>
    <a href="query.php">Query</a>
    <a href="#" onclick="exitPage()" style="float: right; background-color: #f44336;">Exit</a>
  </div>

  <h2>Student Entry Form</h2>

  <?php if (!empty($errors)): ?>
    <div class="error-box">
      <ul>
        <?php foreach ($errors as $err): ?>
          <li><?= htmlspecialchars($err) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php elseif ($success): ?>
    <div class="success-box"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <!-- Input Fields -->
  <div class="form-group">
    <div class="form-control">
      <label for="rollno">Roll No</label>
      <span class="hint">1000–9999 only</span>
      <input type="number" id="rollno" />
    </div>
    <div class="form-control">
      <label for="name">Name</label>
      <span class="hint">Letters & spaces (no numbers)</span>
      <input type="text" id="name" />
    </div>
    <div class="form-control">
      <label for="semester">Semester</label>
      <span class="hint">1–8 only</span>
      <input type="number" id="semester" />
    </div>
    <div class="form-control">
      <label for="result">Result</label>
      <span class="hint">Pass or Fail only</span>
      <input type="text" id="result" />
    </div>
    <div class="form-control" style="width: auto; margin: 0;">
      <button type="button" onclick="addRow()">Add</button>
    </div>
  </div>

  <!-- Display Table -->
  <form action="index.php" method="post" onsubmit="return confirmSubmission()">
    <table id="studentTable">
      <thead>
        <tr>
          <th>Roll No.</th>
          <th>Name</th>
          <th>Semester</th>
          <th>Result</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php
          // On page load, populate the table with PHP data (previously submitted or empty)
          if (!empty($studentList)) {
              foreach ($studentList as $index => $stu) {
                  echo '<tr>';
                  echo '<td>' . htmlspecialchars($stu['roll']) . '</td>';
                  echo '<td>' . htmlspecialchars($stu['name']) . '</td>';
                  echo '<td>' . htmlspecialchars($stu['sem']) . '</td>';
                  echo '<td>' . htmlspecialchars($stu['result']) . '</td>';
                  echo '<td>
                    <button type="button" class="action-btn edit" onclick="editRow(' . $index . ')">Edit</button>
                    <button type="button" class="action-btn" onclick="deleteRow(' . $index . ')">Delete</button>
                  </td>';
                  echo '</tr>';
              }
          }
        ?>
      </tbody>
    </table>

    <br>
    <input type="submit" value="FINAL SUBMIT" />
    <input type="hidden" name="allData" id="allData" />
  </form>

  <marquee behavior="scroll" direction="left" style="background-color:#fff3ca; color:#ff0303; padding:12px; border:1px solid #ffeeba; border-radius:6px; font-weight:600; box-shadow: 0 2px 6px rgba(0,0,0,0.05); margin-top:30px;">
    Once the data is FINAL SUBMIT, it cannot be changed. Please double-check your entries. Format instructions are provided near each field.
  </marquee>

<script>
  // The studentList array holds all student objects.
  let studentList = <?php echo json_encode($studentList ?: []); ?>;

  const tableBody = document.querySelector('#studentTable tbody');

  function addRow() {
    // Grab values from input fields
    const roll = document.getElementById('rollno').value.trim();
    const name = document.getElementById('name').value.trim();
    const sem = document.getElementById('semester').value.trim();
    const result = document.getElementById('result').value.trim();

    // Add student to list (no validation here, PHP does the validation)
    studentList.push({ roll, name, sem, result });
    renderTable();
    clearInputs();
  }

  function renderTable() {
    tableBody.innerHTML = '';
    studentList.forEach((stu, index) => {
      const row = `<tr>
          <td>${stu.roll}</td>
          <td>${stu.name}</td>
          <td>${stu.sem}</td>
          <td>${stu.result}</td>
          <td>
            <button type="button" class="action-btn edit" onclick="editRow(${index})">Edit</button>
            <button type="button" class="action-btn" onclick="deleteRow(${index})">Delete</button>
          </td>
      </tr>`;
      tableBody.innerHTML += row;
    });
  }

  function clearInputs() {
    document.getElementById('rollno').value = '';
    document.getElementById('name').value = '';
    document.getElementById('semester').value = '';
    document.getElementById('result').value = '';
  }

  function deleteRow(index) {
    studentList.splice(index, 1);
    renderTable();
  }

  function editRow(index) {
    const stu = studentList[index];
    document.getElementById('rollno').value = stu.roll;
    document.getElementById('name').value = stu.name;
    document.getElementById('semester').value = stu.sem;
    document.getElementById('result').value = stu.result;
    deleteRow(index);
  }

  function confirmSubmission() {
    if (studentList.length === 0) {
      alert("No data to submit.");
      return false;
    }
    if (confirm("Are you sure you want to submit? Once submitted, data cannot be changed.")) {
      document.getElementById('allData').value = JSON.stringify(studentList);
      return true;
    }
    return false;
  }

  function exitPage() {
    if (confirm("Are you sure you want to exit?")) {
      alert("Please click the browser's ❌ button to close this tab.");
    }
  }
</script>

</body>
</html>
