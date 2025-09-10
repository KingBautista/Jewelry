<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Fee;
use App\Models\User;

class FeeTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_can_list_fees()
    {
        Fee::factory(3)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/financial-management/fees');

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
                        'status'
                    ]
                ]
            ]);
    }

    /** @test */
    public function it_can_create_a_fee()
    {
        $feeData = [
            'name' => 'Test Fee',
            'code' => 'TEST_FEE',
            'amount' => 100.00,
            'type' => 'fixed',
            'description' => 'Test fee description',
            'active' => true
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/financial-management/fees', $feeData);

        $response->assertStatus(201);
        $this->assertDatabaseHas('fees', ['code' => 'TEST_FEE']);
    }

    /** @test */
    public function it_can_get_active_fees_for_dropdown()
    {
        Fee::factory()->active()->create();
        Fee::factory()->inactive()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/options/fees');

        $response->assertStatus(200);
    }
}