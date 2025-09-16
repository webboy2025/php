<?php
session_start();

// إعدادات الاتصال بقاعدة البيانات
$servername = "localhost";  // أو 127.0.0.1
$username = "root";
$password = "12345";
$dbname = "userdb";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("فشل الاتصال: " . $conn->connect_error);
}

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "يرجى تعبئة جميع الحقول.";
    } else {
        $stmt = $conn->prepare("SELECT Id, Name, PasswordHash FROM users WHERE Email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 1) {
            $stmt->bind_result($userId, $name, $hashedPassword);
            $stmt->fetch();

            if (password_verify($password, $hashedPassword)) {
                // تسجيل الدخول بنجاح
                $_SESSION['user_id'] = $userId;
                $_SESSION['name'] = $name;
                $success = "تم تسجيل الدخول بنجاح. مرحبًا، $name!";
            } else {
                $error = "كلمة المرور غير صحيحة.";
            }
        } else {
            $error = "البريد الإلكتروني غير مسجّل.";
        }

        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>تسجيل الدخول</title>
</head>
<body>
    <h2>تسجيل الدخول</h2>

    <?php if ($error): ?>
        <p style="color:red;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <?php if ($success): ?>
        <p style="color:green;"><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>

    <form method="POST">
        <label>البريد الإلكتروني:</label><br>
        <input type="email" name="email" required><br><br>

        <label>كلمة المرور:</label><br>
        <input type="password" name="password" required><br><br>

        <button type="submit">دخول</button>
    </form>

    <p>ليس لديك حساب؟ <a href="register.php">سجّل الآن</a></p>
</body>
</html>

