
<?php
require_once 'includes/helpers.php';
require_once 'includes/db.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: ' . dashboard_url());
    exit();
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = strtolower(post_value('email'));
    $password = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';
    $query = $conn->prepare('SELECT u.user_id, u.name, u.email, u.password, u.role, s.profile_picture FROM users u LEFT JOIN students s ON s.user_id = u.user_id WHERE u.email = ? LIMIT 1');
    $query->bind_param('s', $email);
    $query->execute();
    $user = $query->get_result()->fetch_assoc();
    $query->close();

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['user_id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['profile_picture'] = $user['profile_picture'];
        header('Location: ' . dashboard_url());
        exit();
    }

    $message = 'Incorrect email or password.';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login | CampusConnect</title>

    <link rel="stylesheet" href="css/style.css">
</head>

<body>

    <div class="login-container">

        <div class="login-box">

            <h2>Welcome Back!</h2>

            <p>Login to your CampusConnect account</p>

            <?php if (isset($_GET['registered'])) { ?><p class="form-message success">Registration successful. You can now sign in.</p><?php } ?>
            <?php if ($message !== '') { ?><p class="form-message error"><?php echo e($message); ?></p><?php } ?>

            <form action="login.php" method="POST">
                <?php echo csrf_field(); ?>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input id="email" type="email"
                           name="email"
                           placeholder="Enter your email"
                           autocomplete="email"
                           required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input id="password" type="password"
                           name="password"
                           placeholder="Enter your password"
                           autocomplete="current-password"
                           required>
                </div>

                <button type="submit" class="login-submit">
                    Login
                </button>

            </form>

            <p class="register-text">
                Don't have an account?
                <a href="register.php">Register Now</a>
            </p>

            <a href="index.php" class="back-home">
                Back to Home
            </a>

        </div>

    </div>

</body>
</html>
