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

    // Commented out the message
    // echo "Database and table setup completed!";
} catch (mysqli_sql_exception $e) {
    echo "Error: " . $e->getMessage();
    exit;
}

// Automatically integrate CSV data
$csvFilePath = 'C:\xampp\htdocs\myproject\books.csv'; // Update this path to the location of your CSV file

if (file_exists($csvFilePath)) {
    $csvFile = fopen($csvFilePath, 'r');
    fgetcsv($csvFile); // Skip the header row

    $rowCount = 0;
    while (($row = fgetcsv($csvFile)) !== FALSE && $rowCount < 1000) {
        // Ensure correct data types
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Management</title>
    <style>
        .table-container {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 8px 12px;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <h1>Library Management System</h1>

    <h2>Search for Books</h2>
    <form method="GET">
        <label>Category:
            <select name="category">
                <option value="title">Title</option>
                <option value="authors">Author</option>
                <option value="average_rating">Rating</option>
                <option value="isbn">ISBN</option>
                <option value="id">ID</option>
            </select>
        </label>
        <label>Query: <input type="text" name="query" required></label>
        <button type="submit" name="search">Search</button>
    </form>

    <h2>Search Results</h2>
    <?php if (count($searchResults) > 0): ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>ISBN</th>
                        <th>Authors</th>
                        <th>Original Publication Year</th>
                        <th>Title</th>
                        <th>Average Rating</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($searchResults as $book): ?>
                        <tr>
                            <td><?= $book['id'] ?></td>
                            <td><?= $book['isbn'] ?></td>
                            <td><?= $book['authors'] ?></td>
                            <td><?= $book['original_publication_year'] ?></td>
                            <td><?= $book['title'] ?></td>
                            <td><?= $book['average_rating'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p>No search results found.</p>
    <?php endif; ?>

    <h2>All Books</h2>
    <?php if (count($books) > 0): ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>ISBN</th>
                        <th>Authors</th>
                        <th>Original Publication Year</th>
                        <th>Title</th>
                        <th>Average Rating</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($books as $book): ?>
                        <tr>
                            <td><?= $book['id'] ?></td>
                            <td><?= $book['isbn'] ?></td>
                            <td><?= $book['authors'] ?></td>
                            <td><?= $book['original_publication_year'] ?></td>
                            <td><?= $book['title'] ?></td>
                            <td><?= $book['average_rating'] ?></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $book['id'] ?>">
                                    <button type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p>No books found.</p>
    <?php endif; ?>
</body>
</html>