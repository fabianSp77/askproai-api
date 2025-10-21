# ⏰ Terminwunsch konnte nicht erfüllt werden

Hallo,

ein Kunde hat einen Terminwunsch geäußert, der leider nicht automatisch gebucht werden konnte.

## 👤 Kundeninformationen

**Name:** {{ $customer?->name ?? 'Unbekannt' }}
**Telefon:** {{ $customer?->phone ?? 'N/A' }}
**E-Mail:** {{ $customer?->email ?? 'N/A' }}

---

## ⏰ Terminwunsch Details

**Gewünschter Termin:** {{ $formattedDesiredTime }}
**Dauer:** {{ $wish->desired_duration }} Minuten
**Service:** {{ $wish->desired_service ?? 'Nicht angegeben' }}

**Grund der Ablehnung:**
{{ $rejectionReason }}

---

## 📋 Angebotene Alternativen

@if(!empty($alternatives))
@foreach($alternatives as $alt)
- **{{ $alt['datetime'] ?? 'N/A' }}** {{ $alt['type'] ? '(' . $alt['type'] . ')' : '' }}
@endforeach
@else
_Keine Alternativen verfügbar_
@endif

---

## 🎬 Empfohlene Maßnahmen

1. **Kunde anrufen** und ein passendes Zeitfenster erfragen
2. **Termin manuell buchen** wenn Kunde sich auf Alternative einigt
3. **Feedback dokumentieren** für AI-Agent Verbesserungen

---

## 🔗 Schnelle Aktionen

[📞 Anruf öffnen]({{ $callUrl }})

---

_Diese E-Mail wurde automatisch generiert. Bitte antworten Sie nicht auf diese E-Mail._

**AskPro AI Gateway** | Terminwunsch-Tracking System
