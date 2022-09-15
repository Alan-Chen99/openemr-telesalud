<?php

/**
 * This page is used to setup https access to OpenEMR with client certificate authentication.
 * If enabled, the browser must connect to OpenEMR using a client SSL certificate that is
 * generated by OpenEMR.  This page is used to create the Certificate Authority and
 * Apache SSL server certificate.
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Visolve <vicareplus_engg@visolve.com>
 * @author    Brady Miller <brady.g.miller@gmail.com>
 * @author    Jerry Padgett <sjpadgett@gmail.com>
 * @copyright Copyright (c) Visolve <vicareplus_engg@visolve.com>
 * @copyright Copyright (c) 2018-2020 Brady Miller <brady.g.miller@gmail.com>
 * @copyright Copyright (c) 2020 Jerry Padgett <sjpadgett@gmail.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE CNU General Public License 3
 */

require_once("../globals.php");
require_once("../../library/create_ssl_certificate.php");

use OpenEMR\Common\Acl\AclMain;
use OpenEMR\Common\Csrf\CsrfUtils;
use OpenEMR\Common\Session\SessionUtil;
use OpenEMR\Common\Twig\TwigContainer;
use OpenEMR\Core\Header;

if (!empty($_POST)) {
    if (!CsrfUtils::verifyCsrfToken($_POST["csrf_token_form"])) {
        CsrfUtils::csrfNotVerified();
    }
}

if (!AclMain::aclCheckCore('admin', 'users')) {
    echo (new TwigContainer(null, $GLOBALS['kernel']))->getTwig()->render('core/unauthorized.html.twig', ['pageTitle' => xl("SSL Certificate Administration")]);
    exit;
}

/* This string contains any error messages if generating
 * certificates fails.
 */
$error_msg = "";

/**
 * Send an http reply so that the browser downloads the given file.
 * Delete the file once the download is completed.
 * @param $filename  - The file to download.
 * @param $filetype  - The type of file.
 */
function download_file($filename, $filetype)
{

    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Cache-Control: private");
    header("Content-Type: application/" . $filetype);
    header("Content-Disposition: attachment; filename=" . basename($filename) . ";");
    header("Content-Transfer-Encoding: binary");
    header("Content-Length: " . filesize($filename));
    readfile($filename);
    exit;
    flush();
    @unlink($filename);
}

/* This function is called when the "Create Client Certificate" button is clicked.
 * Create and download a client certificate, given the following form inputs:
 *   client_cert_user - The username to store in the certificate
 *   client_cert_email - The email to store in the certificate
 * A temporary certificate will be written to /tmp/openemr_client_cert.p12.
 * If an error occurs, set the $error_msg (which is displayed later below).
 */
function create_client_cert()
{
    global $error_msg;

    if (!$GLOBALS['is_client_ssl_enabled']) {
        $error_msg .= xl('Error, User Certificate Authentication is not enabled in OpenEMR');
        return;
    }
    if (!file_exists($GLOBALS['certificate_authority_crt'])) {
        $error_msg .= xl('Error, the CA Certificate File doesn\'t exist');
        return;
    }
    if (!file_exists($GLOBALS['certificate_authority_key'])) {
        $error_msg .= xl('Error, the CA Key File doesn\'t exist');
        return;
    }

    if ($_POST["client_cert_user"]) {
        $user = trim($_POST['client_cert_user']);
    }

    if ($_POST["client_cert_email"]) {
        $email = trim($_POST['client_cert_email']);
    }

    if ($_POST["clientPassPhrase"]) {
        $clientPassPhrase = trim($_POST['clientPassPhrase']);
    }

    $serial = 0;
    $data = create_user_certificate(
        $user,
        $email,
        $serial,
        $GLOBALS['certificate_authority_crt'],
        $GLOBALS['certificate_authority_key'],
        $GLOBALS['client_certificate_valid_in_days']
    );
    if ($data === false) {
        $error_msg .= xl('Error, unable to create client certificate.');
        return;
    }

    $filename = $GLOBALS['temporary_files_dir'] . "/openemr_client_cert.p12";
    $handle = fopen($filename, 'w');
    fwrite($handle, $data);
    fclose($handle);

    download_file($filename, "p12");
}

/* Delete the following temporary certificate files, if they exist:
 *   /tmp/CertificateAuthority.key
 *   /tmp/CertificateAuthority.crt
 *   /tmp/Server.key
 *   /tmp/Server.crt
 *   /tmp/admin.p12
 *   /tmp/ssl.zip
 */
function delete_certificates()
{
    $tempDir = $GLOBALS['temporary_files_dir'];
    $files = array("CertificateAuthority.key", "CertificateAuthority.crt",
                   "Server.key", "Server.crt", "admin.p12", "ssl.zip");

    foreach ($files as $file) {
        if (file_exists($file)) {
            unlink($file);
        }
    }
}

/**
 * Create and download the following certificates:
 * - CertificateAuthority.key
 * - CertificateAuthority.crt
 * - Server.key
 * - Server.crt
 * - admin.p12
 * The following form inputs are used:
 */
function create_and_download_certificates()
{
    global $error_msg;
    $tempDir = $GLOBALS['temporary_files_dir'];

    $zipName = $tempDir . "/ssl.zip";
    if (file_exists($zipName)) {
        unlink($zipName);
    }

    $commonName             = false;
    $emailAddress           = false;
    $countryName            = false;
    $stateOrProvinceName    = false;
    $localityName           = false;
    $organizationName       = false;
    $organizationalUnitName = false;
    $clientCertValidity     = false;
    $clientPassPhrase = null;

    /* Retrieve the certificate name settings from the form input */
    if ($_POST["commonName"]) {
        $commonName = trim($_POST['commonName']);
    }

    if ($_POST["emailAddress"]) {
        $emailAddress = trim($_POST['emailAddress']);
    }

    if ($_POST["countryName"]) {
        $countryName = trim($_POST['countryName']);
    }

    if ($_POST["stateOrProvinceName"]) {
        $stateOrProvinceName = trim($_POST['stateOrProvinceName']);
    }

    if ($_POST["localityName"]) {
        $localityName = trim($_POST['localityName']);
    }

    if ($_POST["organizationName"]) {
        $organizationName = trim($_POST['organizationName']);
    }

    if ($_POST["organizationalUnitName"]) {
        $organizationName = trim($_POST['organizationalUnitName']);
    }

    if ($_POST["clientCertValidity"]) {
        $clientCertValidity = trim($_POST['clientCertValidity']);
    }

    if ($_POST["clientPassPhrase"]) {
        $clientPassPhrase = trim($_POST['clientPassPhrase']);
    }

    /* Create the Certficate Authority (CA) */
    $arr = create_csr(
        "OpenEMR CA for " . $commonName,
        $emailAddress,
        $countryName,
        $stateOrProvinceName,
        $localityName,
        $organizationName,
        $organizationalUnitName
    );

    if ($arr === false) {
        $error_msg .= xl('Error, unable to create the Certificate Authority certificate.');
        delete_certificates();
        return;
    }

    $ca_csr = $arr[0];
    $ca_key = $arr[1];
    $ca_crt = create_crt($ca_csr, null, $ca_key);
    if ($ca_crt === false) {
        $error_msg .= xl('Error, unable to create the Certificate Authority certificate.');
        delete_certificates();
        return;
    }

    openssl_pkey_export_to_file($ca_key, $tempDir . "/CertificateAuthority.key", null);
    openssl_x509_export_to_file($ca_crt, $tempDir . "/CertificateAuthority.crt");

    /* Create the Server certificate */
    $arr = create_csr(
        $commonName,
        $emailAddress,
        $countryName,
        $stateOrProvinceName,
        $localityName,
        $organizationName,
        $organizationalUnitName
    );
    if ($arr === false) {
        $error_msg .= xl('Error, unable to create the Server certificate.');
        delete_certificates();
        return;
    }

    $server_csr = $arr[0];
    $server_key = $arr[1];
    $server_crt = create_crt($server_csr, $ca_crt, $ca_key);

    if ($server_crt === false) {
        $error_msg .= xl('Error, unable to create the Server certificate.');
        delete_certificates();
        return;
    }

    openssl_pkey_export_to_file($server_key, $tempDir . "/Server.key", null);
    openssl_x509_export_to_file($server_crt, $tempDir . "/Server.crt");

    /* Create the client certificate for the 'admin' user */
    $serial = 0;
    $res = sqlStatement("select id from users where username='admin'");
    if ($row = sqlFetchArray($res)) {
        $serial = $row['id'];
    }

    $user_cert = create_user_certificate(
        "admin",
        $emailAddress,
        $serial,
        $tempDir . "/CertificateAuthority.crt",
        $tempDir . "/CertificateAuthority.key",
        $clientCertValidity
    );
    if ($user_cert === false) {
        $error_msg .= xl('Error, unable to create the admin.p12 certificate.');
        delete_certificates();
        return;
    }

    $adminFile = $tempDir . "/admin.p12";
    $handle = fopen($adminFile, 'w');
    fwrite($handle, $user_cert);
    fclose($handle);

    /* Create a zip file containing the CertificateAuthority, Server, and admin files */
    try {
        if (! (class_exists('ZipArchive'))) {
            SessionUtil::setSession('zip_error', xl("Error, Class ZipArchive does not exist"));
            return;
        }

        $zip = new ZipArchive();
        if (!($zip)) {
            SessionUtil::setSession('zip_error', xl("Error, Could not create file archive"));
             return;
        }

        if ($zip->open($zipName, ZipArchive::CREATE)) {
            $files = array("CertificateAuthority.key", "CertificateAuthority.crt",
                       "Server.key", "Server.crt", "admin.p12");
            foreach ($files as $file) {
                 $zip->addFile($tempDir . "/" . $file, $file);
            }
        } else {
            SessionUtil::setSession('zip_error', xl("Error, unable to create zip file with all the certificates"));
            return;
        }

        $zip->close();

        if (ini_get('zlib.output_compression')) {
            ini_set('zlib.output_compression', 'Off');
        }
    } catch (Exception $e) {
        SessionUtil::setSession('zip_error', xl("Error, Could not create file archive"));
        return;
    }

    download_file($zipName, "zip");
}

/*if ($_POST["mode"] == "save_ssl_settings") {
  save_certificate_settings();
}*/

if (!empty($_POST["mode"]) && ($_POST["mode"] == "create_client_certificate")) {
    create_client_cert();
} elseif (!empty($_POST["mode"]) && ($_POST["mode"] == "download_certificates")) {
    create_and_download_certificates();
}

if (!empty($_SESSION["zip_error"])) {
    $zipErrorOutput = '<div><table align="center"><tr valign="top"><td rowspan="3"><font class="redtext">' . text($_SESSION["zip_error"]) . '</td></tr></table></div>';
    SessionUtil::unsetSession('zip_error');
}

?>

<html>
  <head>
    <script>

    //check whether email id is valid or not
    function checkEmail(email) {
    var str=email;
    var at="@";
    var dot=".";
    var lat=str.indexOf(at);
    var lstr=str.length;
    var ldot=str.indexOf(dot);
    if (str.indexOf(at)==-1){
       return false;
    }

    if (str.indexOf(at)==-1 || str.indexOf(at)==0 || str.indexOf(at)==lstr){
       return false;
    }

    if (str.indexOf(dot)==-1 || str.indexOf(dot)==0 || str.indexOf(dot)==lstr){
        return false;
    }

     if (str.indexOf(at,(lat+1))!=-1){
        return false;
     }

     if (str.substring(lat-1,lat)==dot || str.substring(lat+1,lat+2)==dot){
        return false;
     }

     if (str.indexOf(dot,(lat+2))==-1){
        return false;
     }

     if (str.indexOf(" ")!=-1){
        return false;
     }

    return true;
    }
    function download_click(){
        if (document.ssl_certificate_frm.commonName.value == "") {
            alert (<?php echo xlj('Host Name cannot be empty'); ?>);
            document.ssl_certificate_frm.commonName.focus();
            return false;
        }

        if (document.ssl_certificate_frm.emailAddress.value) {
         //call checkEmail function
             if(checkEmail(document.ssl_certificate_frm.emailAddress.value) == false){
        alert (<?php echo xlj('Provide valid Email Address'); ?>);
        return false;
         }
        }

        if (document.ssl_certificate_frm.countryName.value.length > 2) {
            alert (<?php echo xlj('Country Name should be represent in two letters. (Example: United States is US)'); ?>);
            document.ssl_certificate_frm.countryName.focus();
            return false;
        }
        if (document.ssl_certificate_frm.clientCertValidity.value < 1) {
            alert (<?php echo xlj('Client certificate validity should be a valid number.'); ?>);
            document.ssl_certificate_frm.clientCertValidity.focus();
            return false;
        }
    }
    function create_client_certificate_click(){

        /*if(document.ssl_frm.isClientAuthenticationEnabled[1].checked == true)
        {
        alert (<?php echo xlj('User Certificate Authentication is disabled'); ?>);
            return false;
        }*/

        if (document.client_cert_frm.client_cert_user.value == "") {
            alert (<?php echo xlj('User name or Host name cannot be empty'); ?>);
            document.ssl_certificate_frm.commonName.focus();
            return false;
        }
    if (document.client_cert_frm.client_cert_email.value) {
         //call checkEmail function
             if(checkEmail(document.client_cert_frm.client_cert_email.value) == false){
        alert (<?php echo xlj('Provide valid Email Address'); ?>);
        return false;
         }
        }
    }

    function isNumberKey(evt) {
        var charCode = (evt.which) ? evt.which : evt.keyCode
        if (charCode > 31 && (charCode < 48 || charCode > 57))
            return false;
        else
            return true;
    }

    </script>

    <?php Header::setupHeader(); ?>

    <style>
      div.borderbox {
        margin: 5px 5px;
        padding: 5px 5px;
        border: solid 1px;
        width: 60%;
      }
    </style>

  </head>
  <body class="body_top">
  <span class='title'><b><?php echo xlt('SSL Certificate Administration'); ?></b></span>
  <br /> <br />
    <?php if (!empty($zipErrorOutput)) {
        echo $zipErrorOutput;
    } else { ?>
  <span class='text'>
        <?php
        if ($error_msg != "") {
            echo "<font class='redtext'>" . text($error_msg) . "</font><br /><br />";
        }
        ?>
        <?php echo xlt('To setup https access with client certificate authentication, do the following'); ?>
  <ul>
    <li><?php echo xlt('Create the SSL Certificate Authority and Server certificates.'); ?>
    <li><?php echo xlt('Configure Apache to use HTTPS.'); ?>
    <li><?php echo xlt('Configure Apache and OpenEMR to use Client side SSL certificates.'); ?>
    <li><?php echo xlt('Import certificate to the browser.'); ?>
    <li><?php echo xlt('Create a Client side SSL certificate for each user or client machine.'); ?>
  </ul>
  <br />
        <?php
        if ($GLOBALS['certificate_authority_crt'] != "" && $GLOBALS['is_client_ssl_enabled']) {
            echo xlt('OpenEMR already has a Certificate Authority configured.');
        }
        ?>
  <form method='post' name=ssl_certificate_frm action='ssl_certificates_admin.php'>
  <input type="hidden" name="csrf_token_form" value="<?php echo attr(CsrfUtils::collectCsrfToken()); ?>" />
  <input type='hidden' name='mode' value='download_certificates'>
  <div class='borderbox'>
    <b><?php echo xlt('Create the SSL Certificate Authority and Server certificates.'); ?></b><br />
    <br />
    1. <?php echo xlt('Fill in the values below'); ?><br />
    2. <?php echo xlt('Click Download Certificate to download the certificates in the file ssl.zip'); ?> <br />
    3. <?php echo xlt('Extract the zip file');
    echo ": ssl.zip "; ?><br /><br />
        <?php echo xlt('The zip file will contain the following items'); ?> <br />
    <ul>
      <li>Server.crt : <?php echo xlt('The Apache SSL server certificate and public key'); ?>
      <li>Server.key : <?php echo xlt('The corresponding private key'); ?>
      <li>CertificateAuthority.crt : <?php echo xlt('The Certificate Authority certificate'); ?>
      <li>CertificateAuthority.key : <?php echo xlt('The corresponding private key'); ?>
      <li>admin.p12 : <?php echo xlt('A client certificate for the admin user'); ?>
    </ul>
        <table border=0>
      <tr class='text'>
        <td><?php echo xlt('Host Name'); ?> *:</td>
        <td><input name='commonName' type='text' value=''></td>
        <td><?php echo xlt('Example') ;
        echo ': hostname.domain.com'; ?></td>
      </tr>
      <tr class='text'>
        <td><?php echo xlt('Email Address'); ?>:</td>
        <td><input name='emailAddress' type='text' value=''></td>
        <td><?php echo xlt('Example') ;
        echo ': web_admin@domain.com'; ?></td>
      </tr>
      <tr class='text'>
        <td><?php echo xlt('Organization Name'); ?>:</td>
        <td><input name='organizationName' type='text' value=''></td>
        <td><?php echo xlt('Example');
        echo ': My Company Ltd'; ?></td>
      </tr>
      <tr class='text'>
        <td><?php echo xlt('Organizational Unit Name'); ?>:</td>
        <td><input name='organizationalUnitName' type='text' value=''></td>
        <td><?php echo xlt('Example');
        echo ': OpenEMR'; ?></td>
      </tr>
      <tr class='text'>
        <td><?php echo xlt('Locality'); ?>:</td>
        <td><input name='localityName' type='text' value=''></td>
        <td><?php echo xlt('Example') ;
        echo ': City'; ?></td>
      </tr>
      <tr class='text'>
        <td><?php echo xlt('State Or Province'); ?>:</td>
        <td><input name='stateOrProvinceName' type='text' value=''></td>
        <td><?php echo xlt('Example') ;
        echo ': California'; ?></td>
      </tr>
      <tr class='text'>
        <td><?php echo xlt('Country'); ?>:</td>
        <td><input name='countryName' type='text' value='' maxlength='2'></td>
        <td><?php echo xlt('Example');
        echo ': US';
        echo ' (';
        echo xlt('Should be two letters');
        echo ')'; ?></td>
      </tr>
      <tr class='text'>
        <td><?php echo xlt('Client certificate validation period'); ?>:</td>
        <td><input name='clientCertValidity' type='text' onkeypress='return isNumberKey(event)' value='365'></td>
        <td><?php echo xlt('days'); ?></td>
      </tr>
      <tr class='text'>
        <td><?php echo xlt('Client certificate passphrase'); ?>:</td>
        <td><input name='clientPassPhrase' type='text' value=''></td>
        <td><?php echo xlt('Not required. This password is for generated admin.p12'); ?></td>
      </tr>
      <tr>
        <td colspan=3 align='center'>
          <input name='sslcrt' type='submit' onclick='return download_click();' value='<?php echo xla('Download Certificates'); ?>'>
        </td>
      </tr>
    </table>
  </div>
  </form>
  <br />

  <div class="borderbox">
    <b><?php echo xlt('Configure Apache to use HTTPS.'); ?></b><br />
    <br />
        <?php echo xlt('Add new certificates to the Apache configuration file'); ?>:<br />
    <br />
    SSLEngine on<br />
    SSLCertificateFile   /path/to/Server.crt<br />
    SSLCertificateKeyFile /path/to/Server.key<br />
    SSLCACertificateFile /path/to/CertificateAuthority.crt<br />
    <br />
        <?php echo xlt('Note'); ?>:
    <ul>
      <li><?php echo xlt('To Enable only HTTPS, perform the above changes and restart Apache server. If you want to configure client side certificates also, please configure them in the next section.'); ?><br />
    <li> <?php echo xlt('To Disable HTTPS, comment the above lines in Apache configuration file and restart Apache server.'); ?>
    <ul/>
  </div>

  <br />
  <div class="borderbox">
    <form name='ssl_frm' method='post'>
    <input type="hidden" name="csrf_token_form" value="<?php echo attr(CsrfUtils::collectCsrfToken()); ?>" />
    <b><?php echo xlt('Configure Apache to use Client side SSL certificates'); ?> </b>
    <br /><br />
        <?php echo xlt('Add following lines to the Apache configuration file'); ?>:<br />
    <br />
    SSLVerifyClient require<br />
    SSLVerifyDepth 2<br />
    SSLOptions +StdEnvVars<br />
    <br /> <b><?php echo xlt('Configure Openemr to use Client side SSL certificates'); ?> </b><br />
      <input type='hidden' name='clientCertValidity_hidden' value=''>
      <br />

        <?php echo xlt('Update the following settings in Administration->Globals->Security'); ?>:<br />
        <?php echo xlt('Turn on Enable Client SSL'); ?><br />
        <?php echo xlt('Provide absolute path of following file in Path to CA Certificate File'); ?> CertificateAuthority.crt<br />
        <?php echo xlt('Provide absolute path of following file in Path to CA Key File'); ?> CertificateAuthority.key<br />
     <br />
    <br /><?php echo xlt('Note'); ?>:
    <ul>
      <li><?php echo xlt('To Enable Client side SSL certificates authentication, HTTPS should be enabled.'); ?>
      <li><?php echo xlt('After performing above configurations, import the admin client certificate to the browser and restart Apache server (empty password).'); ?>
      <li><?php echo xlt('To Disable client side SSL certificates, comment above lines in Apache configuration file and turn off the \'Enable Client SSL\' global setting in OpenEMR and restart Apache server.'); ?>
    </form>
  </div>
  <br />
  <div class="borderbox">
    <b><?php echo xlt('Create Client side SSL certificates'); ?></b><br />
    <br />
        <?php echo xlt('Create a client side SSL certificate for either a user or a client hostname.'); ?>
    <br />
        <?php
        if (
            !$GLOBALS['is_client_ssl_enabled'] ||
            $GLOBALS['certificate_authority_crt'] == ""
        ) {
            echo "<font class='redtext'>" . xlt('OpenEMR must be configured to use certificates before it can create client certificates.') . "</font><br />";
        }
        ?>
    <form name='client_cert_frm' method='post' action='ssl_certificates_admin.php'>
      <input type="hidden" name="csrf_token_form" value="<?php echo attr(CsrfUtils::collectCsrfToken()); ?>" />
      <input type='hidden' name='mode' value='create_client_certificate'>
      <table>
        <tr class='text'>
          <td><?php echo xlt('User or Host name'); ?>*:</td>
          <td><input type='text' name='client_cert_user' size=20 />
        </tr>
        <tr class='text'>
          <td><?php echo xlt('Email'); ?>:</td>
          <td><input type='text' name='client_cert_email' size=20 />
        </tr>
        <tr class='text'>
          <td><?php echo xlt('Client certificate passphrase'); ?>:</td>
          <td><input name='clientPassPhrase' type='password' value=''></td>
          <td><?php echo xlt('Not required.'); ?></td>
        </tr>
      </table>
      <br /> <input type='submit' onclick='return create_client_certificate_click();' value='<?php echo xla('Create Client Certificate'); ?>'>
    </form>
  </div>
  <br />
  <br />&nbsp;
  <br />&nbsp;
  </span>
    <?php } ?>
  </body>
</html>
