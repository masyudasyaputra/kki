<?php

namespace Tests\Feature;

use App\Models\BeltLevel;
use App\Services\BeltCatalog;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
use Tests\TestCase;

class BeltCatalogTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'belt_catalog_test', 'database.connections.belt_catalog_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        Schema::create('belt_levels', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('kyu_dan'); $t->integer('order');
            $t->integer('membership_fee'); $t->timestamps();
        });
        Schema::create('users', function (Blueprint $t) { $t->id(); $t->integer('belt_level_id')->nullable(); });
        foreach (['fee_configurations', 'exam_fees'] as $name) Schema::create($name, function (Blueprint $t) {
            $t->id(); $t->integer('province_id')->nullable(); $t->integer('belt_level_id'); $t->integer('amount'); $t->timestamps();
        });
        Schema::create('historic_records', function (Blueprint $t) { $t->id(); $t->integer('belt_level_id'); $t->json('snapshot'); });
    }

    public function test_migration_converts_members_to_seven_kyu_without_rewriting_historical_records(): void
    {
        $names = ['Putih', 'Kuning Muda', 'Kuning Tua', 'Orange', 'Hijau', 'Biru', 'Ungu', 'Cokelat', 'Cokelat', 'Cokelat'];
        foreach ($names as $index => $name) {
            $id = $index + 1;
            BeltLevel::create(['name' => $name, 'kyu_dan' => 'Kyu '.(10 - $index), 'order' => $id, 'membership_fee' => 50000 + $index * 5000]);
            DB::table('users')->insert(['id' => $id, 'belt_level_id' => $id]);
            DB::table('fee_configurations')->insert(['province_id' => 1, 'belt_level_id' => $id, 'amount' => 50000 + $index * 5000]);
            DB::table('exam_fees')->insert(['belt_level_id' => $id, 'amount' => 150000 + $index * 5000]);
        }
        $dan = BeltLevel::create(['name' => 'Hitam', 'kyu_dan' => 'DAN I', 'order' => 11, 'membership_fee' => 150000]);
        DB::table('users')->insert(['id' => 11, 'belt_level_id' => $dan->id]);
        $snapshot = json_encode(['belt' => 'Kuning Muda', 'rank' => 'Kyu 9']);
        DB::table('historic_records')->insert(['belt_level_id' => 2, 'snapshot' => $snapshot]);
        (require database_path('migrations/2026_11_09_000003_set_seven_kyu_belt_levels.php'))->up();

        $active = BeltLevel::active()->where('kyu_dan', 'like', '%Kyu%')->orderBy('order')->get();
        $this->assertSame(array_column(BeltCatalog::KYU, 'name'), $active->pluck('name')->all());
        $this->assertSame(['Kyu 7', 'Kyu 6', 'Kyu 5', 'Kyu 4', 'Kyu 3', 'Kyu 2', 'Kyu 1'], $active->pluck('kyu_dan')->all());
        $expected = ['Putih', 'Kuning', 'Kuning', 'Kuning', 'Hijau', 'Biru Strip', 'Biru Tua', 'Coklat Strip', 'Coklat Strip', 'Coklat Polos/Tua'];
        foreach ($expected as $index => $name) {
            $this->assertSame($active->firstWhere('name', $name)->id, DB::table('users')->where('id', $index + 1)->value('belt_level_id'));
        }
        $this->assertFalse(BeltLevel::findOrFail(2)->is_active);
        $this->assertTrue($dan->fresh()->is_active);
        $this->assertSame($dan->id, DB::table('users')->where('id', 11)->value('belt_level_id'));
        $this->assertDatabaseHas('historic_records', ['belt_level_id' => 2, 'snapshot' => $snapshot]);
        $yellow = $active->firstWhere('name', 'Kuning');
        $this->assertDatabaseHas('fee_configurations', ['province_id' => 1, 'belt_level_id' => $yellow->id, 'amount' => 55000]);
        $this->assertDatabaseHas('exam_fees', ['belt_level_id' => $yellow->id, 'amount' => 155000]);
        $this->assertSame($yellow->id, app(BeltCatalog::class)->next($active->first(), 'kyu')->id);
        $this->assertNull(app(BeltCatalog::class)->next($active->last(), 'kyu'));
        $this->assertNull(app(BeltCatalog::class)->next($active->first(), 'dan'));
        $this->assertSame($dan->id, app(BeltCatalog::class)->next($active->last(), 'dan')->id);
        app(BeltCatalog::class)->syncKyu();
        $this->assertSame(7, BeltLevel::active()->where('kyu_dan', 'like', '%Kyu%')->count());
    }

    public function test_new_install_seed_has_only_the_seven_kyu_and_existing_dan_levels(): void
    {
        Schema::table('belt_levels', fn (Blueprint $t) => $t->boolean('is_active')->default(true));
        $this->seed(\Database\Seeders\BeltLevelSeeder::class);
        $this->assertSame(7, BeltLevel::active()->where('kyu_dan', 'like', '%Kyu%')->count());
        $this->assertSame(6, BeltLevel::active()->where('kyu_dan', 'like', '%DAN%')->count());
        $this->seed(\Database\Seeders\BeltLevelSeeder::class);
        $this->assertDatabaseCount('belt_levels', 13);
    }
}
