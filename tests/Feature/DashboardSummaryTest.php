<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Dashboard\DashboardSummary;
use Carbon\CarbonImmutable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DashboardSummaryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 10, 25)->startOfDay());
        config(['database.default' => 'dashboard_test', 'database.connections.dashboard_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        foreach ([
            'users' => ['name', 'role', 'province_id', 'city_id', 'dojo_id', 'belt_level_id', 'is_active', 'email', 'whatsapp', 'parent_name', 'created_at'],
            'provinces' => ['name'],
            'cities' => ['name', 'province_id'],
            'dojos' => ['name', 'city_id', 'sk_expiry_date'],
            'officials' => ['name', 'province_id', 'city_id', 'sk_expiry_date'],
            'exams' => ['name', 'province_id', 'execution_date', 'status', 'location'],
            'exam_participants' => ['exam_id', 'user_id', 'dojo_id', 'fee_amount', 'payment_status'],
            'belt_levels' => ['name', 'order'],
            'payments' => ['user_id', 'type', 'status', 'amount', 'paid_at', 'created_at', 'updated_at', 'expires_at'],
        ] as $name => $columns) {
            Schema::create($name, function (Blueprint $table) use ($columns) {
                $table->id();
                foreach ($columns as $column) {
                    if (str_ends_with($column, '_id') || in_array($column, ['amount', 'fee_amount', 'order', 'is_active'])) {
                        $table->integer($column)->nullable();
                    } else {
                        $table->string($column)->nullable();
                    }
                }
            });
        }
        DB::table('users')->insert([
            ['id' => 1, 'name' => 'Admin A', 'role' => 'pengprov', 'province_id' => 1, 'city_id' => 1, 'dojo_id' => 1, 'belt_level_id' => null, 'is_active' => 1],
            ['id' => 2, 'name' => 'Anggota A', 'role' => 'member', 'province_id' => 1, 'city_id' => 1, 'dojo_id' => 1, 'belt_level_id' => 1, 'is_active' => 1],
            ['id' => 3, 'name' => 'Anggota B', 'role' => 'member', 'province_id' => 2, 'city_id' => 2, 'dojo_id' => 2, 'belt_level_id' => 1, 'is_active' => 1],
            ['id' => 4, 'name' => 'Anggota C', 'role' => 'member', 'province_id' => 1, 'city_id' => 1, 'dojo_id' => 1, 'belt_level_id' => 1, 'is_active' => 0],
        ]);
        DB::table('provinces')->insert([['id' => 1, 'name' => 'Provinsi A'], ['id' => 2, 'name' => 'Provinsi B']]);
        DB::table('cities')->insert([['id' => 1, 'name' => 'A', 'province_id' => 1], ['id' => 2, 'name' => 'B', 'province_id' => 2]]);
        DB::table('dojos')->insert([['id' => 1, 'name' => 'A', 'city_id' => 1, 'sk_expiry_date' => '2026-11-01'], ['id' => 2, 'name' => 'B', 'city_id' => 2, 'sk_expiry_date' => '2026-09-01']]);
        DB::table('officials')->insert(['name' => 'Pengurus A', 'province_id' => 1, 'city_id' => 1, 'sk_expiry_date' => '2026-10-20']);
        DB::table('belt_levels')->insert(['id' => 1, 'name' => 'Putih', 'order' => 1]);
        DB::table('exams')->insert([
            ['id' => 1, 'name' => 'Ujian A', 'province_id' => 1, 'execution_date' => '2026-10-30', 'status' => 'open', 'location' => 'A'],
            ['id' => 2, 'name' => 'Ujian B', 'province_id' => 2, 'execution_date' => '2026-10-30', 'status' => 'open', 'location' => 'B'],
        ]);
        DB::table('exam_participants')->insert([
            ['exam_id' => 1, 'user_id' => 2, 'dojo_id' => 1, 'fee_amount' => 200, 'payment_status' => 'paid'],
            ['exam_id' => 2, 'user_id' => 3, 'dojo_id' => 2, 'fee_amount' => 900, 'payment_status' => 'unpaid'],
        ]);
        $defaults = ['user_id' => 1, 'type' => 'iuran', 'status' => 'paid', 'amount' => 100, 'paid_at' => '2026-10-10', 'created_at' => '2026-09-01', 'updated_at' => '2026-10-10', 'expires_at' => null];
        foreach ([[], ['user_id' => 2, 'amount' => 50, 'paid_at' => null], ['user_id' => 3, 'amount' => 900],
            ['amount' => 500, 'paid_at' => '2026-09-30'],
            ['status' => 'pending', 'amount' => 40, 'created_at' => '2026-10-20', 'expires_at' => '2026-11-01'],
            ['status' => 'pending', 'amount' => 60, 'created_at' => '2026-10-20', 'expires_at' => '2026-10-21'],
            ['type' => 'exam_fee', 'amount' => 20],
        ] as $overrides) {
            DB::table('payments')->insert(array_merge($defaults, $overrides));
        }
    }

    public function test_receipts_include_collective_payer_and_do_not_double_count_participants(): void
    {
        $summary = app(DashboardSummary::class)->build(User::findOrFail(1), 'pengprov', CarbonImmutable::parse('2026-10-01'));
        $this->assertEquals(150, $summary['finance']['iuran']['paid']);
        $this->assertEquals(20, $summary['finance']['ujian']['paid']);
        $this->assertEquals(170, $summary['finance']['total']);
        $this->assertEquals(40, $summary['finance']['pending']);
        $this->assertEquals(200, $summary['examSummary']['paid_amount']);
        $this->assertSame(1, $summary['examSummary']['sessions']);
        $this->assertSame(1, $summary['upcomingExams']->count());
    }

    public function test_organization_and_belt_counts_cover_the_whole_access_scope(): void
    {
        $summary = app(DashboardSummary::class)->build(User::findOrFail(1), 'pengprov', CarbonImmutable::parse('2026-10-01'));
        $this->assertSame(2, $summary['organization']['members']);
        $this->assertSame(1, $summary['organization']['active_members']);
        $this->assertSame(1, $summary['organization']['dojos']);
        $this->assertSame(1, $summary['organization']['dojo_expiring']);
        $this->assertSame(1, $summary['organization']['official_expired']);
        $this->assertSame(['Putih' => 2], $summary['beltCounts']);
    }

    public function test_an_unassigned_regional_account_cannot_see_unassigned_or_other_regions(): void
    {
        $actor = User::findOrFail(1);
        $actor->province_id = null;
        $summary = app(DashboardSummary::class)->build($actor, 'pengprov', CarbonImmutable::parse('2026-10-01'));
        $this->assertSame(0, $summary['organization']['members']);
        $this->assertSame(0, $summary['organization']['dojos']);
        $this->assertEquals(0, $summary['finance']['total']);
        $this->assertSame(0, $summary['examSummary']['participants']);
    }

    public function test_finance_panel_renders_month_and_separate_exam_administration(): void
    {
        $month = CarbonImmutable::parse('2026-10-01');
        $summary = app(DashboardSummary::class)->build(User::findOrFail(1), 'pengprov', $month);
        $html = view('admin.partials.dashboard-finance', array_merge($summary, ['month' => $month]))->render();
        $this->assertStringContainsString('Penerimaan iuran', $html);
        $this->assertStringContainsString('Penerimaan ujian', $html);
        $this->assertStringContainsString('Administrasi ujian', $html);
        $this->assertStringContainsString('value="2026-10"', $html);
        $this->assertStringContainsString('Ujian A', $html);
        $this->assertStringNotContainsString('Ujian B', $html);
    }
    public function test_full_dashboard_renders_the_new_summary_with_member_filters(): void
    {
        $actor = User::findOrFail(1);
        $actor->roles = [];
        $this->actingAs($actor);
        $month = CarbonImmutable::parse('2026-10-01');
        $summary = app(DashboardSummary::class)->build($actor, 'pengprov', $month);
        $html = view('admin.dashboard', array_merge($summary, [
            'month' => $month, 'title' => 'Dashboard Pengurus Provinsi', 'role' => 'pengprov',
            'members' => User::where('role', 'member')->where('province_id', 1)->paginate(10),
            'beltLevels' => \App\Models\BeltLevel::all(), 'cities' => collect(), 'provinces' => collect(), 'dojos' => collect(),
        ]))->render();
        $this->assertStringContainsString('Ringkasan organisasi', $html);
        $this->assertStringContainsString('Total penerimaan tercatat', $html);
        $this->assertStringContainsString('Daftar Anggota', $html);
        $this->assertStringContainsString('Perbandingan iuran &amp; ujian', str_replace('& ujian', '&amp; ujian', $html));
        $this->assertStringContainsString('Lihat rincian semua cabang', $html);
        $this->assertStringContainsString('Anggota A', $html);
        $this->assertStringNotContainsString('Anggota B', $html);
    }

    public function test_dojo_counts_are_grouped_by_branch_and_remain_scoped(): void
    {
        DB::table('dojos')->insert(['name' => 'Dojo tambahan', 'city_id' => 1, 'sk_expiry_date' => '2026-09-01']);
        $summary = app(DashboardSummary::class)->build(User::findOrFail(1), 'pengprov', CarbonImmutable::parse('2026-10-01'));
        $this->assertCount(1, $summary['branchDojos']);
        $branch = $summary['branchDojos']->first();
        $this->assertSame('A', $branch->city->name);
        $this->assertEquals(2, $branch->dojo_count);
        $this->assertEquals(1, $branch->active_dojo_count);
    }

    public function test_charts_render_safely_when_all_values_are_zero(): void
    {
        $actor = User::findOrFail(1);
        $actor->province_id = null;
        $month = CarbonImmutable::parse('2026-10-01');
        $summary = app(DashboardSummary::class)->build($actor, 'pengprov', $month);
        $html = view('admin.partials.dashboard-charts', array_merge($summary, ['month' => $month]))->render();
        $this->assertStringContainsString('Belum ada data dojo.', $html);
        $this->assertStringNotContainsString('NaN', $html);
        $this->assertStringContainsString('width: 0%', $html);
    }

    public function test_members_menu_lists_only_members_within_the_accounts_region(): void
    {
        $actor = User::findOrFail(1);
        $actor->roles = [];
        $this->actingAs($actor)->get(route('admin.members.index'))
            ->assertOk()->assertSee('Data Anggota')->assertSee('Anggota A')->assertSee('Anggota C')
            ->assertDontSee('Anggota B')->assertSee('Tambah Anggota')->assertSee('data-server-sort', false);
    }

    public function test_members_filters_sorting_and_foreign_region_filter_remain_scoped(): void
    {
        $actor = User::findOrFail(1);
        $actor->roles = [];
        $this->actingAs($actor)->get(route('admin.members.index', ['status' => '0', 'sort' => 'name', 'direction' => 'asc']))
            ->assertOk()->assertSee('Anggota C')->assertDontSee('Anggota A')->assertDontSee('Anggota B');
        $this->get(route('admin.members.index', ['province_id' => 2]))
            ->assertOk()->assertSee('Tidak ada anggota yang sesuai.')->assertDontSee('Anggota B');
    }

    public function test_regular_members_cannot_access_the_management_list(): void
    {
        $this->actingAs(User::findOrFail(2))->get(route('admin.members.index'))->assertForbidden();
    }

}
