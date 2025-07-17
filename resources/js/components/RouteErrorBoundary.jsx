import React from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import { AlertCircle, ArrowLeft } from 'lucide-react';
import { Button } from './ui/button';
import { Alert, AlertDescription, AlertTitle } from './ui/alert';

class RouteErrorBoundaryClass extends React.Component {
    constructor(props) {
        super(props);
        this.state = { hasError: false, error: null };
    }

    static getDerivedStateFromError(error) {
        return { hasError: true };
    }

    componentDidCatch(error, errorInfo) {
        // Log to console in development
        if (process.env.NODE_ENV === 'development') {
            console.error('Route error:', error, errorInfo);
        }
        
        this.setState({ error });
    }

    componentDidUpdate(prevProps) {
        // Reset error state when route changes
        if (prevProps.location !== this.props.location) {
            this.setState({ hasError: false, error: null });
        }
    }

    render() {
        if (this.state.hasError) {
            return (
                <div className="container mx-auto p-6 max-w-2xl">
                    <Alert variant="destructive">
                        <AlertCircle className="h-4 w-4" />
                        <AlertTitle>Fehler beim Laden der Seite</AlertTitle>
                        <AlertDescription className="mt-2">
                            Diese Seite konnte nicht geladen werden. 
                            Bitte versuchen Sie es später erneut oder kehren Sie zur vorherigen Seite zurück.
                        </AlertDescription>
                    </Alert>
                    
                    <div className="mt-4 flex gap-2">
                        <Button
                            variant="outline"
                            onClick={() => this.props.navigate(-1)}
                        >
                            <ArrowLeft className="w-4 h-4 mr-2" />
                            Zurück
                        </Button>
                        <Button
                            onClick={() => window.location.reload()}
                        >
                            Seite neu laden
                        </Button>
                    </div>
                    
                    {process.env.NODE_ENV === 'development' && this.state.error && (
                        <div className="mt-4 p-4 bg-gray-100 dark:bg-gray-800 rounded-lg">
                            <p className="text-sm font-mono text-red-600 dark:text-red-400">
                                {this.state.error.toString()}
                            </p>
                        </div>
                    )}
                </div>
            );
        }

        return this.props.children;
    }
}

// Wrapper component to use hooks
const RouteErrorBoundary = ({ children }) => {
    const location = useLocation();
    const navigate = useNavigate();
    
    return (
        <RouteErrorBoundaryClass location={location} navigate={navigate}>
            {children}
        </RouteErrorBoundaryClass>
    );
};

export default RouteErrorBoundary;