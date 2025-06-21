<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Validation\PhoneNumberValidator;
use Illuminate\Support\Facades\Validator;

class ValidationServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register(): void
    {
        // Register PhoneNumberValidator as singleton
        $this->app->singleton(PhoneNumberValidator::class, function ($app) {
            return new PhoneNumberValidator();
        });
    }
    
    /**
     * Bootstrap services
     */
    public function boot(): void
    {
        // Add custom phone validation rule
        Validator::extend('phone', function ($attribute, $value, $parameters, $validator) {
            $phoneValidator = app(PhoneNumberValidator::class);
            
            // Get country parameter if provided
            $country = $parameters[0] ?? null;
            
            // If country is a field name, get its value from the data
            if ($country && $validator->getData()[$country] ?? false) {
                $country = $validator->getData()[$country];
            }
            
            try {
                $result = $phoneValidator->validate($value, $country);
                return $result['valid'];
            } catch (\Exception $e) {
                return false;
            }
        });
        
        // Add phone validation message
        Validator::replacer('phone', function ($message, $attribute, $rule, $parameters) {
            return str_replace(':attribute', $attribute, 'The :attribute must be a valid phone number.');
        });
        
        // Add phone_mobile validation rule
        Validator::extend('phone_mobile', function ($attribute, $value, $parameters, $validator) {
            $phoneValidator = app(PhoneNumberValidator::class);
            
            // Get country parameter if provided
            $country = $parameters[0] ?? null;
            
            // If country is a field name, get its value from the data
            if ($country && $validator->getData()[$country] ?? false) {
                $country = $validator->getData()[$country];
            }
            
            try {
                return $phoneValidator->isMobile($value, $country);
            } catch (\Exception $e) {
                return false;
            }
        });
        
        // Add phone_mobile validation message
        Validator::replacer('phone_mobile', function ($message, $attribute, $rule, $parameters) {
            return str_replace(':attribute', $attribute, 'The :attribute must be a valid mobile phone number.');
        });
        
        // Add phone_country validation rule
        Validator::extend('phone_country', function ($attribute, $value, $parameters, $validator) {
            $phoneValidator = app(PhoneNumberValidator::class);
            
            // Required: list of allowed countries
            if (empty($parameters)) {
                return false;
            }
            
            try {
                $result = $phoneValidator->validate($value);
                if (!$result['valid']) {
                    return false;
                }
                
                // Check if country is in allowed list
                return in_array($result['country'], $parameters);
            } catch (\Exception $e) {
                return false;
            }
        });
        
        // Add phone_country validation message
        Validator::replacer('phone_country', function ($message, $attribute, $rule, $parameters) {
            $countries = implode(', ', $parameters);
            return str_replace(
                [':attribute', ':countries'],
                [$attribute, $countries],
                'The :attribute must be a valid phone number from: :countries.'
            );
        });
        
        // Add phone_normalized validation rule (validates and normalizes)
        Validator::extend('phone_normalized', function ($attribute, $value, $parameters, $validator) {
            $phoneValidator = app(PhoneNumberValidator::class);
            
            // Get country parameter if provided
            $country = $parameters[0] ?? null;
            
            // If country is a field name, get its value from the data
            if ($country && $validator->getData()[$country] ?? false) {
                $country = $validator->getData()[$country];
            }
            
            try {
                $normalized = $phoneValidator->validateForStorage($value, $country);
                
                // Update the value in the validator data
                $data = $validator->getData();
                $data[$attribute] = $normalized;
                $validator->setData($data);
                
                return true;
            } catch (\Exception $e) {
                return false;
            }
        });
        
        // Add phone_normalized validation message
        Validator::replacer('phone_normalized', function ($message, $attribute, $rule, $parameters) {
            return str_replace(':attribute', $attribute, 'The :attribute must be a valid phone number.');
        });
    }
}