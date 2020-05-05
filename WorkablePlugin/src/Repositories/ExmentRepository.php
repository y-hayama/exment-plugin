<?php

namespace App\Plugins\WorkableSync;

require_once __DIR__ . '/IExmentRepository.php';

use Exceedone\Exment\Model\CustomTable;
use App\Plugins\WorkableSync\IExmentRepository;

class ExmentRepository implements IExmentRepository {
    public function store(CustomTable $table, array $candidates) {
        foreach ($candidates as $candidate) {
            \Log::debug(var_export($candidate, true));

            $model = $table->getValueModel();
            $result = $table->searchValue($candidate->get("id"));
            if($result->count() > 0) {
                \Log::debug("candidate hit: " . $result->count());
                $model = $result->get(0);
            }
    
            $model->setValue("workable_id", $candidate->get("id"));
            $model->setValue("firstname_kanji", $candidate->get("firstname"));
            $model->setValue("lastname_kankji", $candidate->get("lastname"));
            $model->setValue("workable_create_date", $candidate->get("created_at"));
            $model->setValue("workable_update_date", $candidate->get("updated_at"));
            $model->setValue("workable_job_title", $candidate->get("job")["title"]);
            $model->setValue("workable_candidate_stage", $candidate->get("stage"));
            $model->setValue("workable_disqualified", $candidate->get("disqualified"));
            $model->setValue("workable_profile_url", $candidate->get("profile_url"));
            $model->setValue("workable_domain", $candidate->get("domain"));
            $result = $model->save();
    
            \Log::debug(var_export($result, true));
        }

        // $model = $table->find(27);
        // $table->getValueModel(27)->delete();
        return count($candidates);
    }
}
