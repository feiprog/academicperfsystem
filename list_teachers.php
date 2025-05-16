<?php
require_once 'db.php';

$result = $conn->query("SELECT username, email FROM users WHERE role = 'teacher'");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Teacher Accounts</title>
    <link href="https://cdn.tailwindcss.com" rel="stylesheet">
</head>
<body class="bg-gray-50 font-sans min-h-screen">
    <div class="max-w-xl mx-auto mt-12 bg-white rounded-2xl shadow-xl p-8">
        <h1 class="text-2xl font-bold text-sky-700 mb-6">Teacher Accounts</h1>
        <table class="w-full">
            <thead>
                <tr class="bg-gray-100">
                    <th class="px-4 py-2 text-left">Username</th>
                    <th class="px-4 py-2 text-left">Email</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td class="px-4 py-2 text-gray-900"><?php echo htmlspecialchars($row['username']); ?></td>
                    <td class="px-4 py-2 text-gray-700"><?php echo htmlspecialchars($row['email']); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html> 