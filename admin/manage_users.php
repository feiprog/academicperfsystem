<?php
require_once 'auth_check.php';
require_once 'db.php';

// Get all users
$stmt = $conn->prepare("
    SELECT u.*, r.role_name 
    FROM users u 
    JOIN roles r ON u.role_id = r.id 
    ORDER BY u.created_at DESC
");
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
    <link rel="stylesheet" href="styles/global.css">
    <link rel="stylesheet" href="styles/manage_users.css">
</head>
<body>
    <div class="container">
        <h1>Manage Users</h1>
        <div class="flex flex-wrap gap-4 mb-6">
            <!-- Action buttons will be dynamically added here -->
        </div>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= $user['id'] ?></td>
                        <td><?= $user['username'] ?></td>
                        <td><?= $user['email'] ?></td>
                        <td><?= $user['role_name'] ?></td>
                        <td><?= $user['created_at'] ?></td>
                        <td>
                            <button class="btn btn-warning">Edit</button>
                            <button class="btn btn-danger">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <script>
    function showAddUserModal() {
        // Implementation for showing add user modal
    }

    function showEditUserModal() {
        // Implementation for showing edit user modal
    }

    function showDeleteUserModal() {
        // Implementation for showing delete user modal
    }

    function showResetPasswordModal() {
        // Implementation for showing reset password modal
    }

    function exportUsers() {
        // Implementation for exporting users
    }
    </script>
</body>
</html> 