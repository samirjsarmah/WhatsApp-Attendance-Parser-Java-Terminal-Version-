<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);

$db_host = 'localhost';
$db_name = 'student_db';
$db_username = 'root';
$db_password = '';

$filter_type = $_GET['filter'] ?? '';//filter query SELECT
$rollno_value = $_GET['rollno'] ?? '';
$semester_value = $_GET['semester'] ?? '';
$result_value = $_GET['result'] ?? '';
$students = [];
$error = '';
$show_results = false;

//filter criteria SELECT
$filter_value = '';
if ($filter_type === 'rollno') {
    $filter_value = $rollno_value;
} elseif ($filter_type === 'semester') {
    $filter_value = $semester_value;
} elseif ($filter_type === 'result') {
    $filter_value = $result_value;
} elseif (isset($_GET['show_all'])) {
    $show_results = true;
}

if ((isset($_GET['filter']) && $filter_value !== '') || isset($_GET['show_all'])) {
    $show_results = true;
} elseif (isset($_GET['query'])) {
    $error = "Please select a filter type and provide a corresponding value.";
}

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_username, $db_password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    if ($show_results) {
        $sql = "SELECT rollno, name, semester, result FROM students";
        $params = [];

        if (!isset($_GET['show_all'])) {
            switch ($filter_type) {
                case 'rollno':
                    $sql .= " WHERE rollno = ?"; //fetching Roll no
                    $params[] = (int)$filter_value;
                    break;
                case 'semester':
                    $sql .= " WHERE semester = ?"; //fetching Semester
                    $params[] = (int)$filter_value;
                    break;
                case 'result':
                    $sql .= " WHERE result = ?"; //fetching Result
                    $params[] = ucfirst(strtolower($filter_value));
                    break;
            }
        }

        $sql .= " ORDER BY rollno";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $students = $stmt->fetchAll();
    }

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// ------------------ PDF HANDLER ------------------
require_once 'lib/fpdf/fpdf.php'; //fething from lib for fdpf libray PDF Generation

if (isset($_GET['download_pdf']) && $show_results && !empty($students)) {

    class PDF extends FPDF {
        public $filterText = 'Report includes all records';

        function Header() {
            $this->SetFont('Arial', 'B', 14);
            $this->Cell(0, 10, 'STUDENT RECORDS REPORT', 0, 1, 'C');
            $this->Ln(3);
            $this->SetFont('Arial', '', 11);
            $this->Cell(0, 8, $this->filterText, 0, 1, 'C');
            $this->Ln(5);
        }

        function Footer() {
            $this->SetY(-15);
            $this->SetFont('Arial', 'I', 8);
            $this->Cell(0, 10, 'Page ' . $this->PageNo(), 0, 0, 'C');
        }
    }

    $filterText = 'Report includes all student records';
    if (!empty($filter_type) && !empty($filter_value)) {
        switch ($filter_type) {
            case 'rollno':
                $filterText = "Report based on Roll Number $filter_value";
                break;
            case 'semester':
                $filterText = "Report based on $filter_value Semester";
                break;
            case 'result':
                $filterText = "Report based on $filter_value Result";
                break;
        }
    }

    $pdf = new PDF();
    $pdf->filterText = $filterText;
    $pdf->AddPage();
    $pdf->SetFont('Arial', '', 12);

    // Centering logic
    $tableWidth = 30 + 70 + 30 + 40; // = 170
    $marginX = ($pdf->GetPageWidth() - $tableWidth) / 2;

    // Table Header
    $pdf->SetX($marginX);
    $pdf->SetFillColor(200, 200, 200);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(30, 10, 'Roll No', 1, 0, 'C', true);
    $pdf->Cell(70, 10, 'Name', 1, 0, 'C', true);
    $pdf->Cell(30, 10, 'Semester', 1, 0, 'C', true);
    $pdf->Cell(40, 10, 'Result', 1, 1, 'C', true);

    // Table Body
    $pdf->SetFont('Arial', '', 12);
    foreach ($students as $student) {
        $pdf->SetX($marginX);
        $pdf->Cell(30, 10, $student['rollno'], 1, 0, 'C');
        $pdf->Cell(70, 10, $student['name'], 1, 0, 'C');
        $pdf->Cell(30, 10, $student['semester'], 1, 0, 'C');
        $pdf->Cell(40, 10, $student['result'], 1, 1, 'C');
    }

    $pdf->Output('D', 'Student_Report.pdf'); //download Location
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Records Query</title>
    <style>
        body {
  font-family: 'Segoe UI', sans-serif;
  max-width: 900px;
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
.navbar a:focus,
.navbar a:hover,
.navbar a:active {
  text-decoration: none !important;
} 

h2 {
  font-size: 28px;
  font-weight: 600;
  text-align: center;
  margin-bottom: 25px;
}

.error {
  color: red;
  margin-bottom: 15px;
  font-weight: bold;
  text-align: center;
}

.filter-section {
  background-color: white;
  padding: 20px;
  border-radius: 10px;
  margin-bottom: 30px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.filter-group {
  margin-bottom: 20px;
}

label {
  font-weight: 600;
  display: block;
  margin-bottom: 6px;
  color: #444;
}

input[type="text"],
select {
  padding: 10px;
  width: 100%;
  max-width: 300px;
  border: 1px solid #ccc;
  border-radius: 6px;
  font-size: 15px;
  outline-color: #4CAF50;
}

button {
  padding: 12px 20px;
  background-color: #4CAF50;
  border: none;
  color: white;
  border-radius: 6px;
  cursor: pointer;
  font-weight: bold;
  margin-top: 10px;
  transition: background 0.3s ease;
}

button:hover {
  background-color: #45a049;
}

a {
  font-weight: 500;
  text-decoration: none;
  margin-left: 12px;
  color: #007bff;
}

a:hover {
  text-decoration: underline;
}

table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 20px;
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

.no-records {
  margin: 20px 0;
  color: #666;
  font-style: italic;
  text-align: center;
}

form.pdf-download {
  margin-bottom: 20px;
}

form.pdf-download button {
  background-color: #2196F3;
  padding: 12px 20px;
  border: none;
  border-radius: 6px;
  color: white;
  cursor: pointer;
  font-weight: bold;
  transition: background 0.3s ease;
}

form.pdf-download button:hover {
  background-color: #1976D2;
}

    </style>
</head>
<body>
<div class="navbar">
    <a href="index.php">Entry</a>
    <a href="query.php" class="active">Query</a>
    <a href="#" onclick="exitPage()" style="float: right; background-color: #f44336;">Exit</a>
</div>

<h2>Student Records</h2>


<?php if (!empty($error)): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!------------------- Query options by Rollno , Semester , Result ---------------------->
<div class="filter-section">
    <form method="get" action="query.php">
        <div class="filter-group">
            <label><input type="radio" name="filter" value="rollno" <?= $filter_type === 'rollno' ? 'checked' : '' ?>> Filter by Roll No:</label>
            <input type="text" name="rollno" placeholder="Enter Roll No" value="<?= htmlspecialchars($rollno_value) ?>">
        </div>
        <div class="filter-group">
            <label><input type="radio" name="filter" value="semester" <?= $filter_type === 'semester' ? 'checked' : '' ?>> Filter by Semester:</label>
            <select name="semester">
                <option value="">Select Semester</option>
                <?php for ($i = 1; $i <= 8; $i++): ?>
                    <option value="<?= $i ?>" <?= ($semester_value == $i) ? 'selected' : '' ?>>Semester <?= $i ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="filter-group">
            <label><input type="radio" name="filter" value="result" <?= $filter_type === 'result' ? 'checked' : '' ?>> Filter by Result:</label>
            <select name="result">
                <option value="">Select Result</option>
                <option value="Pass" <?= ($result_value === 'Pass') ? 'selected' : '' ?>>Pass</option>
                <option value="Fail" <?= ($result_value === 'Fail') ? 'selected' : '' ?>>Fail</option>
        
            </select>
        </div>
        <button type="submit" name="query">Filter</button>
        <button type="submit" name="show_all">Show All</button>
        <a href="query.php" style="margin-left: 10px;">Reset Filters</a>
    </form>
</div>
<!-- ----------------------------------------------------------------------------- -->
<?php if ($show_results && !empty($students)): ?>
    <form method="get" action="query.php" class="pdf-download">
        <input type="hidden" name="filter" value="<?= htmlspecialchars($filter_type) ?>">
        <input type="hidden" name="rollno" value="<?= htmlspecialchars($rollno_value) ?>">
        <input type="hidden" name="semester" value="<?= htmlspecialchars($semester_value) ?>">
        <input type="hidden" name="result" value="<?= htmlspecialchars($result_value) ?>">
        <?php if (isset($_GET['show_all'])): ?>
            <input type="hidden" name="show_all" value="1">
        <?php endif; ?>
        <input type="hidden" name="download_pdf" value="1">
        <button type="submit">Download PDF Report</button>
    </form>
<!--------------------Diplay Datas of the table------------------------------------>
    <table>
        <thead>
        <tr>
            <th>Roll No</th>
            <th>Name</th>
            <th>Semester</th>
            <th>Result</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($students as $student): ?>
            <tr>
                <td><?= htmlspecialchars($student['rollno']) ?></td>
                <td><?= htmlspecialchars($student['name']) ?></td>
                <td><?= htmlspecialchars($student['semester']) ?></td>
                <td><?= htmlspecialchars($student['result']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <!-------------------- NOT Found data Empty Database-------------------------->
<?php elseif ($show_results): ?>
    <p class="no-records">No student records found matching your criteria.</p>
<?php endif; ?>
<!-- ------------------------------------------------------------------------------------- -->
<p><a href="index.php">Back to Entry Form</a></p>

<!-- -----------------------Exit Button  -------------------------->
<script>
      function exitPage() {
  if (confirm("Are you sure you want to exit?")) {
    alert("Please click the browser's ❌ button to close this tab.");
  }
}
</script>
</body>
</html>
