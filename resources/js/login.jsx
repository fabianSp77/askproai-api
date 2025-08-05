// Login Page Bundle
import "./bootstrap";
import React from "react";
import ReactDOM from "react-dom/client";

// Login styles
import "../css/app.css";

// Simple login component
function LoginApp() {
    return (
        <div className="min-h-screen flex items-center justify-center bg-gray-50">
            <div className="max-w-md w-full space-y-8">
                <div>
                    <h2 className="mt-6 text-center text-3xl font-extrabold text-gray-900">
                        AskProAI Login
                    </h2>
                </div>
            </div>
        </div>
    );
}

// Initialize login if container exists
const loginContainer = document.getElementById("login-app");
if (loginContainer) {
    const root = ReactDOM.createRoot(loginContainer);
    root.render(<LoginApp />);
}

console.log("AskProAI Login initialized");
