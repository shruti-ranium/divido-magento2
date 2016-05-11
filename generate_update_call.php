<?php

http://magento19.divido.dev/index.php/pay/payment/return/quote_id/30/

//http://magento19.divido.dev/divido_callback.php
// http://opencart-integration.divido.dev/index.php?route=payment/divido/update
//

$quote_id = $argv[1];

$db = new mysqli('localhost', 'root', 'root', 'magento20');
if ($db->connect_errno) {
    die($db->connect_error);
}

$s = $db->prepare("select salt from divido_lookup where quote_id = ?");
$s->bind_param('i', $quote_id);
$s->execute();
$s->bind_result($salt);
$s->fetch();
$s->close();
$db->close();

$url = 'http://magento20.divido.dev/rest/V1/divido/update';
$statuses = [
    'ACCEPTED',
    'DEPOSIT-PAID',
    'SIGNED',
    'FULFILLED',
    'COMPLETED',
    'DEFERRED',
    'REFERRED',
    'DECLINED',
    'CANCELED',
];

$status = $statuses[0];
$hash = hash('sha256', $salt.$quote_id);

$req_tpl = [
    "application" => 'C84047A6D-89B2-FECF-D2B4-168444F5178C',
    "reference" => 100024,
    "status" => "{$status}",
    "live" => false,
    "metadata" =>  [
       "quote_id" => $quote_id,
       "quote_hash" => $hash,
    ],
];

$data = json_encode($req_tpl);
$cmd = "curl -v -X POST -d '{$data}' -H 'Content-Type: application/json' {$url}";

echo $cmd;
