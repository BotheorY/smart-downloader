<?php

include_once ('../utilities.php');

$data = $_POST;

if ((!empty($data)) && (!empty($data['email'])) && (!empty($data['from'])) && (!empty($data['key']))) {
    $email = $data['email'];
    if (chk_email($email)) {
        unset($data['email']);
        $from = $data['from'];
        if (chk_email($from)) {
            unset($data['from']);
            $body = "Data received from Download Manager server: \r\n\r\n";
            foreach ($data as $key => $val) {
                $body .= "$key = $val\r\n";
            }
            $headers = "From: $from\r\n" .
            "Reply-To: $from\r\n" .
            'X-Mailer: PHP/' . phpversion();
            mail($email, "[{$data['key']}] Download Data", $body, $headers);
        }
    }
}

/*******************************************************************************
 FUNCTIONS
 *******************************************************************************/    

function chk_email($email) {

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    list($user, $domain) = explode('@', $email);

    if (!checkdnsrr($domain, 'MX')) {
        return false;
    }

    return true;

}
