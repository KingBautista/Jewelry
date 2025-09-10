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
            ->getJson('/api/financial-management/payment-terms');

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
            ->postJson('/api/financial-management/payment-terms', $paymentTermData);

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
            ->getJson("/api/financial-management/payment-terms/{$paymentTerm->id}");

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
            ->putJson("/api/financial-management/payment-terms/{$paymentTerm->id}", $updateData);

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
            ->deleteJson("/api/financial-management/payment-terms/{$paymentTerm->id}");

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
            ->postJson('/api/financial-management/payment-terms', []);

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
            ->postJson('/api/financial-management/payment-terms', $paymentTermData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['schedules.0.percentage']);
    }

    /** @test */
    public function it_can_generate_equal_payment_schedule()
    {
        $requestData = [
            'term_months' => 5,
            'remaining_percentage' => 70.00
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/financial-management/payment-terms/generate-equal-schedule', $requestData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'schedules' => [
                    '*' => [
                        'month_number',
                        'percentage',
                        'description'
                    ]
                ],
                'message'
            ])
            ->assertJson([
                'success' => true,
                'schedules' => [
                    ['month_number' => 1, 'percentage' => 14.00, 'description' => 'Month 1 payment'],
                    ['month_number' => 2, 'percentage' => 14.00, 'description' => 'Month 2 payment'],
                    ['month_number' => 3, 'percentage' => 14.00, 'description' => 'Month 3 payment'],
                    ['month_number' => 4, 'percentage' => 14.00, 'description' => 'Month 4 payment'],
                    ['month_number' => 5, 'percentage' => 14.00, 'description' => 'Month 5 payment'],
                ]
            ]);
    }

    /** @test */
    public function it_can_validate_payment_term_completeness()
    {
        $paymentTerm = PaymentTerm::factory()->create([
            'term_months' => 3,
            'remaining_percentage' => 60.00
        ]);

        // Create schedules that don't match the remaining percentage
        PaymentTermSchedule::factory()->create([
            'payment_term_id' => $paymentTerm->id,
            'month_number' => 1,
            'percentage' => 20.00
        ]);
        PaymentTermSchedule::factory()->create([
            'payment_term_id' => $paymentTerm->id,
            'month_number' => 2,
            'percentage' => 20.00
        ]);
        // Missing third month schedule

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/financial-management/payment-terms/{$paymentTerm->id}/validate-completeness");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'validation' => [
                    'is_complete',
                    'issues'
                ]
            ])
            ->assertJson([
                'success' => true,
                'validation' => [
                    'is_complete' => false,
                    'issues' => [
                        "Schedule count (2) doesn't match term months (3)",
                        "Schedule percentages (40%) don't match remaining percentage (60%)"
                    ]
                ]
            ]);
    }

    /** @test */
    public function it_validates_payment_breakdown_percentages()
    {
        $paymentTermData = [
            'name' => 'Test Payment Plan',
            'code' => 'TEST_PLAN',
            'down_payment_percentage' => 30.00,
            'remaining_percentage' => 80.00, // Total = 110%, should fail
            'term_months' => 2,
            'description' => 'Test payment plan',
            'active' => true,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/financial-management/payment-terms', $paymentTermData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['payment_breakdown']);
    }

    /** @test */
    public function it_validates_schedule_month_numbers_are_sequential()
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
                    'month_number' => 3, // Missing month 2
                    'percentage' => 25.00,
                    'description' => 'Third month'
                ],
                [
                    'month_number' => 4, // Should be month 3
                    'percentage' => 20.00,
                    'description' => 'Fourth month'
                ]
            ]
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/financial-management/payment-terms', $paymentTermData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['schedules']);
    }

    /** @test */
    public function it_validates_unique_month_numbers()
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
                    'percentage' => 35.00,
                    'description' => 'First month'
                ],
                [
                    'month_number' => 1, // Duplicate month number
                    'percentage' => 35.00,
                    'description' => 'Duplicate month'
                ]
            ]
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/financial-management/payment-terms', $paymentTermData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['schedules']);
    }

    /** @test */
    public function it_can_soft_delete_payment_terms()
    {
        $paymentTerm = PaymentTerm::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/financial-management/payment-terms/{$paymentTerm->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('payment_terms', [
            'id' => $paymentTerm->id
        ]);

        // Verify schedules are also soft deleted (cascade)
        $this->assertDatabaseMissing('payment_term_schedules', [
            'payment_term_id' => $paymentTerm->id
        ]);
    }

    /** @test */
    public function it_can_bulk_delete_payment_terms()
    {
        $paymentTerms = PaymentTerm::factory(3)->create();
        $ids = $paymentTerms->pluck('id')->toArray();

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/financial-management/payment-terms/bulk/delete', [
                'ids' => $ids
            ]);

        $response->assertStatus(200);

        foreach ($ids as $id) {
            $this->assertSoftDeleted('payment_terms', ['id' => $id]);
        }
    }
}