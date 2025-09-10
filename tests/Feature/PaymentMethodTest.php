<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\PaymentMethod;
use App\Models\User;

class PaymentMethodTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_can_list_payment_methods()
    {
        PaymentMethod::factory(3)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/financial-management/payment-methods');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'bank_name',
                        'account_name',
                        'account_number',
                        'masked_account_number',
                        'active',
                        'status'
                    ]
                ]
            ]);
    }

    /** @test */
    public function it_can_create_a_payment_method()
    {
        $paymentMethodData = [
            'bank_name' => 'Test Bank',
            'account_name' => 'Test Account',
            'account_number' => '1234567890',
            'description' => 'Test payment method',
            'active' => true
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/financial-management/payment-methods', $paymentMethodData);

        $response->assertStatus(201);
        $this->assertDatabaseHas('payment_methods', ['bank_name' => 'Test Bank']);
    }

    /** @test */
    public function it_can_get_active_payment_methods_for_dropdown()
    {
        PaymentMethod::factory()->active()->create();
        PaymentMethod::factory()->inactive()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/options/payment-methods');

        $response->assertStatus(200);
    }
}