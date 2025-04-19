<x-mail::message>
# Kritischer Fehler im AskProAI RetellWebhookController

Ein unerwarteter Fehler ist bei der Verarbeitung eines Retell-Webhooks aufgetreten.

**Fehlermeldung:**<br>
`{{ $errorMessage }}`

**Datei:**<br>
`{{ $errorFile }}:{{ $errorLine }}`

**Retell Call ID:**<br>
`{{ $retellCallId }}`

**Payload (Auszug):**
```json
{{ $payloadSnippet }}
