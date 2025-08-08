import React from 'react';

const Customers = () => {
    return (
        <div className="p-6 space-y-6">
            <div className="flex items-center justify-between">
                <h1 className="text-2xl font-bold text-gray-900">Customers</h1>
                <div className="text-sm text-gray-500">
                    Coming soon
                </div>
            </div>
            
            <div className="bg-white rounded-lg shadow p-8 text-center">
                <div className="text-6xl text-gray-300 mb-4">🚧</div>
                <h2 className="text-xl font-semibold text-gray-700 mb-2">In Entwicklung</h2>
                <p className="text-gray-500">Dieser Bereich wird bald verfügbar sein.</p>
            </div>
        </div>
    );
};

export default Customers;
