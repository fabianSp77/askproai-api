
// Force Auth for Demo
window.__DEMO_MODE__ = true;
window.__SKIP_AUTH__ = true;
localStorage.setItem("portal_auth", "true");
localStorage.setItem("portal_user", JSON.stringify({
    id: 1, name: "Demo User", email: "demo@askproai.de", company_id: 1, role: "admin"
}));

// Override fetch
const originalFetch = window.fetch;
window.fetch = function(url, options = {}) {
    if (url.includes("/api/user") || url.includes("/api/auth")) {
        return Promise.resolve({
            ok: true,
            json: () => Promise.resolve({ authenticated: true, user: { id: 1, name: "Demo User" } })
        });
    }
    if (url.includes("/logout")) {
        return Promise.resolve({ ok: true });
    }
    return originalFetch(url, options);
};
