<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\PaymentTerm;
use App\Models\PaymentTermSchedule;
use App\Models\User;

class PaymentTermTest extends TestCase
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
    public function it_can_list_payment_terms()
    {
        // Create some payment terms with schedules
        PaymentTerm::factory(2)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/financial-config/payment-terms');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'code',
                        'down_payment_percentage',
                        'remaining_percentage',
                        'term_months',
                        'description',
                        'active',
                        'status',
                        'schedules',
                        'created_at',
                        'updated_at'
                    ]
                ],
                'meta'
            ]);
    }

    /** @test */
    public function it_can_create_a_payment_term_with_schedules()
    {
        $paymentTermData = [
            'name' => 'Test Payment Plan',
            'code' => 'TEST_PLAN',
            'down_payment_percentage' => 30.00,
            'remaining_percentage' => 70.00,
            'term_months' => 3,
            'description' => 'Test payment plan',
            'active' => true,
            'schedules' => [
                [
                    'month_number' => 1,
                    'percentage' => 25.00,
                    'description' => 'First month'
                ],
                [
                    'month_number' => 2,
                    'percentage' => 25.00,
                    'description' => 'Second month'
                ],
                [
                    'month_number' => 3,
                    'percentage' => 20.00,
                    'description' => 'Third month'
                ]
            ]
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/financial-config/payment-terms', $paymentTermData);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'name' => 'Test Payment Plan',
                'code' => 'TEST_PLAN',
                'down_payment_percentage' => 30.00
            ]);

        $this->assertDatabaseHas('payment_terms', [
            'name' => 'Test Payment Plan',
            'code' => 'TEST_PLAN'
        ]);

        // Check that schedules were created
        $paymentTerm = PaymentTerm::where('code', 'TEST_PLAN')->first();
        $this->assertCount(3, $paymentTerm->schedules);
    }

    /** @test */
    public function it_can_show_a_payment_term_with_schedules()
    {
        $paymentTerm = PaymentTerm::factory()->create();
        PaymentTermSchedule::factory(2)->create(['payment_term_id' => $paymentTerm->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/financial-config/payment-terms/{$paymentTerm->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $paymentTerm->id,
                'name' => $paymentTerm->name
            ])
            ->assertJsonStructure([
                'schedules' => [
                    '*' => [
                        'id',
                        'month_number',
                        'percentage',
                        'description'
                    ]
                ]
            ]);
    }

    /** @test */
    public function it_can_update_a_payment_term_with_schedules()
    {
        $paymentTerm = PaymentTerm::factory()->create();
        PaymentTermSchedule::factory(2)->create(['payment_term_id' => $paymentTerm->id]);

        $updateData = [
            'name' => 'Updated Payment Plan',
            'code' => 'UPDATED_PLAN',
            'down_payment_percentage' => 40.00,
            'remaining_percentage' => 60.00,
            'term_months' => 2,
            'description' => 'Updated description',
            'active' => false,
            'schedules' => [
                [
                    'month_number' => 1,
                    'percentage' => 30.00,
                    'description' => 'First month updated'
                ],
                [
                    'month_number' => 2,
                    'percentage' => 30.00,
                    'description' => 'Second month updated'
                ]
            ]
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/financial-config/payment-terms/{$paymentTerm->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'name' => 'Updated Payment Plan',
                'code' => 'UPDATED_PLAN'
            ]);

        // Check that old schedules were deleted and new ones created
        $paymentTerm->refresh();
        $this->assertCount(2, $paymentTerm->schedules);
    }

    /** @test */
    public function it_can_delete_a_payment_term()
    {
        $paymentTerm = PaymentTerm::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/financial-config/payment-terms/{$paymentTerm->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('payment_terms', [
            'id' => $paymentTerm->id
        ]);
    }

    /** @test */
    public function it_can_get_active_payment_terms_for_dropdown()
    {
        // Create active and inactive payment terms
        PaymentTerm::factory()->active()->create();
        PaymentTerm::factory()->inactive()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/options/payment-terms');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'name',
                    'code',
                    'down_payment_percentage',
                    'remaining_percentage',
                    'term_months',
                    'schedules'
                ]
            ]);
    }

    /** @test */
    public function it_validates_payment_term_creation()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/financial-config/payment-terms', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'code', 'down_payment_percentage', 'remaining_percentage', 'term_months']);
    }

    /** @test */
    public function it_validates_schedule_percentages()
    {
        $paymentTermData = [
            'name' => 'Test Payment Plan',
            'code' => 'TEST_PLAN',
            'down_payment_percentage' => 30.00,
            'remaining_percentage' => 70.00,
            'term_months' => 2,
            'description' => 'Test payment plan',
            'active' => true,
            'schedules' => [
                [
                    'month_number' => 1,
                    'percentage' => 150.00, // Invalid percentage > 100
                    'description' => 'First month'
                ]
            ]
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/financial-config/payment-terms', $paymentTermData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['schedules.0.percentage']);
    }
}