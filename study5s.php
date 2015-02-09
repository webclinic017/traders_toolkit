<?php
/*
* @Version 130214
*
* This script presents the following studies:
* a) 20-day linear regression with x-ATR. 
* b) Bulls-to-Bears delta (BBd). 
* 
* @contents
* 
* @author Alex Kay
* 
* a-table (initial table with full OHLCV and some basic indicators added to build the pivot tables upon.
* p-table (pivot tables)
* d-table (%-distribution tables) 
* c-table (confidence tables)
* s-table (stat-tables, simple stats for each row)
* 
*/

/*
* Prepare Script
*/

//phpinfo(); exit();

$start_time = microtime();

ini_set( 'error_reporting', E_ALL | E_PARSE | E_NOTICE );
ini_set( 'display_errors', TRUE );
ini_set( 'Log_errors', FALSE );
ini_set('date.timezone', "America/New_York");
ini_set( 'max_execution_time', 12000 );

define( 'DEBUG', TRUE );
define( 'COLOR_INDEX', 1 );
define( 'COLOR_SERIES', 2 );

$path = array( 
	'library/customexception.class.php',
	'library/chart.class.php',
	'library/calc1.class.php',
	'library/csv2.class.php',
	//'library/lin_reg_atr.class.php',
	'library/bbd5.class.php',
	//'library/lin_reg_slope.class.php',
	'library/lrs_ext1.class.php',

 );
foreach ( $path as $library ) {
	include realpath( $library );
}


/*
*	Start Script
*/

ob_start(); 
?>

<!DOCTYPE html>
<html>
	<head>
	<meta charset="UTF-8">
		<title>Trade Helper Online</title>
		<style type='text/css'>
			html, body, div, span, applet, object, iframe,
			h1, h2, h3, h4, h5, h6, p, blockquote, pre,
			a, abbr, acronym, address, big, cite, code,
			del, dfn, em, font, img, ins, kbd, q, s, samp,
			small, strike, strong, sub, sup, tt, var,
			dl, dt, dd, ol, ul, li,
			fieldset, form, label, legend, textarea,
			table, caption, tbody, tfoot, thead, tr, th, td {
				margin: 0;
				padding: 0;
				border: 0;
				outline: 0;
				font-weight: inherit;
				font-style: inherit;
				font-size: 100%;
				font-family: inherit;
				vertical-align: baseline;
			}
			/* remember to define focus styles! */
			:focus {
				outline: 0;
			}
			body {
				line-height: 1;
				color: black;
				background: white;
			}
			ol, ul {
				list-style: none;
			}
			/* tables still need 'cellspacing="0"' in the markup */
			table {
				border-collapse: separate;
				border-spacing: 0;
			}
			caption, th, td {
				text-align: left;
				font-weight: normal;
			}
			blockquote:before, blockquote:after,
			q:before, q:after {
				content: "";
			}
			blockquote, q {
				quotes: "" "";
			}


			/* Done resetting now on to styling */

			html, body {
				font-family: Arial, Verdana, sans-serif;
				color: black;
				
			}
			
			.lin_reg-atr {
				padding: 0;
			}
			
			.symbol {
				float: left;
				font-size: 30px;
				font-weight: bold;
				margin-right: 10px;
			}
			
			.quote {
				float: left;
				font-size: 12px;
				margin: 16px 0 0 0;
			}
			
			.study_data {
				clear: right;
				float: left;
				font-size: 12.5px;
				line-height: 118%;
				margin: -1px 0 0 16px;
			}
			
			.quote_box {
				clear: both;
				margin: 18px 0 10px 10px;
				overflow: hidden;
			}
			
			.chart {
			
			}
			
			.chart img {
				width:100%;
			}

		</style>
		
		<link rel="stylesheet" type="text/css" media="screen" href="./css/coda-slider.css">

		<!--<script src="./scripts/jquery-1.7.2.min.js"></script>-->
		<!--<script src="./scripts/jquery-ui-1.8.20.custom.min.js"></script>-->
		<!--<script src="./scripts/jquery.coda-slider-3.0.js"></script>
		<script>
				$(function() {
						$('#slider-id').codaSlider();
				});
		</script>-->
	</head>
	<body>
		<div id=wrapper>
			<div class="coda-slider" id="slider-id">

<?php

$research = array( 
	'SPY' => array( 
		 //'lin_reg-atr' => array( 'study_path' => 'assets/', 'linreg' => 20, 'atr' => 14, 'chart_bars' => 300, 'multiple' => 0.2, 'multiple2' => 0.3, 'holding_period' => 15, 'conf_level' => 66, 'atr_threshold' => 1.5, ),
		'bbd' => array( 'study_path' => 'assets/studies/bbd/', 'full_study' => FALSE, 'bullish' => array( 6 => array( 9, ), ), 'bearish' => array( 9 => array( 15, ), ), 'chart_bars' => 100, 'multiple' => 0.2, 'multiple2' => 0.3, 'holding_period' => 15, 'conf_level' => 66, ),
		'lrs_ext' => array( 'study_path' => 'assets/studies/lrs_ext/', 'full_study' => FALSE, 'bullish' => array( 1 => array( 6, ), ), 'bearish' => array( 1 => array( 6, ), ), 'chart_bars' => 100, 'multiple' => 11, 'multiple2' => 0.3, 'holding_period' => 15, 'conf_level' => 66, ),
		),
	'XME' => array( 
		'bbd' => array( 'study_path' => 'assets/studies/bbd/', 'full_study' => FALSE, 'bullish' => array( 7 => array( 11, ), ), 'bearish' => array( 8 => array( 16, ), ), 'chart_bars' => 300, 'multiple' => 0.15, 'multiple2' => 0.2, 'holding_period' => 15, 'conf_level' => 66, ),
		'lrs_ext' => array( 'study_path' => 'assets/studies/lrs_ext/', 'full_study' => FALSE, 'bullish' => array( 1 => array( 5, ), ), 'bearish' => array( 1 => array( 5, ), ), 'chart_bars' => 300, 'multiple' => 4, 'multiple2' => 0.3, 'holding_period' => 15, 'conf_level' => 66, ),
		),
	'XHB' => array( 
		'bbd' => array( 'study_path' => 'assets/studies/bbd/', 'full_study' => FALSE, 'bullish' => array( 10 => array( 6, ), ), 'bearish' => array( 9 => array( 15, ), ), 'chart_bars' => 300, 'multiple' => 0.15, 'multiple2' => 0.2, 'holding_period' => 15, 'conf_level' => 66, ),
		'lrs_ext' => array( 'study_path' => 'assets/studies/lrs_ext/', 'full_study' => FALSE, 'bullish' => array( 1 => array( 5, ), ), 'bearish' => array( 1 => array( 6, ), ), 'chart_bars' => 300, 'multiple' => 3, 'multiple2' => 0.3, 'holding_period' => 15, 'conf_level' => 66, ),
		),
	'XLV' => array( 
		'bbd' => array( 'study_path' => 'assets/studies/bbd/', 'full_study' => FALSE, 'bullish' => array( 10 => array( 10, ), ), 'bearish' => array( 5 => array( 9, ), ), 'chart_bars' => 300, 'multiple' => 0.15, 'multiple2' => 0.2, 'holding_period' => 15, 'conf_level' => 66, ),
		'lrs_ext' => array( 'study_path' => 'assets/studies/lrs_ext/', 'full_study' => FALSE, 'bullish' => array( 1 => array( 6, ), ), 'bearish' => array( 1 => array( 7, ), ), 'chart_bars' => 300, 'multiple' => 3, 'multiple2' => 0.3, 'holding_period' => 15, 'conf_level' => 66, ),
		),
	'XLE' => array( 
		'bbd' => array( 'study_path' => 'assets/studies/bbd/', 'full_study' => FALSE, 'bullish' => array( 10 => array( 14, ), ), 'bearish' => array( 10 => array( 17, ), ), 'chart_bars' => 300, 'multiple' => 0.15, 'multiple2' => 0.2, 'holding_period' => 15, 'conf_level' => 66, ),
		'lrs_ext' => array( 'study_path' => 'assets/studies/lrs_ext/', 'full_study' => FALSE, 'bullish' => array( 1 => array( 6, ), ), 'bearish' => array( 1 => array( 6, ), ), 'chart_bars' => 300, 'multiple' => 7, 'multiple2' => 0.3, 'holding_period' => 15, 'conf_level' => 66, ),
		),
	'XLP' => array( 
		'bbd' => array( 'study_path' => 'assets/studies/bbd/', 'full_study' => FALSE, 'bullish' => array( 10 => array( 13, ), ), 'bearish' => array( 6 => array( 8, ), ), 'chart_bars' => 300, 'multiple' => 0.15, 'multiple2' => 0.2, 'holding_period' => 15, 'conf_level' => 66, ),
		'lrs_ext' => array( 'study_path' => 'assets/studies/lrs_ext/', 'full_study' => FALSE, 'bullish' => array( 1 => array( 6, ), ), 'bearish' => array( 1 => array( 6, ), ), 'chart_bars' => 300, 'multiple' => 2, 'multiple2' => 0.3, 'holding_period' => 15, 'conf_level' => 66, ),
		),
	'XLU' => array( 
		'bbd' => array( 'study_path' => 'assets/studies/bbd/', 'full_study' => FALSE, 'bullish' => array( 9 => array( 8, ), ), 'bearish' => array( 11 => array( 11, ), ), 'chart_bars' => 300, 'multiple' => 0.15, 'multiple2' => 0.2, 'holding_period' => 15, 'conf_level' => 66, ),
		'lrs_ext' => array( 'study_path' => 'assets/studies/lrs_ext/', 'full_study' => FALSE, 'bullish' => array( 1 => array( 6, ), ), 'bearish' => array( 1 => array( 5, ), ), 'chart_bars' => 300, 'multiple' => 3, 'multiple2' => 0.3, 'holding_period' => 15, 'conf_level' => 66, ),
		),
	'XLK' => array( 
		'bbd' => array( 'study_path' => 'assets/studies/bbd/', 'full_study' => FALSE, 'bullish' => array( 7 => array( 12, ), ), 'bearish' => array( 8 => array( 9, ), ), 'chart_bars' => 300, 'multiple' => 0.15, 'multiple2' => 0.2, 'holding_period' => 15, 'conf_level' => 66, ),
		'lrs_ext' => array( 'study_path' => 'assets/studies/lrs_ext/', 'full_study' => FALSE, 'bullish' => array( 1 => array( 4, ), ), 'bearish' => array( 1 => array( 4, ), ), 'chart_bars' => 300, 'multiple' => 1.5, 'multiple2' => 0.3, 'holding_period' => 15, 'conf_level' => 66, ),
		),
	'XLF' => array( 
		'bbd' => array( 'study_path' => 'assets/studies/bbd/', 'full_study' => FALSE, 'bullish' => array( 6 => array( 9, ), ), 'bearish' => array( 8 => array( 16, ), ), 'chart_bars' => 300, 'multiple' => 0.15, 'multiple2' => 0.2, 'holding_period' => 15, 'conf_level' => 66, ),
		'lrs_ext' => array( 'study_path' => 'assets/studies/lrs_ext/', 'full_study' => FALSE, 'bullish' => array( 1 => array( 6, ), ), 'bearish' => array( 1 => array( 6, ), ), 'chart_bars' => 300, 'multiple' => 0.8, 'multiple2' => 0.3, 'holding_period' => 15, 'conf_level' => 66, ),
		),
	'XBI' => array( 
		'bbd' => array( 'study_path' => 'assets/studies/bbd/', 'full_study' => FALSE, 'bullish' => array( 7 => array( 15, ), ), 'bearish' => array( 9 => array( 14, ), ), 'chart_bars' => 300, 'multiple' => 0.15, 'multiple2' => 0.2, 'holding_period' => 15, 'conf_level' => 66, ),
		'lrs_ext' => array( 'study_path' => 'assets/studies/lrs_ext/', 'full_study' => FALSE, 'bullish' => array( 1 => array( 5, ), ), 'bearish' => array( 1 => array( 7, ), ), 'chart_bars' => 300, 'multiple' => 7, 'multiple2' => 0.3, 'holding_period' => 15, 'conf_level' => 66, ),
		),
	// 'XES' => array( 
		// 'bbd' => array( 'study_path' => 'assets/studies/bbd/', 'full_study' => FALSE, 'bullish' => array( 7 => array( 8, ), ), 'bearish' => array( 10 => array( 14, ), ), 'chart_bars' => 300, 'multiple' => 0.15, 'multiple2' => 0.2, 'holding_period' => 15, 'conf_level' => 66, ),
		// 'lrs_ext' => array( 'study_path' => 'assets/studies/lrs_ext/', 'full_study' => FALSE, 'bullish' => array( 1 => array( 5, ), ), 'bearish' => array( 1 => array( 5, ), ), 'chart_bars' => 300, 'multiple' => 5, 'multiple2' => 0.3, 'holding_period' => 15, 'conf_level' => 66, ),
		// ),
	// 'XOP' => array( 
		// 'bbd' => array( 'study_path' => 'assets/studies/bbd/', 'full_study' => FALSE, 'bullish' => array( 8 => array( 12, ), ), 'bearish' => array( 10 => array( 10, ), ), 'chart_bars' => 300, 'multiple' => 0.15, 'multiple2' => 0.2, 'holding_period' => 15, 'conf_level' => 66, ),
		// 'lrs_ext' => array( 'study_path' => 'assets/studies/lrs_ext/', 'full_study' => FALSE, 'bullish' => array( 1 => array( 5, ), ), 'bearish' => array( 1 => array( 5, ), ), 'chart_bars' => 300, 'multiple' => 7, 'multiple2' => 0.2, 'holding_period' => 15, 'conf_level' => 66, ),
		// ),
	// 'XPH' => array( 
		// 'bbd' => array( 'study_path' => 'assets/studies/bbd/', 'full_study' => FALSE, 'bullish' => array( 8 => array( 8, ), ), 'bearish' => array( 9 => array( 15, ), ), 'chart_bars' => 300, 'multiple' => 0.15, 'multiple2' => 0.2, 'holding_period' => 15, 'conf_level' => 66, ),
		// 'lrs_ext' => array( 'study_path' => 'assets/studies/lrs_ext/', 'full_study' => FALSE, 'bullish' => array( 1 => array( 6, ), ), 'bearish' => array( 1 => array( 5, ), ), 'chart_bars' => 300, 'multiple' => 3, 'multiple2' => 0.3, 'holding_period' => 15, 'conf_level' => 66, ),
		// ),
	// 'XRT' => array( 
		// 'bbd' => array( 'study_path' => 'assets/studies/bbd/', 'full_study' => FALSE, 'bullish' => array( 9 => array( 12, ), ), 'bearish' => array( 9 => array( 14, ), ), 'chart_bars' => 300, 'multiple' => 0.15, 'multiple2' => 0.2, 'holding_period' => 15, 'conf_level' => 66, ),
		// 'lrs_ext' => array( 'study_path' => 'assets/studies/lrs_ext/', 'full_study' => FALSE, 'bullish' => array( 1 => array( 5, ), ), 'bearish' => array( 1 => array( 5, ), ), 'chart_bars' => 300, 'multiple' => 4, 'multiple2' => 0.3, 'holding_period' => 15, 'conf_level' => 66, ),
		// ),
	// 'EEM' => array( 
		// 'bbd' => array( 'study_path' => 'assets/studies/bbd/', 'full_study' => FALSE, 'bullish' => array( 6 => array( 12, ), ), 'bearish' => array( 7 => array( 16, ), ), 'chart_bars' => 300, 'multiple' => 0.2, 'multiple2' => 0.3, 'holding_period' => 15, 'conf_level' => 66, ),
		// 'lrs_ext' => array( 'study_path' => 'assets/studies/lrs_ext/', 'full_study' => FALSE, 'bullish' => array( 1 => array( 5, ), ), 'bearish' => array( 1 => array( 7, ), ), 'chart_bars' => 300, 'multiple' => 2, 'multiple2' => 0.3, 'holding_period' => 15, 'conf_level' => 66, ),
		// ),

);

$csv['path'] = 'assets/ohlcv/';
$csv['start_date'] = '20000101';

foreach ( $research as $symbol_name => $data_line ) {

	/* 
	*  Downloand latest .csv's, save .csv files and name them as <SYMBOL>_d_<YYYYMMDD>.csv
	* 'refresh' parameter is in minutes
	*/
	$csv['data'] = new CSV( array( 'symbol' => $symbol_name, 'start_date' => $csv['start_date'], 'path' => $csv['path'], 'refresh' => 30, ) );
	//exit();
	
	
	foreach( $data_line as $study_name => $study_data ) {
	
		switch ( $study_name ) {
			
			case 'lin_reg-atr':
				$study = new LIN_REG_ATR( $symbol_name, $study_name, $study_data, $csv['data'] );
				echo $study;
			break; // lin_reg-atr
			
			case 'bbd':
				$study = new BBD( $symbol_name, $study_name, $study_data, $csv['data'] );
				echo $study;
			break; // lin_reg-atr

			case 'lrs_ext':
				$study = new LRS_EXT( $symbol_name, $study_name, $study_data, $csv['data'] );
				echo $study;
			break; // lin_reg-atr
			
		} // end switch()

	} // end foreach ( $data_line...

	
} // end foreach ( $research...


?>
      </div><!-- end coda slider-->
		</div>
	</body>
</html>


<?php

ob_end_flush(); 

?>
