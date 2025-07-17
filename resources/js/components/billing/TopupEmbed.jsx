import React, { useState } from 'react';
import { Card } from '../ui/card';
import { Button } from '../ui/button';
import { X, Loader2, ExternalLink } from 'lucide-react';
import { cn } from '../../lib/utils';

export const TopupEmbed = ({ companyId, onClose, className }) => {
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState(false);
    
    if (!companyId) {
        return null;
    }

    const topupUrl = `/topup/${companyId}`;
    
    // Handle iframe errors
    const handleError = () => {
        setError(true);
        setIsLoading(false);
    };

    return (
        <Card className={cn("relative overflow-hidden", className)}>
            {/* Header with close button */}
            <div className="flex items-center justify-between p-4 border-b bg-gray-50">
                <h3 className="text-lg font-semibold">Guthaben aufladen</h3>
                <div className="flex items-center gap-2">
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => window.open(topupUrl, '_blank')}
                        className="hover:bg-gray-200"
                        title="In neuem Tab öffnen"
                    >
                        <ExternalLink className="h-4 w-4" />
                    </Button>
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={onClose}
                        className="hover:bg-gray-200"
                    >
                        <X className="h-4 w-4" />
                    </Button>
                </div>
            </div>
            
            {/* Loading indicator */}
            {isLoading && !error && (
                <div className="absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center z-10">
                    <div className="flex flex-col items-center gap-2">
                        <Loader2 className="h-8 w-8 animate-spin text-primary" />
                        <p className="text-sm text-muted-foreground">Lade Aufladungsformular...</p>
                    </div>
                </div>
            )}
            
            {/* Error state */}
            {error && (
                <div className="p-8 text-center">
                    <div className="text-muted-foreground mb-4">
                        <p>Das Aufladungsformular konnte nicht geladen werden.</p>
                        <p className="text-sm mt-2">Bitte versuchen Sie es später erneut.</p>
                    </div>
                    <Button 
                        variant="outline" 
                        onClick={() => window.open(topupUrl, '_blank')}
                        className="mt-4"
                    >
                        In neuem Tab öffnen
                    </Button>
                </div>
            )}
            
            {/* Iframe */}
            {!error && (
                <div className="relative">
                    <iframe
                        src={topupUrl}
                        className="w-full border-0 transition-opacity duration-300"
                        style={{ 
                            height: window.innerWidth < 768 ? '700px' : '900px',
                            opacity: isLoading ? 0 : 1,
                            minHeight: '600px'
                        }}
                        onLoad={() => setIsLoading(false)}
                        onError={handleError}
                        title="Guthaben aufladen"
                        allow="payment"
                        sandbox="allow-same-origin allow-scripts allow-forms allow-popups allow-popups-to-escape-sandbox"
                    />
                </div>
            )}
        </Card>
    );
};