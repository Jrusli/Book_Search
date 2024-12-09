<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $csvFilePath = 'C:\xampp\htdocs\Library_Search\user.csv'; // Update this path to the location of your CSV file

    if (file_exists($csvFilePath)) {
        $file = fopen($csvFilePath, 'r');
        fgetcsv($file); // Skip the header row

        $isValidUser = false;
        while (($row = fgetcsv($file)) !== FALSE) {
            if ($row[0] === $username && $row[1] === $password) { // Compare plain text passwords
                $isValidUser = true;
                break;
            }
        }
        fclose($file);

        if ($isValidUser) {
            // Redirect to database.php
            header("Location: database.php");
            exit();
        } else {
            echo "Invalid username or password.";
        }
    } else {
        echo "User data file not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #e0f7fa;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            background-color: #ffffff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 300px;
            text-align: center;
        }
        h2 {
            margin-top: 0;
            color: #00796b;
        }
        input[type="text"], input[type="password"] {
            width: calc(100% - 20px);
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #b0bec5;
            border-radius: 5px;
        }
        button {
            background-color: #00796b;
            color: #ffffff;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        button:hover {
            background-color: #004d40;
        }
        .register-button {
            background-color: #0288d1;
            margin-top: 10px;
        }
        .register-button:hover {
            background-color: #01579b;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Login</h2>
        <form method="POST" action="login.php">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
        <form action="register.php">
            <button type="submit" class="register-button">Register</button>
        </form>
    </div>
</body>
</html>