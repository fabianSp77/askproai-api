// Passwortschutz
const correctPassword = "AskProAI2025"; // Ändern Sie dies auf Ihr gewünschtes Passwort

document.getElementById('loginForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const password = document.getElementById('password').value;
    
    if (password === correctPassword) {
        document.getElementById('loginContainer').style.display = 'none';
        document.getElementById('dashboardContainer').style.display = 'block';
        localStorage.setItem('isAuthenticated', 'true');
    } else {
        document.getElementById('loginError').style.display = 'block';
    }
});

// Bei Seiten-Reload prüfen, ob bereits authentifiziert
window.addEventListener('DOMContentLoaded', () => {
    if (localStorage.getItem('isAuthenticated') === 'true') {
        document.getElementById('loginContainer').style.display = 'none';
        document.getElementById('dashboardContainer').style.display = 'block';
    }
    
    // Aktuelle Datum anzeigen
    const now = new Date();
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    if (document.getElementById('currentDate')) {
        document.getElementById('currentDate').textContent = now.toLocaleDateString('de-DE', options);
    }
});

// Tab-Navigation
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('nav-link')) {
        e.preventDefault();
        const targetId = e.target.getAttribute('href').substring(1);
        
        // Alle Tabs ausblenden und diesen anzeigen
        document.querySelectorAll('.content-section').forEach(section => {
            section.classList.remove('active');
            section.classList.add('hidden');
        });
        document.getElementById(targetId).classList.remove('hidden');
        document.getElementById(targetId).classList.add('active');
        
        // Navigation-Links aktualisieren
        document.querySelectorAll('.nav-link').forEach(link => {
            link.classList.remove('active', 'bg-gray-900');
            link.classList.add('text-gray-300', 'hover:bg-gray-700', 'hover:text-white');
        });
        e.target.classList.add('active', 'bg-gray-900', 'text-white');
        e.target.classList.remove('text-gray-300', 'hover:bg-gray-700', 'hover:text-white');
    }
});

// Ausloggen
if (document.getElementById('logoutBtn')) {
    document.getElementById('logoutBtn').addEventListener('click', function() {
        localStorage.removeItem('isAuthenticated');
        document.getElementById('loginContainer').style.display = 'flex';
        document.getElementById('dashboardContainer').style.display = 'none';
        document.getElementById('password').value = '';
    });
}
