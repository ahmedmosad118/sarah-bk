<?php

namespace Tests\Feature;

use App\Core\Tenancy\TenantDatabaseManager;
use App\Models\Central\Tenant;
use App\Models\Customer;
use App\Models\User;
use App\Services\Tenant\TenantProvisioningService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecurityAttackVerificationTest extends TestCase
{
    protected TenantProvisioningService $provisioningService;
    protected Tenant $tenant;
    protected User $owner;
    protected string $slug;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('migrate', [
            '--database' => 'central',
            '--path' => 'database/migrations/central',
            '--force' => true,
        ]);

        $this->provisioningService = app(TenantProvisioningService::class);
        $this->slug = 'sec-guard-' . time() . '-' . rand(10, 99);

        // Provision Tenant
        $res = $this->provisioningService->provision([
            'name' => 'Security Guard Enterprise',
            'slug' => $this->slug,
            'company_code' => 'SECG-' . rand(100, 999),
            'domain' => $this->slug . '.localhost',
        ], [
            'name' => 'Sec Owner',
            'email' => "sec.owner@{$this->slug}.test",
            'password' => 'SecPass123!#',
        ]);

        $this->tenant = $res['tenant'];
        $this->owner = $res['owner'];

        TenantDatabaseManager::switchToTenant($this->tenant);
        $this->token = $this->owner->createToken('sec_token')->plainTextToken;

        RateLimiter::clear(strtolower("sec.owner@{$this->slug}.test") . '|' . $this->slug . '|127.0.0.1');
    }

    protected function tearDown(): void
    {
        try {
            TenantDatabaseManager::dropDatabase($this->tenant);
            $this->tenant->domains()->delete();
            $this->tenant->delete();
        } catch (\Throwable $e) {
            // Ignore teardown errors
        }

        parent::tearDown();
    }

    // ==========================================
    // 1. XSS (Cross-Site Scripting) Attacks
    // ==========================================

    /**
     * Attack Test: Stored XSS via executable script tags in text fields.
     */
    public function test_xss_script_tags_are_sanitized_and_neutralized(): void
    {
        $payload = '<script>alert("PWNED_XSS")</script>Tariq Client';

        $res = $this->withHeaders([
            'X-Tenant-Slug' => $this->slug,
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/customers', [
            'customer_type' => 'individual',
            'name' => $payload,
            'notes' => '<iframe src="http://evil-site.test/steal-cookie"></iframe>Important notes',
            'status' => 'active',
        ]);

        $res->assertStatus(201);

        // Verify in database that script and iframe were completely stripped
        TenantDatabaseManager::switchToTenant($this->tenant);
        $customer = Customer::find($res->json('data.id'));

        $this->assertStringNotContainsString('<script>', $customer->name);
        $this->assertStringNotContainsString('alert("PWNED_XSS")', $customer->name);
        $this->assertStringNotContainsString('<iframe>', $customer->notes);
        $this->assertStringContainsString('Tariq Client', $customer->name);
        $this->assertStringContainsString('Important notes', $customer->notes);
    }

    /**
     * Attack Test: Inline event handler XSS (e.g. onerror=, onload=).
     */
    public function test_xss_inline_event_handlers_are_neutralized(): void
    {
        $payload = '<img src=x onerror=alert(document.cookie)>Fahad Contracting';

        $res = $this->withHeaders([
            'X-Tenant-Slug' => $this->slug,
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/customers', [
            'customer_type' => 'company',
            'name' => 'Fahad',
            'company_name' => $payload,
            'status' => 'active',
        ]);

        $res->assertStatus(201);

        TenantDatabaseManager::switchToTenant($this->tenant);
        $customer = Customer::find($res->json('data.id'));

        $this->assertStringNotContainsString('onerror=', $customer->company_name);
        $this->assertStringNotContainsString('alert(document.cookie)', $customer->company_name);
    }

    /**
     * Attack Test: Null-Byte Poisoning (\0).
     */
    public function test_null_byte_injection_is_stripped(): void
    {
        $poisonedName = "SafeName" . chr(0) . "PoisonPayload";

        $res = $this->withHeaders([
            'X-Tenant-Slug' => $this->slug,
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/customers', [
            'customer_type' => 'individual',
            'name' => $poisonedName,
            'status' => 'active',
        ]);

        $res->assertStatus(201);

        TenantDatabaseManager::switchToTenant($this->tenant);
        $customer = Customer::find($res->json('data.id'));

        $this->assertStringNotContainsString(chr(0), $customer->name);
    }

    /**
     * Security Headers Test: Verify HTTP response hardening headers.
     */
    public function test_http_security_headers_are_present(): void
    {
        $res = $this->withHeaders([
            'X-Tenant-Slug' => $this->slug,
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/customers');

        $res->assertStatus(200);
        $res->assertHeader('X-Content-Type-Options', 'nosniff');
        $res->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $res->assertHeader('X-XSS-Protection', '1; mode=block');
        $res->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    // ==========================================
    // 2. SQL Injection (SQLi) Attacks
    // ==========================================

    /**
     * Attack Test: SQL Injection in sort_by parameter.
     */
    public function test_sql_injection_in_sort_by_is_neutralized(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenant);
        Customer::create([
            'customer_type' => 'individual',
            'name' => 'Target Alpha',
            'status' => 'active',
        ]);

        // Attempt SQL injection via sort_by
        $sqliPayloads = [
            'id; DROP TABLE customers; --',
            'id, (SELECT SLEEP(5))',
            'name UNION SELECT 1,2,3',
            'id->\'$.invalid\'',
        ];

        foreach ($sqliPayloads as $payload) {
            $res = $this->withHeaders([
                'X-Tenant-Slug' => $this->slug,
                'Authorization' => 'Bearer ' . $this->token,
            ])->getJson('/api/customers?sort_by=' . urlencode($payload));

            // Must NOT throw 500 SQL syntax error; must execute cleanly with fallback sorting
            $res->assertStatus(200);
        }
    }

    /**
     * Attack Test: SQL Injection in sort_order parameter.
     */
    public function test_sql_injection_in_sort_order_is_neutralized(): void
    {
        $res = $this->withHeaders([
            'X-Tenant-Slug' => $this->slug,
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/customers?sort_order=' . urlencode('asc; SELECT 1 --'));

        $res->assertStatus(200);
    }

    /**
     * Attack Test: SQL Injection in search query.
     */
    public function test_sql_injection_in_search_query_is_neutralized(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenant);
        Customer::create([
            'customer_type' => 'individual',
            'name' => 'Hidden VIP Client',
            'status' => 'active',
        ]);

        // Payload intended to match all records if concatenated unsafely
        $res = $this->withHeaders([
            'X-Tenant-Slug' => $this->slug,
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/customers?search=' . urlencode("' OR '1'='1' --"));

        $res->assertStatus(200);
        // Parameterized search looks for literal string match -> returns 0
        $this->assertCount(0, $res->json('data'));
    }

    // ==========================================
    // 3. File Upload & RCE Attacks
    // ==========================================

    /**
     * Attack Test: Direct PHP Web Shell Upload.
     */
    public function test_php_web_shell_upload_is_blocked(): void
    {
        Storage::fake('public');

        $maliciousPhpFile = UploadedFile::fake()->createWithContent('shell.php', '<?php phpinfo(); ?>');

        $res = $this->withHeaders([
            'X-Tenant-Slug' => $this->slug,
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/customers', [
            'customer_type' => 'individual',
            'name' => 'Hacker Client',
            'status' => 'active',
            'documents' => [$maliciousPhpFile],
        ]);

        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['documents.0']);
    }

    /**
     * Attack Test: Dangerous script extensions (.phtml, .exe, .sh).
     */
    public function test_dangerous_script_extensions_are_blocked(): void
    {
        Storage::fake('public');

        $dangerousFiles = [
            UploadedFile::fake()->create('malware.exe', 100),
            UploadedFile::fake()->create('backdoor.phtml', 100),
            UploadedFile::fake()->create('script.sh', 100),
        ];

        foreach ($dangerousFiles as $file) {
            $res = $this->withHeaders([
                'X-Tenant-Slug' => $this->slug,
                'Authorization' => 'Bearer ' . $this->token,
            ])->postJson('/api/customers', [
                'customer_type' => 'individual',
                'name' => 'Script Attacker',
                'status' => 'active',
                'documents' => [$file],
            ]);

            $res->assertStatus(422);
        }
    }

    // ==========================================
    // 4. DOS & Rate Limiting Attacks
    // ==========================================

    /**
     * Attack Test: Brute Force Login DOS Attack triggers 429 Too Many Requests.
     */
    public function test_login_brute_force_is_throttled(): void
    {
        $email = 'brute.target@sarh.test';

        // 5 allowed attempts
        for ($i = 0; $i < 5; $i++) {
            $this->withHeaders([
                'X-Tenant-Slug' => $this->slug,
            ])->postJson('/api/auth/login', [
                'tenant' => $this->slug,
                'email' => $email,
                'password' => 'WrongPassword!',
            ]);
        }

        // 6th attempt must be blocked by rate limiter (429)
        $rateLimitedRes = $this->withHeaders([
            'X-Tenant-Slug' => $this->slug,
        ])->postJson('/api/auth/login', [
            'tenant' => $this->slug,
            'email' => $email,
            'password' => 'WrongPassword!',
        ]);

        $rateLimitedRes->assertStatus(429);
        $this->assertEquals('TOO_MANY_ATTEMPTS', $rateLimitedRes->json('error_code'));
    }

    /**
     * Attack Test: Large pagination DOS attempt (?per_page=10000000).
     */
    public function test_large_pagination_dos_is_capped(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenant);
        for ($i = 1; $i <= 5; $i++) {
            Customer::create([
                'customer_type' => 'individual',
                'name' => "Client {$i}",
                'status' => 'active',
            ]);
        }

        $res = $this->withHeaders([
            'X-Tenant-Slug' => $this->slug,
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/customers?per_page=10000000');

        $res->assertStatus(200);
        // Per-page must be safely capped to 100 max
        $this->assertEquals(100, $res->json('meta.per_page'));
    }
}
