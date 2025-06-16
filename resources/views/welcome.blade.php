<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AskProAI</title>
    <style>
        body {
            font-family: system-ui, -apple-system, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            background-color: #f3f4f6;
        }
        .container {
            text-align: center;
        }
        h1 {
            color: #1f2937;
            margin-bottom: 20px;
        }
        a {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 500;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>AskProAI</h1>
        <p>KI-gestützte Telefonassistenz für Praxen und Dienstleister</p>
        <p style="margin-top: 30px;">
            <a href="/admin">Zum Admin-Panel →</a>
        </p>
    </div>
</body>
</html>