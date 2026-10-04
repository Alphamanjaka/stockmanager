<?php

namespace App\Modules\Contact\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Contact\Http\Requests\StoreCustomerRequest;
use App\Modules\Contact\Models\Customer;
use App\Modules\Contact\Services\CustomerService;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function __construct(protected CustomerService $customerService) {}

    public function index(Request $request)
    {
        $customers = $this->customerService->getAll([
            'search' => $request->get('search'),
            'sort' => $request->get('sort', 'name'),
            'order' => $request->get('order', 'asc'),
            'per_page' => $request->get('per_page', 15),
        ]);

        return view('contact::customers.index', compact('customers'));
    }

    public function create()
    {
        return view('contact::customers.create');
    }

    public function store(StoreCustomerRequest $request)
    {
        $customer = $this->customerService->create($request->validated());

        return redirect()->route('admin.customers.show', $customer->id)
            ->with('success', 'Client créé avec succès.');
    }

    public function show(Customer $customer)
    {
        return view('contact::customers.show', compact('customer'));
    }

    public function edit(Customer $customer)
    {
        return view('contact::customers.edit', compact('customer'));
    }

    public function update(StoreCustomerRequest $request, Customer $customer)
    {
        $customer->update($request->validated());

        return redirect()->route('admin.customers.show', $customer->id)
            ->with('success', 'Client mis à jour avec succès.');
    }

    /**
     * Les ventes du client sont conservées (sales.customer_id passe à NULL).
     */
    public function destroy(Customer $customer)
    {
        $customer->delete();

        return redirect()->route('admin.customers.index')
            ->with('success', 'Client supprimé. Ses ventes sont conservées.');
    }
}
