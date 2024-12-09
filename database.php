<?php
// Configuration: Database connection details
$host = 'localhost';
$username = 'root';
$password = ''; // Default password for XAMPP
$database = 'library_management';

try {
    // Connect to the database
    $conn = new mysqli($host, $username, $password);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Create the database if it does not exist
    if (!$conn->query("CREATE DATABASE IF NOT EXISTS $database")) {
        throw new mysqli_sql_exception("Database creation failed: " . $conn->error);
    }

    // Use the created database
    if (!$conn->select_db($database)) {
        throw new mysqli_sql_exception("Database selection failed: " . $conn->error);
    }

    // Drop the existing books table if it exists
    if (!$conn->query("DROP TABLE IF EXISTS books")) {
        throw new mysqli_sql_exception("Dropping table failed: " . $conn->error);
    }

    // Create a "books" table if it does not exist
    $createTableQuery = "CREATE TABLE IF NOT EXISTS books (
        id INT AUTO_INCREMENT PRIMARY KEY,
        isbn VARCHAR(20),
        authors VARCHAR(255),
        original_publication_year INT,
        title VARCHAR(255),
        average_rating FLOAT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    if (!$conn->query($createTableQuery)) {
        throw new mysqli_sql_exception("Table creation failed: " . $conn->error);
    }

    $createTableQuery = "CREATE TABLE IF NOT EXISTS employees (
        employee_id INT PRIMARY KEY,
        name VARCHAR(100),
        position VARCHAR(100),
        department VARCHAR(100),
        salary DECIMAL(10, 2)
    )";

    if (!$conn->query($createTableQuery)) {
        throw new mysqli_sql_exception("Table creation failed: " . $conn->error);
    }


} catch (mysqli_sql_exception $e) {
    echo "Error: " . $e->getMessage();
    exit;
}


$csvFilePath = 'C:\xampp\htdocs\Library_Search\books.csv';

if (file_exists($csvFilePath)) {
    $csvFile = fopen($csvFilePath, 'r');
    fgetcsv($csvFile); 

    $rowCount = 0;
    while (($row = fgetcsv($csvFile)) !== FALSE && $rowCount < 1000) {
        $isbn = $row[5];
        $authors = $row[7];
        $original_publication_year = (int)$row[8];
        $title = $row[10];
        $average_rating = (float)$row[12];

        $stmt = $conn->prepare("INSERT INTO books (
            isbn, authors, original_publication_year, title, average_rating
        ) VALUES (?, ?, ?, ?, ?)");

        $stmt->bind_param("ssisd", 
            $isbn, $authors, $original_publication_year, $title, $average_rating
        );

        $stmt->execute();
        $rowCount++;
    }

    fclose($csvFile);
    // Commented out the message
    // echo "CSV data uploaded successfully!";
} else {
    echo "CSV file not found.";
}

// Handle search functionality
// Handle search functionality
$searchResults = [];
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['search'])) {
    $searchCategory = $_GET['category'];
    $searchQuery = $_GET['query'];

    $stmt = $conn->prepare("SELECT * FROM books WHERE $searchCategory LIKE ?");
    $searchTerm = "%" . $searchQuery . "%";
    $stmt->bind_param("s", $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    $searchResults = $result->fetch_all(MYSQLI_ASSOC);
}

// Fetch all books to display
$result = $conn->query("SELECT * FROM books ORDER BY created_at DESC");
$books = $result->fetch_all(MYSQLI_ASSOC);

// Automatically integrate CSV data for employees
$csvFilePath = 'C:\xampp\htdocs\Library_Search\employee.csv'; // Update this path to the location of your CSV file

if (file_exists($csvFilePath)) {
    $csvFile = fopen($csvFilePath, 'r');
    fgetcsv($csvFile); // Skip the header row

    while (($row = fgetcsv($csvFile)) !== FALSE) {
        // Ensure correct data types
        $employee_id = (int)$row[0];
        $name = $row[1];
        $position = $row[2];
        $department = $row[3];
        $salary = (float)$row[4];

        // Use INSERT IGNORE to avoid duplicate entry errors
        $stmt = $conn->prepare("INSERT IGNORE INTO employees (employee_id, name, position, department, salary) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isssd", $employee_id, $name, $position, $department, $salary);

        if (!$stmt->execute()) {
            echo "Error inserting row: " . $stmt->error . "<br>";
        }
    }

    fclose($csvFile);
    //echo "CSV data uploaded successfully!";
} else {
    echo "CSV file not found.";
}

// Fetch all employees to display
$result = $conn->query("SELECT * FROM employees ORDER BY employee_id ASC");
$employees = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Random Online Library Query</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #e0f7fa;
            display: flex;
            flex-direction: column;
            align-items: center;
            margin: 0;
            padding: 0;
        }
        h1 {
            background-color: #00796b;
            color: #ffffff;
            padding: 20px;
            width: 100%;
            text-align: center;
            margin: 0;
        }
        .button-container {
            margin: 20px 0;
        }
        button {
            background-color: #00796b;
            color: #ffffff;
            border: none;
            padding: 10px 20px;
            margin: 0 10px;
            cursor: pointer;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        button:hover {
            background-color: #004d40;
        }
        .table-container {
            display: none;
            overflow-x: auto;
            background-color: #ffffff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            width: 90%;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            border: 1px solid #b0bec5;
            text-align: left;
        }
        th {
            background-color: #00796b;
            color: #ffffff;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        form {
            margin-bottom: 20px;
        }
        label, select, input, button {
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <h1>Random Online Library Query</h1>

    <div class="button-container">
        <button onclick="showTable('booksTable')">Show Book Search</button>
        <button onclick="showTable('allBooksTable')">Show Book</button>
        <button onclick="showTable('employeesTable')">Show Employees</button>
        <form action="login.php" method="get" style="display: inline;">
            <button type="submit">Logout</button>
    </div>

    <div id="booksTable" class="table-container">
        <h2>Book Search Results</h2>
        <form method="GET" action="">
            <label for="category">Search by:</label>
            <select name="category" id="category">
                <option value="isbn">ISBN</option>
                <option value="authors">Authors</option>
                <option value="original_publication_year">Year</option>
                <option value="title">Title</option>
                <option value="average_rating">Rating</option>
            </select>
            <input type="text" name="query" placeholder="Search...">
            <input type="hidden" name="search" value="1">
            <button type="submit">Search</button>
        </form>

        <?php if (!empty($searchResults)): ?>
            <table>
                <thead>
                    <tr>
                        <th>ISBN</th>
                        <th>Authors</th>
                        <th>Year</th>
                        <th>Title</th>
                        <th>Rating</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($searchResults as $book): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($book['isbn']); ?></td>
                            <td><?php echo htmlspecialchars($book['authors']); ?></td>
                            <td><?php echo htmlspecialchars($book['original_publication_year']); ?></td>
                            <td><?php echo htmlspecialchars($book['title']); ?></td>
                            <td><?php echo htmlspecialchars($book['average_rating']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No search results found.</p>
        <?php endif; ?>
    </div>

    <div id="allBooksTable" class="table-container">
        <h2>All Books</h2>
        <table>
            <thead>
                <tr>
                    <th>ISBN</th>
                    <th>Authors</th>
                    <th>Year</th>
                    <th>Title</th>
                    <th>Rating</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($books as $book): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($book['isbn']); ?></td>
                        <td><?php echo htmlspecialchars($book['authors']); ?></td>
                        <td><?php echo htmlspecialchars($book['original_publication_year']); ?></td>
                        <td><?php echo htmlspecialchars($book['title']); ?></td>
                        <td><?php echo htmlspecialchars($book['average_rating']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div id="employeesTable" class="table-container">
        <h2>Employees</h2>
        <table>
            <thead>
                <tr>
                    <th>Employee ID</th>
                    <th>Name</th>
                    <th>Position</th>
                    <th>Department</th>
                    <th>Salary</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($employees as $employee): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($employee['employee_id']); ?></td>
                        <td><?php echo htmlspecialchars($employee['name']); ?></td>
                        <td><?php echo htmlspecialchars($employee['position']); ?></td>
                        <td><?php echo htmlspecialchars($employee['department']); ?></td>
                        <td><?php echo htmlspecialchars($employee['salary']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
        function showTable(tableId) {
            document.getElementById('booksTable').style.display = 'none';
            document.getElementById('allBooksTable').style.display = 'none';
            document.getElementById('employeesTable').style.display = 'none';
            document.getElementById(tableId).style.display = 'block';
        }

        // Show books table by default
        showTable('booksTable');
    </script>
</body>
</html>

<?php
$conn->close();
?>
