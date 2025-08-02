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
    
    // Quick fix: Open in new window immediately
    React.useEffect(() => {
        window.open(topupUrl, '_blank');
        if (onClose) {
            onClose();
        }
    }, [topupUrl, onClose]);

    return (
        <Card className={cn("relative overflow-hidden", className)}>
            {/* Quick message */}
            <div className="p-8 text-center">
                <h3 className="text-lg font-semibold mb-4">Guthaben aufladen</h3>
                <p className="text-muted-foreground mb-4">
                    Das Aufladungsformular wurde in einem neuen Tab geöffnet.
                </p>
                <p className="text-sm text-muted-foreground mb-6">
                    Falls sich kein neues Fenster geöffnet hat, klicken Sie bitte auf den Button unten.
                </p>
                <div className="flex gap-3 justify-center">
                    <Button 
                        onClick={() => window.open(topupUrl, '_blank')}
                        className=""
                    >
                        <ExternalLink className="h-4 w-4 mr-2" />
                        Aufladungsformular öffnen
                    </Button>
                    <Button
                        variant="outline"
                        onClick={onClose}
                    >
                        Schließen
                    </Button>
                </div>
            </div>
        </Card>
    );
};