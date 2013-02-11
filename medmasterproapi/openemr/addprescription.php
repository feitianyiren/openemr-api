<?php
/**
 * Copyright (C) 2012 Karl Englund <karl@mastermobileproducts.com>
 *
 * LICENSE: This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 3
 * of the License, or (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://opensource.org/licenses/gpl-3.0.html>;.
 *
 * @package OpenEMR
 * @author  Karl Englund <karl@mastermobileproducts.com>
 * @link    http://www.open-emr.org
 */
header("Content-Type:text/xml");
$ignoreAuth = true;
require('classes.php');

$xml_string = "";
$xml_string = "<prescription>";

$token = $_POST['token'];
$patientId = add_escape_custom($_POST['patientId']);
$startDate = $_POST['startDate'];
$drug = add_escape_custom($_POST['drug']);
$visit_id = add_escape_custom($_POST['visit_id']);
$dosage = add_escape_custom($_POST['dosage']);
$quantity = add_escape_custom($_POST['quantity']);

$per_refill = add_escape_custom($_POST['refill']);
$medication = add_escape_custom($_POST['medication']);
$note = add_escape_custom($_POST['note']);
$provider_id = add_escape_custom($_POST['provider_id']);

if ($userId = validateToken($token)) {
    $user = getUsername($userId);
    $acl_allow = acl_check('patients', 'med', $user);

    $_SESSION['authUser'] = $user;
    $_SESSION['authGroup'] = $site;
    $_SESSION['pid'] = $patientId;

    if ($acl_allow) {
        $provider_username = getProviderUsername($provider_id);

        $strQuery = "INSERT INTO prescriptions (patient_id, date_added, date_modified, provider_id, start_date, drug, dosage, quantity, refills, medication, note, active, encounter) 
                                            VALUES (" . $patientId . ", '" . date('Y-m-d') . "', '" . date('Y-m-d') . "', {$provider_id}, '" . $startDate . "', '" . $drug . "', '" . $dosage . "', '" . $quantity . "', '" . $per_refill . "', " . $medication . ", '" . $note . "', 1, {$visit_id})";

        if ($medication) {
            $list_query = "insert into lists(date,begdate,type,activity,pid,user,groupname,title) 
                            values (now(),cast(now() as date),'medication',1,{$patientId},'{$user}','','{$drug}')";
            $list_result = sqlStatement($list_query);
        }
        $result = sqlStatement($strQuery);

        $device_token_badge = getDeviceTokenBadge($provider_username, 'prescription');
        $badge = $device_token_badge ['badge'];
        $deviceToken = $device_token_badge ['device_token'];
        if ($deviceToken) {
            $notification_res = notification($deviceToken, $badge, $msg_count = 0, $apt_count = 0, $message = 'New Prescription Notification!');
        }

        if ($result && $list_result) {


            $xml_string .= "<status>0</status>";
            $xml_string .= "<reason>Patient prescription added successfully</reason>";
            if ($notification_res) {
                $xml_array['notification'] = 'Add Prescription Notification(' . $notification_res . ')';
            } else {
                $xml_array['notification'] = 'Notificaiotn Failed.';
            }
        } else {
            $xml_string .= "<status>-1</status>";
            $xml_string .= "<reason>Couldn't add record</reason>";
        }
    } else {
        $xml_string .= "<status>-2</status>\n";
        $xml_string .= "<reason>You are not Authorized to perform this action</reason>\n";
    }
} else {
    $xml_string .= "<status>-2</status>";
    $xml_string .= "<reason>Invalid Token</reason>";
}

$xml_string .= "</prescription>";
echo $xml_string;
?>