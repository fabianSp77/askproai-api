<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SimpleDashboardController extends Controller
{
    public function index(Request $request)
    {
        // Get user from session
        $userId = session("portal_user_id");
        
        if (!$userId) {
            return redirect("/business/login");
        }
        
        // Return a simple HTML view
        return '<!DOCTYPE html>
<html>
<head>
    <title>Business Portal Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; }
        .header { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; }
        .card { background: white; padding: 20px; border-radius: 8px; text-align: center; }
        .card h3 { margin: 0 0 10px 0; }
        .btn { display: inline-block; padding: 10px 20px; background: #3B82F6; color: white; text-decoration: none; border-radius: 6px; margin: 5px; }
        .btn:hover { background: #2563EB; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Business Portal Dashboard</h1>
            <p>Welcome! You are logged in. (User ID: ' . $userId . ')</p>
        </div>
        
        <div class="grid">
            <div class="card">
                <h3>📞 Calls</h3>
                <p>Manage your calls</p>
                <a href="/business/calls" class="btn">View Calls</a>
            </div>
            
            <div class="card">
                <h3>📅 Appointments</h3>
                <p>View appointments</p>
                <a href="/business/appointments" class="btn">Appointments</a>
            </div>
            
            <div class="card">
                <h3>👥 Customers</h3>
                <p>Customer management</p>
                <a href="/business/customers" class="btn">Customers</a>
            </div>
            
            <div class="card">
                <h3>⚙️ Settings</h3>
                <p>Configure settings</p>
                <a href="/business/settings" class="btn">Settings</a>
            </div>
        </div>
    </div>
</body>
</html>';
    }
}
