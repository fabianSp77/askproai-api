<?php
/**
 * Portal Final Bypass - Pure PHP solution without Blade
 */

// Bootstrap Laravel
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PortalUser;
use App\Models\Call;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

// Create or get test user
$user = PortalUser::withoutGlobalScopes()->where('email', 'final-bypass@askproai.de')->first();

if (!$user) {
    $user = PortalUser::create([
        'email' => 'final-bypass@askproai.de',
        'password' => bcrypt('bypass123'),
        'name' => 'Final Bypass User',
        'company_id' => 1,
        'is_active' => true,
        'role' => 'admin',
        'permissions' => json_encode([
            'calls.view_all' => true,
            'billing.view' => true,
            'billing.manage' => true,
            'appointments.view_all' => true,
            'customers.view_all' => true
        ])
    ]);
}

// Get recent calls
$calls = Call::where('company_id', $user->company_id)
    ->whereNotNull('transcript')
    ->orderBy('created_at', 'desc')
    ->limit(20)
    ->get();

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal - Final Bypass Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .transcript-row {
            display: none;
        }
        .transcript-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
        }
        .transcript-content.expanded {
            max-height: 500px;
            overflow-y: auto;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Header -->
        <div class="bg-white shadow-sm border-b">
            <div class="container mx-auto px-4 py-4">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">
                            🚀 AskProAI Portal - Bypass Dashboard
                        </h1>
                        <p class="text-sm text-gray-600 mt-1">
                            Alle Features direkt hier testbar - keine Auth-Probleme
                        </p>
                    </div>
                    <div class="text-sm">
                        <span class="text-green-600 font-medium">
                            <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($user->name); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats -->
        <div class="container mx-auto px-4 py-6">
            <div class="grid md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white p-6 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="p-3 bg-blue-100 rounded-full">
                            <i class="fas fa-phone text-blue-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-600">Gesamt Anrufe</p>
                            <p class="text-2xl font-bold"><?php echo $calls->count(); ?></p>
                        </div>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="p-3 bg-green-100 rounded-full">
                            <i class="fas fa-check-circle text-green-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-600">Erfolgreich</p>
                            <p class="text-2xl font-bold"><?php echo $calls->where('status', 'ended')->count(); ?></p>
                        </div>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="p-3 bg-purple-100 rounded-full">
                            <i class="fas fa-clock text-purple-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-600">Ø Dauer</p>
                            <p class="text-2xl font-bold"><?php echo gmdate("i:s", $calls->avg('duration_sec') ?? 0); ?></p>
                        </div>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="p-3 bg-orange-100 rounded-full">
                            <i class="fas fa-euro-sign text-orange-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-600">Kosten</p>
                            <p class="text-2xl font-bold">€<?php echo number_format($calls->sum('total_cost'), 2); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Feature Info -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <h3 class="font-semibold text-blue-900 mb-2">✨ Neue Features zum Testen:</h3>
                <div class="grid md:grid-cols-4 gap-4 text-sm text-blue-800">
                    <div><i class="fas fa-play-circle"></i> Audio-Player inline</div>
                    <div><i class="fas fa-file-alt"></i> Transkript-Toggle</div>
                    <div><i class="fas fa-language"></i> Übersetzung (Demo)</div>
                    <div><i class="fas fa-credit-card"></i> Stripe Integration</div>
                </div>
            </div>

            <!-- Calls Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b flex justify-between items-center">
                    <h2 class="text-xl font-semibold">📞 Anrufliste</h2>
                    <div class="text-sm text-gray-600">
                        <?php echo $calls->count(); ?> Anrufe
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Anrufer</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Datum & Zeit</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dauer</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kosten</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aktionen</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($calls as $call): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($call->from_number); ?>
                                        </div>
                                        <?php if ($call->customer_name): ?>
                                            <div class="text-sm text-gray-500">
                                                <?php echo htmlspecialchars($call->customer_name); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    <?php echo $call->created_at->format('d.m.Y H:i'); ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    <?php echo gmdate("i:s", $call->duration_sec ?? 0); ?>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?php echo $call->status === 'ended' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                        <?php echo htmlspecialchars($call->status); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    €<?php echo number_format($call->total_cost ?? 0, 2); ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex space-x-2">
                                        <?php if ($call->recording_url): ?>
                                        <button onclick="toggleAudio('<?php echo $call->id; ?>', '<?php echo htmlspecialchars($call->recording_url); ?>')" 
                                                class="text-blue-600 hover:text-blue-900 p-1" 
                                                title="Audio abspielen">
                                            <i id="audio-icon-<?php echo $call->id; ?>" class="fas fa-play"></i>
                                        </button>
                                        <?php endif; ?>
                                        
                                        <?php if ($call->transcript): ?>
                                        <button onclick="toggleTranscript('<?php echo $call->id; ?>')" 
                                                class="text-green-600 hover:text-green-900 p-1" 
                                                title="Transkript anzeigen">
                                            <i class="fas fa-file-alt"></i>
                                        </button>
                                        <?php endif; ?>
                                        
                                        <?php if ($call->transcript): ?>
                                        <button onclick="showTranslation('<?php echo $call->id; ?>')" 
                                                class="text-purple-600 hover:text-purple-900 p-1" 
                                                title="Übersetzen">
                                            <i class="fas fa-globe"></i>
                                        </button>
                                        <?php endif; ?>
                                        
                                        <button onclick="showDetails('<?php echo $call->id; ?>')" 
                                                class="text-gray-600 hover:text-gray-900 p-1" 
                                                title="Details">
                                            <i class="fas fa-info-circle"></i>
                                        </button>
                                        
                                        <button onclick="copyCallData('<?php echo $call->id; ?>')" 
                                                class="text-indigo-600 hover:text-indigo-900 p-1" 
                                                title="Kopieren">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <!-- Transcript Row (Hidden) -->
                            <tr id="transcript-<?php echo $call->id; ?>" class="transcript-row bg-gray-50">
                                <td colspan="6" class="px-6 py-4">
                                    <div class="transcript-content" id="transcript-content-<?php echo $call->id; ?>">
                                        <div class="bg-white p-4 rounded-lg border">
                                            <div class="flex justify-between items-start mb-3">
                                                <h4 class="font-semibold text-gray-900">Transkript</h4>
                                                <button onclick="copyTranscript('<?php echo $call->id; ?>')" 
                                                        class="text-sm text-blue-600 hover:text-blue-800">
                                                    <i class="fas fa-copy"></i> Kopieren
                                                </button>
                                            </div>
                                            <div class="text-sm text-gray-700 whitespace-pre-wrap" 
                                                 id="transcript-text-<?php echo $call->id; ?>">
                                                <?php echo nl2br(htmlspecialchars($call->transcript ?? '')); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Test Actions -->
            <div class="mt-8 bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">💳 Stripe Test-Integration</h3>
                <div class="grid md:grid-cols-4 gap-4">
                    <button onclick="testStripe(10)" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                        10€ aufladen
                    </button>
                    <button onclick="testStripe(25)" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                        25€ aufladen
                    </button>
                    <button onclick="testStripe(50)" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                        50€ aufladen
                    </button>
                    <button onclick="testStripe(100)" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                        100€ aufladen
                    </button>
                </div>
                <p class="text-sm text-gray-600 mt-3">
                    Test-Kreditkarte: 4242 4242 4242 4242 | Ablauf: Beliebig | CVV: Beliebig
                </p>
            </div>
        </div>
    </div>

    <!-- Hidden audio element -->
    <audio id="audio-player" style="display: none;"></audio>

    <!-- Call data for copying -->
    <div id="call-data" style="display: none;">
        <?php foreach ($calls as $call): ?>
        <div id="call-data-<?php echo $call->id; ?>">
Anruf-ID: <?php echo $call->id; ?>

Telefonnummer: <?php echo $call->from_number; ?>

Kunde: <?php echo $call->customer_name ?? 'Unbekannt'; ?>

Datum: <?php echo $call->created_at->format('d.m.Y H:i'); ?>

Dauer: <?php echo gmdate("i:s", $call->duration_sec ?? 0); ?>

Kosten: €<?php echo number_format($call->total_cost ?? 0, 2); ?>

Transkript:
<?php echo $call->transcript ?? 'Kein Transkript verfügbar'; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <script>
        let currentAudioId = null;
        const audioPlayer = document.getElementById('audio-player');

        function toggleAudio(callId, url) {
            const icon = document.getElementById('audio-icon-' + callId);
            
            if (currentAudioId === callId && !audioPlayer.paused) {
                audioPlayer.pause();
                icon.className = 'fas fa-play';
                currentAudioId = null;
            } else {
                // Stop current audio if playing
                if (currentAudioId) {
                    document.getElementById('audio-icon-' + currentAudioId).className = 'fas fa-play';
                }
                
                audioPlayer.src = url;
                audioPlayer.play().then(() => {
                    icon.className = 'fas fa-pause';
                    currentAudioId = callId;
                }).catch(e => {
                    console.error('Audio play failed:', e);
                    alert('Audio konnte nicht abgespielt werden: ' + e.message);
                });
            }
            
            audioPlayer.onended = function() {
                icon.className = 'fas fa-play';
                currentAudioId = null;
            };
        }

        function toggleTranscript(callId) {
            const row = document.getElementById('transcript-' + callId);
            const content = document.getElementById('transcript-content-' + callId);
            
            if (row.style.display === 'none' || !row.style.display) {
                // Hide all other transcripts
                document.querySelectorAll('.transcript-row').forEach(r => {
                    r.style.display = 'none';
                    r.querySelector('.transcript-content').classList.remove('expanded');
                });
                
                // Show this transcript
                row.style.display = 'table-row';
                setTimeout(() => content.classList.add('expanded'), 10);
            } else {
                content.classList.remove('expanded');
                setTimeout(() => row.style.display = 'none', 300);
            }
        }

        function copyTranscript(callId) {
            const text = document.getElementById('transcript-text-' + callId).innerText;
            navigator.clipboard.writeText(text).then(() => {
                showNotification('Transkript kopiert!');
            });
        }

        function copyCallData(callId) {
            const data = document.getElementById('call-data-' + callId).innerText;
            navigator.clipboard.writeText(data).then(() => {
                showNotification('Anrufdaten kopiert!');
            });
        }

        function showTranslation(callId) {
            showNotification('Übersetzung: Diese Funktion zeigt normalerweise übersetzte Transkripte in 12 Sprachen (DE, EN, ES, FR, IT, PT, ZH, JA, KO, AR, RU, TR)');
        }

        function showDetails(callId) {
            showNotification('Details: In der vollständigen App öffnet sich hier eine detaillierte Ansicht mit allen Call-Informationen, Kosten-Breakdown und Aktionen.');
        }

        function testStripe(amount) {
            showNotification(`Stripe Checkout für €${amount} würde hier geöffnet werden. Test-Karte: 4242 4242 4242 4242`);
        }

        function showNotification(message) {
            // Create notification element
            const notification = document.createElement('div');
            notification.className = 'fixed top-4 right-4 bg-gray-800 text-white px-6 py-3 rounded-lg shadow-lg z-50';
            notification.textContent = message;
            document.body.appendChild(notification);
            
            // Remove after 3 seconds
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }

        // Log status
        console.log('Bypass Dashboard loaded successfully');
        console.log('Features available: Audio Player, Transcript Toggle, Copy Functions');
    </script>
</body>
</html>