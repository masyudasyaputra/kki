<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PublicMemberStatisticsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'statistics_test', 'database.connections.statistics_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        $this->withoutVite();
        foreach (['provinces', 'belt_levels'] as $name) {
            Schema::create($name, function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->integer('order')->default(0);
                $table->boolean('is_active')->default(true);
            });
        }
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('role');
            $table->string('verification_status')->nullable();
            $table->boolean('is_active');
        });
        Schema::create('dojos', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active');
            $table->date('sk_expiry_date')->nullable();
        });
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->date('execution_date');
            $table->string('status');
        });
    }

    public function test_public_statistics_only_count_active_approved_members(): void
    {
        foreach ([
            ['member', 'approved', true], ['member', 'approved', true],
            ['member', 'pending', true], ['member', 'rejected', true],
            ['member', null, true], ['member', 'approved', false],
            ['super_admin', 'approved', true], ['pengprov', 'approved', true],
        ] as [$role, $status, $active]) {
            DB::table('users')->insert(['role' => $role, 'verification_status' => $status, 'is_active' => $active]);
        }

        $this->get('/')->assertOk()->assertViewHas('verifiedMemberCount', 2)
            ->assertSee('Anggota terverifikasi')->assertSee('verifikasi keanggotaan');
    }

    public function test_public_statistics_show_zero_when_no_members_are_eligible(): void
    {
        $this->get('/')->assertOk()->assertViewHas('verifiedMemberCount', 0)
            ->assertSee('Anggota terverifikasi');
    }
}
