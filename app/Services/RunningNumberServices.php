<?php

namespace App\Services;

use App\Models\Settings\Prefix;
use App\Models\Settings\ShipmentSchedulePrefix;

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

    public function getRunningNumberBuku()
    {
        $data = Prefix::firstOrFail();

        $data_prefix = $data->prefix_buku_penerimaan;
        $data_runnig_nbr = $data->running_nbr_buku_penerimaan;

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

        $data->running_nbr_buku_penerimaan = $new_rn;
        $data->save();

        return $result;
    }

    public function getRunningNumberShipmentSchedule()
    {
        $data = ShipmentSchedulePrefix::firstOrFail();

        $data_prefix = $data->ship_schedule_prefix;
        $data_running_nbr = $data->ship_schedule_running_nbr;

        $curr_year = date('y');
        $curr_month = date('m');
        $result = '';

        if ($data->ship_schedule_year != $curr_year) {
            // same year
            $data->ship_schedule_year = $curr_year;
            $data->ship_schedule_month = $curr_month;
            $data->ship_schedule_running_nbr = 0;

            $data_running_nbr = $data->ship_schedule_running_nbr;
        }

        if ($data->ship_schedule_month != $curr_month) {
            $data->ship_schedule_month = $curr_month;
        }

        $new_rn = $data_running_nbr + 1;
        $padded_number = str_pad($new_rn, 4, '0', STR_PAD_LEFT);
        $result = $data_prefix . $curr_year . $curr_month . $padded_number;

        $data->ship_schedule_running_nbr = $new_rn;
        $data->save();

        return $result;
    }
}
