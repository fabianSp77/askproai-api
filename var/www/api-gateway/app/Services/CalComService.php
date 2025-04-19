<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Service;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CalComService implements CalendarServiceInterface
{
    protected $baseUrl;
    protected $apiKey;
    protected $customer;

    public function __construct(Customer $customer = null)
    {
        $this->baseUrl = 'https://api.cal.com/v1';
        $this->customer = $customer;

        if ($customer) {
            $this->apiKey = $customer->api_key;
        }
    }

    public function checkAvailability(array $data)
    {
        $apiKey = $this->apiKey ?? $data['apiKey'] ?? null;

        if (!$apiKey) {
            return ['error' => 'API key is required'];
        }

        $response = Http::get("{$this->baseUrl}/availability", array_merge([
            'apiKey' => $apiKey
        ], $data));

        return $response->json();
    }

    public function getAvailableSlots(array $data)
    {
        $apiKey = $this->apiKey ?? $data['apiKey'] ?? null;

        if (!$apiKey) {
            return ['error' => 'API key is required'];
        }

        $userId = $data['userId'] ?? $this->customer?->default_user_id;

        $response = Http::get("{$this->baseUrl}/slots", array_merge([
            'apiKey' => $apiKey,
            'userId' => $userId
        ], $data));

        return $response->json();
    }

    public function bookAppointment(array $data)
    {
        $apiKey = $this->apiKey ?? $data['apiKey'] ?? null;

        if (!$apiKey) {
            return ['error' => 'API key is required'];
        }

        $service = Service::where('customer_id', $this->customer->id)
            ->where('external_event_type_id', $data['eventTypeId'])
            ->first();

        if ($service) {
            $startTime = new \DateTime($data['start']);
            $endTime = clone $startTime;
            $endTime->modify("+{$service->duration} minutes");
            $data['end'] = $endTime->format('Y-m-d\TH:i:s.u\Z');
        }

        $bookingData = [
            'eventTypeId' => $data['eventTypeId'],
            'start' => $data['start'],
            'end' => $data['end'],
            'timeZone' => $data['timeZone'],
            'language' => 'de',
            'metadata' => new \stdClass(),
            'responses' => [
                'name' => $data['name'],
                'email' => $data['email'],
                'guests' => [],
                'location' => ['optionValue' => '', 'value' => 'Vor Ort'],
                'notes' => $data['notes'] ?? '',
            ]
        ];

        $response = Http::post("{$this->baseUrl}/bookings?apiKey={$apiKey}", $bookingData);

        return $response->json();
    }
}
