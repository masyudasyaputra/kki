<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\TableSort;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TableSortTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'table_sort_test', 'database.connections.table_sort_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        Schema::create('provinces', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('province_id');
            $table->timestamp('created_at');
        });
        DB::table('provinces')->insert([['id' => 1, 'name' => 'Zeta'], ['id' => 2, 'name' => 'Alpha']]);
        DB::table('users')->insert([
            ['id' => 1, 'name' => 'Zaki', 'province_id' => 1, 'created_at' => '2026-01-01'],
            ['id' => 2, 'name' => 'Adi', 'province_id' => 1, 'created_at' => '2026-01-02'],
            ['id' => 3, 'name' => 'Budi', 'province_id' => 2, 'created_at' => '2026-01-03'],
        ]);
    }

    public function test_sorting_happens_before_pagination_and_keeps_access_scope(): void
    {
        $query = TableSort::apply(User::where('province_id', 1), Request::create('/', 'GET', [
            'sort' => 'name', 'direction' => 'asc',
        ]), ['name' => 'name']);
        $page = $query->paginate(1);
        $this->assertSame('Adi', $page->items()[0]->name);
        $this->assertSame(2, $page->total());
    }

    public function test_related_columns_can_be_sorted(): void
    {
        $rows = TableSort::apply(User::query(), Request::create('/', 'GET', [
            'sort' => 'province', 'direction' => 'asc',
        ]), ['province' => ['province', 'name']])->get();
        $this->assertSame([3, 1, 2], $rows->modelKeys());
    }

    public function test_unknown_and_array_sort_keys_fall_back_to_default_order(): void
    {
        foreach (['name desc; drop table users', ['name']] as $sort) {
            $rows = TableSort::apply(User::query(), Request::create('/', 'GET', [
                'sort' => $sort,
            ]), ['name' => 'name'])->get();
            $this->assertSame([3, 2, 1], $rows->modelKeys());
        }
    }

    public function test_invalid_direction_is_normalized(): void
    {
        $rows = TableSort::apply(User::query(), Request::create('/', 'GET', [
            'sort' => 'name', 'direction' => 'desc; drop table users',
        ]), ['name' => 'name'])->get();
        $this->assertSame(['Adi', 'Budi', 'Zaki'], $rows->pluck('name')->all());
    }
}
