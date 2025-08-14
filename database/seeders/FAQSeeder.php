<?php

namespace Database\Seeders;

use App\Models\FAQ;
use Illuminate\Database\Seeder;

class FAQSeeder extends Seeder
{
    public function run(): void
    {
        FAQ::create([
            'question' => 'Was ist AskProAI?',
            'answer' => 'AskProAI ist ein KI-Telefonsystem, das Anrufe entgegennimmt und Termine automatisch bucht.',
            'active' => true,
        ]);

        FAQ::create([
            'question' => 'Wie funktioniert die Terminbuchung?',
            'answer' => 'Unsere KI erkennt Terminwünsche und bucht diese automatisch im Kalender der Praxis.',
            'active' => true,
        ]);

        FAQ::create([
            'question' => 'Wofür eignet sich AskProAI besonders?',
            'answer' => 'Besonders geeignet für Praxen, Friseursalons und andere Dienstleister mit hohem Terminaufkommen.',
            'active' => true,
        ]);
    }
}
