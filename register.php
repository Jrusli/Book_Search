<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password']; // Store the password as plain text

    $csvFilePath = 'C:\xampp\htdocs\Library_Search\user.csv'; // Update this path to the location of your CSV file

    // Check if the file exists
    if (!file_exists($csvFilePath)) {
        // Create the file and add the header row
        $file = fopen($csvFilePath, 'w');
        fputcsv($file, ['username', 'password']);
        fclose($file);
    }

    // Append the new user data to the CSV file
    $file = fopen($csvFilePath, 'a');
    fputcsv($file, [$username, $password]);
    fclose($file);

    // Redirect to login.php
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
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
        <h2>Register</h2>
        <form method="POST" action="register.php">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Register</button>
        </form>
    </div>
</body>
</html>