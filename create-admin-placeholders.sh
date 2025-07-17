#!/bin/bash

# Erstelle Platzhalter-Komponenten für alle Admin-Seiten

# Users
cat > /var/www/api-gateway/resources/js/Pages/Admin/Users/Index.jsx << 'EOF'
import React from 'react';
import { Card } from 'antd';

const UsersIndex = () => (
    <div>
        <h1 className="text-2xl font-semibold mb-6">Benutzer</h1>
        <Card>
            <p>Benutzerverwaltung wird hier implementiert...</p>
        </Card>
    </div>
);

export default UsersIndex;
EOF

# Calls
cat > /var/www/api-gateway/resources/js/Pages/Admin/Calls/Index.jsx << 'EOF'
import React from 'react';
import { Card } from 'antd';

const CallsIndex = () => (
    <div>
        <h1 className="text-2xl font-semibold mb-6">Anrufe</h1>
        <Card>
            <p>Anrufverwaltung wird hier implementiert...</p>
        </Card>
    </div>
);

export default CallsIndex;
EOF

# Appointments
cat > /var/www/api-gateway/resources/js/Pages/Admin/Appointments/Index.jsx << 'EOF'
import React from 'react';
import { Card } from 'antd';

const AppointmentsIndex = () => (
    <div>
        <h1 className="text-2xl font-semibold mb-6">Termine</h1>
        <Card>
            <p>Terminverwaltung wird hier implementiert...</p>
        </Card>
    </div>
);

export default AppointmentsIndex;
EOF

# Customers
cat > /var/www/api-gateway/resources/js/Pages/Admin/Customers/Index.jsx << 'EOF'
import React from 'react';
import { Card } from 'antd';

const CustomersIndex = () => (
    <div>
        <h1 className="text-2xl font-semibold mb-6">Kunden</h1>
        <Card>
            <p>Kundenverwaltung wird hier implementiert...</p>
        </Card>
    </div>
);

export default CustomersIndex;
EOF

# System Health
cat > /var/www/api-gateway/resources/js/Pages/Admin/System/Health.jsx << 'EOF'
import React from 'react';
import { Card } from 'antd';

const SystemHealth = () => (
    <div>
        <h1 className="text-2xl font-semibold mb-6">System Status</h1>
        <Card>
            <p>System-Überwachung wird hier implementiert...</p>
        </Card>
    </div>
);

export default SystemHealth;
EOF

# Integrations
cat > /var/www/api-gateway/resources/js/Pages/Admin/Integrations/Index.jsx << 'EOF'
import React from 'react';
import { Card } from 'antd';

const IntegrationsIndex = () => (
    <div>
        <h1 className="text-2xl font-semibold mb-6">Integrationen</h1>
        <Card>
            <p>Integrationsverwaltung wird hier implementiert...</p>
        </Card>
    </div>
);

export default IntegrationsIndex;
EOF

echo "✅ Alle Platzhalter-Komponenten erstellt!"