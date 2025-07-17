import React from 'react';
import { AlertTriangle, RefreshCw, Home } from 'lucide-react';
import { Button } from './ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from './ui/card';

class ErrorBoundary extends React.Component {
    constructor(props) {
        super(props);
        this.state = { 
            hasError: false, 
            error: null,
            errorInfo: null,
            errorCount: 0
        };
    }

    static getDerivedStateFromError(error) {
        return { hasError: true };
    }

    componentDidCatch(error, errorInfo) {
        // Log error to error reporting service
        if (window.Sentry) {
            window.Sentry.captureException(error, {
                contexts: {
                    react: {
                        componentStack: errorInfo.componentStack,
                    },
                },
            });
        }

        // Log to console in development
        if (process.env.NODE_ENV === 'development') {
            console.error('Error caught by boundary:', error, errorInfo);
        }

        this.setState({
            error: error,
            errorInfo: errorInfo,
            errorCount: this.state.errorCount + 1
        });
    }

    handleReset = () => {
        this.setState({ 
            hasError: false, 
            error: null, 
            errorInfo: null 
        });
        
        // Optionally reload the page if errors persist
        if (this.state.errorCount > 3) {
            window.location.reload();
        }
    };

    handleGoHome = () => {
        window.location.href = '/business/dashboard';
    };

    render() {
        if (this.state.hasError) {
            const isDevelopment = process.env.NODE_ENV === 'development';
            
            return (
                <div className="min-h-screen flex items-center justify-center p-4 bg-gray-50 dark:bg-gray-900">
                    <Card className="max-w-lg w-full">
                        <CardHeader className="text-center">
                            <div className="mx-auto w-12 h-12 text-red-500 mb-4">
                                <AlertTriangle className="w-full h-full" />
                            </div>
                            <CardTitle className="text-2xl">
                                Etwas ist schief gelaufen
                            </CardTitle>
                            <CardDescription>
                                Ein unerwarteter Fehler ist aufgetreten. Bitte versuchen Sie es erneut.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {isDevelopment && this.state.error && (
                                <div className="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                                    <p className="text-sm font-mono text-red-800 dark:text-red-200 mb-2">
                                        {this.state.error.toString()}
                                    </p>
                                    {this.state.errorInfo && (
                                        <details className="text-xs text-red-700 dark:text-red-300">
                                            <summary className="cursor-pointer font-medium mb-1">
                                                Technische Details
                                            </summary>
                                            <pre className="overflow-auto p-2 bg-red-100 dark:bg-red-900/30 rounded mt-2">
                                                {this.state.errorInfo.componentStack}
                                            </pre>
                                        </details>
                                    )}
                                </div>
                            )}
                            
                            <div className="flex gap-2">
                                <Button 
                                    onClick={this.handleReset}
                                    variant="outline"
                                    className="flex-1"
                                >
                                    <RefreshCw className="w-4 h-4 mr-2" />
                                    Erneut versuchen
                                </Button>
                                <Button 
                                    onClick={this.handleGoHome}
                                    className="flex-1"
                                >
                                    <Home className="w-4 h-4 mr-2" />
                                    Zum Dashboard
                                </Button>
                            </div>
                            
                            {this.state.errorCount > 2 && (
                                <p className="text-sm text-muted-foreground text-center">
                                    Der Fehler tritt wiederholt auf. Bitte kontaktieren Sie den Support, 
                                    falls das Problem weiterhin besteht.
                                </p>
                            )}
                        </CardContent>
                    </Card>
                </div>
            );
        }

        return this.props.children;
    }
}

export default ErrorBoundary;