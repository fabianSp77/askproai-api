<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PhoneNumber;
use App\Models\Company;
use App\Models\Branch;
use Illuminate\Support\Str;

class PhoneNumberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        echo "📞 Creating phone numbers for Retell integration...\n";

        // AskProAI Phone Number
        $askproai = Company::where('slug', 'askproai')->first();

        if (!$askproai) {
            echo "❌ AskProAI company not found. Please run CompanySeeder first.\n";
            return;
        }

        $askproaiBranch = Branch::where('company_id', $askproai->id)->first();

        if (!$askproaiBranch) {
            echo "❌ AskProAI branch not found. Please ensure branches are seeded.\n";
            return;
        }

        PhoneNumber::create([
            'id' => (string) Str::uuid(),
            'company_id' => $askproai->id,
            'branch_id' => $askproaiBranch->id,
            'phone_number' => '+493083793369',
            'number_normalized' => '+493083793369',
            'retell_agent_id' => $askproai->retell_agent_id,
            'agent_id' => $askproai->retell_agent_id,
            'type' => 'hotline',
            'is_active' => true,
            'is_primary' => true,
            'friendly_name' => 'AskProAI Hauptnummer',
            'description' => 'Haupthotline für Kundenanfragen - Retell AI Integration',
            'provider' => 'retell',
            'country_code' => '+49',
        ]);

        echo "   ✅ AskProAI: +493083793369 (Agent: {$askproai->retell_agent_id})\n";

        // Friseur 1 Phone Number
        $friseur = Company::where('slug', 'friseur-1')->first();

        if (!$friseur) {
            echo "❌ Friseur 1 company not found. Please run CompanySeeder first.\n";
            return;
        }

        $friseurBranch = Branch::where('company_id', $friseur->id)
            ->where('slug', 'friseur-1-zentrale')
            ->first();

        if (!$friseurBranch) {
            echo "⚠️  Friseur 1 Zentrale branch not found, using first branch.\n";
            $friseurBranch = Branch::where('company_id', $friseur->id)->first();
        }

        if (!$friseurBranch) {
            echo "❌ Friseur 1 branch not found. Please ensure branches are seeded.\n";
            return;
        }

        PhoneNumber::create([
            'id' => (string) Str::uuid(),
            'company_id' => $friseur->id,
            'branch_id' => $friseurBranch->id,
            'phone_number' => '+493033081738',
            'number_normalized' => '+493033081738',
            'retell_agent_id' => $friseur->retell_agent_id,
            'agent_id' => $friseur->retell_agent_id,
            'type' => 'hotline',
            'is_active' => true,
            'is_primary' => true,
            'friendly_name' => 'Friseur 1 Zentrale',
            'description' => 'Hauptnummer Zentrale - Retell AI Integration',
            'provider' => 'retell',
            'country_code' => '+49',
        ]);

        echo "   ✅ Friseur 1: +493033081738 (Agent: {$friseur->retell_agent_id})\n";

        echo "\n✅ Phone numbers created successfully!\n";
        echo "   Total: 2 phone numbers\n";
        echo "   Both configured for Retell webhook integration\n\n";
    }
}
