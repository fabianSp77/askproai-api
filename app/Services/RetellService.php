<?php
namespace App\Services;

class RetellService
{
    public static function buildInboundResponse(string $agentId, string $caller): array
    {
        // NEUES 5-/30-Schema = Felder auf Root-Level
        return [
            'override_agent_id' => $agentId,
            'dynamic_variables' => [
                'customer_phone' => $caller,
            ],
        ];
    }
}
