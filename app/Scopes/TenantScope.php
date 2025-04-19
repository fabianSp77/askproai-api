<?php // app/Scopes/TenantScope.php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use App\Models\Tenant;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth; // Für Admin-Check (optional)
use App\Models\User; // Für Admin-Check (optional)


class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        // Prüfe, ob ein Tenant im Container ist UND wir nicht gerade auf der Konsole sind
        // (In Artisan-Commands wollen wir den Scope oft nicht automatisch anwenden)
        if (App::has(Tenant::class) && !App::runningInConsole()) {
            $tenant = App::make(Tenant::class);

            // --- Optional: Ausnahme für Super-Admin (Hier Logik anpassen!) ---
            // Diese Logik muss auf eurem User/Rollen-System basieren
            $isSuperAdmin = false;
            if (Auth::check()) {
                $user = Auth::user();
                // Beispiel: Prüfe, ob User eine bestimmte Rolle oder Eigenschaft hat
                // if ($user instanceof User && $user->hasRole('super-admin')) {
                //     $isSuperAdmin = true;
                // }
                // Beispiel: Prüfe auf eine bestimmte User-ID
                // if ($user instanceof User && $user->id === 1) {
                //     $isSuperAdmin = true;
                // }
            }
            // --- Ende Admin-Ausnahme ---

            // Scope nur anwenden, wenn es KEIN Super-Admin ist
            if (!$isSuperAdmin) {
                $builder->where($model->getTable().'.tenant_id', $tenant->id);
            }
            // Wenn es ein Super-Admin ist, wird keine Where-Klausel hinzugefügt

        } elseif (!App::runningInConsole()) {
            // Wenn KEIN Tenant im Container ist (und wir nicht auf der Konsole sind),
            // sollten Anfragen normalerweise fehlschlagen (durch Middleware).
            // Sicherster Fallback: Keine Daten zurückgeben.
            Log::warning('TenantScope applied but no tenant found in container.', ['model' => get_class($model)]);
            $builder->whereRaw('1 = 0');
        }
        // Wenn wir auf der Konsole sind (App::runningInConsole() === true),
        // wird der Scope standardmäßig NICHT angewendet.
        // Das erlaubt Artisan Commands, alle Daten zu sehen (z.B. für Migrationen).
        // Man kann ihn in Commands bei Bedarf manuell anwenden: Model::withoutGlobalScope(TenantScope::class)->...
    }
}
