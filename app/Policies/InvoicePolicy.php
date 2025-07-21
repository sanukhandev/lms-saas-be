<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Invoice;

class InvoicePolicy
{
    public function view(User $user, Invoice $invoice): bool
    {
        return $user->role === 'super_admin' || $user->tenant_id === $invoice->tenant_id;
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $user->role === 'super_admin' || $user->tenant_id === $invoice->tenant_id;
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $user->role === 'super_admin' || $user->tenant_id === $invoice->tenant_id;
    }
} 