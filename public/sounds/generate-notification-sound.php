#!/usr/bin/env php
<?php
// Generate a simple notification sound

// Create a simple sine wave beep sound
$sampleRate = 44100;
$frequency = 800; // Hz
$duration = 0.3; // seconds
$amplitude = 0.5;

$numSamples = $sampleRate * $duration;
$samples = [];

// Generate sine wave
for ($i = 0; $i < $numSamples; $i++) {
    $t = $i / $sampleRate;
    $samples[] = $amplitude * sin(2 * M_PI * $frequency * $t);
}

// Add envelope for smooth start/end
$fadeInSamples = $sampleRate * 0.05; // 50ms fade in
$fadeOutSamples = $sampleRate * 0.05; // 50ms fade out

for ($i = 0; $i < $fadeInSamples; $i++) {
    $samples[$i] *= $i / $fadeInSamples;
}

for ($i = 0; $i < $fadeOutSamples; $i++) {
    $idx = $numSamples - $fadeOutSamples + $i;
    $samples[$idx] *= ($fadeOutSamples - $i) / $fadeOutSamples;
}

// WAV file header
function createWavHeader($dataSize, $sampleRate) {
    $header = 'RIFF';
    $header .= pack('V', 36 + $dataSize); // File size - 8
    $header .= 'WAVE';
    $header .= 'fmt ';
    $header .= pack('V', 16); // Subchunk1Size
    $header .= pack('v', 1); // AudioFormat (PCM)
    $header .= pack('v', 1); // NumChannels
    $header .= pack('V', $sampleRate); // SampleRate
    $header .= pack('V', $sampleRate * 2); // ByteRate
    $header .= pack('v', 2); // BlockAlign
    $header .= pack('v', 16); // BitsPerSample
    $header .= 'data';
    $header .= pack('V', $dataSize);
    return $header;
}

// Convert samples to 16-bit PCM
$pcmData = '';
foreach ($samples as $sample) {
    $intSample = intval($sample * 32767);
    $pcmData .= pack('s', $intSample);
}

// Write WAV file
$wavData = createWavHeader(strlen($pcmData), $sampleRate) . $pcmData;
file_put_contents(__DIR__ . '/notification.wav', $wavData);

echo "Generated notification.wav\n";

// Convert to MP3 and OGG if tools available
if (shell_exec('which ffmpeg')) {
    echo "Converting to MP3...\n";
    exec('ffmpeg -y -i ' . __DIR__ . '/notification.wav -acodec mp3 -ab 128k ' . __DIR__ . '/notification.mp3 2>&1');
    
    echo "Converting to OGG...\n";
    exec('ffmpeg -y -i ' . __DIR__ . '/notification.wav -acodec libvorbis -ab 128k ' . __DIR__ . '/notification.ogg 2>&1');
    
    echo "Done! Created notification.mp3 and notification.ogg\n";
} else {
    echo "ffmpeg not found. Only WAV file created.\n";
    echo "To create MP3 and OGG files, install ffmpeg:\n";
    echo "  apt-get install ffmpeg\n";
}