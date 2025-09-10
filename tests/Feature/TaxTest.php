<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Tax;
use App\Models\User;

class TaxTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_can_list_taxes()
    {
        // Create some taxes
        Tax::factory(3)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/financial-management/taxes');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'code',
                        'rate',
                        'formatted_rate',
                        'description',
                        'active',
                        'status',
                        'created_at',
                        'updated_at'
                    ]
                ],
                'meta'
            ]);
    }

    /** @test */
    public function it_can_create_a_tax()
    {
        $taxData = [
            'name' => 'Test Tax',
            'code' => 'TEST_TAX',
            'rate' => 15.00,
            'description' => 'Test tax description',
            'active' => true
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/financial-management/taxes', $taxData);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'name' => 'Test Tax',
                'code' => 'TEST_TAX',
                'rate' => 15.00
            ]);

        $this->assertDatabaseHas('taxes', [
            'name' => 'Test Tax',
            'code' => 'TEST_TAX',
            'rate' => 15.00
        ]);
    }

    /** @test */
    public function it_can_show_a_tax()
    {
        $tax = Tax::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/financial-management/taxes/{$tax->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $tax->id,
                'name' => $tax->name,
                'code' => $tax->code
            ]);
    }

    /** @test */
    public function it_can_update_a_tax()
    {
        $tax = Tax::factory()->create();

        $updateData = [
            'name' => 'Updated Tax',
            'code' => 'UPDATED_TAX',
            'rate' => 20.00,
            'description' => 'Updated description',
            'active' => false
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/financial-management/taxes/{$tax->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'name' => 'Updated Tax',
                'code' => 'UPDATED_TAX',
                'rate' => 20.00
            ]);

        $this->assertDatabaseHas('taxes', [
            'id' => $tax->id,
            'name' => 'Updated Tax',
            'code' => 'UPDATED_TAX',
            'rate' => 20.00
        ]);
    }

    /** @test */
    public function it_can_delete_a_tax()
    {
        $tax = Tax::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/financial-management/taxes/{$tax->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('taxes', [
            'id' => $tax->id
        ]);
    }

    /** @test */
    public function it_can_get_active_taxes_for_dropdown()
    {
        // Create active and inactive taxes
        Tax::factory()->active()->create();
        Tax::factory()->inactive()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/options/taxes');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'name',
                    'code',
                    'rate'
                ]
            ]);
    }

    /** @test */
    public function it_validates_tax_creation()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/financial-management/taxes', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'code', 'rate']);
    }

    /** @test */
    public function it_validates_unique_tax_code()
    {
        $existingTax = Tax::factory()->create(['code' => 'EXISTING_CODE']);

        $taxData = [
            'name' => 'Test Tax',
            'code' => 'EXISTING_CODE', // Duplicate code
            'rate' => 15.00,
            'description' => 'Test description',
            'active' => true
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/financial-management/taxes', $taxData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    /** @test */
    public function it_validates_tax_rate_range()
    {
        $taxData = [
            'name' => 'Test Tax',
            'code' => 'TEST_TAX',
            'rate' => 150.00, // Invalid rate > 100
            'description' => 'Test description',
            'active' => true
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/financial-management/taxes', $taxData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['rate']);
    }

    /** @test */
    public function it_can_filter_taxes_by_status()
    {
        Tax::factory()->active()->create();
        Tax::factory()->inactive()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/financial-management/taxes?active=Active');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertNotEmpty($data);
        
        // All returned taxes should be active
        foreach ($data as $tax) {
            $this->assertEquals('Active', $tax['status']);
        }
    }

    /** @test */
    public function it_can_search_taxes()
    {
        Tax::factory()->create(['name' => 'VAT Tax']);
        Tax::factory()->create(['name' => 'Sales Tax']);
        Tax::factory()->create(['code' => 'SERVICE_TAX']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/financial-management/taxes?search=VAT');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertNotEmpty($data);
        
        // Should find VAT Tax
        $this->assertTrue(collect($data)->contains('name', 'VAT Tax'));
    }
}