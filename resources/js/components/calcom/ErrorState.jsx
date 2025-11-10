import React from 'react';

export default function ErrorState({
    message = 'Something went wrong',
    onRetry = null
}) {
    return (
        <div className="flex flex-col items-center justify-center p-12 text-center">
            <div className="inline-flex items-center justify-center w-16 h-16 mb-4 rounded-full bg-red-100">
                <svg className="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                </svg>
            </div>
            <h3 className="text-lg font-semibold text-gray-900 mb-2">
                Error
            </h3>
            <p className="text-sm text-gray-600 mb-4">
                {message}
            </p>
            {onRetry && (
                <button
                    onClick={onRetry}
                    className="px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700"
                >
                    Try Again
                </button>
            )}
        </div>
    );
}
