<?php

namespace App\Plugins\WorkableSync;

use Exceedone\Exment\Model\CustomTable;

interface IExmentRepository {
    public function store(CustomTable $table, array $candidates);
}
