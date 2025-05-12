document.addEventListener('DOMContentLoaded', function () {
    const token = localStorage.getItem('token');

    async function kundenLaden() {
        const response = await fetch('/api/kunden', {
            headers: {
                'Accept': 'application/json',
                'Authorization': `Bearer ${token}`
            }
        });

        if (!response.ok) {
            document.getElementById('kunde-status').innerText = '❌ Fehler beim Laden der Kunden.';
            return;
        }

        const kunden = await response.json();

        const kundenListe = document.getElementById('kunden-liste');
        kundenListe.innerHTML = '';

        kunden.forEach(kunde => {
            const li = document.createElement('li');
            li.textContent = `${kunde.name} - ${kunde.email} - ${kunde.telefonnummer}`;
            kundenListe.appendChild(li);
        });
    }

document.addEventListener('DOMContentLoaded', function() {
    const token = localStorage.getItem('token');

    if (token) {
        document.getElementById('kunde-status').innerText = '✅ Eingeloggt!';
        kundenLaden();
    } else {
        document.getElementById('kunde-status').innerText = '❌ Nicht eingeloggt.';
    }
});
