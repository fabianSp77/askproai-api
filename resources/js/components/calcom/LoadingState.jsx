import React from 'react';

export default function LoadingState({ message = 'Loading...' }) {
    return (
        <div className="flex flex-col items-center justify-center p-12">
            <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600 mb-4"></div>
            <p className="text-sm text-gray-600">{message}</p>
        </div>
    );
}
