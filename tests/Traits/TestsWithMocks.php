<?php

namespace Tests\Traits;

use Tests\Mocks\CalcomServiceMock;
use Tests\Mocks\RetellServiceMock;
use Tests\Mocks\StripeServiceMock;
use Tests\Mocks\EmailServiceMock;
use App\Services\CalcomService;
use App\Services\RetellService;
use App\Services\StripeService;
use App\Services\EmailService;

trait TestsWithMocks
{
    protected function mockExternalServices(): void
    {
        $this->mockCalcom();
        $this->mockRetell();
        $this->mockStripe();
        $this->mockEmail();
    }
    
    protected function mockCalcom(): void
    {
        $this->app->singleton(CalcomService::class, function () {
            return new CalcomServiceMock();
        });
    }
    
    protected function mockRetell(): void
    {
        $this->app->singleton(RetellService::class, function () {
            return new RetellServiceMock();
        });
    }
    
    protected function mockStripe(): void
    {
        $this->app->singleton(StripeService::class, function () {
            return new StripeServiceMock();
        });
    }
    
    protected function mockEmail(): void
    {
        $this->app->singleton(EmailService::class, function () {
            return new EmailServiceMock();
        });
    }
    
    protected function getEmailMock(): EmailServiceMock
    {
        return app(EmailService::class);
    }
}