<?php
// C_TSalud_Vc.php
/**
 * - Mostrar link iniciar teleconsutla en encabezado de resumen de paciente.
 * - Mostrar opciones de teleconsulta en el momento y hora correctas
 * - corregir el error sql de traducciones.
 * - Traducciones del excel
 * ----
 * API OpenEmr
 * - Envio de email a paciente y medico
 * - Recibir notificaciones :
 * - medic-set-attendance: El médico ingresa a la videoconsulta
 * - medic-unset-attendance: El médico cierra la pantalla de videoconsulta
 * - videoconsultation-started: Se da por iniciada la videoconsulta, esto se da cuando tanto el médico como el paciente están presentes
 * - videoconsultation-finished: El médico presiona el botón Finalizar consulta
 * - patient-set-attendance: El paciente anuncia su presencia
 * -Enviar mail al medico y acitavar color de que el paciente esta presente
 */
require_once ($p = $_SERVER['DOCUMENT_ROOT'] . "/telesalud/globals.php");

/**
 *
 * @return NULL|mysqli
 */
function dbConn()
{
    $servername = "telesalud-openemr-mysql";
    $username = "openemr";
    $password = "openemr";
    $database = "openemr";
    // Create connection
    $conn = new mysqli($servername, $username, $password, $database);
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
        $conn = null;
    }
    // echo "Connected successfully";
    return $conn;
}

/**
 *
 * @param unknown $conn            
 * @param unknown $sql            
 * @return array|NULL[]
 */
function sqlS($sql)
{
    $r = array();
    try {
        $conn = dbConn();
        if ($conn) {
            // echo $sql;
            $result = $conn->query($sql) or trigger_error($conn->error . " " . $sql);
            if ($result !== false && $result->num_rows > 0) {
                $r = $result->fetch_assoc();
            }
            $conn->close();
        }
    } catch (Exception $e) {
        $r = array(
            'error' => $e->getMessage()
        );
    }
    return $r;
}

/**
 * Show VC HTML Button link
 *
 * @param integer $authUserID            
 * @param integer $patientID            
 * @param string $url_field_name            
 * @return string
 */
function showVCButtonlink($authUserID, $patientID, $url_field_name = 'medic_url', $vcCatList = '16')
{
    try {
        $r = '';
        $sql = "
            
SELECT cal.pc_eid,
    cal.pc_aid,
    cal.pc_pid,
    cal.pc_title,
    pc_hometext,
    pc_startTime,
    pc_endTime,
    vcdata.*
FROM `openemr_postcalendar_events` as cal
    INNER join tsalud_vc as vcdata on cal.pc_eid = vcdata.pc_eid
    INNER JOIN patient_data AS p ON cal.pc_pid = p.id
where pc_eventDate = current_date()
    and CURRENT_TIME BETWEEN cal.pc_startTime and cal.pc_endTime
    and cal.pc_catid IN ($vcCatList)
    and cal.pc_aid = $authUserID
    and cal.pc_pid = $patientID";
        
        $row = sqlS($sql);
        if (isset($row[$url_field_name])) {
            $url = $row[$url_field_name];
            $r .= vcButton($url, $url_field_name);
        }
        // }
    } catch (Exception $e) {
        echo $e->getMessage();
    }
    return $r;
}

/**
 *
 * @param unknown $url            
 * @param unknown $url_field_name            
 * @return string
 */
function vcButton($url, $url_field_name)
{
    $button = '';
    if ($url_field_name == 'medic_url') {
        // xlt("Medic Teleconsultation");
        $title = 'Start video consultation';
    } else {
        // xlt("Patient Teleconsultation");
        $title = 'Patient Teleconsultation';
    }
    // echo $url;
    $link_element = "href=\"$url\" target=\"_blank\"";
    $button = " &nbsp  <a class=\"btn btn-primary\" $link_element title=\"$title\" >$title</a>";
    return $button;
}

/**
 * * Crea una video consulta desde una cita
 * creada.
 * * Para esto usa el Servicio de creacion de video consulta (SCV)
 * La categoria de la cita debe estar entre * las cagetoiras de tipo
 * video consultas configuradas * dentro de la tabla de confguraciones de *
 * de video consultas * * @param integer $pc_eid
 */
function createVc($pc_eid)
{
    /**
     * * Categorias de video consultas
     *
     * @var integer $vc_category_id
     */
    $vc_category_list = '16';
    /**
     *
     * @var string $sql_vc_calender -
     *      Consulta cita de tipo video consulta
     *      devuelve:
     *      - datos a enviar al SCV
     *      - emails del paciente y del medico
     */
    $sql_vc_calender = "
SELECT c.pc_eid, c.pc_catid, c.pc_aid, c.pc_pid,
c.pc_title, c.pc_time, c.pc_eventDate as encounterDate,
c.pc_endDate, c.pc_startTime as encounterTime,
c.pc_endTime, c.pc_duration, 
CONCAT_WS( p.fname, p.mname, p.lname ) AS patientFullName, 
CONCAT_WS( m.fname, m.mname, m.lname ) AS medicFullName
, p.email as patientEmail
,  vc.patient_url as patientEncounterUrl
,  vc.medic_url as medicEncounterUrl
, m.email as medicEmail

FROM
openemr_postcalendar_events AS c 
INNER JOIN patient_data AS p ON
c.pc_pid = p.id 
INNER JOIN users AS m ON c.pc_aid = m.id 
left join tsalud_vc as vc on c.pc_eid =vc.pc_eid

WHERE
c.pc_catid IN ($vc_category_list) and c.pc_eid=$pc_eid;";
    echo $sql_vc_calender;
    try {
        $res = sqlStatement($sql_vc_calender);
        $calendar_data = sqlFetchArray($res);
        $extra_data = array(
            'saludo' => 'Hola'
        );
        
        // preparar datos a enviar al SCV
        $appoinment_date = $calendar_data['encounterDate'] . ' ' . $calendar_data['encounterTime'];
        /**
         *
         * @var array $vc_data -
         *      datos a enviar al SCV
         *      Ejemplo:
         *      appointment_date:2022-10-31 14:30:00
         *      days_before_expiration:1
         *      medic_name:medico-yois
         *      patient_name:paciente-yois
         *      extra[]:hola
         */
        $data = array(
            "medic_name" => $calendar_data['medicFullName'],
            "patient_name" => $calendar_data['patientFullName'],
            "days_before_expiration" => '1',
            "appointment_date" => "$appoinment_date",
            "extra" => $extra_data
        );
        /**
         *
         * @var string $vc_response -
         *      respuesta de SCV
         */
        $svc_response = requestAPI($data, CURLOPT_POST);
        /**
         * * * @var array $vc_data datos devueltos por el SCV
         */
        $vc_data = json_decode($svc_response, TRUE);
        // si hay respuesta
        if ($vc_data['success']) {
            // agregar video consulta a la bd
            insertVc($pc_eid, $vc_data);
            updateLinksToAgenda($pc_eid, $vc_data);
            // enviar email de la video consulta al medico
            sendEmail($calendar_data);
            // sendVcMedicEmail
            // enviar email a paciente // sendVcPatientEmail
        } else {
            echo "Errores en respuesta API Datos devueltos: " . print_r($vc_data, true);
        }
    } catch (Exception $e) {
        // ehco $e
    }
}

/**
 *
 * @param unknown $calendar_data            
 */
function sendEmail($calendar_data)
{
    $mailData = emailMessageFor($calendar_data);
    $to = $mailData['to'];
    print_r($mailData);
    //
    if (empty($to)) {
        echo "Email could not be
        sent, the address supplied: '$to' was empty or invalid.";
        return false;
    } else {
        // $fromEmail = 'noreplay@telesalud.com';
        // $fromName = 'All in One OPS';
        // $mailHost = "localhost";
        // $text_body = $p->get_prescription_display();
        // $subject = "Prescription for: " . $p->patient->get_name_display();
        // //
        // $mail = new PHPMailer();
        // // this is a temporary config item until the rest of the per practice billing settings make their way in
        // $mail->From = $fromEmail;
        // $mail->FromName = $fromName;
        // $mail->isMail();
        // $mail->Host = $mailHost;
        // $mail->Mailer = "mail";
        // $mail->Body = $mailData['body'];
        // $mail->Subject = $mailData['subject'];
        // $mail->AddAddress($to);
        // //
        // if ($mail->Send()) {
        // $this->assign("process_result", "Email was successfully sent to: " . $email);
        // return;
        // } else {
        // $this->assign("process_result", "There has been a mail error sending to " . $_POST['email_to'] . " " . $mail->ErrorInfo);
        return true;
        // }
    }
}

/**
 * returns subjetc, body and sender email to send
 *
 * @param array $constationData            
 * @param string $for            
 * @return string[]|unknown[]
 */
function emailMessageFor($constationData, $for = 'pac')
{
    $patientFullName = $constationData['patientFullName'];
    $medicFullName = $constationData['medicFullName'];
    //
    $encounterDate = $constationData['encounterDate'];
    $encounterTime = $constationData['encounterTime'];
    //
    $medicEncounterUrl = $constationData['medicEncounterUrl'];
    $patientEncounterUrl = $constationData['patientEncounterUrl'];
    //
    // We build the body to patient y doctor
    if ($for == 'doc') {
        $result = [
            'to' => $constationData['medicEmail'],
            'subject' => "[All in One OPS ] - Nueva video consulta con el Paciente {$patientFullName}",
            'body' => "Hola, se ha agendado una video consulta médica con el paciente {$patientFullName} el día {$encounterDate} a las {$encounterTime}. <br> <br> Para acceder a la video consulta ingrese al siguiente enlace: <br> <a href='{$medicEncounterUrl}' target='_blank'>{$medicEncounterUrl}</a>"
        ];
    } else {
        $result = [
            'to' => $constationData['patientEmail'],
            'subject' => "[All in One OPS ] - Usted tiene una video consulta para el {$encounterDate} a las {$encounterTime}",
            'body' => "Hola, {$patientFullName} usted tiene una video consulta médica con el médico {$medicFullName} para el día {$encounterDate} a las {$encounterTime}. <br> <br> Para acceder a la video consulta médica ingrese al siguiente enlace: <br> <a href='{$patientEncounterUrl}' target='_blank'>{$patientEncounterUrl}</a>"
        ];
    }
    return $result;
}

/**
 * * Actualizacion de liks dentro
 * de consulta despues de generar consutla * guardar datos de acceso a la
 * video consulta de comentarios de la cita.
 * * * @param unknown $vc_data *
 *
 * @return recordset
 */
function updateLinksToAgenda($pc_eid, $vc_data)
{
    $patient_url = $vc_data['data']['patient_url'];
    $medic_url = $vc_data['data']['medic_url'];
    $pc_hometext = "Accesos a la video consulta:
<ul>
<li>Profesional: <a href=\"{$medic_url}\" target=\"_blank\">{$medic_url}</a></li>
<li>Paciente: <a href=\"{$patient_url}\" target=\"_blank\">{$patient_url}</a></li>
</ul>
";
    $sql_update_pc_hometext = "update openemr_postcalendar_events set
pc_hometext='$pc_hometext' where pc_eid=$pc_eid;";
    // echo
    $sql_update_pc_hometext;
    return sqlStatement($sql_update_pc_hometext);
}

/**
 * * agrega una nueva video consulta a la Base de datos * * @param
 * integer $pc_eid * @param string $vc_response * datos devueltos por el
 * servicio de video consulta * * @return boolean|number
 */
function insertVc($pc_eid, $vc_data)
{
    /**
     * * * @var boolean $response valor de
     * retorno verdadero/false
     */
    $return = false;
    //
    $success = $vc_data['success'];
    $message = $vc_data['message'];
    $id = $vc_data['data']['id'];
    $valid_from = $vc_data['data']['valid_from'];
    $valid_to = $vc_data['data']['valid_to'];
    $patient_url = $vc_data['data']['patient_url'];
    $medic_url = $vc_data['data']['medic_url'];
    $data_url = $vc_data['data']['data_url'];
    $medic_secret = $vc_data['data']['medic_secret'];
    //
    try {
        // Save new vc on Database
        $query = "INSERT INTO tsalud_vc ";
        $query .= "( pc_eid,
success,message,
data_id,
valid_from,
valid_to,
patient_url,
medic_url,
url,medic_secret ) ";
        $query .= " VALUES (
$pc_eid, '$success','$message','$id',
'$valid_from','$valid_to','$patient_url','$medic_url','$data_url','$medic_secret')";
        $return = sqlInsert($query);
    } catch (Exception $e) {
        //
        echo $e;
        // Error: Duplicate entry '1' for key 'PRIMARY' //
        return false;
    }
    return $return;
}

/**
 * solcita servicio de video consulta
 *
 * @param array $data            
 * @param string $method            
 * @param string $bearToken            
 * @param unknown $authorization            
 * @param string $api_url            
 * @return string -
 *         respuesta del servicio de video consulta
 */
function requestAPI($data, $method)

{
    $bearToken = "1|hqg8cSkfrmLVwq12jK6yAv03HHGyP6BYJNpH84Wg";
    $authorization = "Authorization: Bearer $bearToken";
    $api_url = 'https://srv3.integrandosalud.com/os-telesalud/api/videoconsultation?';
    
    try {
        // Create VC
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $api_url);
        // Returns the data/output as a string instead of raw data
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        // Set your auth headers
        /**
         * --header 'Content-Type: application/x-www-form-urlencoded'
         */
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type:application/json',
            'Authorization: Bearer ' . $bearToken
        ));
        
        curl_setopt($curl, $method, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        //
        $result = curl_exec($curl);
        if (! $result) {
            die("API $curl - Connection Failure");
        }
    } catch (Exception $e) {
        echo $e;
    } finally {
        curl_close($curl);
    }
    
    return $result;
}

function saveNotify($response)
{
    $json = json_decode($response, TRUE);
/**
 * * `pc_eid` int(11) unsigned
 * NOT NULL, * `vc_secret` varchar(1024) DEFAULT NULL, * `vc_medic_secret`
 * varchar(1024) DEFAULT NULL, * `vc_status` varchar(1024) DEFAULT NULL, *
 * `vc_medic_attendance_date` varchar(1024) DEFAULT NULL, *
 * `vc_patient_attendance_date` varchar(1024) DEFAULT NULL, *
 * `vc_start_date` varchar(1024) DEFAULT NULL, * `vc_finish_date`
 * varchar(1024) DEFAULT NULL, * `vc_extra` varchar(1024) DEFAULT NULL, *
 * `topic` varchar(1024) DEFAULT NULL
 */
} // include_once
'../globals.php';
// print_r($GLOBALS["pid"]); /** * Only for demo */
$pc_aid = 5;
$pc_pid = 1;
if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'insertEvent':
            createVc(1);
            break;
        case 'vcButton': // echo "generate link"; //
            $pc_aid = $_GET['pc_aid'];
            $pc_pid = $_GET['pc_pid'];
            // $links =
            echo showVCButtonlink($pc_aid, $pc_pid);
            // print_r($links); // $patient_l =
            // $links['patient_url'];
            // echo $links['medic_url'];
            break;
        default:
            break;
    }
}
