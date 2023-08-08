<?php
require_once(__DIR__.'/../../../../config.php');
use local_hourslog\lib;
require_login();
$lib = new lib;
$returnText = new stdClass();
$p = 'local_hourslog';

if(isset($_POST['id'])){
    $id = $_POST['id'];
    if(!preg_match("/^[0-9]*$/", $id) || empty($id)){
        $returnText->error = get_string('invalid_ip', $p);
    } else {
        $result = $lib->get_hours_log_id_data($id);
        if($result){
            $html = "
                <form action='' id='hourslog_form_u'>
                    <table class='table table-bordered table-striped table-hover'>
                        <thead>
                            <tr>
                                <th>".get_string('date', $p)."</th>
                                <th>".get_string('activity', $p)."</th>
                                <th>".get_string('what_link_title', $p)."</th>
                                <th>".get_string('what_learn_title', $p)."</th>
                                <th>".get_string('duration_title', $p)."</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td id='td_date_u'><input type='date' class='w-100' id='date_u' value='$result[1]'></td>
                                <td id='td_activity_u' class='w-25'><textarea class='w-100' id='activity_u'>$result[2]</textarea></td>
                                <td id='td_whatlink_u' class='w-25'>
                                    <select class='w-100' id='whatlink_u'>
            ";
            /*<option value='{{0}}'>{{0}}</option>*/
            $array = $lib->get_plan_json_modules();
            foreach($array as $arr){
                $html .= ($arr[0] == $result[3]) ? "<option value='$arr[0]' selected>$arr[0]</option>" : "<option value='$arr[0]'>$arr[0]</option>";
            }
            $html .="               
                                    </select>
                                </td>
                                <td id='td_impact_u' class='w-50'><textarea class='w-100' id='impact_u'>$result[4]</textarea></td>
                                <td id='td_duration_u'><input type='number' class='w-100' step='0.01' id='duration_u' value='$result[5]'></td>
                            </tr>
                        </tbody>
                    </table>
                    <h2 style='display:none;' class='text-error' id='hl_error_u'></h2>
                    <button class='btn btn-primary' type='submit'>".get_string('update', $p)."</button>
                </form>
            ";
            $returnText->return = str_replace("  ","",$html);
            $_SESSION['hl_records_lid'] = $id;
        } else {
            $returnText->return = false;
        }
    }
} else {
    $returnText->error = get_string('no_ip', $p);
}
echo(json_encode($returnText));