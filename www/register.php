<?php
$servername = "localhost";  // أو 127.0.0.1
$username = "root";
$password = "12345";
$dbname = "userdb";


$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // معالجة الصورة
    $avatarPath = null;
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = "uploads/";
        if (!is_dir($uploadDir)) mkdir($uploadDir);
        
        $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
        $filename = uniqid("avatar_", true) . "." . $ext;
        $avatarPath = $uploadDir . $filename;
        move_uploaded_file($_FILES['avatar']['tmp_name'], $avatarPath);
    }

    // تحقق من البيانات
    if (empty($name) || empty($email) || empty($password)) {
        $error = "كل الحقول مطلوبة.";
    } else {
        $stmt = $conn->prepare("SELECT Id FROM users WHERE Email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "هذا البريد مسجّل مسبقًا.";
        } else {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (Name, Email, PasswordHash, AvatarPath) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $email, $passwordHash, $avatarPath);
            
            if ($stmt->execute()) {
                $success = "تم التسجيل بنجاح!";
            } else {
                $error = "حدث خطأ أثناء التسجيل.";
            }
        }
        $stmt->close();
    }
}

$conn->close();
?>


<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>تسجيل مستخدم</title>
</head>
<body>
    <h2>نموذج التسجيل</h2>

    <?php if ($error): ?>
        <p style="color:red;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <?php if ($success): ?>
        <p style="color:green;"><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <label>الاسم:</label><br>
        <input type="text" name="name" required><br><br>

        <label>البريد الإلكتروني:</label><br>
        <input type="email" name="email" required><br><br>

        <label>كلمة المرور:</label><br>
        <input type="password" name="password" required><br><br>

        <label>الصورة الشخصية (اختياري):</label><br>
        <input type="file" name="avatar" accept="image/*"><br><br>

        <button type="submit">تسجيل</button>
    </form>
</body>
</html>
