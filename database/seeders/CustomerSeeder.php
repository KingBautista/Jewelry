<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Helpers\PasswordHelper;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 5 customers with realistic invoice and payment transactions
        $customers = [
            [
                'first_name' => 'Maria',
                'last_name' => 'Santos',
                'email' => 'maria.santos@gmail.com',
                'phone' => '+63 917 123 4567',
                'address' => '123 Rizal Street, Barangay San Antonio',
                'city' => 'Manila',
                'state' => 'Metro Manila',
                'postal_code' => '1000',
                'country' => 'Philippines',
                'date_of_birth' => '1985-03-15',
                'gender' => 'female',
                'notes' => 'VIP customer, mahilig sa ginto at alahas',
                'active' => true,
                'invoices' => [
                    [
                        'invoice_number' => 'INV-2024-011',
                        'issue_date' => now()->subDays(45),
                        'due_date' => now()->subDays(15),
                        'subtotal' => 285000.00,
                        'tax_amount' => 34200.00,
                        'fee_amount' => 1500.00,
                        'discount_amount' => 15000.00,
                        'total_amount' => 305700.00,
                        'payment_status' => 'fully_paid',
                        'notes' => 'Wedding jewelry set - engagement ring, wedding bands, and bridal accessories',
                        'items' => [
                            [
                                'product_name' => '1.2ct Diamond Engagement Ring',
                                'description' => '18k white gold solitaire ring with VS1 clarity, E color diamond',
                                'price' => 180000.00,
                            ],
                            [
                                'product_name' => 'Matching Wedding Band (Him)',
                                'description' => '18k white gold plain wedding band, 6mm width',
                                'price' => 35000.00,
                            ],
                            [
                                'product_name' => 'Matching Wedding Band (Her)',
                                'description' => '18k white gold wedding band with diamond accents',
                                'price' => 45000.00,
                            ],
                            [
                                'product_name' => 'Pearl Necklace Set',
                                'description' => 'White pearl necklace with matching earrings for wedding',
                                'price' => 25000.00,
                            ],
                        ],
                        'payments' => [
                            [
                                'amount_paid' => 150000.00,
                                'payment_date' => now()->subDays(40),
                                'payment_type' => 'downpayment',
                                'status' => 'confirmed',
                            ],
                            [
                                'amount_paid' => 155700.00,
                                'payment_date' => now()->subDays(20),
                                'payment_type' => 'final',
                                'status' => 'confirmed',
                            ]
                        ]
                    ],
                    [
                        'invoice_number' => 'INV-2024-012',
                        'issue_date' => now()->subDays(15),
                        'due_date' => now()->addDays(15),
                        'subtotal' => 45000.00,
                        'tax_amount' => 5400.00,
                        'fee_amount' => 300.00,
                        'discount_amount' => 2000.00,
                        'total_amount' => 48700.00,
                        'payment_status' => 'unpaid',
                        'notes' => 'Anniversary gift - gold jewelry set',
                        'items' => [
                            [
                                'product_name' => '18k Gold Chain',
                                'description' => 'Classic 18k yellow gold chain, 20 inches',
                                'price' => 25000.00,
                            ],
                            [
                                'product_name' => 'Gold Cross Pendant',
                                'description' => '18k yellow gold cross pendant with small diamonds',
                                'price' => 20000.00,
                            ],
                        ],
                        'payments' => []
                    ]
                ]
            ],
            [
                'first_name' => 'Juan',
                'last_name' => 'Cruz',
                'email' => 'juan.cruz@yahoo.com',
                'phone' => '+63 918 234 5678',
                'address' => '456 Quezon Avenue, Barangay Pinyahan',
                'city' => 'Quezon City',
                'state' => 'Metro Manila',
                'postal_code' => '1100',
                'country' => 'Philippines',
                'date_of_birth' => '1990-07-22',
                'gender' => 'male',
                'notes' => 'Regular customer, naghahanap ng engagement ring',
                'active' => true,
                'invoices' => [
                    [
                        'invoice_number' => 'INV-2024-013',
                        'issue_date' => now()->subDays(20),
                        'due_date' => now()->addDays(10),
                        'subtotal' => 125000.00,
                        'tax_amount' => 15000.00,
                        'fee_amount' => 800.00,
                        'discount_amount' => 5000.00,
                        'total_amount' => 135800.00,
                        'payment_status' => 'partially_paid',
                        'notes' => 'Traditional Filipino jewelry set - gold necklace, earrings, and bracelet',
                        'items' => [
                            [
                                'product_name' => '18k Gold Chain (Mano)',
                                'description' => 'Traditional Filipino gold chain, 22 inches, 18k yellow gold',
                                'price' => 45000.00,
                            ],
                            [
                                'product_name' => 'Gold Cross Pendant',
                                'description' => '18k yellow gold cross pendant with small diamonds',
                                'price' => 35000.00,
                            ],
                            [
                                'product_name' => 'Matching Gold Earrings',
                                'description' => '18k yellow gold hoop earrings with small diamonds',
                                'price' => 25000.00,
                            ],
                            [
                                'product_name' => 'Gold Bracelet',
                                'description' => '18k yellow gold link bracelet, 7 inches',
                                'price' => 20000.00,
                            ],
                        ],
                        'payments' => [
                            [
                                'amount_paid' => 50000.00,
                                'payment_date' => now()->subDays(15),
                                'payment_type' => 'downpayment',
                                'status' => 'confirmed',
                            ],
                            [
                                'amount_paid' => 30000.00,
                                'payment_date' => now()->subDays(5),
                                'payment_type' => 'partial',
                                'status' => 'confirmed',
                            ]
                        ]
                    ],
                    [
                        'invoice_number' => 'INV-2024-014',
                        'issue_date' => now()->subDays(5),
                        'due_date' => now()->addDays(25),
                        'subtotal' => 35000.00,
                        'tax_amount' => 4200.00,
                        'fee_amount' => 200.00,
                        'discount_amount' => 0.00,
                        'total_amount' => 39400.00,
                        'payment_status' => 'unpaid',
                        'notes' => 'Simple gold ring for daily wear',
                        'items' => [
                            [
                                'product_name' => '18k Gold Signet Ring',
                                'description' => 'Classic 18k yellow gold signet ring, size 8',
                                'price' => 35000.00,
                            ],
                        ],
                        'payments' => []
                    ]
                ]
            ],
            [
                'first_name' => 'Ana',
                'last_name' => 'Reyes',
                'email' => 'ana.reyes@outlook.com',
                'phone' => '+63 919 345 6789',
                'address' => '789 Ayala Avenue, Barangay San Lorenzo',
                'city' => 'Makati',
                'state' => 'Metro Manila',
                'postal_code' => '1200',
                'country' => 'Philippines',
                'date_of_birth' => '1992-11-08',
                'gender' => 'female',
                'notes' => 'Corporate client, malaking order ng alahas',
                'active' => true,
                'invoices' => [
                    [
                        'invoice_number' => 'INV-2024-015',
                        'issue_date' => now()->subDays(60),
                        'due_date' => now()->subDays(30),
                        'subtotal' => 85000.00,
                        'tax_amount' => 10200.00,
                        'fee_amount' => 500.00,
                        'discount_amount' => 0.00,
                        'total_amount' => 95700.00,
                        'payment_status' => 'overdue',
                        'notes' => 'Corporate jewelry set for business meetings and formal events',
                        'items' => [
                            [
                                'product_name' => 'Pearl Necklace Set',
                                'description' => 'White pearl necklace with matching earrings, professional set',
                                'price' => 35000.00,
                            ],
                            [
                                'product_name' => 'Gold Business Watch',
                                'description' => '18k gold ladies watch with diamond markers, business style',
                                'price' => 50000.00,
                            ],
                        ],
                        'payments' => []
                    ],
                    [
                        'invoice_number' => 'INV-2024-006',
                        'issue_date' => now()->subDays(10),
                        'due_date' => now()->addDays(20),
                        'subtotal' => 120000.00,
                        'tax_amount' => 14400.00,
                        'fee_amount' => 800.00,
                        'discount_amount' => 10000.00,
                        'total_amount' => 125200.00,
                        'payment_status' => 'unpaid',
                        'notes' => 'Executive jewelry collection for corporate events',
                        'items' => [
                            [
                                'product_name' => 'Diamond Tennis Bracelet',
                                'description' => '18k white gold tennis bracelet with 2ct total diamond weight',
                                'price' => 80000.00,
                            ],
                            [
                                'product_name' => 'Pearl and Diamond Earrings',
                                'description' => 'White pearl earrings with diamond accents, 18k gold',
                                'price' => 40000.00,
                            ],
                        ],
                        'payments' => []
                    ]
                ]
            ],
            [
                'first_name' => 'Pedro',
                'last_name' => 'Garcia',
                'email' => 'pedro.garcia@hotmail.com',
                'phone' => '+63 920 456 7890',
                'address' => '321 BGC High Street, Barangay Fort Bonifacio',
                'city' => 'Taguig',
                'state' => 'Metro Manila',
                'postal_code' => '1630',
                'country' => 'Philippines',
                'date_of_birth' => '1988-05-12',
                'gender' => 'male',
                'notes' => 'Mahilig sa mamahaling alahas at koleksyon',
                'active' => true,
                'invoices' => [
                    [
                        'invoice_number' => 'INV-2024-007',
                        'issue_date' => now()->subDays(30),
                        'due_date' => now()->addDays(0),
                        'subtotal' => 450000.00,
                        'tax_amount' => 54000.00,
                        'fee_amount' => 3000.00,
                        'discount_amount' => 25000.00,
                        'total_amount' => 482000.00,
                        'payment_status' => 'partially_paid',
                        'notes' => 'Luxury watch collection - Rolex-style timepiece with diamonds',
                        'items' => [
                            [
                                'product_name' => 'Luxury Gold Watch',
                                'description' => '18k yellow gold watch with diamond bezel and markers',
                                'price' => 350000.00,
                            ],
                            [
                                'product_name' => 'Diamond Cufflinks',
                                'description' => '18k white gold cufflinks with small diamonds',
                                'price' => 100000.00,
                            ],
                        ],
                        'payments' => [
                            [
                                'amount_paid' => 200000.00,
                                'payment_date' => now()->subDays(25),
                                'payment_type' => 'downpayment',
                                'status' => 'confirmed',
                            ]
                        ]
                    ],
                    [
                        'invoice_number' => 'INV-2024-008',
                        'issue_date' => now()->subDays(3),
                        'due_date' => now()->addDays(27),
                        'subtotal' => 180000.00,
                        'tax_amount' => 21600.00,
                        'fee_amount' => 1200.00,
                        'discount_amount' => 8000.00,
                        'total_amount' => 194800.00,
                        'payment_status' => 'unpaid',
                        'notes' => 'Collector\'s jewelry set - rare pieces for collection',
                        'items' => [
                            [
                                'product_name' => 'Antique Gold Chain',
                                'description' => 'Vintage 18k gold chain, 24 inches, collector\'s piece',
                                'price' => 120000.00,
                            ],
                            [
                                'product_name' => 'Diamond Ring',
                                'description' => '18k white gold ring with 1ct center diamond',
                                'price' => 60000.00,
                            ],
                        ],
                        'payments' => []
                    ]
                ]
            ],
            [
                'first_name' => 'Carmen',
                'last_name' => 'Lopez',
                'email' => 'carmen.lopez@icloud.com',
                'phone' => '+63 921 567 8901',
                'address' => '654 Ortigas Avenue, Barangay San Antonio',
                'city' => 'Pasig',
                'state' => 'Metro Manila',
                'postal_code' => '1600',
                'country' => 'Philippines',
                'date_of_birth' => '1995-09-30',
                'gender' => 'female',
                'notes' => 'Bagong customer, gusto ng custom na alahas',
                'active' => true,
                'invoices' => [
                    [
                        'invoice_number' => 'INV-2024-009',
                        'issue_date' => now()->subDays(5),
                        'due_date' => now()->addDays(25),
                        'subtotal' => 95000.00,
                        'tax_amount' => 11400.00,
                        'fee_amount' => 600.00,
                        'discount_amount' => 5000.00,
                        'total_amount' => 102000.00,
                        'payment_status' => 'unpaid',
                        'notes' => 'Custom designed jewelry set for debut celebration',
                        'items' => [
                            [
                                'product_name' => 'Custom Pearl Necklace',
                                'description' => 'Handcrafted pearl necklace with custom pendant design',
                                'price' => 45000.00,
                            ],
                            [
                                'product_name' => 'Matching Pearl Earrings',
                                'description' => 'Custom pearl earrings to match necklace design',
                                'price' => 25000.00,
                            ],
                            [
                                'product_name' => 'Pearl Bracelet',
                                'description' => 'Matching pearl bracelet with sterling silver clasp',
                                'price' => 25000.00,
                            ],
                        ],
                        'payments' => []
                    ],
                    [
                        'invoice_number' => 'INV-2024-010',
                        'issue_date' => now()->subDays(1),
                        'due_date' => now()->addDays(29),
                        'subtotal' => 28000.00,
                        'tax_amount' => 3360.00,
                        'fee_amount' => 200.00,
                        'discount_amount' => 0.00,
                        'total_amount' => 31560.00,
                        'payment_status' => 'unpaid',
                        'notes' => 'Simple everyday jewelry - gold earrings and ring',
                        'items' => [
                            [
                                'product_name' => 'Gold Stud Earrings',
                                'description' => '14k yellow gold stud earrings, simple design',
                                'price' => 15000.00,
                            ],
                            [
                                'product_name' => 'Gold Ring',
                                'description' => '14k yellow gold ring with small diamond accent',
                                'price' => 13000.00,
                            ],
                        ],
                        'payments' => []
                    ]
                ]
            ]
        ];

        foreach ($customers as $customerData) {
            // Generate password fields for each customer
            $salt = PasswordHelper::generateSalt();
            $password = PasswordHelper::generatePassword($salt, 'password123');
            $activation_key = PasswordHelper::generateSalt();
            // Prepare user data
            $userData = [
                'user_login' => $customerData['email'],
                'user_email' => $customerData['email'],
                'user_salt' => $salt,
                'user_pass' => $password,
                'user_status' => 1,
                'user_activation_key' => $activation_key,
                'user_role_id' => 7, // Customer role
            ];

            // Prepare customer meta data
            $customerMetaData = [
                'user_type' => 'customer',
                'customer_code' => User::generateCustomerCode(),
                'first_name' => $customerData['first_name'],
                'last_name' => $customerData['last_name'],
                'phone' => $customerData['phone'],
                'address' => $customerData['address'],
                'city' => $customerData['city'],
                'state' => $customerData['state'],
                'postal_code' => $customerData['postal_code'],
                'country' => $customerData['country'],
                'date_of_birth' => $customerData['date_of_birth'],
                'gender' => $customerData['gender'],
                'notes' => $customerData['notes'],
            ];
            
            // Check if user already exists
            $existingUser = User::where('user_email', $customerData['email'])->first();
            
            if (!$existingUser) {
                $user = User::create($userData);
                $user->saveUserMeta($customerMetaData);
            } else {
                // Update existing user with correct role ID
                $existingUser->update($userData);
                $user = $existingUser;
                
                // Create invoices and payments for this customer
                if (isset($customerData['invoices'])) {
                    $this->createCustomerInvoices($user, $customerData['invoices']);
                }
            }
        }
    }

    /**
     * Create invoices and payments for a customer
     */
    private function createCustomerInvoices($customer, $invoices)
    {
        foreach ($invoices as $invoiceData) {
            // Get proper references based on invoice type and customer
            $tax = \App\Models\Tax::where('code', 'VAT')->first(); // Always use VAT for jewelry sales
            $fee = $this->getAppropriateFee($invoiceData);
            $discount = $this->getAppropriateDiscount($customer, $invoiceData);
            $paymentTerm = $this->getAppropriatePaymentTerm($invoiceData);
            $paymentMethod = \App\Models\PaymentMethod::first();

            // Check if invoice already exists
            $existingInvoice = \App\Models\Invoice::where('invoice_number', $invoiceData['invoice_number'])->first();
            
            if ($existingInvoice) {
                $invoice = $existingInvoice;
            } else {
                // Create invoice
                $invoice = \App\Models\Invoice::create([
                'invoice_number' => $invoiceData['invoice_number'],
                'customer_id' => $customer->id,
                'issue_date' => $invoiceData['issue_date'],
                'due_date' => $invoiceData['due_date'],
                'subtotal' => $invoiceData['subtotal'],
                'tax_amount' => $invoiceData['tax_amount'],
                'fee_amount' => $invoiceData['fee_amount'],
                'discount_amount' => $invoiceData['discount_amount'],
                'total_amount' => $invoiceData['total_amount'],
                'total_paid_amount' => 0,
                'remaining_balance' => $invoiceData['total_amount'],
                'payment_status' => $invoiceData['payment_status'],
                'notes' => $invoiceData['notes'],
                'tax_id' => $tax?->id,
                'fee_id' => $invoiceData['fee_amount'] > 0 ? $fee?->id : null,
                'discount_id' => $invoiceData['discount_amount'] > 0 ? $discount?->id : null,
                'payment_term_id' => $paymentTerm?->id,
                'active' => true,
                ]);
                
                // Generate payment schedules if payment terms exist
                if ($paymentTerm) {
                    $invoice->generatePaymentSchedules();
                }
            }

            // Create invoice items
            foreach ($invoiceData['items'] as $itemData) {
                \App\Models\InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_name' => $itemData['product_name'],
                    'description' => $itemData['description'],
                    'price' => $itemData['price'],
                ]);
            }

            // Create payments that match payment schedules
            $paymentSchedules = $invoice->paymentSchedules()->orderBy('payment_order')->get();
            $paymentIndex = 0;
            
            foreach ($invoiceData['payments'] as $paymentData) {
                $paymentType = $this->getAppropriatePaymentType($paymentData['payment_type']);
                
                // Get the corresponding payment schedule amount
                $scheduleAmount = 0;
                if ($paymentSchedules->count() > $paymentIndex) {
                    $scheduleAmount = $paymentSchedules[$paymentIndex]->expected_amount;
                } else {
                    // If no more schedules, use the remaining balance
                    $scheduleAmount = $invoiceData['total_amount'] - array_sum(array_column($invoiceData['payments'], 'amount_paid'));
                }
                
                $payment = \App\Models\Payment::create([
                    'invoice_id' => $invoice->id,
                    'customer_id' => $customer->id,
                    'amount_paid' => $scheduleAmount,
                    'expected_amount' => $scheduleAmount,
                    'payment_date' => $paymentData['payment_date'],
                    'payment_type' => $paymentData['payment_type'],
                    'payment_method_id' => $paymentMethod?->id,
                    'reference_number' => 'PAY-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
                    'status' => $paymentData['status'],
                    'confirmed_at' => $paymentData['status'] === 'confirmed' ? $paymentData['payment_date'] : null,
                    'confirmed_by' => $paymentData['status'] === 'confirmed' ? \App\Models\User::first()?->id : null,
                    'source' => 'admin_created',
                ]);
                
                // Update the corresponding payment schedule if it exists
                if ($paymentSchedules->count() > $paymentIndex) {
                    $schedule = $paymentSchedules[$paymentIndex];
                    $schedule->update([
                        'paid_amount' => $scheduleAmount,
                        'status' => $paymentData['status'] === 'confirmed' ? 'paid' : 'partial'
                    ]);
                }
                
                $paymentIndex++;
            }

            // Update invoice payment status
            $invoice->updatePaymentStatus();
        }
    }

    /**
     * Get appropriate fee based on invoice data
     */
    private function getAppropriateFee($invoiceData)
    {
        $notes = strtolower($invoiceData['notes'] ?? '');
        
        // Custom design fee for custom jewelry
        if (strpos($notes, 'custom') !== false) {
            return \App\Models\Fee::where('code', 'CUSTOM_DESIGN')->first();
        }
        
        // Express delivery for luxury items
        if ($invoiceData['total_amount'] > 200000) {
            return \App\Models\Fee::where('code', 'EXPRESS_DELIVERY')->first();
        }
        
        // Metro Manila delivery for regular items
        return \App\Models\Fee::where('code', 'MM_DELIVERY')->first();
    }

    /**
     * Get appropriate discount based on customer and invoice data
     */
    private function getAppropriateDiscount($customer, $invoiceData)
    {
        $customerEmail = $customer->user_email;
        $totalAmount = $invoiceData['total_amount'];
        $notes = strtolower($invoiceData['notes'] ?? '');
        
        // VIP customer (Maria Santos)
        if (strpos($customerEmail, 'maria.santos') !== false) {
            return \App\Models\Discount::where('code', 'VIP_PREMIUM')->first();
        }
        
        // Corporate client (Ana Reyes)
        if (strpos($customerEmail, 'ana.reyes') !== false) {
            return \App\Models\Discount::where('code', 'CORPORATE')->first();
        }
        
        // New customer (Carmen Lopez)
        if (strpos($customerEmail, 'carmen.lopez') !== false) {
            return \App\Models\Discount::where('code', 'NEW_CUSTOMER')->first();
        }
        
        // Wedding/anniversary items
        if (strpos($notes, 'wedding') !== false || strpos($notes, 'anniversary') !== false) {
            return \App\Models\Discount::where('code', 'ANNIVERSARY')->first();
        }
        
        // Bulk purchase discount for high amounts
        if ($totalAmount > 100000) {
            return \App\Models\Discount::where('code', 'BULK_PURCHASE')->first();
        }
        
        // Loyalty discount for regular customers
        return \App\Models\Discount::where('code', 'LOYALTY')->first();
    }

    /**
     * Get appropriate payment term based on invoice data
     */
    private function getAppropriatePaymentTerm($invoiceData)
    {
        $totalAmount = $invoiceData['total_amount'];
        $notes = strtolower($invoiceData['notes'] ?? '');
        
        // Cash payment for small amounts
        if ($totalAmount < 50000) {
            return \App\Models\PaymentTerm::where('code', 'CASH_PAYMENT')->first();
        }
        
        // 50% down, 50% on delivery for custom items
        if (strpos($notes, 'custom') !== false) {
            return \App\Models\PaymentTerm::where('code', '50_50_DELIVERY')->first();
        }
        
        // 30% down, 70% in 30 days for luxury items
        if ($totalAmount > 200000) {
            return \App\Models\PaymentTerm::where('code', '30_70_30DAYS')->first();
        }
        
        // 3-month installment for medium amounts
        if ($totalAmount > 100000) {
            return \App\Models\PaymentTerm::where('code', '3_MONTH_INSTALLMENT')->first();
        }
        
        // Net 30 for regular business transactions
        return \App\Models\PaymentTerm::where('code', 'NET_30')->first();
    }

    /**
     * Get appropriate payment type based on payment data
     */
    private function getAppropriatePaymentType($paymentType)
    {
        $paymentTypeMap = [
            'downpayment' => 'DOWN_PAYMENT',
            'partial' => 'PARTIAL',
            'final' => 'FINAL_PAYMENT',
            'full' => 'CASH',
            'installment' => 'INSTALLMENT',
            'balance' => 'BALANCE',
        ];

        $code = $paymentTypeMap[$paymentType] ?? 'CASH';
        return \App\Models\PaymentType::where('code', $code)->first();
    }
}