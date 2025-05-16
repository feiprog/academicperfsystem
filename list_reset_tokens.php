<?php
require_once 'db.php';

$result = $conn->query("SELECT pr.id, u.username, u.email, pr.token, pr.expires_at, pr.used FROM password_resets pr JOIN users u ON pr.user_id = u.id ORDER BY pr.id DESC");
?>
<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <title>Password Reset Tokens</title>
    <link href='https://cdn.tailwindcss.com' rel='stylesheet'>
</head>
<body class='bg-gray-50 font-sans min-h-screen'>
    <div class='max-w-4xl mx-auto mt-12 bg-white rounded-2xl shadow-xl p-8'>
        <h1 class='text-2xl font-bold text-sky-700 mb-6'>Password Reset Tokens</h1>
        <table class='w-full'>
            <thead>
                <tr class='bg-gray-100'>
                    <th class='px-4 py-2 text-left'>ID</th>
                    <th class='px-4 py-2 text-left'>Username</th>
                    <th class='px-4 py-2 text-left'>Email</th>
                    <th class='px-4 py-2 text-left'>Token</th>
                    <th class='px-4 py-2 text-left'>Expires At</th>
                    <th class='px-4 py-2 text-left'>Used</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td class='px-4 py-2'><?php echo $row['id']; ?></td>
                    <td class='px-4 py-2'><?php echo htmlspecialchars($row['username']); ?></td>
                    <td class='px-4 py-2'><?php echo htmlspecialchars($row['email']); ?></td>
                    <td class='px-4 py-2' style='max-width:200px; word-break:break-all;'><?php echo htmlspecialchars($row['token']); ?></td>
                    <td class='px-4 py-2'><?php echo $row['expires_at']; ?></td>
                    <td class='px-4 py-2'><?php echo $row['used'] ? 'Yes' : 'No'; ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html> 