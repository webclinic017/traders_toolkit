<?php
set_include_path( get_include_path() . PATH_SEPARATOR . __DIR__ . '/library' );

spl_autoload_register();

$client = new \Scheb\YahooFinanceApi\ApiClient();

//Fetch basic data
$data[1] = $client->getQuotesList("YHOO"); //Single stock
$data[2] = $client->getQuotesList( array( 'YHOO', 'GOOG' ) ); //Multiple stocks at once

//Fetch full data set
$data[3] = $client->getQuotes("YHOO"); //Single stock
$data[4] = $client->getQuotes(array("YHOO", "GOOG")); //Multiple stocks at once

//Get historical data
$startDate = new DateTime( '20160701T09:00:00' );
$endDate = new DateTime();
$data[5] = $client->getHistoricalData( 'YHOO', $startDate, $endDate );


/*
ob_start();
echo '<p>Fetch basic data:</p>';
echo '<p>' . print_r( $data[1] ) . '<br>' . print_r( $data[2] ) . '</p>';

echo '<p>Fetch full data set:</p>';
echo '<p>' . print_r( $data[3] ) . '<br>' . print_r( $data[4] ) . '</p>';

echo '<p>Get historical data:</p>';
echo '<p>' . print_r( $data[5] ) . '</p>';
ob_end_flush();
*/


ob_start();
//print_r( $data[5]['query']['results']['quote'] );
echo json_encode($data[5]);

ob_end_flush();


