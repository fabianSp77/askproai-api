import React, { useEffect, useState } from 'react';

const Calls = () => {
    const [calls, setCalls] = useState([]);
    const [isLoading, setIsLoading] = useState(true);

    useEffect(() => {
        // Simulate API call
        const timer = setTimeout(() => {
            setCalls([
                {
                    id: 1,
                    caller: '+49 30 12345678',
                    duration: '3:42',
                    status: 'completed',
                    timestamp: '2024-01-15 14:30:00',
                    summary: 'Termin vereinbart für Zahnarzt Dr. Schmidt'
                },
                {
                    id: 2,
                    caller: '+49 40 98765432',
                    duration: '2:15',
                    status: 'completed',
                    timestamp: '2024-01-15 14:25:00',
                    summary: 'Informationen zu Öffnungszeiten angefragt'
                },
                {
                    id: 3,
                    caller: '+49 89 55443322',
                    duration: '1:33',
                    status: 'missed',
                    timestamp: '2024-01-15 14:20:00',
                    summary: 'Anruf nicht beantwortet'
                }
            ]);
            setIsLoading(false);
        }, 800);

        return () => clearTimeout(timer);
    }, []);

    if (isLoading) {
        return (
            <div className="p-6">
                <div className="animate-pulse">
                    <div className="h-8 bg-gray-200 rounded w-1/4 mb-6"></div>
                    <div className="bg-white rounded-lg shadow">
                        <div className="space-y-4 p-6">
                            {[...Array(5)].map((_, i) => (
                                <div key={i} className="flex space-x-4">
                                    <div className="h-4 bg-gray-200 rounded w-1/4"></div>
                                    <div className="h-4 bg-gray-200 rounded w-1/6"></div>
                                    <div className="h-4 bg-gray-200 rounded w-1/3"></div>
                                    <div className="h-4 bg-gray-200 rounded w-1/4"></div>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            </div>
        );
    }

    const getStatusColor = (status) => {
        switch (status) {
            case 'completed':
                return 'text-green-600 bg-green-100';
            case 'missed':
                return 'text-red-600 bg-red-100';
            case 'ongoing':
                return 'text-blue-600 bg-blue-100';
            default:
                return 'text-gray-600 bg-gray-100';
        }
    };

    const getStatusText = (status) => {
        switch (status) {
            case 'completed':
                return 'Beendet';
            case 'missed':
                return 'Verpasst';
            case 'ongoing':
                return 'Laufend';
            default:
                return 'Unbekannt';
        }
    };

    return (
        <div className="p-6 space-y-6">
            <div className="flex items-center justify-between">
                <h1 className="text-2xl font-bold text-gray-900">Anrufe</h1>
                <button className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    Exportieren
                </button>
            </div>

            {/* Summary Cards */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div className="bg-white p-6 rounded-lg shadow">
                    <h3 className="text-sm font-medium text-gray-500">Heute</h3>
                    <p className="text-2xl font-bold text-blue-600">23</p>
                    <p className="text-xs text-gray-400">+12% gegenüber gestern</p>
                </div>
                <div className="bg-white p-6 rounded-lg shadow">
                    <h3 className="text-sm font-medium text-gray-500">Diese Woche</h3>
                    <p className="text-2xl font-bold text-green-600">156</p>
                    <p className="text-xs text-gray-400">+8% gegenüber letzter Woche</p>
                </div>
                <div className="bg-white p-6 rounded-lg shadow">
                    <h3 className="text-sm font-medium text-gray-500">Durchschnittliche Dauer</h3>
                    <p className="text-2xl font-bold text-purple-600">2:34</p>
                    <p className="text-xs text-gray-400">-5% gegenüber letzter Woche</p>
                </div>
            </div>

            {/* Calls Table */}
            <div className="bg-white rounded-lg shadow overflow-hidden">
                <div className="px-6 py-4 border-b border-gray-200">
                    <h2 className="text-lg font-medium text-gray-900">Letzte Anrufe</h2>
                </div>
                <div className="overflow-x-auto">
                    <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Anrufer
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Dauer
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Zeitpunkt
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Zusammenfassung
                                </th>
                            </tr>
                        </thead>
                        <tbody className="bg-white divide-y divide-gray-200">
                            {calls.map((call) => (
                                <tr key={call.id} className="hover:bg-gray-50 transition-colors">
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {call.caller}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {call.duration}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap">
                                        <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${getStatusColor(call.status)}`}>
                                            {getStatusText(call.status)}
                                        </span>
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {new Date(call.timestamp).toLocaleString('de-DE')}
                                    </td>
                                    <td className="px-6 py-4 text-sm text-gray-900">
                                        <div className="max-w-xs truncate">
                                            {call.summary}
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    );
};

export default Calls;