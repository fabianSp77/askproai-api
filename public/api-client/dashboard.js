document.addEventListener('DOMContentLoaded', function() {
    kundenLaden();
});

async function kundenLaden() {
    const token = localStorage.getItem('token');
    const kundenListe = document.getElementById('kunden-liste');
    kundenListe.innerHTML = '';

    if (!token) {
        kundenListe.innerHTML = '<li class="list-group-item list-group-item-danger">Token nicht gefunden. Bitte erneut einloggen.</li>';
        return;
    }

    const response = await fetch('https://v2202503255565320322.happysrv.de/api/kunden', {
        headers: {
            'Accept': 'application/json',
            'Authorization': `Bearer ${token}`
        }
    });

    if (response.ok) {
        const kunden = await response.json();
        kunden.forEach(kunde => {
            const li = document.createElement('li');
            li.className = 'list-group-item';
            li.textContent = `${kunde.name} - ${kunde.email} - ${kunde.telefonnummer}`;
            kundenListe.appendChild(li);
        });
    } else if (response.status === 401) {
        kundenListe.innerHTML = '<li class="list-group-item list-group-item-danger">Nicht authentifiziert. Bitte erneut einloggen.</li>';
    } else {
        kundenListe.innerHTML = '<li class="list-group-item list-group-item-danger">Fehler beim Laden der Kunden.</li>';
    }
}
