// Token aus LocalStorage holen
const token = localStorage.getItem('token');

// Funktion, um neuen Kunden anzulegen
async function kundeHinzufuegen() {
    const name = document.getElementById('kunde-name').value;
    const email = document.getElementById('kunde-email').value;
    const telefonnummer = document.getElementById('kunde-telefonnummer').value;

    const response = await fetch('/api/kunden', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify({ name, email, telefonnummer })
    });

    const result = await response.json();

    if (response.ok) {
        alert('✅ Kunde erfolgreich erstellt.');
    } else {
        alert('❌ Fehler: ' + (result.message || 'Unbekannter Fehler.'));
    }
}

// Alle Kunden laden und anzeigen
async function kundenLaden() {
    const response = await fetch('/api/kunden', {
        headers: {
            'Accept': 'application/json',
            'Authorization': `Bearer ${token}`
        }
    });

    const result = await response.json();

    if (response.ok) {
        document.getElementById('kunden-liste').innerHTML = result.map(
            kunde => `<div>${kunde.name} | ${kunde.email} | ${kunde.telefonnummer}</div>`
        ).join('');
    } else {
        alert('❌ Fehler beim Laden: ' + (result.message || 'Unbekannter Fehler.'));
    }
}
