<?php

namespace App\Services;

use App\Models\Settings\Prefix;

class RunningNumberServices
{
    public function getRunningNumberReceipt()
    {
        $data = Prefix::firstOrFail();

        $data_prefix = $data->prefix_receipt;
        $data_runnig_nbr = $data->running_nbr_receipt;

        $curr_year = date('y');
        $result = '';

        if (substr($data_runnig_nbr, 0, 2) == $curr_year) {
            // same year
            $new_rn = $data_runnig_nbr + 1;
            $result = $data_prefix . $data_runnig_nbr + 1;
        } else {
            // diff year
            $new_rn = $curr_year . '000001';
            $result = $data_prefix . $curr_year . '000001';
        }

        $data->running_nbr_receipt = $new_rn;
        $data->save();

        return $result;
    }
}
