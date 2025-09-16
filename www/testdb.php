<?php
$servername = "localhost";  // أو 127.0.0.1
$username = "root";
$password = "12345";
$dbname = "userdb";

// إنشاء الاتصال
$conn = new mysqli($servername, $username, $password, $dbname);

// التحقق من الاتصال
if ($conn->connect_error) {
    die("فشل الاتصال: " . $conn->connect_error);
}

echo "تم الاتصال بنجاح بقاعدة البيانات";
?>
