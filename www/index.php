<?php
$servername = "mysql";
$username = "root";
$password = "12345";
$dbname = "userdb";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("فشل الاتصال: " . $conn->connect_error);

$error = $success = "";

// دالة للتحقق من نوع الصورة المرفوعة
function is_valid_image($file) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    return in_array($file['type'], $allowedTypes);
}

// حذف مستخدم
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = intval($_GET['delete']);

    // جلب مسار الصورة القديمة بحماية
    $stmt = $conn->prepare("SELECT AvatarPath FROM users WHERE Id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        if ($row['AvatarPath'] && file_exists($row['AvatarPath'])) {
            unlink($row['AvatarPath']);
        }
    }
    $stmt->close();

    // حذف المستخدم
    $stmt = $conn->prepare("DELETE FROM users WHERE Id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    header("Location: index.php");
    exit;
}

// جلب بيانات مستخدم للتعديل
$editMode = false;
$editUser = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $editMode = true;
    $editId = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM users WHERE Id = ?");
    $stmt->bind_param("i", $editId);
    $stmt->execute();
    $result = $stmt->get_result();
    $editUser = $result->fetch_assoc();
    $stmt->close();
}

// تنفيذ التعديل
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_id']) && is_numeric($_POST['update_id'])) {
    $updateId = intval($_POST['update_id']);
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);

    // جلب البيانات الحالية للمستخدم
    $stmt = $conn->prepare("SELECT AvatarPath FROM users WHERE Id = ?");
    $stmt->bind_param("i", $updateId);
    $stmt->execute();
    $result = $stmt->get_result();
    $currentUser = $result->fetch_assoc();
    $stmt->close();

    $avatarPath = $currentUser['AvatarPath']; // القيمة الحالية مبدئياً

    // التعامل مع الصورة الجديدة (لو تم رفع صورة جديدة)
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        if (!is_valid_image($_FILES['avatar'])) {
            $error = "الرجاء رفع صورة بصيغة صحيحة (JPEG, PNG, GIF).";
        } else {
            $uploadDir = "uploads/";
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            // حذف الصورة القديمة لو موجودة
            if ($avatarPath && file_exists($avatarPath)) {
                unlink($avatarPath);
            }

            $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
            $filename = uniqid("avatar_", true) . "." . $ext;
            $avatarPath = $uploadDir . $filename;

            if (!move_uploaded_file($_FILES['avatar']['tmp_name'], $avatarPath)) {
                $error = "حدث خطأ أثناء رفع الصورة.";
            }
        }
    }

    if (empty($error)) {
        if (!empty($name) && !empty($email)) {
            $stmt = $conn->prepare("UPDATE users SET Name = ?, Email = ?, AvatarPath = ? WHERE Id = ?");
            $stmt->bind_param("sssi", $name, $email, $avatarPath, $updateId);
            if ($stmt->execute()) {
                $success = "تم تعديل المستخدم بنجاح.";
                header("Location: index.php");
                exit;
            } else {
                $error = "حدث خطأ أثناء التعديل.";
            }
            $stmt->close();
        } else {
            $error = "الاسم والبريد مطلوبان.";
        }
    }
}

// جلب كل المستخدمين
$users = $conn->query("SELECT * FROM users ORDER BY CreatedAt DESC");

?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8" />
    <title>إدارة المستخدمين</title>
    <style>
        body { font-family: Tahoma; direction: rtl; margin: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 30px; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: center; }
        img { width: 60px; height: 60px; border-radius: 50%; object-fit: cover; }
        .btn { padding: 6px 10px; border: none; border-radius: 4px; text-decoration: none; margin: 2px; cursor: pointer; }
        .edit { background-color: #f0ad4e; color: white; }
        .delete { background-color: #d9534f; color: white; }
        .success { color: green; }
        .error { color: red; }
        form { margin-top: 20px; }
    </style>
</head>
<body>

<h2>قائمة المستخدمين</h2>

<?php if ($error): ?>
    <p class="error"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>
<?php if ($success): ?>
    <p class="success"><?= htmlspecialchars($success) ?></p>
<?php endif; ?>

<?php if ($editMode && $editUser): ?>
    <h3>تعديل المستخدم: <?= htmlspecialchars($editUser['Name']) ?></h3>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="update_id" value="<?= $editUser['Id'] ?>" />

        <label>الاسم:</label><br />
        <input type="text" name="name" value="<?= htmlspecialchars($editUser['Name']) ?>" required /><br /><br />

        <label>البريد الإلكتروني:</label><br />
        <input type="email" name="email" value="<?= htmlspecialchars($editUser['Email']) ?>" required /><br /><br />

        <label>الصورة الشخصية (اختياري):</label><br />
        <?php if ($editUser['AvatarPath'] && file_exists($editUser['AvatarPath'])): ?>
            <img src="<?= htmlspecialchars($editUser['AvatarPath']) ?>" alt="Avatar" style="width:80px;height:80px;border-radius:50%;object-fit:cover;" /><br /><br />
        <?php endif; ?>
        <input type="file" name="avatar" accept="image/*" /><br /><br />

        <button type="submit">حفظ التعديلات</button>
        <a href="index.php">إلغاء</a>
    </form>
<?php endif; ?>

<table>
    <thead>
        <tr>
            <th>الاسم</th>
            <th>البريد الإلكتروني</th>
            <th>الصورة</th>
            <th>تاريخ التسجيل</th>
            <th>الإجراءات</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $users->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['Name']) ?></td>
                <td><?= htmlspecialchars($row['Email']) ?></td>
                <td>
                    <?php if ($row['AvatarPath'] && file_exists($row['AvatarPath'])): ?>
                        <img src="<?= htmlspecialchars($row['AvatarPath']) ?>" alt="Avatar" />
                    <?php else: ?>
                        لا توجد
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($row['CreatedAt']) ?></td>
                <td>
                    <a class="btn edit" href="index.php?edit=<?= $row['Id'] ?>">تعديل</a>
                    <a class="btn delete" href="?delete=<?= $row['Id'] ?>" onclick="return confirm('هل أنت متأكد من حذف المستخدم؟')">حذف</a>
                </td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>

</body>
</html>
