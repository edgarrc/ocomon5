<?php session_start();
/*      Copyright 2023 Flávio Ribeiro

        This file is part of OCOMON.

        OCOMON is free software; you can redistribute it and/or modify
        it under the terms of the GNU General Public License as published by
        the Free Software Foundation; either version 3 of the License, or
        (at your option) any later version.
        OCOMON is distributed in the hope that it will be useful,
        but WITHOUT ANY WARRANTY; without even the implied warranty of
        MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
        GNU General Public License for more details.

        You should have received a copy of the GNU General Public License
        along with Foobar; if not, write to the Free Software
        Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

if (!isset($_SESSION['s_logado']) || $_SESSION['s_logado'] == 0) {
    $_SESSION['session_expired'] = 1;
    echo "<script>top.window.location = '../../index.php'</script>";
    exit;
}

require_once __DIR__ . "/" . "../../includes/include_basics_only.php";
require_once __DIR__ . "/" . "../../includes/classes/ConnectPDO.php";

use includes\classes\ConnectPDO;

$conn = ConnectPDO::getInstance();

$post = $_POST;

$exception = "";
$screenNotification = "";
$data = [];
$data['success'] = true;
$data['message'] = "";
$data['cod'] = (isset($post['cod']) ? intval($post['cod']) : "");
$data['action'] = $post['action'];
$data['field_id'] = "";

$data['database'] = DB_CCUSTO;
$data['table'] = TB_CCUSTO;
$data['table_id'] = CCUSTO_ID;
$data['table_cost_number'] = CCUSTO_COD;
$data['table_cost_description'] = CCUSTO_DESC;

$data['costcenter_name'] = (isset($post['costcenter_name']) ? noHtml($post['costcenter_name']) : "");
$data['costcenter_number'] = (isset($post['costcenter_number']) ? noHtml($post['costcenter_number']) : "");
$data['client'] = (isset($post['client']) ? noHtml($post['client']) : "");


/* Validações */
if ($data['action'] == "new" || $data['action'] == "edit") {

    if (empty($data['costcenter_name'])) {
        $data['success'] = false; 
        $data['field_id'] = "costcenter_name";
    } elseif (empty($data['costcenter_number'])) {
        $data['success'] = false; 
        $data['field_id'] = "costcenter_number";
    }

    if ($data['success'] == false) {
        $data['message'] = message('warning', 'Ooops!', TRANS('MSG_EMPTY_DATA'),'');
        echo json_encode($data);
        return false;
    }
}

if ($data['action'] == 'new') {

    $terms = (!empty($data['client']) ? " client = {$data['client']} " :  " client IS NULL ");


    /* verifica se um registro com esse nome já existe */
    $sql = "SELECT * FROM `" . $data['database'] . "`." . $data['table'] . " WHERE (" . $data['table_cost_description'] . " = '" . $data['costcenter_name'] . "' OR  " . $data['table_cost_number'] . " = '" . $data['costcenter_number'] . "') AND {$terms}";
    $res = $conn->query($sql);
    if ($res->rowCount()) {
        $data['success'] = false; 
        $data['field_id'] = "costcenter_name";
        $data['message'] = message('warning', '', TRANS('MSG_RECORD_EXISTS'), '');
        echo json_encode($data);
        return false;
    }


    if (!csrf_verify($post)) {
        $data['success'] = false; 
        $data['message'] = message('warning', 'Ooops!', TRANS('FORM_ALREADY_SENT'),'');
    
        echo json_encode($data);
        return false;
    }

    $sql = "INSERT INTO `" . $data['database'] . "`." . $data['table'] . " 
        (
            " . $data['table_cost_description'] . ", 
            " . $data['table_cost_number'] . ",
            client 
        ) 
        VALUES 
        (
            '" . $data['costcenter_name'] . "', 
            '" . $data['costcenter_number'] . "', 
            ".dbField($data['client'])."
        )";

    try {
        $conn->exec($sql);
        $data['success'] = true; 
        $data['message'] = TRANS('MSG_SUCCESS_INSERT');
        $_SESSION['flash'] = message('success', '', $data['message'], '');
        echo json_encode($data);
        return false;
    } catch (Exception $e) {
        $exception .= "<hr>" . $e->getMessage();
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_ERR_SAVE_RECORD') . $exception;
        $_SESSION['flash'] = message('danger', '', $data['message'], '');
        echo json_encode($data);
        return false;
    }

} elseif ($data['action'] == 'edit') {

    $terms = (!empty($data['client']) ? " client = {$data['client']} " :  " client IS NULL ");

    /* verifica se um registro com esse nome já existe para outro código */
    $sql = "SELECT * FROM `" . $data['database'] . "`." . $data['table'] . " WHERE (" . $data['table_cost_description'] . " = '" . $data['costcenter_name'] . "' OR  " . $data['table_cost_number'] . " = '" . $data['costcenter_number'] . "') AND " . $data['table_id'] . " <> '" . $data['cod'] . "' AND {$terms}";
    $res = $conn->query($sql);
    if ($res->rowCount()) {
        $data['success'] = false; 
        $data['field_id'] = "costcenter_name";
        $data['message'] = message('warning', '', TRANS('MSG_RECORD_EXISTS'), '');
        echo json_encode($data);
        return false;
    }

    if (!csrf_verify($post)) {
        $data['success'] = false; 
        $data['message'] = message('warning', 'Ooops!', TRANS('FORM_ALREADY_SENT'),'');
    
        echo json_encode($data);
        return false;
    }

    $sql = "UPDATE `" . $data['database'] . "`." . $data['table'] . " SET 
				" . $data['table_cost_description'] . " =  '" . $data['costcenter_name'] . "' ,
                " . $data['table_cost_number'] . " = '" . $data['costcenter_number'] . "', 
                client = ".dbField($data['client'])."
            WHERE " . $data['table_id'] . " = '" . $data['cod'] . "'";

    try {
        $conn->exec($sql);
        $data['success'] = true; 
        $data['message'] = TRANS('MSG_SUCCESS_EDIT');
        $_SESSION['flash'] = message('success', '', $data['message'], '');
        echo json_encode($data);
        return false;
    } catch (Exception $e) {
        $exception .= "<hr>" . $e->getMessage();
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_ERR_DATA_UPDATE') . $exception;
        $_SESSION['flash'] = message('danger', '', $data['message'], '');
        echo json_encode($data);
        return false;
    }

} elseif ($data['action'] == 'delete') {

    $sqlFindPrevention = "SELECT C.*, E.* FROM equipamentos E, `" . $data['database'] . "`." . $data['table'] . " C WHERE E.comp_ccusto = C." . $data['table_cost_number'] . " and C." . $data['table_cost_number'] . " = ".$data['cod']."";

    $resFindPrevention = $conn->query($sqlFindPrevention);
    $foundPrevention = $resFindPrevention->rowCount();

    if ($foundPrevention) {
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_CANT_DEL');
        $_SESSION['flash'] = message('danger', '', $data['message'], '');
        echo json_encode($data);
        return false;
    }


    $sql = "DELETE FROM `" . $data['database'] . "`." . $data['table'] . " WHERE " . $data['table_id'] . " = '" . $data['cod'] . "'";

    try {
        $conn->exec($sql);
        $data['success'] = true; 
        $data['message'] = TRANS('OK_DEL');
        $_SESSION['flash'] = message('success', '', $data['message'], '');
        echo json_encode($data);
        return false;
    } catch (Exception $e) {
        $exception .= "<hr>" . $e->getMessage();
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_ERR_DATA_REMOVE');
        $_SESSION['flash'] = message('danger', '', $data['message'] . $exception, '');
        echo json_encode($data);
        return false;
    }
    
}

echo json_encode($data);