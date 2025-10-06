<?php

namespace App\Services\Retell;

use App\Models\Call;
use App\Models\Customer;
use App\ValueObjects\AnonymousCallDetector;
use Illuminate\Support\Facades\Log;

/**
 * AppointmentCustomerResolver
 *
 * Handles customer resolution and creation for appointment bookings.
 * Supports both normal and anonymous callers with intelligent matching.
 *
 * Phase 3: Extracted from RetellFunctionCallHandler
 */
class AppointmentCustomerResolver
{
    /**
     * Ensure customer exists for the call
     *
     * Strategy:
     * - Anonymous callers: Find by name (fuzzy match) or create with placeholder phone
     * - Regular callers: Find by phone number or create new
     * - Auto-link customer to call if not already linked
     *
     * @param Call $call Call record
     * @param string $name Customer name from conversation
     * @param string|null $email Customer email (optional)
     * @return Customer Existing or newly created customer
     */
    public function ensureCustomerFromCall(Call $call, string $name, ?string $email = null): Customer
    {
        $isAnonymous = AnonymousCallDetector::isAnonymous($call);

        if ($isAnonymous) {
            return $this->handleAnonymousCaller($call, $name, $email);
        }

        return $this->handleRegularCaller($call, $name, $email);
    }

    /**
     * Handle anonymous caller (blocked/withheld caller ID)
     *
     * @param Call $call Call record
     * @param string $name Customer name
     * @param string|null $email Customer email
     * @return Customer
     */
    private function handleAnonymousCaller(Call $call, string $name, ?string $email): Customer
    {
        Log::info('📞 Anonymous caller detected - searching by name', [
            'name' => $name,
            'company_id' => $call->company_id,
            'from_number' => $call->from_number,
            'anonymity_reason' => AnonymousCallDetector::getReason($call)
        ]);

        // Try to find customer by name + company (fuzzy match)
        $customer = Customer::where('company_id', $call->company_id)
            ->where(function($query) use ($name) {
                $query->where('name', 'LIKE', '%' . $name . '%')
                      ->orWhere('name', $name);
            })
            ->first();

        if ($customer) {
            Log::info('✅ Found existing customer by name (anonymous call)', [
                'customer_id' => $customer->id,
                'name' => $customer->name,
                'call_id' => $call->id
            ]);

            $this->linkCustomerToCall($call, $customer);
            return $customer;
        }

        // Create new customer with unique phone placeholder
        return $this->createAnonymousCustomer($call, $name, $email);
    }

    /**
     * Handle regular caller (with valid phone number)
     *
     * @param Call $call Call record
     * @param string $name Customer name
     * @param string|null $email Customer email
     * @return Customer
     */
    private function handleRegularCaller(Call $call, string $name, ?string $email): Customer
    {
        // Try to find existing customer by phone number
        $customer = Customer::where('phone', $call->from_number)
            ->where('company_id', $call->company_id)
            ->first();

        if ($customer) {
            Log::info('✅ Found existing customer by phone', [
                'customer_id' => $customer->id,
                'phone' => $call->from_number,
                'call_id' => $call->id
            ]);

            // Update name/email if they've changed
            $updated = false;
            if ($customer->name !== $name && strlen($name) > 2) {
                $customer->name = $name;
                $updated = true;
            }
            if ($email && $customer->email !== $email) {
                $customer->email = $email;
                $updated = true;
            }
            if ($updated) {
                $customer->save();
                Log::info('Updated customer information', [
                    'customer_id' => $customer->id
                ]);
            }

            $this->linkCustomerToCall($call, $customer);
            return $customer;
        }

        // Create new customer
        return $this->createRegularCustomer($call, $name, $email);
    }

    /**
     * Create customer for anonymous caller
     *
     * @param Call $call Call record
     * @param string $name Customer name
     * @param string|null $email Customer email
     * @return Customer
     */
    private function createAnonymousCustomer(Call $call, string $name, ?string $email): Customer
    {
        // Generate unique phone placeholder
        $uniquePhone = 'anonymous_' . time() . '_' . substr(md5($name . $call->id), 0, 8);

        $customer = new Customer();
        $customer->company_id = $call->company_id;
        $customer->forceFill([
            'name' => $name,
            'email' => $email,
            'phone' => $uniquePhone,
            'source' => 'retell_webhook_anonymous',
            'status' => 'active',
            'notes' => '⚠️ Created from anonymous call - phone number unknown'
        ]);
        $customer->save();

        Log::info('✅ New anonymous customer created', [
            'customer_id' => $customer->id,
            'name' => $name,
            'placeholder_phone' => $uniquePhone,
            'company_id' => $customer->company_id,
            'branch_id' => $customer->branch_id,
            'call_id' => $call->id
        ]);

        $this->linkCustomerToCall($call, $customer);
        return $customer;
    }

    /**
     * Create customer for regular caller
     *
     * @param Call $call Call record
     * @param string $name Customer name
     * @param string|null $email Customer email
     * @return Customer
     */
    private function createRegularCustomer(Call $call, string $name, ?string $email): Customer
    {
        $customer = new Customer();
        $customer->company_id = $call->company_id;
        $customer->forceFill([
            'name' => $name,
            'email' => $email,
            'phone' => $call->from_number,
            'source' => 'retell_webhook',
            'status' => 'active'
        ]);
        $customer->save();

        Log::info('✅ New customer created from call', [
            'customer_id' => $customer->id,
            'company_id' => $customer->company_id,
            'phone' => $call->from_number,
            'call_id' => $call->id
        ]);

        $this->linkCustomerToCall($call, $customer);
        return $customer;
    }

    /**
     * Link customer to call if not already linked
     *
     * @param Call $call Call record
     * @param Customer $customer Customer to link
     * @return void
     */
    private function linkCustomerToCall(Call $call, Customer $customer): void
    {
        if (!$call->customer_id) {
            $call->customer_id = $customer->id;
            $call->save();

            Log::info('🔗 Customer linked to call', [
                'customer_id' => $customer->id,
                'call_id' => $call->id
            ]);
        }
    }
}
