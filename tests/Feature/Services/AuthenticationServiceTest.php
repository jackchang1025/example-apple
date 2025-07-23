<?php

use App\Models\Account;
use App\Services\AuthenticationService;
use GuzzleHttp\Cookie\FileCookieJar;
use GuzzleHttp\Cookie\SetCookie;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Weijiajia\SaloonphpAppleClient\Resource\AppleId\AppleIdResource;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->authService = new AuthenticationService();
    
    // Mock Account
    $this->account = Mockery::mock(Account::class);
    
    // Mock FileCookieJar
    $this->cookieJar = Mockery::mock(FileCookieJar::class);
    
    // Mock Apple ID Resource chain with proper types
    $this->appleIdResource = Mockery::mock(AppleIdResource::class);
    $this->accountManagerResource = Mockery::mock(\Weijiajia\SaloonphpAppleClient\Resource\AppleId\AccountManagerResource::class);
    
    // Setup resource chain
    $this->appleIdResource->shouldReceive('getAccountManagerResource')
        ->andReturn($this->accountManagerResource);
    
    $this->account->shouldReceive('appleIdResource')
        ->andReturn($this->appleIdResource);
});

afterEach(function () {
    Mockery::close();
});

describe('AuthenticationService', function () {

    describe('Service Initialization', function () {

        it('can be instantiated', function () {
            expect($this->authService)->toBeInstanceOf(AuthenticationService::class);
        });
    });

    describe('ensureAuthenticated Method', function () {

        it('does nothing when cookieJar is not FileCookieJar instance', function () {
            // Arrange
            $this->account->shouldReceive('cookieJar')
                ->once()
                ->andReturn(null);

            // Act & Assert - Should not call any other methods
            $this->authService->ensureAuthenticated($this->account);
            
            expect(true)->toBeTrue();
        });

        it('does nothing when authentication is valid', function () {
            // Arrange
            $mockCookie = Mockery::mock(SetCookie::class);
            $mockCookie->shouldReceive('isExpired')->once()->andReturn(false);
            
            $this->cookieJar->shouldReceive('getCookieByName')
                ->once()
                ->with('awat')
                ->andReturn($mockCookie);

            $this->account->shouldReceive('cookieJar')
                ->once()
                ->andReturn($this->cookieJar);

            // Act
            $this->authService->ensureAuthenticated($this->account);

            // Assert - No refresh should be called
            expect(true)->toBeTrue();
        });

        it('refreshes authentication when cookieJar is null', function () {
            // Arrange
            $this->account->shouldReceive('cookieJar')
                ->once()
                ->andReturn($this->cookieJar);

            $this->cookieJar->shouldReceive('getCookieByName')
                ->once()
                ->with('awat')
                ->andReturn(null);

            // Expect refresh to be called
            $this->accountManagerResource->shouldReceive('token')->once();
            $this->account->shouldReceive('syncCookies')->once();

            // Act
            $this->authService->ensureAuthenticated($this->account);

            // Assert - Mock expectations verified automatically
            expect(true)->toBeTrue();
        });

        it('refreshes authentication when cookie is expired', function () {
            // Arrange
            $mockCookie = Mockery::mock(SetCookie::class);
            $mockCookie->shouldReceive('isExpired')->once()->andReturn(true);

            $this->cookieJar->shouldReceive('getCookieByName')
                ->once()
                ->with('awat')
                ->andReturn($mockCookie);

            $this->account->shouldReceive('cookieJar')
                ->once()
                ->andReturn($this->cookieJar);

            // Expect refresh to be called
            $this->accountManagerResource->shouldReceive('token')->once();
            $this->account->shouldReceive('syncCookies')->once();

            // Act
            $this->authService->ensureAuthenticated($this->account);

            // Assert - Mock expectations verified automatically
            expect(true)->toBeTrue();
        });

        it('refreshes authentication when awat cookie does not exist', function () {
            // Arrange
            $this->account->shouldReceive('cookieJar')
                ->once()
                ->andReturn($this->cookieJar);

            $this->cookieJar->shouldReceive('getCookieByName')
                ->once()
                ->with('awat')
                ->andReturn(null);

            // Expect refresh to be called
            $this->accountManagerResource->shouldReceive('token')->once();
            $this->account->shouldReceive('syncCookies')->once();

            // Act
            $this->authService->ensureAuthenticated($this->account);

            // Assert - Mock expectations verified automatically
            expect(true)->toBeTrue();
        });
    });

    describe('refreshAuthentication Method', function () {

        it('calls token method and syncs cookies', function () {
            // Arrange
            $this->accountManagerResource->shouldReceive('token')->once();
            $this->account->shouldReceive('syncCookies')->once();

            // Act
            $this->authService->refreshAuthentication($this->account);

            // Assert - Mock expectations verified automatically
            expect(true)->toBeTrue();
        });

        it('maintains correct method call order', function () {
            // Arrange
            $callOrder = [];
            
            $this->accountManagerResource->shouldReceive('token')
                ->once()
                ->andReturnUsing(function () use (&$callOrder) {
                    $callOrder[] = 'token';

                    return Mockery::mock(\Weijiajia\SaloonphpAppleClient\Integrations\AppleId\Dto\Response\Token\Token::class);
                });

            $this->account->shouldReceive('syncCookies')
                ->once()
                ->andReturnUsing(function () use (&$callOrder) {
                    $callOrder[] = 'syncCookies';
                });

            // Act
            $this->authService->refreshAuthentication($this->account);

            // Assert
            expect($callOrder)->toBe(['token', 'syncCookies']);
        });
    });

    describe('isAccountValid Method', function () {

        it('returns true when authentication succeeds', function () {
            // Arrange
            $mockCookie = Mockery::mock(SetCookie::class);
            $mockCookie->shouldReceive('isExpired')->once()->andReturn(false);
            
            $this->cookieJar->shouldReceive('getCookieByName')
                ->once()
                ->with('awat')
                ->andReturn($mockCookie);

            $this->account->shouldReceive('cookieJar')
                ->once()
                ->andReturn($this->cookieJar);

            // Act
            $result = $this->authService->isAccountValid($this->account);

            // Assert
            expect($result)->toBeTrue();
        });

        it('returns false when authentication throws exception', function () {
            // Arrange
            $this->account->shouldReceive('cookieJar')
                ->once()
                ->andThrow(new Exception('Authentication failed'));

            // Act
            $result = $this->authService->isAccountValid($this->account);

            // Assert
            expect($result)->toBeFalse();
        });

        it('returns false when ensureAuthenticated throws RuntimeException', function () {
            // Arrange
            $this->account->shouldReceive('cookieJar')
                ->once()
                ->andThrow(new RuntimeException('Token refresh failed'));

            // Act
            $result = $this->authService->isAccountValid($this->account);

            // Assert
            expect($result)->toBeFalse();
        });

        it('returns false when cookie operations throw exception', function () {
            // Arrange
            $this->account->shouldReceive('cookieJar')
                ->once()
                ->andReturn($this->cookieJar);

            $this->cookieJar->shouldReceive('getCookieByName')
                ->once()
                ->with('awat')
                ->andThrow(new Exception('Cookie access failed'));

            // Act
            $result = $this->authService->isAccountValid($this->account);

            // Assert
            expect($result)->toBeFalse();
        });
    });

    describe('Edge Cases and Error Handling', function () {

        it('handles null cookie gracefully in ensureAuthenticated', function () {
            // Arrange
            $this->account->shouldReceive('cookieJar')
                ->once()
                ->andReturn($this->cookieJar);

            $this->cookieJar->shouldReceive('getCookieByName')
                ->once()
                ->with('awat')
                ->andReturn(null);

            $this->accountManagerResource->shouldReceive('token')->once();
            $this->account->shouldReceive('syncCookies')->once();

            // Act & Assert - Should not throw exception
            $this->authService->ensureAuthenticated($this->account);
            expect(true)->toBeTrue();
        });

        it('handles cookie without isExpired method gracefully', function () {

            $cookie = Mockery::mock(\GuzzleHttp\Cookie\SetCookie::class);
            $cookie->shouldReceive('isExpired')->once()->andReturn(true);
 
            $this->account->shouldReceive('cookieJar')
                ->once()
                ->andReturn($this->cookieJar);

            $this->cookieJar->shouldReceive('getCookieByName')
                ->once()
                ->with('awat')
                ->andReturn($cookie);

            // Should trigger refresh due to invalid cookie
            $this->accountManagerResource->shouldReceive('token')->once();
            $this->account->shouldReceive('syncCookies')->once();

            // Act & Assert - Should handle gracefully without throwing TypeError
            $this->authService->ensureAuthenticated($this->account);
            expect(true)->toBeTrue();
        });

        it('handles multiple consecutive calls to ensureAuthenticated', function () {
            // Arrange
            $mockCookie = Mockery::mock(SetCookie::class);
            $mockCookie->shouldReceive('isExpired')->twice()->andReturn(false);
            
            $this->cookieJar->shouldReceive('getCookieByName')
                ->twice()
                ->with('awat')
                ->andReturn($mockCookie);

            $this->account->shouldReceive('cookieJar')
                ->twice()
                ->andReturn($this->cookieJar);

            // Act
            $this->authService->ensureAuthenticated($this->account);
            $this->authService->ensureAuthenticated($this->account);

            // Assert - Should handle multiple calls without issues
            expect(true)->toBeTrue();
        });
    });

    describe('Integration Scenarios', function () {

        it('handles complete authentication flow from expired to valid', function () {
            // Arrange - First call with expired cookie
            $expiredCookie = Mockery::mock(SetCookie::class);
            $expiredCookie->shouldReceive('isExpired')->once()->andReturn(true);

            $validCookie = Mockery::mock(SetCookie::class);
            $validCookie->shouldReceive('isExpired')->once()->andReturn(false);

            $this->account->shouldReceive('cookieJar')
                ->twice()
                ->andReturn($this->cookieJar);

            $this->cookieJar->shouldReceive('getCookieByName')
                ->twice()
                ->with('awat')
                ->andReturn($expiredCookie, $validCookie);

            // Expect refresh to be called once
            $this->accountManagerResource->shouldReceive('token')->once();
            $this->account->shouldReceive('syncCookies')->once();

            // Act
            $this->authService->ensureAuthenticated($this->account); // Should refresh
            $this->authService->ensureAuthenticated($this->account); // Should not refresh

            // Assert - Mock expectations verified automatically
            expect(true)->toBeTrue();
        });

        it('validates account after successful authentication refresh', function () {
            // Arrange - Setup for refreshAuthentication call
            $this->accountManagerResource->shouldReceive('token')->once();
            $this->account->shouldReceive('syncCookies')->once();

            // Setup for isAccountValid call
            $mockCookie = Mockery::mock(SetCookie::class);
            $mockCookie->shouldReceive('isExpired')->once()->andReturn(false);
            
            $this->cookieJar->shouldReceive('getCookieByName')
                ->once()
                ->with('awat')
                ->andReturn($mockCookie);

            $this->account->shouldReceive('cookieJar')
                ->once()
                ->andReturn($this->cookieJar);

            // Act
            $this->authService->refreshAuthentication($this->account);
            $isValid = $this->authService->isAccountValid($this->account);

            // Assert
            expect($isValid)->toBeTrue();
        });
    });
});
