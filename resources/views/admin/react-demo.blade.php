<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>AskProAI - React Admin Portal</title>
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    <script src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
    <script src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>
    <script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body>
    <div id="root"></div>

    <script type="text/babel">
        const { useState, useEffect } = React;

        // Mock Auth Context
        const useAuth = () => ({
            user: { name: 'Admin User', email: 'admin@askproai.de' },
            isAuthenticated: true,
            logout: () => {
                localStorage.removeItem('admin_token');
                window.location.href = '/admin/login';
            }
        });

        // Dashboard Component
        const Dashboard = () => {
            const [stats, setStats] = useState({
                companies: 12,
                appointments_today: 24,
                calls_today: 89,
                customers_new: 15
            });

            return (
                <div>
                    <h1 className="text-2xl font-semibold mb-6">Dashboard</h1>
                    
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                        <div className="bg-white rounded-lg shadow p-6">
                            <div className="text-gray-500 text-sm">Aktive Mandanten</div>
                            <div className="text-2xl font-bold mt-2">{stats.companies}</div>
                            <div className="text-green-600 text-sm mt-2">+2 diese Woche</div>
                        </div>
                        
                        <div className="bg-white rounded-lg shadow p-6">
                            <div className="text-gray-500 text-sm">Termine heute</div>
                            <div className="text-2xl font-bold mt-2">{stats.appointments_today}</div>
                            <div className="text-blue-600 text-sm mt-2">5 anstehend</div>
                        </div>
                        
                        <div className="bg-white rounded-lg shadow p-6">
                            <div className="text-gray-500 text-sm">Anrufe heute</div>
                            <div className="text-2xl font-bold mt-2">{stats.calls_today}</div>
                            <div className="text-green-600 text-sm mt-2">+15% vs gestern</div>
                        </div>
                        
                        <div className="bg-white rounded-lg shadow p-6">
                            <div className="text-gray-500 text-sm">Neue Kunden</div>
                            <div className="text-2xl font-bold mt-2">{stats.customers_new}</div>
                            <div className="text-green-600 text-sm mt-2">+3 heute</div>
                        </div>
                    </div>

                    <div className="bg-blue-50 border border-blue-200 rounded-lg p-6">
                        <h3 className="font-semibold text-blue-900 mb-2">🎉 Willkommen im React Admin Portal!</h3>
                        <p className="text-blue-800">
                            Dies ist das neue Admin Portal basierend auf React. Es löst die Session-Konflikte 
                            mit dem Business Portal und bietet eine moderne, schnelle Benutzeroberfläche.
                        </p>
                        <ul className="mt-3 space-y-1 text-sm text-blue-700">
                            <li>✅ Keine 419 Session Errors mehr</li>
                            <li>✅ Einheitliche Technologie mit Business Portal</li>
                            <li>✅ Schnellere Performance</li>
                            <li>✅ Modernes Design</li>
                        </ul>
                    </div>
                </div>
            );
        };

        // Main App Component
        const AdminApp = () => {
            const [currentPage, setCurrentPage] = useState('dashboard');
            const [sidebarOpen, setSidebarOpen] = useState(true);
            const auth = useAuth();

            const menuItems = [
                { key: 'dashboard', label: 'Dashboard', icon: '📊' },
                { key: 'companies', label: 'Mandanten', icon: '🏢' },
                { key: 'users', label: 'Benutzer', icon: '👥' },
                { key: 'calls', label: 'Anrufe', icon: '📞' },
                { key: 'appointments', label: 'Termine', icon: '📅' },
                { key: 'customers', label: 'Kunden', icon: '👤' },
                { key: 'settings', label: 'Einstellungen', icon: '⚙️' },
            ];

            const renderContent = () => {
                switch(currentPage) {
                    case 'dashboard':
                        return <Dashboard />;
                    default:
                        return (
                            <div className="bg-white rounded-lg shadow p-6">
                                <h2 className="text-xl font-semibold mb-4">{menuItems.find(m => m.key === currentPage)?.label}</h2>
                                <p className="text-gray-600">Diese Seite wird noch implementiert...</p>
                            </div>
                        );
                }
            };

            return (
                <div className="min-h-screen bg-gray-100 flex">
                    {/* Sidebar */}
                    <aside className={`${sidebarOpen ? 'w-64' : 'w-16'} bg-gray-900 text-white transition-all duration-300`}>
                        <div className="p-4">
                            <h1 className={`text-xl font-bold ${sidebarOpen ? '' : 'hidden'}`}>AskProAI Admin</h1>
                            <button 
                                onClick={() => setSidebarOpen(!sidebarOpen)}
                                className="mt-4 p-2 hover:bg-gray-800 rounded"
                            >
                                {sidebarOpen ? '◀' : '▶'}
                            </button>
                        </div>
                        
                        <nav className="mt-8">
                            {menuItems.map(item => (
                                <button
                                    key={item.key}
                                    onClick={() => setCurrentPage(item.key)}
                                    className={`w-full flex items-center gap-3 px-4 py-3 hover:bg-gray-800 transition-colors ${
                                        currentPage === item.key ? 'bg-blue-600' : ''
                                    }`}
                                >
                                    <span className="text-xl">{item.icon}</span>
                                    {sidebarOpen && <span>{item.label}</span>}
                                </button>
                            ))}
                        </nav>
                    </aside>

                    {/* Main Content */}
                    <div className="flex-1 flex flex-col">
                        {/* Header */}
                        <header className="bg-white shadow px-6 py-4 flex justify-between items-center">
                            <h2 className="text-xl font-semibold">
                                {menuItems.find(m => m.key === currentPage)?.label || 'Dashboard'}
                            </h2>
                            
                            <div className="flex items-center gap-4">
                                <span className="text-gray-600">{auth.user.name}</span>
                                <button
                                    onClick={auth.logout}
                                    className="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600"
                                >
                                    Abmelden
                                </button>
                            </div>
                        </header>

                        {/* Page Content */}
                        <main className="flex-1 p-6">
                            {renderContent()}
                        </main>
                    </div>
                </div>
            );
        };

        // Render App
        const root = ReactDOM.createRoot(document.getElementById('root'));
        root.render(<AdminApp />);
    </script>
</body>
</html>