@extends('portal.layouts.auth')

@section('title', 'Business Portal Login')

@section('content')
<style>
    .login-container {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #f3f4f6;
        padding: 3rem 1rem;
    }
    .login-box {
        max-width: 28rem;
        width: 100%;
        background: white;
        padding: 2rem;
        border-radius: 0.5rem;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
    }
    .login-header {
        text-align: center;
        margin-bottom: 2rem;
    }
    .login-title {
        font-size: 1.875rem;
        font-weight: 800;
        color: #111827;
        margin: 0 0 0.5rem 0;
    }
    .login-subtitle {
        font-size: 0.875rem;
        color: #6b7280;
    }
    .demo-info {
        background-color: #dbeafe;
        border: 1px solid #93c5fd;
        border-radius: 0.375rem;
        padding: 1rem;
        margin-bottom: 1.5rem;
    }
    .demo-info strong {
        color: #1e40af;
    }
    .error-message {
        background-color: #fee2e2;
        border: 1px solid #fca5a5;
        border-radius: 0.375rem;
        padding: 1rem;
        margin-bottom: 1.5rem;
        color: #991b1b;
    }
    .form-group {
        margin-bottom: 1rem;
    }
    .form-label {
        display: block;
        font-size: 0.875rem;
        font-weight: 500;
        color: #374151;
        margin-bottom: 0.25rem;
    }
    .form-input {
        display: block;
        width: 100%;
        padding: 0.5rem 0.75rem;
        font-size: 1rem;
        line-height: 1.5;
        color: #111827;
        background-color: white;
        border: 1px solid #d1d5db;
        border-radius: 0.375rem;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }
    .form-input:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    .checkbox-group {
        display: flex;
        align-items: center;
        margin-bottom: 1.5rem;
    }
    .checkbox-input {
        width: 1rem;
        height: 1rem;
        color: #3b82f6;
        border-radius: 0.25rem;
        margin-right: 0.5rem;
    }
    .checkbox-label {
        font-size: 0.875rem;
        color: #374151;
    }
    .submit-button {
        width: 100%;
        padding: 0.75rem 1rem;
        font-size: 1rem;
        font-weight: 500;
        color: white;
        background-color: #3b82f6;
        border: none;
        border-radius: 0.375rem;
        cursor: pointer;
        transition: background-color 0.15s ease-in-out;
    }
    .submit-button:hover {
        background-color: #2563eb;
    }
    .submit-button:disabled {
        opacity: 0.75;
        cursor: not-allowed;
    }
    .loading-spinner {
        display: inline-block;
        width: 1rem;
        height: 1rem;
        border: 2px solid #f3f4f6;
        border-radius: 50%;
        border-top-color: #3b82f6;
        animation: spin 1s linear infinite;
    }
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
</style>

<div class="login-container">
    <div class="login-box">
        <div class="login-header">
            <h2 class="login-title">Business Portal</h2>
            <p class="login-subtitle">Melden Sie sich an, um auf Ihr Unternehmenskonto zuzugreifen</p>
        </div>

        @if(config('app.debug'))
        <div class="demo-info">
            <strong>Demo Login:</strong><br>
            Email: demo@askproai.de<br>
            Passwort: password
        </div>
        @endif

        @if ($errors->any())
        <div class="error-message">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
        @endif

        <form action="{{ route('business.login.post') }}" method="POST" id="login-form">
            @csrf
            
            <div class="form-group">
                <label for="email" class="form-label">E-Mail-Adresse</label>
                <input 
                    id="email" 
                    name="email" 
                    type="email" 
                    class="form-input" 
                    value="{{ old('email', 'demo@askproai.de') }}"
                    required 
                    autofocus
                >
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Passwort</label>
                <input 
                    id="password" 
                    name="password" 
                    type="password" 
                    class="form-input"
                    value="password"
                    required
                >
            </div>

            <div class="checkbox-group">
                <input 
                    id="remember" 
                    name="remember" 
                    type="checkbox" 
                    class="checkbox-input"
                >
                <label for="remember" class="checkbox-label">
                    Angemeldet bleiben
                </label>
            </div>

            <button type="submit" class="submit-button" id="submit-button">
                <span id="button-text">Anmelden</span>
                <span id="button-spinner" style="display: none;">
                    <span class="loading-spinner"></span>
                </span>
            </button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('login-form');
    const submitButton = document.getElementById('submit-button');
    const buttonText = document.getElementById('button-text');
    const buttonSpinner = document.getElementById('button-spinner');
    
    form.addEventListener('submit', function(e) {
        // Show loading state
        submitButton.disabled = true;
        buttonText.style.display = 'none';
        buttonSpinner.style.display = 'inline-block';
        
        // Re-enable after 5 seconds if form doesn't submit
        setTimeout(function() {
            submitButton.disabled = false;
            buttonText.style.display = 'inline';
            buttonSpinner.style.display = 'none';
        }, 5000);
    });
});
</script>
@endsection