<?php

namespace Tests\Feature\Contact;

use App\Models\User;
use App\Modules\Contact\Models\Contact;
use App\Modules\Contact\Models\Customer;
use App\Modules\Contact\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fournisseurs et clients partagent le modèle abstrait Contact : mêmes coordonnées, même recherche.
 */
class ContactModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function suppliers_and_customers_share_the_contact_attributes()
    {
        $this->assertInstanceOf(Contact::class, new Supplier);
        $this->assertInstanceOf(Contact::class, new Customer);
        // Coordonnées communes ; le fournisseur a en plus son délai de livraison
        $this->assertSame([...(new Customer)->getFillable(), 'lead_time_days'], (new Supplier)->getFillable());
        $this->assertSame(['suppliers', 'customers'], [(new Supplier)->getTable(), (new Customer)->getTable()]);
    }

    /** @test */
    public function the_same_search_works_for_both()
    {
        Supplier::factory()->create(['name' => 'Tissus Mada', 'phone' => '020 22 111 11']);
        Supplier::factory()->create(['name' => 'Autre', 'phone' => '020 22 999 99']);
        Customer::factory()->create(['name' => 'Rasoa', 'phone' => '034 00 111 11']);

        $this->assertSame(['Tissus Mada'], Supplier::search('111 11')->pluck('name')->all());
        $this->assertSame(['Rasoa'], Customer::search('111 11')->pluck('name')->all());
        $this->assertSame(['Tissus Mada'], Supplier::search('tissus')->pluck('name')->all(), 'Insensible à la casse');
    }

    /** @test */
    public function suppliers_now_have_notes_and_can_be_found_by_phone()
    {
        $this->actingAs(User::factory()->backOffice()->create());

        $this->post(route('admin.suppliers.store'), [
            'name' => 'Tissus Mada', 'phone' => '020 22 111 11', 'notes' => 'Livre le mardi',
        ])->assertRedirect(route('admin.suppliers.index'));
        // Le service enregistre les noms de fournisseurs en minuscules (comportement existant)
        $supplier = Supplier::where('name', 'tissus mada')->firstOrFail();
        $this->assertSame('Livre le mardi', $supplier->notes);

        $this->put(route('admin.suppliers.update', $supplier->id), [
            'name' => 'Tissus Mada', 'phone' => '020 22 111 11', 'notes' => 'Livre le mardi et le vendredi',
        ]);
        $this->assertSame('Livre le mardi et le vendredi', $supplier->fresh()->notes);

        $this->get(route('admin.suppliers.show', $supplier->id))->assertOk()->assertSee('Livre le mardi et le vendredi');
        $this->get(route('admin.suppliers.index', ['search' => '22 111']))->assertOk()->assertSee('tissus mada');
    }
}
