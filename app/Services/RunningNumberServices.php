<?php

namespace App\Services;

use App\Models\Settings\Prefix;

class RunningNumberServices
{
    public function getRunningNumber()
    {
        $data = Prefix::where('domain_id', '1')->firstOrFail(); // hardcode RISIS

        $data_prefix = $data->prefix_item_master;
        $data_runnig_nbr = $data->running_nbr_item_master;

        $curr_year = date('y');
        $result = '';

        if(substr($data_runnig_nbr ,0 ,2) == $curr_year){
            // same year
            $new_rn = $data_runnig_nbr + 1;
            $result = $data_prefix.$data_runnig_nbr + 1;
        }else{
            // diff year
            $new_rn = $curr_year.substr($data_runnig_nbr,2,8);
            $result = $data_prefix.$curr_year.substr($data_runnig_nbr,2,8);
        }

        $data->running_nbr_item_master = $new_rn;
        $data->save();

        return $result;
    }
}
