<?php
require 'db_connection.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email']));
    $password = trim($_POST['password']);

    try {
        $stmt = $db->prepare("SELECT * FROM volunteers WHERE LOWER(email) = :email");
        $stmt->bindValue(':email', $email, SQLITE3_TEXT);
        $result = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
        if (!$result || !password_verify($password, $result['password'])) {
            $errorMessage = "Invalid email or password.";
        } else {
            // Set session for successful login
            $_SESSION['volunteer_logged_in'] = true;
            $_SESSION['volunteer_id'] = $result['id'];
            $_SESSION['volunteer_email'] = $result['email'];
            $_SESSION['volunteer_name'] = $result['name'];
            // echo $_SESSION['volunteer_name'];

            header('Location: role_selection.php'); // Redirect to role selection
            exit;
        }
    } catch (Exception $e) {
        $errorMessage = "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Volunteer Login</title>
    <link rel="stylesheet" href="./master.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        input::placeholder {
            color: black;
            /* Placeholder text color */
            opacity: 1;
            /* Ensure full visibility in some browsers */
        }

        input {
            color: black;
            opacity: 1;
            /* Input text color */
        }
        .custom-btn {
            background: linear-gradient(45deg, #007bff, #0056b3);
            /* Blue gradient */
            color: white;
            padding: 10px 20px;
            font-size: 16px;
            border: none;
            border-radius: 5px;
            transition: 0.3s;
        }

        .custom-btn:hover {
            background: linear-gradient(45deg, #0056b3, #003f7f);
            /* Darker on hover */
        }

        .form-controller {
            display: flex;
            justify-content: center;
            gap: 15px;
            /* Space between buttons */
        }
    </style>
</head>

<body style="background-color: #003f74;">
    <div id="login-window">
        <div id="login-box" class="glassy-dark-bg rounded-custom" style="background-color:#01368C">
            <div id='logo'>
                <!-- <img src="./images/dummy_logo.png" alt="logo" height="64" /> -->
            </div>
            <h2 id="login-header" class="logo-font">Volunteer Login</h2>
            <?php if (!empty($errorMessage)): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($errorMessage) ?>
                </div>
            <?php endif; ?>
            <form class="my-3" method="POST" style="color:black;">
                <input class="input rounded-custom" type="email" name="email" id="email" autocomplete="username" placeholder="Email:" required style="color:black;" /><br />
                <input class="input rounded-custom" type="password" name="password" id="password" autocomplete="current-password" placeholder="Password:" required style="color:black;" /><br />
                <div class="row form-group mt-5">
                    <div class="form-controller d-flex justify-content-between">
                        <button class="btn custom-btn w-50" type="submit" style="margin : 0 ;background-color:#003f74">Log In</button>
                        <button class="btn custom-btn w-50" type="button" style="margin : 0 ;background-color:#003f74" onclick="window.location.href='volunteer_signup.php'">Sign Up</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</body>

</html>