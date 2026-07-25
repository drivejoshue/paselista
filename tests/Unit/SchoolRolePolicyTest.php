<?php

namespace Tests\Unit;

use App\Services\Authorization\SchoolRolePolicy;
use Tests\TestCase;

class SchoolRolePolicyTest extends TestCase
{
    private SchoolRolePolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = app(
            SchoolRolePolicy::class
        );
    }

    public function test_director_can_manage_students(): void
    {
        $this->assertTrue(
            $this->policy->allowsRoute(
                'director',
                'admin.students.store',
                'POST'
            )
        );
    }

    public function test_director_can_manage_cycles(): void
    {
        $this->assertTrue(
            $this->policy->allowsRoute(
                'director',
                'admin.cycles.activate',
                'PATCH'
            )
        );
    }

    public function test_director_can_manage_operational_users(): void
    {
        $this->assertTrue(
            $this->policy->allowsRoute(
                'director',
                'admin.users.store',
                'POST'
            )
        );
    }

    public function test_director_cannot_open_license(): void
    {
        $this->assertFalse(
            $this->policy->allowsRoute(
                'director',
                'admin.license.show',
                'GET'
            )
        );
    }

    public function test_director_cannot_use_technical_tools(): void
    {
        $this->assertFalse(
            $this->policy->allowsRoute(
                'director',
                'admin.tools.index',
                'GET'
            )
        );
    }

    public function test_director_is_denied_unknown_admin_route(): void
    {
        $this->assertFalse(
            $this->policy->allowsRoute(
                'director',
                'admin.future-sensitive-module.index',
                'GET'
            )
        );
    }

    public function test_school_admin_has_admin_bypass(): void
    {
        $this->assertTrue(
            $this->policy->allowsRoute(
                'school_admin',
                'admin.future-sensitive-module.store',
                'POST'
            )
        );
    }
}
