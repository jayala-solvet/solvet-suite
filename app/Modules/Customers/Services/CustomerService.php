<?php

namespace App\Modules\Customers\Services;

use App\Models\User;
use App\Modules\Customers\Models\Customer;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class CustomerService
{
    public function create(User $actor, array $data): Customer
    {
        Gate::forUser($actor)->authorize('write-business');
        $data = Validator::make($data, [
            'name' => 'required|string|max:200', 'tax_id' => 'nullable|string|max:50',
            'contact' => 'nullable|string|max:200', 'email' => 'nullable|email|max:200',
            'phone' => 'nullable|string|max:50', 'address' => 'nullable|string|max:1000',
        ])->validate();

        return Customer::create($data);
    }
}
