<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

//
class JobCategory extends Pivot
{
    protected $table = 'job_category';
    public $timestamps = false;
    public $incrementing = false;
}
