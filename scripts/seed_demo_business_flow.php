<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Core\Tenancy\TenantDatabaseManager;
use App\Models\Central\Tenant;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\SiteVisit;
use App\Models\SiteVisitRoom;
use App\Models\Measurement;
use App\Models\MeasurementItem;
use App\Models\User;
use App\Services\MeasurementCalculationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

echo "====================================================\n";
echo "   SARH ERP — End-to-End Demo Business Flow Seeder   \n";
echo "====================================================\n\n";

// 1. Resolve or Create Main Tenant
$tenant = Tenant::first();
if (!$tenant) {
    echo "Creating demo tenant...\n";
    $provisioningService = app(\App\Services\Tenant\TenantProvisioningService::class);
    $res = $provisioningService->provision([
        'name' => 'شركة صرح للمقاولات والتشطيبات',
        'slug' => 'sarh-demo',
        'company_code' => 'SARH-001',
        'domain' => 'demo.localhost',
    ], [
        'name' => 'المدير العام (Owner)',
        'email' => 'admin@sarh.test',
        'password' => 'SarhAdmin123!',
    ]);
    $tenant = $res['tenant'];
}

echo "Active Tenant: [{$tenant->name}] (Slug: {$tenant->slug})\n";
TenantDatabaseManager::switchToTenant($tenant);

// 2. Resolve User (Engineer / Commercial)
$adminUser = User::where('email', 'like', '%admin%')->orWhere('id', 1)->first();
if (!$adminUser) {
    $adminUser = User::create([
        'name' => 'م. أحمد مصطفى (مدير المشروعات)',
        'email' => 'ahmed.mostafa@sarh.test',
        'password' => bcrypt('Password123!'),
        'status' => 'active',
    ]);
}

$calculationService = app(MeasurementCalculationService::class);

DB::transaction(function () use ($adminUser, $calculationService) {
    echo "\n----------------------------------------------------\n";
    echo "1. CUSTOMER (WHO): إنشاء العميل التجاري\n";
    echo "----------------------------------------------------\n";

    $customer = Customer::create([
        'name' => 'مجموعة الفهد للإنشاءات والاستثمار العقاري',
        'company_name' => 'شركة الفهد جروب للتطوير',
        'customer_type' => 'company',
        'contact_person' => 'م. طارق العوضي (مدير التطوير)',
        'email' => 'tarek.awadi@alfahd-group.com',
        'phone' => '01023456789',
        'address' => 'مبنى 44، شارع التسعين الشمالي، التجمع الخامس، القاهرة الجديدة',
        'tax_number' => 'TRX-948201',
        'status' => 'active',
        'notes' => 'عميل استراتيجي - محفظة مشاريع إدارية وتجارية بالتجمع والعاصمة الإدارية.',
        'created_by' => $adminUser->id,
    ]);
    echo "✓ تم إنشاء العميل: {$customer->name} (ID: #{$customer->id})\n";

    echo "\n----------------------------------------------------\n";
    echo "2. LEAD (WHAT THEY WANT): استلام الطلب وتأهيله\n";
    echo "----------------------------------------------------\n";

    $lead = Lead::create([
        'customer_id' => $customer->id,
        'title' => 'طلب أعمال تشطيبات متكاملة للمقر الإداري الجديد (350 م²)',
        'description' => 'العميل يرغب في تشطيب دور إداري كامل يشمل قاعات اجتماعات، مكاتب تنفيذية، منطقة عمل مفتوحة، وخدمات متكاملة.',
        'source' => 'معرض سيتي سكيب / إحالة عميل سابق',
        'status' => 'Qualified',
        'priority' => 'High',
        'estimated_budget' => 1300000.00,
        'assigned_to' => $adminUser->id,
        'created_by' => $adminUser->id,
        'qualified_at' => Carbon::now()->subDays(10),
        'notes' => 'تم التواصل الفني ومطابقة متطلبات العميل، جاهز للتحويل إلى فرصة تجارية.',
    ]);
    echo "✓ تم تسجيل الطلب (Lead): {$lead->title} (ID: #{$lead->id}) - الحالة: مؤهل (Qualified)\n";

    echo "\n----------------------------------------------------\n";
    echo "3. OPPORTUNITY (COMMERCIAL HUB): إنشاء الفرصة التجارية\n";
    echo "----------------------------------------------------\n";

    $opportunity = Opportunity::create([
        'customer_id' => $customer->id,
        'lead_id' => $lead->id,
        'title' => 'مشروع تشطيب المقر الإداري لشركة الفهد - التسعين الشمالي',
        'description' => 'تنفيذ وتجهيز المقر الإداري بالكامل (أرضيات، دهانات، أسقف معلقة، كهرباء، قواطع زجاجية، وشبكات).',
        'stage' => 'Proposal',
        'estimated_value' => 1250000.00,
        'expected_start_date' => Carbon::now()->addDays(15)->toDateString(),
        'expected_close_date' => Carbon::now()->addDays(45)->toDateString(),
        'assigned_to' => $adminUser->id,
        'created_by' => $adminUser->id,
        'notes' => 'الفرصة في مرحلة تقديم العرض والمقايسة الهندسية المعتمدة.',
    ]);

    $lead->update([
        'status' => 'Converted',
        'converted_at' => Carbon::now()->subDays(8),
    ]);
    echo "✓ تم إنشاء الفرصة التجارية: {$opportunity->title} (ID: #{$opportunity->id}) - القيمة التقديرية: 1,250,000 ج.م\n";

    echo "\n----------------------------------------------------\n";
    echo "4. SITE VISIT (WHAT WE SAW): المعاينة الميدانية ورصد الفراغات\n";
    echo "----------------------------------------------------\n";

    $siteVisit = SiteVisit::create([
        'customer_id' => $customer->id,
        'opportunity_id' => $opportunity->id,
        'assigned_to' => $adminUser->id,
        'visit_date' => Carbon::now()->subDays(6)->toDateString(),
        'status' => 'Completed',
        'location_address' => 'التجمع الخامس - مجمع البنوك - الدور الثالث',
        'general_assessment' => 'الموقع على الطوب الأحمر مع وجود محارة تأسيسية جيدة. المناسيب منتظمة ومداخل التكييف المركزي جاهزة.',
        'recommendations' => 'يوصى بعمل دكة ميول خفيفة لعزل الحمامات واستخدام قواطع زجاجية سيكوريت عازلة للصوت.',
        'created_by' => $adminUser->id,
    ]);

    $rooms = [
        ['room_name' => 'قاعة الاجتماعات الرئيسية (Boardroom)', 'estimated_area' => 65.00, 'notes' => 'ارتفاع السقف 3.20 م'],
        ['room_name' => 'مكتب رئيس مجلس الإدارة (CEO Suite)', 'estimated_area' => 45.00, 'notes' => 'مطلوب تجليد حوائط ديكوري'],
        ['room_name' => 'منطقة العمل المفتوحة (Open Workspace)', 'estimated_area' => 160.00, 'notes' => 'توزيع 30 نقطة شبكات'],
        ['room_name' => 'منطقة الاستقبال واللوبي (Reception & Lobby)', 'estimated_area' => 50.00, 'notes' => 'أرضيات بورسلين رخامي'],
        ['room_name' => 'البوفيه ودورات المياه (Pantry & Restrooms)', 'estimated_area' => 30.00, 'notes' => 'تأسيس سباكة وعزل رطوبة'],
    ];

    foreach ($rooms as $r) {
        $siteVisit->rooms()->create($r);
    }
    echo "✓ تم تسجيل وإتمام المعاينة رقم #{$siteVisit->id} ورصد (" . count($rooms) . ") فراغات ميدانية.\n";

    echo "\n----------------------------------------------------\n";
    echo "5. MEASUREMENT V1 (WHAT WE MEASURED): حصر الكميات الهندسي المعتمد\n";
    echo "----------------------------------------------------\n";

    $itemsV1Data = [
        // Boardroom Items
        [
            'room_name' => 'قاعة الاجتماعات الرئيسية (Boardroom)',
            'item_name' => 'أرضيات باركيه HDF ألماني عالي الكثافة (AC5)',
            'unit' => 'm2',
            'measurement_type' => 'area',
            'count' => 1,
            'length' => 10.00,
            'width' => 6.50,
            'deductions' => 0.00, // Net: 65.00 m2
            'notes' => 'شامل طبقة الفوم العازل للصوت بسمك 3 مم.',
        ],
        [
            'room_name' => 'قاعة الاجتماعات الرئيسية (Boardroom)',
            'item_name' => 'دهانات حوائط جوتن فينوماستيك بلاستيك حريري',
            'unit' => 'm2',
            'measurement_type' => 'area',
            'count' => 2,
            'length' => 16.50, // Perimeter = 2 * (10 + 6.5) = 33m -> 2 lines of 16.5
            'width' => 3.20, // Height
            'deductions' => 8.00, // Door & window deduction -> Gross: 105.6, Net: 97.60 m2
            'notes' => 'شامل وشين سيلر و3 سكينات معجون وصنفرة ووجهين بطانة وتشطيب.',
        ],
        [
            'room_name' => 'قاعة الاجتماعات الرئيسية (Boardroom)',
            'item_name' => 'أسقف معلقة جبسوم بورد كناوف أخضر مقاوم للرطوبة',
            'unit' => 'm2',
            'measurement_type' => 'area',
            'count' => 1,
            'length' => 10.00,
            'width' => 6.50,
            'deductions' => 5.00, // Center recessed dome -> Net: 60.00 m2
            'notes' => 'شامل بيت نور غير مباشر وشاسيهات صاج مجلفن محمل.',
        ],
        [
            'room_name' => 'قاعة الاجتماعات الرئيسية (Boardroom)',
            'item_name' => 'وزرات خشبية بارتفاع 10 سم مدهونة لاكيه مط',
            'unit' => 'lm',
            'measurement_type' => 'linear',
            'count' => 2,
            'length' => 16.50,
            'deductions' => 2.00, // Doors openings -> Net: 31.00 lm
            'notes' => 'تثبيت مخفي بكلبسات استانلس.',
        ],
        [
            'room_name' => 'قاعة الاجتماعات الرئيسية (Boardroom)',
            'item_name' => 'مخارج إضاءة سبوت لايت غاطسة LED 12W 3000K',
            'unit' => 'pcs',
            'measurement_type' => 'count',
            'count' => 24.00,
            'deductions' => 0.00, // Net: 24 pcs
            'notes' => 'توزيع متساوي وفق المخطط الكهربائي للإنارة.',
        ],

        // CEO Suite Items
        [
            'room_name' => 'مكتب رئيس مجلس الإدارة (CEO Suite)',
            'item_name' => 'أرضيات بورسلين إسباني ليزر مقاس 60×120 سم',
            'unit' => 'm2',
            'measurement_type' => 'area',
            'count' => 1,
            'length' => 7.50,
            'width' => 6.00,
            'deductions' => 0.00, // Net: 45.00 m2
            'notes' => 'شامل سقية الإيبوكسي وفواصل التمدد.',
        ],
        [
            'room_name' => 'مكتب رئيس مجلس الإدارة (CEO Suite)',
            'item_name' => 'تجليد حوائط بديل خشب WPC وخشب طبيعي أرو',
            'unit' => 'm2',
            'measurement_type' => 'area',
            'count' => 1,
            'length' => 7.50,
            'width' => 3.20,
            'deductions' => 0.00, // Net: 24.00 m2
            'notes' => 'شامل الشاسيه الخشبي المعالج والعزل.',
        ],
        [
            'room_name' => 'مكتب رئيس مجلس الإدارة (CEO Suite)',
            'item_name' => 'دهانات حوائط ديكورية ناعمة',
            'unit' => 'm2',
            'measurement_type' => 'area',
            'count' => 2,
            'length' => 13.50,
            'width' => 3.20,
            'deductions' => 6.00, // Net: 80.40 m2
            'notes' => 'حوائط جانبية وخلفية.',
        ],
        [
            'room_name' => 'مكتب رئيس مجلس الإدارة (CEO Suite)',
            'item_name' => 'كرانيش جبسية مودرن بارتفاع 12 سم',
            'unit' => 'lm',
            'measurement_type' => 'linear',
            'count' => 2,
            'length' => 13.50,
            'deductions' => 0.00, // Net: 27.00 lm
            'notes' => 'تصميم حديث بدون زخارف.',
        ],
        [
            'room_name' => 'مكتب رئيس مجلس الإدارة (CEO Suite)',
            'item_name' => 'مخارج كهرباء وداتا وتجهيز شاشة عرض',
            'unit' => 'pcs',
            'measurement_type' => 'count',
            'count' => 16.00,
            'deductions' => 0.00, // Net: 16 pcs
            'notes' => 'مفاتيح وبراويز شنايدر أصلية.',
        ],

        // Open Workspace Items
        [
            'room_name' => 'منطقة العمل المفتوحة (Open Workspace)',
            'item_name' => 'أرضيات سجاد بلاطات مكتبي Carpet Tiles 50×50 سم',
            'unit' => 'm2',
            'measurement_type' => 'area',
            'count' => 1,
            'length' => 20.00,
            'width' => 8.00,
            'deductions' => 0.00, // Net: 160.00 m2
            'notes' => 'مقاوم للاحتكاك والكهرباء الاستاتيكية.',
        ],
        [
            'room_name' => 'منطقة العمل المفتوحة (Open Workspace)',
            'item_name' => 'دهانات حوائط إكريليك مقاومة للغسيل والاتساخ',
            'unit' => 'm2',
            'measurement_type' => 'area',
            'count' => 2,
            'length' => 28.00,
            'width' => 3.20,
            'deductions' => 18.00, // Window wall deduction -> Net: 161.20 m2
            'notes' => 'لون أوف وايت موحد للمكاتب.',
        ],
        [
            'room_name' => 'منطقة العمل المفتوحة (Open Workspace)',
            'item_name' => 'قواطع زجاجية سيكوريت 10 مم مع قطاعات ألومنيوم أسود',
            'unit' => 'm2',
            'measurement_type' => 'area',
            'count' => 1,
            'length' => 12.00,
            'width' => 3.00,
            'deductions' => 0.00, // Net: 36.00 m2
            'notes' => 'فصل بين المكاتب وقاعة الاجتماعات المصغرة.',
        ],
        [
            'room_name' => 'منطقة العمل المفتوحة (Open Workspace)',
            'item_name' => 'أسقف معلقة بلاطات ألومنيوم/آرمسترونج 60×60 سم',
            'unit' => 'm2',
            'measurement_type' => 'area',
            'count' => 1,
            'length' => 20.00,
            'width' => 8.00,
            'deductions' => 0.00, // Net: 160.00 m2
            'notes' => 'سهلة الفك لأعمال الصيانة والتكييف.',
        ],
        [
            'room_name' => 'منطقة العمل المفتوحة (Open Workspace)',
            'item_name' => 'وحدات إنارة طولية Linear Light LED 120cm معلقة',
            'unit' => 'pcs',
            'measurement_type' => 'count',
            'count' => 32.00,
            'deductions' => 0.00, // Net: 32 pcs
            'notes' => 'إنارة مكتبية مريحة 4000K.',
        ],

        // Wet Areas & Concrete Screed
        [
            'room_name' => 'البوفيه ودورات المياه (Pantry & Restrooms)',
            'item_name' => 'صب خرسانة ميول فومية عازلة للأسطح والأرضيات',
            'unit' => 'm3',
            'measurement_type' => 'volume',
            'count' => 1,
            'length' => 10.00,
            'width' => 3.00,
            'height' => 0.10,
            'deductions' => 0.00, // Net: 3.00 m3
            'notes' => 'تأسيس ميول الصرف قبل العزل الكيميائي.',
        ],
        [
            'room_name' => 'البوفيه ودورات المياه (Pantry & Restrooms)',
            'item_name' => 'سيراميك حوائط وأرضيات فرز أول',
            'unit' => 'm2',
            'measurement_type' => 'area',
            'count' => 1,
            'length' => 10.00,
            'width' => 3.00,
            'deductions' => 0.00, // Net: 30.00 m2
            'notes' => 'مقاوم للرطوبة والانزلاق.',
        ],
        [
            'room_name' => 'البوفيه ودورات المياه (Pantry & Restrooms)',
            'item_name' => 'مخارج ونقاط تغذية وصرف صحي (تواليت/حوض/سخان)',
            'unit' => 'pcs',
            'measurement_type' => 'count',
            'count' => 12.00,
            'deductions' => 0.00, // Net: 12 pcs
            'notes' => 'مواسير بولي بروبلين ألماني كفر الشيخ/باننجر مع الضمان.',
        ],
    ];

    $calcV1Items = [];
    foreach ($itemsV1Data as $idx => $item) {
        $item['sort_order'] = $idx;
        $calcV1Items[] = $calculationService->calculateItem($item);
    }
    $summaryV1 = $calculationService->calculateSummary($calcV1Items);

    $measurementV1 = Measurement::create([
        'opportunity_id' => $opportunity->id,
        'site_visit_id' => $siteVisit->id,
        'measurement_number' => "M-OPP{$opportunity->id}-0001",
        'version' => 1,
        'status' => 'Approved', // Authoritative approved baseline
        'measured_by' => $adminUser->id,
        'measured_at' => Carbon::now()->subDays(4)->toDateString(),
        'reviewed_by' => $adminUser->id,
        'reviewed_at' => Carbon::now()->subDays(3),
        'approved_by' => $adminUser->id,
        'approved_at' => Carbon::now()->subDays(2),
        'total_area' => $summaryV1['total_area'],
        'total_volume' => $summaryV1['total_volume'],
        'total_linear' => $summaryV1['total_linear'],
        'total_count' => $summaryV1['total_count'],
        'notes' => 'المقايسة الهندسية المعتمدة للإصدار الأول مستندة لرفع المعاينة الميدانية ومخططات الأوتوكاد.',
        'created_by' => $adminUser->id,
    ]);

    foreach ($calcV1Items as $calcItem) {
        $measurementV1->items()->create($calcItem);
    }

    echo "✓ تم إنشاء واعتماد المقايسة V1 برقم #{$measurementV1->measurement_number}:\n";
    echo "  - إجمالي المساحات (Total Area): {$measurementV1->total_area} م²\n";
    echo "  - إجمالي الحجوم (Total Volume): {$measurementV1->total_volume} م³\n";
    echo "  - إجمالي الأطوال (Total Linear): {$measurementV1->total_linear} م.ط\n";
    echo "  - إجمالي العدد والقطع (Total Count): {$measurementV1->total_count} قطعة/نقطة\n";
    echo "  - عدد البنود المسجلة: " . count($calcV1Items) . " بنداً هندسياً\n";
    echo "  - الحالة: معتمدة (Approved) كحقيقة هندسية تاريخية.\n";

    echo "\n----------------------------------------------------\n";
    echo "6. MEASUREMENT REVISION V2 (DRAFT -> APPROVED): محاكاة دورة الإصدارات\n";
    echo "----------------------------------------------------\n";

    // Client requested expanding open workspace and adding extra linear lights
    $measurementV2 = Measurement::create([
        'opportunity_id' => $opportunity->id,
        'site_visit_id' => $siteVisit->id,
        'measurement_number' => "M-OPP{$opportunity->id}-0001-V2",
        'version' => 2,
        'status' => 'Approved', // V2 approved -> V1 becomes Superseded
        'measured_by' => $adminUser->id,
        'measured_at' => Carbon::now()->subDays(1)->toDateString(),
        'reviewed_by' => $adminUser->id,
        'reviewed_at' => Carbon::now()->subHours(12),
        'approved_by' => $adminUser->id,
        'approved_at' => Carbon::now()->subHours(2),
        'total_area' => $summaryV1['total_area'] + 20.00, // added 20m2
        'total_volume' => $summaryV1['total_volume'],
        'total_linear' => $summaryV1['total_linear'] + 4.00, // added 4 lm
        'total_count' => $summaryV1['total_count'] + 8.00, // added 8 pcs
        'notes' => 'المراجعة والإصدار رقم 2 المعتمد بعد تعديل مسار قواطع الزجاج وزيادة عدد نقاط الإنارة للمكاتب.',
        'created_by' => $adminUser->id,
    ]);

    foreach ($measurementV1->items as $item) {
        $copy = $item->toArray();
        unset($copy['id'], $copy['measurement_id'], $copy['created_at'], $copy['updated_at']);
        if (str_contains($copy['item_name'], 'بلاطات سجاد')) {
            $copy['width'] = 9.00; // 20 * 9 = 180 m2 (+20)
            $copy['gross_quantity'] = 180.00;
            $copy['net_quantity'] = 180.00;
        }
        if (str_contains($copy['item_name'], 'وحدات إنارة طولية')) {
            $copy['count'] = 40.00; // +8 pcs
            $copy['gross_quantity'] = 40.00;
            $copy['net_quantity'] = 40.00;
        }
        $measurementV2->items()->create($copy);
    }

    // Atomically mark V1 as Superseded now that V2 is Approved
    $measurementV1->update(['status' => 'Superseded']);

    echo "✓ تم إنشاء واعتماد المراجعة V2 برقم #{$measurementV2->measurement_number}:\n";
    echo "  - المقايسة V1 أصبحت: مُستبدلة (Superseded) محفوظة تاريخياً.\n";
    echo "  - المقايسة V2 أصبحت: المعتمدة الحالية (Approved) (إجمالي مساحة: {$measurementV2->total_area} م²).\n";
    echo "  - جاهزة 100% للربط مع نطاق الأعمال المستقبلي (Scope Module).\n";
});

echo "\n====================================================\n";
echo "   DEMO SEEDING COMPLETED SUCCESSFULLY!   \n";
echo "====================================================\n";
