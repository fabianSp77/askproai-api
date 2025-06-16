document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    document.getElementById('token').value = urlParams.get('token');

    document.getElementById('reset-form').addEventListener('submit', async function(event) {
        event.preventDefault();

        const token = document.getElementById('token').value;
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;
        const password_confirmation = document.getElementById('password_confirmation').value;

        const response = await fetch('/api/password/reset', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ token, email, password, password_confirmation })
        });

        const result = await response.json();

        if (response.ok) {
            alert('✅ Passwort erfolgreich geändert.');
            window.location.href = '/api-client/index.html';
        } else {
            alert('❌ Fehler: ' + (result.message || 'Unbekannter Fehler.'));
        }
    });
});
