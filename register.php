<?php
require_once 'includes/helpers.php';
require_once 'includes/db.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: ' . dashboard_url());
    exit();
}

$message = '';
$departments = $conn->query('SELECT department_id, department_name FROM departments ORDER BY department_name');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = post_value('name');
    $email = strtolower(post_value('email'));
    $phone = post_value('phone');
    $departmentId = filter_var(post_value('department_id'), FILTER_VALIDATE_INT);
    $semester = post_value('semester');
    $address = post_value('address');
    $password = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';
    $confirmPassword = isset($_POST['confirm_password']) && is_string($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $phone === '' || !$departmentId || $semester === '' || $address === '') {
        $message = 'Please complete every field with valid information.';
    } elseif (strlen($password) < 8) {
        $message = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirmPassword) {
        $message = 'Passwords do not match.';
    } else {
        $departmentCheck = $conn->prepare('SELECT department_id FROM departments WHERE department_id = ?');
        $departmentCheck->bind_param('i', $departmentId);
        $departmentCheck->execute();
        $departmentExists = $departmentCheck->get_result()->num_rows === 1;
        $departmentCheck->close();

        if (!$departmentExists) {
            $message = 'Select a valid department.';
        } else {
            $check = $conn->prepare('SELECT user_id FROM users WHERE email = ?');
            $check->bind_param('s', $email);
            $check->execute();
            $emailExists = $check->get_result()->num_rows > 0;
            $check->close();

            if ($emailExists) {
                $message = 'This email is already registered.';
            } else {
                try {
                    $conn->begin_transaction();
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $role = 'Student';
                    $insertUser = $conn->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)');
                    $insertUser->bind_param('ssss', $name, $email, $hash, $role);
                    $insertUser->execute();
                    $userId = $conn->insert_id;
                    $insertStudent = $conn->prepare('INSERT INTO students (user_id, department_id, phone, semester, address) VALUES (?, ?, ?, ?, ?)');
                    $insertStudent->bind_param('iisss', $userId, $departmentId, $phone, $semester, $address);
                    $insertStudent->execute();
                    $conn->commit();
                    header('Location: login.php?registered=1');
                    exit();
                } catch (Throwable $exception) {
                    $conn->rollback();
                    error_log('CampusConnect student registration failed: ' . $exception->getMessage());
                    $message = 'Registration could not be completed. Please try again.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | CampusConnect</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-box register-box">
            <a class="brand auth-brand" href="index.php">Campus<span>Connect</span></a>
            <h2>Create Student Account</h2>
            <p>Join CampusConnect to use university services</p>
            <?php if ($message !== '') { ?><p class="form-message error"><?php echo e($message); ?></p><?php } ?>
            <form action="register.php" method="POST">
                <?php echo csrf_field(); ?>
                <div class="form-group"><label for="name">Full Name</label><input id="name" name="name" maxlength="120" value="<?php echo e(post_value('name')); ?>" required></div>
                <div class="form-group"><label for="email">Email Address</label><input id="email" type="email" name="email" maxlength="190" value="<?php echo e(post_value('email')); ?>" autocomplete="email" required></div>
                <div class="form-group"><label for="phone">Phone Number</label><input id="phone" type="tel" name="phone" maxlength="30" value="<?php echo e(post_value('phone')); ?>" required></div>
                <div class="form-group"><label for="department_id">Department</label>
                    <select id="department_id" name="department_id" required><option value="">Select department</option>
                        <?php while ($department = $departments->fetch_assoc()) { ?>
                            <option value="<?php echo (int) $department['department_id']; ?>" <?php echo post_value('department_id') == $department['department_id'] ? 'selected' : ''; ?>><?php echo e($department['department_name']); ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group"><label for="semester">Semester</label><input id="semester" name="semester" maxlength="30" value="<?php echo e(post_value('semester')); ?>" placeholder="e.g. 5th" required></div>
                <div class="form-group"><label for="address">Address</label><input id="address" name="address" maxlength="255" value="<?php echo e(post_value('address')); ?>" required></div>
                <div class="form-group"><label for="password">Password</label><input id="password" type="password" name="password" minlength="8" autocomplete="new-password" required></div>
                <div class="form-group"><label for="confirm_password">Confirm Password</label><input id="confirm_password" type="password" name="confirm_password" minlength="8" autocomplete="new-password" required></div>
                <button type="submit" class="login-submit">Create Account</button>
            </form>
            <p class="register-text">Already have an account? <a href="login.php">Login now</a></p>
            <a href="index.php" class="back-home">Back to Home</a>
        </div>
    </div>
</body>
</html>
