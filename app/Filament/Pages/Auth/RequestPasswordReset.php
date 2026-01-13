<?php

namespace App\Filament\Pages\Auth;

use App\Notifications\AdminPasswordResetNotification;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Exception;
use Filament\Facades\Filament;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\PasswordReset\RequestPasswordReset as BaseRequestPasswordReset;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Log;

class RequestPasswordReset extends BaseRequestPasswordReset
{
    /**
     * Mount the component and prefill email from query parameter.
     */
    public function mount(): void
    {
        parent::mount();

        // Prefill email from query parameter if provided
        $email = request()->query('email');
        if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->form->fill([
                'email' => $email,
            ]);
        }
    }

    public function getTitle(): string|Htmlable
    {
        return __('Passwort zurücksetzen');
    }

    public function getHeading(): string|Htmlable
    {
        return __('Passwort vergessen?');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return __('Geben Sie Ihre E-Mail-Adresse ein, um einen Link zum Zurücksetzen zu erhalten.');
    }

    /**
     * Customize email form component with German placeholder.
     */
    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label(__('E-Mail-Adresse'))
            ->email()
            ->required()
            ->autocomplete('email')
            ->autofocus()
            ->placeholder('name@unternehmen.de')
            ->maxLength(255);
    }

    /**
     * Override request() to use custom notification with professional email template.
     */
    public function request(): void
    {
        try {
            // Rate limit: 2 attempts per 5 minutes
            $this->rateLimit(2, 300);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();
            return;
        }

        $data = $this->form->getState();

        // Log the password reset request
        Log::info('[PasswordReset] Reset requested', [
            'email' => $data['email'] ?? 'unknown',
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        $status = Password::broker(Filament::getAuthPasswordBroker())->sendResetLink(
            $data,
            function (CanResetPassword $user, string $token): void {
                if (! method_exists($user, 'notify')) {
                    $userClass = $user::class;
                    throw new Exception("Model [{$userClass}] does not have a [notify()] method.");
                }

                // Build the reset URL
                $url = Filament::getResetPasswordUrl($token, $user);

                // Send custom notification with professional email template
                $user->notify(new AdminPasswordResetNotification($token, $url));
            },
        );

        // Always show generic success message to prevent email enumeration
        Notification::make()
            ->title(__('E-Mail gesendet'))
            ->body(__('Falls ein Konto mit dieser E-Mail-Adresse existiert, haben wir Ihnen einen Link zum Zurücksetzen gesendet.'))
            ->success()
            ->send();

        $this->form->fill();
    }

    /**
     * Custom rate limited notification in German.
     */
    protected function getRateLimitedNotification(TooManyRequestsException $exception): ?Notification
    {
        return Notification::make()
            ->title(__('Zu viele Anfragen'))
            ->body(__('Bitte warten Sie :minutes Minuten, bevor Sie es erneut versuchen.', [
                'minutes' => ceil($exception->secondsUntilAvailable / 60),
            ]))
            ->danger()
            ->persistent();
    }
}
