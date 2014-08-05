<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(-1);

require_once 'nmi/api.php';

$nmi = \NMI\API::forge('demo', 'password');


try {

    $customerData = array(
        'first_name' => 'John',
        'last_name'  => 'Doe',
        'address1'   => '123 Someplace Dr',
        'city'       => 'Myrtle Beach',
        'state'      => 'SC',
        'zip'        => '29579',
        'phone'      => '8431234567',
        'email'      => 'johndoe@example.com',
    );

    // Add Customer Credit Card Example
    $creditCardCustomerId = $nmi->addCustomer(array_merge($customerData, array(
        'ccnumber' => '4111111111111111',
        'ccexp'    => '1113',
        'cvv'      => '999',
    )));
    echo "<pre>";
    var_dump($creditCardCustomerId);

    // Add Customer ACH Example
    $achCustomerId = $nmi->addCustomer(array_merge($customerData, array(
        'checkname'           => "{$customerData['first_name']} {$customerData['last_name']}",
        'checkaba'            => '123123123',
        'checkaccount'        => '123123123',
        'account_holder_type' => 'personal',
        'account_type'        => 'checking',
    )));
    var_dump($achCustomerId);

    // Charge Customer Example
    $response = $nmi->chargeCustomer($creditCardCustomerId, array(
        'amount'           => '101.50',
        'orderdescription' => 'My Awesome Order',
        'orderid'          => uniqid(),
    ));
    var_dump($response);


} catch (\NMI\Exception $e) {
    echo "<pre>";
    var_dump($e);
}

