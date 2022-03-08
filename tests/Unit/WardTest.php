<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Ward;
use Illuminate\Database\Eloquent\Collection;

class WardTest extends TestCase
{
    
    public function test_a_ward_has_many_jail()
    {
        $ward=new Ward;

        $this->assertInstanceOf(Collection::class, $ward->jail);
    }
}
