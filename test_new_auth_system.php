<?php

use App\DTOs\Auth\LoginDTO;
use App\DTOs\Auth\RegisterDTO;
use App\Services\Auth\AuthService;
use App\Services\Tenant\TenantService;

// Test script to demonstrate the new authentication and tenant functionality

// Test 1: Register a new user
$registerDTO = new RegisterDTO(
    name: 'John Doe',
    email: 'john@example.com',
    password: 'password123',
    tenantId: 1,
    role: 'student'
);

$authService = new AuthService(new TenantService());
$registerResponse = $authService->register($registerDTO);

echo "Registration successful:\n";
echo "User ID: " . $registerResponse->user->id . "\n";
echo "Token: " . substr($registerResponse->token, 0, 20) . "...\n";
echo "Message: " . $registerResponse->message . "\n\n";

// Test 2: Login with tenant validation
$loginDTO = new LoginDTO(
    email: 'john@example.com',
    password: 'password123',
    tenantDomain: 'acme-university'
);

$loginResponse = $authService->login($loginDTO);

echo "Login successful:\n";
echo "User ID: " . $loginResponse->user->id . "\n";
echo "Token: " . substr($loginResponse->token, 0, 20) . "...\n";
echo "Message: " . $loginResponse->message . "\n\n";

// Test 3: Get tenant information
$tenantService = new TenantService();
$tenant = $tenantService->findByDomain('acme-university');

echo "Tenant information:\n";
echo "ID: " . $tenant->id . "\n";
echo "Name: " . $tenant->name . "\n";
echo "Domain: " . $tenant->domain . "\n";
echo "Settings: " . json_encode($tenant->settings, JSON_PRETTY_PRINT) . "\n";
