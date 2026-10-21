<?php

namespace App\Modules\Contact\Services;

use App\Core\Services\BaseService;
use App\Modules\Contact\Models\Customer;
use Illuminate\Support\Collection;

class CustomerService extends BaseService
{
    protected array $sortable = ['name', 'id', 'created_at'];

    public function __construct(Customer $customer)
    {
        parent::__construct($customer);
    }

    /**
     * Recherche les clients pour la recherche globale.
     */
    public function searchForGlobalSearch(string $query, int $limit = 5): Collection
    {
        return Customer::search($query)
            ->limit($limit)
            ->get()
            ->map(fn (Customer $customer) => [
                'type' => 'customer',
                'id' => $customer->id,
                'name' => $customer->name.($customer->phone ? " ({$customer->phone})" : ''),
                'url' => route('admin.customers.show', $customer->id),
            ]);
    }
}
