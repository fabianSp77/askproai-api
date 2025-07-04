<?php

namespace App\Services;

use App\Models\User;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laravel\Fortify\RecoveryCode;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorService
{
    protected Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    /**
     * Generate a new secret key for 2FA
     */
    public function generateSecretKey(): string
    {
        return $this->google2fa->generateSecretKey();
    }

    /**
     * Generate QR code for authenticator app
     */
    public function generateQrCode(User $user): string
    {
        $company = config('app.name', 'AskProAI');
        $email = $user->email;
        $secret = decrypt($user->two_factor_secret);

        $qrCodeUrl = $this->google2fa->getQRCodeUrl(
            $company,
            $email,
            $secret
        );

        $renderer = new ImageRenderer(
            new RendererStyle(400),
            new ImagickImageBackEnd()
        );

        $writer = new Writer($renderer);
        
        return 'data:image/png;base64,' . base64_encode($writer->writeString($qrCodeUrl));
    }

    /**
     * Generate recovery codes
     */
    public function generateRecoveryCodes(int $count = 8): array
    {
        return Collection::times($count, fn () => RecoveryCode::generate())->all();
    }

    /**
     * Verify 2FA code
     */
    public function verifyCode(User $user, string $code): bool
    {
        $secret = decrypt($user->two_factor_secret);
        
        return $this->google2fa->verifyKey($secret, $code);
    }

    /**
     * Verify recovery code
     */
    public function verifyRecoveryCode(User $user, string $code): bool
    {
        $recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true);
        
        if (!$recoveryCodes || !in_array($code, $recoveryCodes)) {
            return false;
        }

        // Remove used recovery code
        $recoveryCodes = array_values(array_diff($recoveryCodes, [$code]));
        $user->two_factor_recovery_codes = encrypt(json_encode($recoveryCodes));
        $user->save();

        return true;
    }

    /**
     * Generate manual entry key for users who can't scan QR codes
     */
    public function getManualEntryKey(User $user): string
    {
        return decrypt($user->two_factor_secret);
    }

    /**
     * Format recovery codes for display
     */
    public function formatRecoveryCodes(array $codes): string
    {
        return collect($codes)
            ->map(fn ($code) => chunk_split($code, 4, '-'))
            ->map(fn ($code) => rtrim($code, '-'))
            ->implode("\n");
    }

    /**
     * Send SMS code (placeholder for future implementation)
     */
    public function sendSmsCode(User $user): bool
    {
        if (!$user->two_factor_phone_number || !$user->two_factor_phone_verified) {
            return false;
        }

        // TODO: Implement SMS sending logic via Twilio/Vonage
        // $code = $this->generateNumericCode();
        // $this->storeSmsCode($user, $code);
        // $this->smsProvider->send($user->two_factor_phone_number, "Your 2FA code is: {$code}");

        return true;
    }

    /**
     * Generate numeric code for SMS
     */
    protected function generateNumericCode(int $length = 6): string
    {
        return str_pad((string) random_int(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
    }
}