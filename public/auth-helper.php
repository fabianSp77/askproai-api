<?php
// Authentication Helper - Quick Login & User Management

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);
$kernel->terminate($request, $response);

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch($action) {
        case 'create_user':
            try {
                $user = \App\Models\User::create([
                    'name' => $_POST['name'],
                    'email' => $_POST['email'],
                    'password' => bcrypt($_POST['password'])
                ]);
                
                // Assign admin role if requested
                if (isset($_POST['make_admin'])) {
                    $user->assignRole('super_admin');
                }
                
                $message = "User created successfully! Email: {$user->email}";
                $messageType = 'success';
            } catch (Exception $e) {
                $message = "Error creating user: " . $e->getMessage();
                $messageType = 'error';
            }
            break;
            
        case 'reset_password':
            try {
                $user = \App\Models\User::where('email', $_POST['email'])->first();
                if ($user) {
                    $user->password = bcrypt($_POST['new_password']);
                    $user->save();
                    $message = "Password reset successfully for {$user->email}";
                    $messageType = 'success';
                } else {
                    $message = "User not found!";
                    $messageType = 'error';
                }
            } catch (Exception $e) {
                $message = "Error resetting password: " . $e->getMessage();
                $messageType = 'error';
            }
            break;
            
        case 'quick_login':
            try {
                $user = \App\Models\User::find($_POST['user_id']);
                if ($user) {
                    auth()->login($user);
                    header('Location: /admin');
                    exit;
                }
            } catch (Exception $e) {
                $message = "Login failed: " . $e->getMessage();
                $messageType = 'error';
            }
            break;
    }
}

// Get all users
$users = \App\Models\User::all();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authentication Helper</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f7fa;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
        }
        .card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        h1, h2 {
            margin-top: 0;
        }
        .message {
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        button {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.2s;
        }
        button:hover {
            background: #0056b3;
        }
        button.success {
            background: #28a745;
        }
        button.success:hover {
            background: #218838;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
        }
        .status {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 12px;
        }
        .status.active {
            background: #d4edda;
            color: #155724;
        }
        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 Authentication Helper</h1>
            <?php if ($message): ?>
                <div class="message <?= $messageType ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="grid">
            <!-- Existing Users -->
            <div class="card">
                <h2>👥 Existing Users</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Email</th>
                            <th>Name</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= htmlspecialchars($user->email) ?></td>
                                <td><?= htmlspecialchars($user->name) ?></td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="quick_login">
                                        <input type="hidden" name="user_id" value="<?= $user->id ?>">
                                        <button type="submit" class="success">Quick Login</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <h3>🔗 Direct Links</h3>
                <ul>
                    <li><a href="/admin/login">Normal Admin Login</a></li>
                    <li><a href="/admin">Admin Dashboard</a> (requires auth)</li>
                    <li><a href="/horizon">Horizon Dashboard</a></li>
                </ul>
            </div>

            <!-- Create New User -->
            <div class="card">
                <h2>➕ Create New User</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="create_user">
                    
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" name="name" required value="Test Admin">
                    </div>
                    
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" required value="admin@test.de">
                    </div>
                    
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" required value="password">
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="make_admin" checked>
                            Make Super Admin
                        </label>
                    </div>
                    
                    <button type="submit">Create User</button>
                </form>
            </div>

            <!-- Reset Password -->
            <div class="card">
                <h2>🔑 Reset Password</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="reset_password">
                    
                    <div class="form-group">
                        <label>Email</label>
                        <select name="email" required>
                            <option value="">Select user...</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?= htmlspecialchars($user->email) ?>">
                                    <?= htmlspecialchars($user->email) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="new_password" required value="password">
                    </div>
                    
                    <button type="submit">Reset Password</button>
                </form>
            </div>

            <!-- System Status -->
            <div class="card">
                <h2>📊 System Status</h2>
                <table>
                    <tr>
                        <td>Total Users</td>
                        <td><strong><?= $users->count() ?></strong></td>
                    </tr>
                    <tr>
                        <td>PHP Session</td>
                        <td><span class="status active"><?= session()->getId() ? 'Active' : 'Inactive' ?></span></td>
                    </tr>
                    <tr>
                        <td>Current Auth</td>
                        <td><?= auth()->check() ? auth()->user()->email : 'Not authenticated' ?></td>
                    </tr>
                    <tr>
                        <td>Alpine.js</td>
                        <td><span class="status active">v3.14.9</span></td>
                    </tr>
                    <tr>
                        <td>Livewire</td>
                        <td><span class="status active">Loaded</span></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</body>
</html>