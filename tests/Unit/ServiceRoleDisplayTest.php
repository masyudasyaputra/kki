<?php

namespace Tests\Unit;

use App\Models\User;
use PHPUnit\Framework\TestCase;

class ServiceRoleDisplayTest extends TestCase
{
    public function test_service_role_displays_once_while_its_permission_remains_effective(): void
    {
        $user = new User(['role' => 'member', 'positions' => ['verifikasi_data_sabuk_hitam', 'member'], 'roles' => []]);
        $this->assertSame(['verifikasi_data_sabuk_hitam', 'member'], $user->displayRoles());
        $this->assertNotContains('verifikator_sabuk_hitam', $user->assignedRoles());
        $this->assertTrue($user->hasRole('verifikator_sabuk_hitam'));
    }

    public function test_redundant_explicit_permission_is_hidden_but_other_assignments_are_preserved(): void
    {
        $user = new User(['role' => 'verifikasi_data_sabuk_hitam', 'positions' => ['verifikasi_data_sabuk_hitam', 'member'], 'roles' => ['verifikator_sabuk_hitam', 'penguji']]);
        $this->assertEqualsCanonicalizing(['verifikasi_data_sabuk_hitam', 'member', 'penguji'], $user->displayRoles());
        $this->assertContains('verifikator_sabuk_hitam', $user->assignedRoles());
        $this->assertTrue($user->hasRole('verifikator_sabuk_hitam'));
        $user->positions = ['member']; $user->role = 'member';
        $this->assertContains('verifikator_sabuk_hitam', $user->displayRoles());
    }
}
