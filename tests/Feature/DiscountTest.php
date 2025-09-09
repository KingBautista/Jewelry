<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Discount;
use App\Models\User;

class DiscountTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_can_list_discounts()
    {
        Discount::factory(3)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/financial-config/discounts');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'code',
                        'amount',
                        'type',
                        'active',
                        'is_valid'
                    ]
                ]
            ]);
    }

    /** @test */
    public function it_can_create_a_discount()
    {
        $discountData = [
            'name' => 'Test Discount',
            'code' => 'TEST_DISCOUNT',
            'amount' => 500.00,
            'type' => 'fixed',
            'description' => 'Test discount description',
            'active' => true
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/financial-config/discounts', $discountData);

        $response->assertStatus(201);
        $this->assertDatabaseHas('discounts', ['code' => 'TEST_DISCOUNT']);
    }

    /** @test */
    public function it_can_validate_discount_code()
    {
        $discount = Discount::factory()->valid()->create(['code' => 'VALID_CODE']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/financial-config/discounts/validate-code', [
                'code' => 'VALID_CODE'
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['valid' => true]);
    }

    /** @test */
    public function it_can_get_active_discounts_for_dropdown()
    {
        Discount::factory()->valid()->create();
        Discount::factory()->inactive()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/options/discounts');

        $response->assertStatus(200);
    }
}