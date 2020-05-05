<?php

namespace App\Plugins\WorkableSync;

interface IWorkableRepository {
    public function getCandidate(\DateTime $created_after);
}
