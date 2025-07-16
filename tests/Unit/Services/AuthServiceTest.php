<?php

namespace Tests\Unit\Services;

use App\Services\AuthService;
use App\DTOs\LoginDTO;
use App\DTOs\RegisterDTO;
use App\DTOs\ChangePasswordDTO;
use App\Models\User;
use App\Models\Tenant;
use App\Exceptions\TenantValidationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;

class AuthServiceTest extends TestCase
{
    use RefreshDatabase;

    private AuthService $authService;
    private Tenant $tenant;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authService = new AuthService();

        // Create a test tenant
        $this->tenant = Tenant::factory()->create([
            'name' => 'Test Tenant',
            'slug' => 'test-tenant',
            'domain' => 'test-tenant.example.com',
            'status' => 'active',
        ]);

        // Create a test user
        $this->user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'tenant_id' => $this->tenant->id,
            'role' => 'admin',
        ]);
    }

    /** @test */
    public function it_can_login_with_valid_credentials()
    {
        $loginDTO = new LoginDTO(
            email: 'test@example.com',
            password: 'password123',
            tenantSlug: 'test-tenant'
        );

        $result = $this->authService->login($loginDTO);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('tenant', $result);
        $this->assertEquals($this->user->id, $result['user']->id);
        $this->assertEquals($this->tenant->id, $result['tenant']->id);
    }

    /** @test */
    public function it_throws_exception_for_invalid_credentials()
    {
        $loginDTO = new LoginDTO(
            email: 'test@example.com',
            password: 'wrongpassword',
            tenantSlug: 'test-tenant'
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid credentials');

        $this->authService->login($loginDTO);
    }

    /** @test */
    public function it_throws_exception_for_invalid_tenant()
    {
        $loginDTO = new LoginDTO(
            email: 'test@example.com',
            password: 'password123',
            tenantSlug: 'invalid-tenant'
        );

        $this->expectException(TenantValidationException::class);
        $this->expectExceptionMessage('Tenant not found or inactive');

        $this->authService->login($loginDTO);
    }

    /** @test */
    public function it_throws_exception_when_user_not_in_tenant()
    {
        // Create another tenant
        $otherTenant = Tenant::factory()->create([
            'name' => 'Other Tenant',
            'slug' => 'other-tenant',
            'domain' => 'other-tenant.example.com',
            'status' => 'active',
        ]);

        $loginDTO = new LoginDTO(
            email: 'test@example.com',
            password: 'password123',
            tenantSlug: 'other-tenant'
        );

        $this->expectException(TenantValidationException::class);
        $this->expectExceptionMessage('User does not have access to this tenant');

        $this->authService->login($loginDTO);
    }

    /** @test */
    public function it_throws_exception_when_tenant_admin_login_fails_validation()
    {
        // Create a user with admin role but in inactive tenant
        $inactiveTenant = Tenant::factory()->create([
            'name' => 'Inactive Tenant',
            'slug' => 'inactive-tenant',
            'domain' => 'inactive-tenant.example.com',
            'status' => 'inactive',
        ]);

        $adminUser = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'tenant_id' => $inactiveTenant->id,
            'role' => 'admin',
        ]);

        $loginDTO = new LoginDTO(
            email: 'admin@example.com',
            password: 'password123',
            tenantSlug: 'inactive-tenant'
        );

        $this->expectException(TenantValidationException::class);
        $this->expectExceptionMessage('Tenant not found or inactive');

        $this->authService->login($loginDTO);
    }

    /** @test */
    public function it_can_register_new_user()
    {
        $registerDTO = new RegisterDTO(
            name: 'New User',
            email: 'newuser@example.com',
            password: 'password123',
            tenantSlug: 'test-tenant',
            role: 'student'
        );

        $result = $this->authService->register($registerDTO);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('tenant', $result);
        $this->assertEquals('New User', $result['user']->name);
        $this->assertEquals('newuser@example.com', $result['user']->email);
        $this->assertEquals('student', $result['user']->role);
        $this->assertEquals($this->tenant->id, $result['user']->tenant_id);
    }

    /** @test */
    public function it_throws_exception_for_duplicate_email_in_tenant()
    {
        $registerDTO = new RegisterDTO(
            name: 'Duplicate User',
            email: 'test@example.com', // Same email as existing user
            password: 'password123',
            tenantSlug: 'test-tenant',
            role: 'student'
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('User already exists in this tenant');

        $this->authService->register($registerDTO);
    }

    /** @test */
    public function it_can_change_user_password()
    {
        Auth::login($this->user);

        $changePasswordDTO = new ChangePasswordDTO(
            currentPassword: 'password123',
            newPassword: 'newpassword123',
            confirmPassword: 'newpassword123'
        );

        $result = $this->authService->changePassword($changePasswordDTO);

        $this->assertTrue($result);

        // Verify password was changed
        $this->user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $this->user->password));
    }

    /** @test */
    public function it_throws_exception_for_invalid_current_password()
    {
        Auth::login($this->user);

        $changePasswordDTO = new ChangePasswordDTO(
            currentPassword: 'wrongpassword',
            newPassword: 'newpassword123',
            confirmPassword: 'newpassword123'
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Current password is incorrect');

        $this->authService->changePassword($changePasswordDTO);
    }

    /** @test */
    public function it_throws_exception_for_password_mismatch()
    {
        Auth::login($this->user);

        $changePasswordDTO = new ChangePasswordDTO(
            currentPassword: 'password123',
            newPassword: 'newpassword123',
            confirmPassword: 'differentpassword'
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('New password confirmation does not match');

        $this->authService->changePassword($changePasswordDTO);
    }

    /** @test */
    public function it_can_logout_user()
    {
        Auth::login($this->user);

        // Create a token for the user
        $token = $this->user->createToken('test-token')->plainTextToken;

        $result = $this->authService->logout();

        $this->assertTrue($result);
        $this->assertNull(Auth::user());
    }

    /** @test */
    public function it_validates_tenant_access_correctly()
    {
        // Test with valid tenant and user
        $this->assertTrue($this->authService->validateTenantAccess($this->user, $this->tenant));

        // Test with user from different tenant
        $otherTenant = Tenant::factory()->create([
            'name' => 'Other Tenant',
            'slug' => 'other-tenant',
            'domain' => 'other-tenant.example.com',
            'status' => 'active',
        ]);

        $this->assertFalse($this->authService->validateTenantAccess($this->user, $otherTenant));
    }

    /** @test */
    public function it_logs_authentication_attempts()
    {
        Log::shouldReceive('info')
            ->once()
            ->with('Authentication attempt', [
                'email' => 'test@example.com',
                'tenant_slug' => 'test-tenant',
                'ip' => request()->ip(),
                'user_agent' => request()->header('User-Agent')
            ]);

        Log::shouldReceive('info')
            ->once()
            ->with('User authenticated successfully', [
                'user_id' => $this->user->id,
                'tenant_id' => $this->tenant->id,
                'role' => 'admin'
            ]);

        $loginDTO = new LoginDTO(
            email: 'test@example.com',
            password: 'password123',
            tenantSlug: 'test-tenant'
        );

        $this->authService->login($loginDTO);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
