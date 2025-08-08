# Pricing Tier System - Logik Erklärung

## Warum können für manche Companies keine Preise hinterlegt werden?

### Das Reseller-Client-Modell

Das System unterscheidet zwischen drei Company-Typen:

1. **`standalone`** - Eigenständige Firmen
   - Direkte Kunden ohne Vermittler
   - Zahlen Standard-Preise
   - KEINE individuellen Pricing Tiers

2. **`reseller`** - Vermittler/Wiederverkäufer
   - Bringen eigene Kunden mit
   - Können eigene Preise für ihre Kunden definieren
   - Erhalten Einkaufspreise vom System

3. **`client`** - Kunden eines Resellers
   - Gehören zu einem Reseller (parent_company_id)
   - Erhalten individuelle Preise vom Reseller
   - Können Pricing Tiers haben

### Beispiel-Struktur:
```
Premium Telecom (Reseller)
├── Friseur Schmidt (Client) → 0,40€/Min
├── Dr. Müller (Client) → 0,45€/Min
└── Restaurant Vista (Client) → 0,35€/Min
```

### Krückeberg Servicegruppe (ID: 1)
- **Typ**: `standalone`
- **Status**: Eigenständiger Kunde
- **Pricing**: Nutzt Standard-Systempreise
- **Pricing Tiers**: ❌ Nicht möglich

## Lösungsoptionen

### Option 1: Company-Typ ändern (für Reseller-Modell)
```sql
-- Macht Krückeberg zum Reseller
UPDATE companies SET company_type = 'reseller' WHERE id = 1;
```

### Option 2: Standard-Preise im Company-Model
```php
// In app/Models/Company.php
// Feld: price_per_minute (bereits vorhanden)
```

### Option 3: Neues Pricing-System für Standalone
Erweitern Sie das System um eine neue Tabelle:
```sql
CREATE TABLE standalone_pricing (
    company_id INT PRIMARY KEY,
    inbound_price DECIMAL(10,4),
    outbound_price DECIMAL(10,4),
    -- etc.
);
```

## Aktuelle Implementierung

Die Pricing Tiers sind ausschließlich für das Reseller-Client-Modell konzipiert:
- **Reseller** definiert Preise für seine **Clients**
- **Standalone** Companies nutzen Systempreise
- Dies ermöglicht Margin-Berechnungen (Einkauf vs. Verkauf)

## Empfehlung

Für eigenständige Firmen wie Krückeberg:
1. Nutzen Sie das `price_per_minute` Feld im Company-Model
2. Oder erweitern Sie das System um Standalone-Pricing
3. Oder konvertieren Sie zu einem Reseller-Modell

Die aktuelle Implementierung ist optimiert für Vermittler-Szenarien, wo Margins wichtig sind.