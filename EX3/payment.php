
<?php


//payment.php

include('database_connection.php');

session_start();

if(isset($_POST["token"]))
{
 require_once('vendor/autoload.php');

 \Stripe\Stripe::setApiKey('sk_test_51KxyslSI9PivBEvvJxFT3Xd2xd8qM0oZxrHwuoxwmHJe3ZrWwS4jgCvMUpgU618BM8CihICoO0xSqDqgFuWNMlSK00GzjHjhmJ');

 $customer = \Stripe\Customer::create(array(
  'email'   => $_POST["email_address"],
  'source'  => $_POST["token"],
  'name'   => $_POST["customer_name"],
  'address'  => array(
   'line1'   => $_POST["customer_address"],
   'postal_code' => $_POST["customer_pin"],
   'city'   => $_POST["customer_city"],
   'state'   => $_POST["customer_state"],
   'country'  => 'IN'
  )
 ));

 $order_number = rand(100000,999999);

 $charge = \Stripe\Charge::create(array(
  'customer'  => $customer->id,
  'amount'  => $_POST["total_amount"] * 100,
  'currency'  => $_POST["currency_code"],
  'description' => $_POST["item_details"],
  'metadata'  => array(
   'order_id'  => $order_number
  )
 ));

 $response = $charge->jsonSerialize();

 if($response["amount_refunded"] == 0 && empty($response["failure_code"]) && $response['paid'] == 1 && $response["captured"] == 1 && $response['status'] == 'succeeded')
 {
  $amount = $response["amount"]/100;

  $order_data = array(
   ':order_number'   => $order_number,
   ':order_total_amount' => $amount,
   ':transaction_id'  => $response["balance_transaction"],
   ':card_cvc'    => $_POST["card_cvc"],
   ':card_expiry_month' => $_POST["card_expiry_month"],
   ':card_expiry_year'  => $_POST["card_expiry_year"],
   ':order_status'   => $response["status"],
   ':card_holder_number' => $_POST["card_holder_number"],
   ':email_address'  => $_POST['email_address'],
   ':customer_name'  => $_POST["customer_name"],
   ':customer_address'  => $_POST['customer_address'],
   ':customer_city'  => $_POST['customer_city'],
   ':customer_pin'   => $_POST['customer_pin'],
   ':customer_state'  => $_POST['customer_state'],
   ':customer_country'  => $_POST['customer_country']
  );

  $query = "
  INSERT INTO order_table 
     (order_number, order_total_amount, transaction_id, card_cvc, card_expiry_month, card_expiry_year, order_status, card_holder_number, email_address, customer_name, customer_address, customer_city, customer_pin, customer_state, customer_country) 
     VALUES (:order_number, :order_total_amount, :transaction_id, :card_cvc, :card_expiry_month, :card_expiry_year, :order_status, :card_holder_number, :email_address, :customer_name, :customer_address, :customer_city, :customer_pin, :customer_state, :customer_country)
  ";

  $statement = $connect->prepare($query);

  $statement->execute($order_data);

  $order_id = $connect->lastInsertId();

  foreach($_SESSION["shopping_cart"] as $keys => $values)
  {
   $order_item_data = array(
    ':order_id'  => $order_id,
    ':order_item_name' => $values["product_name"],
    ':order_item_quantity' => $values["product_quantity"],
    ':order_item_price' => $values["product_price"]
   );

   $query = "
   INSERT INTO order_item 
   (order_id, order_item_name, order_item_quantity, order_item_price) 
   VALUES (:order_id, :order_item_name, :order_item_quantity, :order_item_price)
   ";

   $statement = $connect->prepare($query);

   $statement->execute($order_item_data);
  }

  unset($_SESSION["shopping_cart"]);

  $_SESSION["success_message"] = "Payment is completed successfully. The TXN ID is " . $response["balance_transaction"] . "";
  header('location:index.php');
 }

}

?>