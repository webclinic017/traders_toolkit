<?php

/**
* 
* @class BBD
* 
* @brief Calculates "Bulls-to_Bears Delta". This indicator takes an x-periods moving average and adds up differences between the closing price and the MA over a time window n. For example, a BBD(3, 19) would build a 3 DMA, then add up positive and negative price differences over 19 days as follows:
  (5closing P - MA value) for day 1 + (closing P - MA value) for day 2 + ... + (closing P - MA value) for day 19.
	Shorthand designation for each tuning of the bbd indicator is x-n, where x is x-periods SMA, and n is the time window.
* The indicator is recommended for symbols with averegae 50-day volume of 800,000 or more.
* Conditions subject to statistical analysis. Bullish condition: changes from negative value of the bbd to positive. Bearish condition: changes from positive bbd value to a negative one vs. subsequent P_extremes (min and max) over a given holding period h. The study discretizes readings of each bullish and bearish condition by 'multiple'. It also descretizes resulting readings of P_extreme max and P_extreme min by multiple2. It then builds frequency tables of BBD condition value vs. P_extreme then converts the frequency tables into 'percent odds' and saves summary of from- to- readings of P_extreme max and min according to a specified confidence interval. Scalar stats are also built from the frequency tables such as std. dev. and average. 
This study was initially devised using the Bulls-to-Bears Ratio, which would take differences in closing prices above an MA divided by differences in closing prices below an MA. However this approach inevitably produced "division by zero" situations, when closing prices were all above an MA. After plotting the distribution of values for the "BBD" (difference) scenario vs. "BBR" (ratio), the difference followed normal distribution, whereas the ratio showed more of a log curve.
* BBD tuning. If 'full_study' flag is set in the study_data array, then all of the x-SMA, n-window values are overriden by the default range of values stored in this class's constructor for($i... and for($j... loops. The tuning mode builds all component indicators for the entire csv series, looks at p_ext_max and p_ext_min values to see if "price goes your way", and based on this, caclculates the odds for each level of the bbd-condition. For example for each bullish condition defined as change of the bbd reading from negative or zero to positive value (let's say bbd reading changes from 0 to +0.2), p_ext_max would be favorable if there is positive spike that follows during the holding period. Sometimes for bullish indicator conditions the appropriate P follow up does not occur. These situations get documented and summarized by numerical reading of each bullish/bearish condition vs. odds. All odds for all bullish conditions are then averaged out and presented in the $bbd_tuning table. Same thing is done for all bearish conditions. The odds are then sorted and are printed on chart if the 'full-study' flag is set. All s-tables (stat tables) get saved as well.
*
* @result Scalar stats table (s-table), price chart with plotted sma, bbd, marked conditions and observed P_max(from, to) and P_min(from, to) intervals plotted on the chart.
* 
* @param (string) $symbol_name Symbol name to be displayed in the quote box.
* @param (string) $study_name Name of the study.
* @param (array) $study_data[keys explained below]
		(string) 'study_path' Directory where to save study results and files. Must end with forward slash. Example: "assets/"
		(array) 'bullish' => array( 3 => array( 8, 19, 29, ), ) Outer array keys are days for the SMA. Inner array values are time-windows.
		(array) 'bearish' => array( 3 => array( 8, 19, 29, ), ) Outer array keys are days for the SMA. Inner array values are time-windows.
		(int) 'chart_bars' (int) desired number of bars to be displayed on chart. The study is donw for the study_bars, but only the chart_bars get displayed.
		(float) 'multiple' descrete interval to round up the independent readings to (BBd readings).
		(float) 'multiple2' descrete interval to round up the dependent readings to (P ext_min and P ext_max readings).
		(int) 'holding_period' number of bars to lookup extreme price readings in.
		(int) 'conf_level' confidence interval to present P ext_max and P ext_min "from" "to".

* @result ...
		s-table(s) for max and min peaks saved on disk (s-table stands for table containing scalar statistics and confidence intervals). If the 'full_study' flag is used all of the calculated s-tables get saved.
		.png image of the chart.
		$this->output gets filled with html code that presents results of the study. 
* 
* @version 130305
* @author Alex Kay (MGWebGroup)
* 
* Contents:
* __construct() - all calcs for the study, creates chart, all necessary reference tables saved on disk and study output html
* __toString() - returns html output for the study when the class's instance gets printed.
* create_s_table() - Creates bullish and bearish s-tables for the bullish and bearish tunings of the indicator.
* 
*/

class BBD {

	public $s_table = array(); 
	private $s_table_header = array( 'bullish' => array( 'name' => NULL, 'url' => NULL ), 'bearish' => array( 'name' => NULL, 'url' => NULL ) );
	private $output = NULL; /**< html code for the study output */

	
	function __construct( $symbol_name, $study_name, $study_data, $csv ) {

		/*
		* Initial conditions
		*/
		$timestamp = time();

		/*
		* Override passed values of sma's and n-windows for tuning of the bbd in case if flag for the full study is set
		* MAKE SURE LENGTH OF $I COUNTER FOR THE BULLISH ARRAY IS EQUAL TO THAT OF THE BEARISH ARRAY. SAME THING WITH THE $J COUNTER. 
		*/ 
		if ( $study_data['full_study'] ) {
			$study_data['bullish'] = array();
			for ( $i = 3; $i <= 10; $i++ ) { // $sma_size = 3; $sma_size <= 10; $sma_size++
				$study_data['bullish'][$i] = array();
				for ( $j = 5; $j <= 15; $j++ ) { // $bbr_size = 5; $bbr_size <=15; $bbr_size++
					$study_data['bullish'][$i][] = $j; 
				}
			}
			//var_dump( $study_data['bullish'] ); exit();
			$study_data['bearish'] = array();
			for ( $i = 4; $i <= 11; $i++ ) { // $sma_size = 4; $sma_size <= 11; $sma_size++
				$study_data['bearish'][$i] = array();
				for ( $j = 7; $j <= 17; $j++ ) { // $bbr_size = 7; $bbr_size <=17; $bbr_size++
					$study_data['bearish'][$i][] = $j; 
				}
			}
			//var_dump( $study_data['bearish'] ); exit();
			$bbd_tuning = array( 'bullish' => array(), 'bearish' => array() ); /** < stores ranking of each bullish and bearish tuning. For example: $key = '03-08', $value = 0.95 */
		}
		$bbd_data = array(); /** < holds passed bbd_data: sma and n values for bullish and bearish bbd's. This variable is set up as array because of situations when sets of values are checked for most effective bbd tuning. */

		for ( $bbd_data['bullish']['sma'] = 0, $bbd_data['bearish']['sma'] = 0; $bbd_data['bullish']['buffer'] = each( $study_data['bullish'] ), $bbd_data['bearish']['buffer'] = each( $study_data['bearish'] ); ) {
			$bbd_data['bullish']['sma'] = $bbd_data['bullish']['buffer']['key'];
			$bbd_data['bearish']['sma'] = $bbd_data['bearish']['buffer']['key'];
			for ( $bbd_data['bullish']['n'] = 0, $bbd_data['bearish']['n'] = 0; $bbd_data['bullish']['buffer1'] = each( $bbd_data['bullish']['buffer']['value'] ), $bbd_data['bearish']['buffer1'] = each( $bbd_data['bearish']['buffer']['value'] ); ) {
				$bbd_data['bullish']['n'] = $bbd_data['bullish']['buffer1']['value'];
				$bbd_data['bearish']['n'] = $bbd_data['bearish']['buffer1']['value'];
				//echo "bullish sma={$bbd_data['bullish']['sma']}, bearish sma={$bbd_data['bearish']['sma']}, bullish n={$bbd_data['bullish']['n']}, bearish n={$bbd_data['bearish']['n']}";				
				//var_dump( $bbd_data ); 
				//exit();
				$this->s_table_header['bullish']['name'] = sprintf( '%s_%s_s-table_bullish%02u-%02u_c%03u_d%02u_m1%.2f_m2%.2f', $symbol_name, $study_name, $bbd_data['bullish']['sma'], $bbd_data['bullish']['n'], $study_data['conf_level'], $study_data['holding_period'], $study_data['multiple'], $study_data['multiple2'] ); /** < Used to put name inside s-table in cell A1 when using Calc::dump_CSV() */
				$this->s_table_header['bearish']['name'] = sprintf( '%s_%s_s-table_bearish%02u-%02u_c%03u_d%02u_m1%.2f_m2%.2f', $symbol_name, $study_name, $bbd_data['bearish']['sma'], $bbd_data['bearish']['n'], $study_data['conf_level'], $study_data['holding_period'], $study_data['multiple'], $study_data['multiple2'] ); /** < Used to put name inside s-table in cell A1 when using Calc::dump_CSV() */
				//var_dump( $name ); exit();
				//$file_location['c-table'] = $study_data['study_path'] . $name['c-table'] . '.csv';
				$this->s_table_header['bullish']['url'] = $study_data['study_path'] . $this->s_table_header['bullish']['name'] . '.csv';
				$this->s_table_header['bearish']['url'] = $study_data['study_path'] . $this->s_table_header['bearish']['name'] . '.csv';
				$bbd_data['bullish']['shortname'] = sprintf( '%02u-%02u', $bbd_data['bullish']['sma'], $bbd_data['bullish']['n'] ); // these arrays store name of the bullisn and bearish combinations for the best tuning tables.
				$bbd_data['bearish']['shortname'] = sprintf( '%02u-%02u', $bbd_data['bearish']['sma'], $bbd_data['bearish']['n'] );				
				
				/*
				* bullish s-table exists in 'study_path'? If not, create.
				*/
				if ( !file_exists( $this->s_table_header['bullish']['url'] ) ) {
					$this->create_s_table( $csv, $bbd_data, $study_data, 'bullish' );
				}	else {
					/*
					* s-table older than holding period for the study data? Create new s-table again to include newly received data. When the s-table is refreshed periodically, new values from the holding period get incorporated. The comparison below does not account for non-trading days. It just looks at number of calendar days passed since last modification times of the c-table and s-table files.
					*/
					$file_stats = stat( $this->s_table_header['bullish']['url'] );
					if ( ( $timestamp - $file_stats['mtime'] ) / 86400 >= $study_data['holding_period'] ) {
						$this->create_s_table( $csv, $bbd_data, $study_data, 'bullish' );
					} else {
						//$this->s_table = array();
						//if ( file_exists( 'test.csv' ) )  { // this line is for debug purposes when you don't need to load up s-table, but just create it instead.
						if ( ( $fh = fopen( $this->s_table_header['bullish']['url'], 'r' ) ) !== FALSE) {
							$data = array();
							$row = 0;
							while ( ( $data = fgetcsv( $fh, 0, ',' ) ) !== FALSE) {
								//var_dump( $data );
								$this->s_table['bullish'][(string) $data[0]] = array( 'ext_max_std_dev' => $data[1], 'ext_max_avg' => $data[2], 'ext_min_std_dev' => $data[3], 'ext_min_avg' => $data[4], 'ext_max_odds' => $data[5], 'ext_min_odds' => $data[6], 'ext_max_from' => $data[7], 'ext_max_to' => $data[8], 'ext_min_from' => $data[9], 'ext_min_to' => $data[10], );
								if ( !$row ) $first_key = $data[0];
								$row++;
							}
							fclose($fh);
						}
						unset( $this->s_table['bullish'][$first_key] );
					
					}
				}
				
				/*
				* bearish s-table exists in 'study_path'? If not, create.
				*/
				if ( !file_exists( $this->s_table_header['bearish']['url'] ) ) {
					$this->create_s_table( $csv, $bbd_data, $study_data, 'bearish' );
				}	else {
					$file_stats = stat( $this->s_table_header['bearish']['url'] );
					if ( ( $timestamp - $file_stats['mtime'] ) / 86400 >= $study_data['holding_period'] ) {
						$this->create_s_table( $csv, $bbd_data, $study_data, 'bearish' );
					} else {
						//$this->s_table = array();
						//if ( file_exists( 'test.csv' ) )  { // this line is for debug purposes when you don't need to load up s-table, but just create it instead.
						if ( ( $fh = fopen( $this->s_table_header['bearish']['url'], 'r' ) ) !== FALSE) {
							$data = array();
							$row = 0;
							while ( ( $data = fgetcsv( $fh, 0, ',' ) ) !== FALSE) {
								//var_dump( $data );
								$this->s_table['bearish'][(string) $data[0]] = array( 'ext_max_std_dev' => $data[1], 'ext_max_avg' => $data[2], 'ext_min_std_dev' => $data[3], 'ext_min_avg' => $data[4], 'ext_max_odds' => $data[5], 'ext_min_odds' => $data[6], 'ext_max_from' => $data[7], 'ext_max_to' => $data[8], 'ext_min_from' => $data[9], 'ext_min_to' => $data[10], );
								if ( !$row ) $first_key = $data[0];
								$row++;
							}
							fclose($fh);
						}
						unset( $this->s_table['bearish'][$first_key] );
					
					}
				}
				//var_dump( $this->s_table ); exit();
				
				/*
				* Figure out weigted average favorable odds for all readings of the bullish bbd and for all readings of the bearish bbd.
				* Weighted average is taken as sum( odds * p_ext_avg ) / sum( p_ext_avg ) 
				*/
				$bbd_tuning['bullish'][$bbd_data['bullish']['shortname']]['weighted_product'] = 0; /** < These vars store intermediate sums for weighted avg calcs */
				$bbd_tuning['bullish'][$bbd_data['bullish']['shortname']]['ext_max_sum'] = 0; 
				$bbd_tuning['bearish'][$bbd_data['bearish']['shortname']]['weighted_product'] = 0;
				$bbd_tuning['bearish'][$bbd_data['bearish']['shortname']]['ext_min_sum'] = 0;
				foreach ( $this->s_table['bullish'] as $row => $line ) {
					$bbd_tuning['bullish'][$bbd_data['bullish']['shortname']]['weighted_product'] += $line['ext_max_odds'] * $line['ext_max_avg'];
					//echo "row=$row, ext_max_odds={$this->s_table[(string) $row]['ext_max_odds']}, ext_max_avg={$this->s_table[(string) $row]['ext_max_avg']} product=". $this->s_table[(string) $row]['ext_max_odds'] * $this->s_table[(string) $row]['ext_max_avg'];
					$bbd_tuning['bullish'][$bbd_data['bullish']['shortname']]['ext_max_sum'] += $line['ext_max_avg'];
				}
				foreach ( $this->s_table['bearish'] as $row => $line ) {
					$bbd_tuning['bearish'][$bbd_data['bearish']['shortname']]['weighted_product'] += $line['ext_min_odds'] * $line['ext_min_avg'];
					$bbd_tuning['bearish'][$bbd_data['bearish']['shortname']]['ext_min_sum'] += $line['ext_min_avg'];
				}
				//var_dump( $bbd_tuning['bullish'][$bbd_data['bullish']['shortname']], $bbd_tuning['bullish'][$bbd_data['bullish']['shortname']]['ext_max_sum'] );
				//var_dump( array_sum( $bbd_tuning['bearish'][$bbd_data['bearish']['shortname']] ), $bbd_tuning['bearish'][$bbd_data['bearish']['shortname']]['ext_min_sum'] );
				//exit();
				$bbd_tuning['bullish'][$bbd_data['bullish']['shortname']] = $bbd_tuning['bullish'][$bbd_data['bullish']['shortname']]['weighted_product'] / $bbd_tuning['bullish'][$bbd_data['bullish']['shortname']]['ext_max_sum'];
				$bbd_tuning['bearish'][$bbd_data['bearish']['shortname']] = $bbd_tuning['bearish'][$bbd_data['bearish']['shortname']]['weighted_product'] / $bbd_tuning['bearish'][$bbd_data['bearish']['shortname']]['ext_min_sum'];

			} // end for ( $bbd['n'] )
		} // end for ( $bbd['sma'] )
		
		arsort( $bbd_tuning['bullish'], SORT_NUMERIC );
		arsort( $bbd_tuning['bearish'], SORT_NUMERIC );
		//unset( $bbd_data );
		//var_dump( $bbd_tuning );
		//var_dump( $this->s_table );
		//exit();
		
		/*
		* Trim uploaded OHLCV data to a the study size (500 bars = 2 years)
		*/
		$study_data['study_bars'] = Calc::myCeil( $study_data['chart_bars'] + max( array( $bbd_data['bullish']['sma'], $bbd_data['bearish']['sma'], ) ) + max( array( $bbd_data['bullish']['n'], $bbd_data['bearish']['n'] ) ) + 3, 10 );
		$study['date_value'] = array_slice( $csv->date_value, -$study_data['study_bars'] ); // array_slice renumbers keys from 0 to count() - 1 in the new sliced out array
		$study['open'] = array_slice( $csv->open, -$study_data['study_bars'] );
		$study['high'] = array_slice( $csv->high, -$study_data['study_bars'] );
		$study['low'] = array_slice( $csv->low, -$study_data['study_bars'] );
		$study['close'] = array_slice( $csv->close, -$study_data['study_bars'] );
		//$study['volume'] = array_slice( $csv->volume, -$study_data['study_bars'] );

		/*
		* Calculate needed indicators for the study size
		*/
		$ma['bullish'] = trader_ma( $study['close'], $bbd_data['bullish']['sma'], TRADER_MA_TYPE_SMA );
		$bbd['bullish'] = Calc::compute_BBD( array( 'values' => $study['close'], 'sma' => $ma['bullish'], 'bbd_size' => $bbd_data['bullish']['n'], ) );
		$ma['bearish'] = trader_ma( $study['close'], $bbd_data['bearish']['sma'], TRADER_MA_TYPE_SMA );
		$bbd['bearish'] = Calc::compute_BBD( array( 'values' => $study['close'], 'sma' => $ma['bearish'], 'bbd_size' => $bbd_data['bearish']['n'], ) );
		//var_dump( $bbd['bullish'], $bbd['bearish'] ); exit();
		$end = count( $study['date_value'] ) - 1;
		$start = $end - $study_data['chart_bars'];
		
		//$result = array(); // debug array
		$symbol_bdd = array( 'bullish' => array(), 'bearish' => array(), );
		
		/*
		* Figure all conditions that require display of ext_max and ext_min P ranges. These values are "raw" meaning they are not discretized. They also will be displayed as raw values on the chart and discretized later when plotting c-levels from- to- intervals. Note that the way you filter, i.e. make comparisons for your focus conditions must be the same as the one used to built the s-table.
		*/
		for ( $key = $start; $key < $end; $key++ ) {
			$study['date_value'][$key] = date( 'm/d/y', $study['date_value'][$key] );

			if ( $bbd['bullish'][$key] <= 0 && $bbd['bullish'][$key + 1] > 0 ) {
				//$symbol_bbd['bullish'][$key + 1] = ceil( $bbd['bullish'][$key + 1] / $study_data['multiple'] ) * $study_data['multiple'];
				$symbol_bbd['bullish'][$key + 1] = $bbd['bullish'][$key + 1];
			}
			if ( $bbd['bearish'][$key] >= 0 && $bbd['bearish'][$key + 1] < 0 ) {
				//$symbol_bbd['bearish'][$key + 1] = floor( $bbd['bearish'][$key + 1] / $study_data['multiple'] ) * $study_data['multiple'];
				$symbol_bbd['bearish'][$key + 1] = $bbd['bearish'][$key + 1];
			}

		}
		
		/*
		* Add human readable dates to the end of the $study['date_value'] for the holding period
		*/
		$key--;
		$value = strtotime( $study['date_value'][$key] ) + 86400;
		for ( $i = $study_data['holding_period']; $i > 0; $value += 86400 ) {
			//echo $study['date_value'][$key] . "<br/>";
			if ( $csv->is_trading_day( $value ) ) {
				$key++;
				$study['date_value'][$key] = date( 'm/d/y', $value );
				$i--;
			}
		}
		
		/*
		* Figure out chart boundaries as well as major and minor steps so that to achieve nice chart presentation.
		*/
		$xmax= $end + $study_data['holding_period'];
		$xmin = $xmax - $study_data['chart_bars'] - $study_data['holding_period'];
		$temp = array_slice( $study['high'], -$study_data['chart_bars'] );
		$ymax = ceil( max( $temp ) * 1.01 / 5 ) * 5;
		$temp = array_slice( $study['low'], -$study_data['chart_bars'] );
		$ymin = floor( min( $temp ) * 0.90 / 5 ) * 5;
		unset( $temp );
		$y_spread = $ymax - $ymin;
		$y_major_interval = Calc::myCeil( $y_spread / 10, 5 );

		/* 
		*  Chart1. The main chart that shows the price action.
		*/
		$path = sprintf( '%s%s_%s_chart1.png', $study_data['study_path'], $symbol_name, $study_name );
		$canvas[1] = array( 'path' => $path, 'percent_chart_area' => 87, 'symbol' => $symbol_name, 'width' => 1600, 'height' => 900, 'img_background' => 'gray', 'chart_background' => 'white', );
		$x_axis = array( 'show' => TRUE, 
				'min' => $xmin, 'max' => $xmax, 
				'y_intersect' => $xmax, 
				'major_tick_size' => 8, 'minor_tick_size' => 4, 
				'categories' => $study['date_value'], 
				'major_interval' => 5, 'minor_interval_count' => 1, 
				'axis_color' => 'black', 
				'show_major_grid' => TRUE, 'show_minor_grid' => TRUE, 
				'major_grid_style' => 'dash', 'minor_grid_style' => 'dash', 
				'major_grid_color' => 'blue', 'minor_grid_color' => 'gray', 
				'major_grid_scale' => 2, 'minor_grid_scale' => 1, 
				'show_major_values' => TRUE, 'show_minor_values' => TRUE, 
				'print_offset_major' => -15, 'print_offset_minor' => -15,
				'font_size' => 9,  
				'precision' => 0,  
				'font_angle' => 90,  
			); 
		$y_axis = array( 'show' => TRUE, 
					'min' => $ymin, 'max' => $ymax, 
					'x_intersect' => $ymin, 
					'major_tick_size' => 8, 'minor_tick_size' => 4, 
					'categories' => NULL, 
					'major_interval' => $y_major_interval, 'minor_interval_count' => 2, 
					'axis_color' => 'black', 
					'show_major_grid' => TRUE, 'show_minor_grid' => TRUE, 
					'major_grid_style' => 'dash', 'minor_grid_style' => 'dash', 
					'major_grid_color' => 'blue', 'minor_grid_color' => 'gray', 
					'major_grid_scale' => 2, 'minor_grid_scale' => 1, 
					'show_major_values' => TRUE, 'show_minor_values' => TRUE, 
					'print_offset_major' => 7, 'print_offset_minor' => 7,
					'font_size' => 10,  
					'precision' => 0,  
					'font_angle' => 0,  
				); 
		$chart = new Chart( array( $canvas[1], $x_axis, $y_axis ) );
		
		/*
		* Just print top 10 ranked BBD tunings if full_study flag is on. BBD ranking is average of all of the odds for all readings of a bullish condition for bullish tune up, and average of all of the odds for all readings of a bearish condition for bearish tune up.
		*/
		if ( $study_data['full_study'] ) {

			$chart->draw_rectangle( array( 'sx' => $xmin, 'sy' => $ymin, 'ex' => $xmax / 2, 'ey' => $ymax, 'color' => 'white', 'transparency' => 20, ) );

			$sx = 100;
			$sy = 85; $text = sprintf( 'full_study flag is set to TRUE. Your best ranking bbd tunings for the symbol %s are:', $symbol_name );
			imagettftext( $chart->image, $chart->default['font_size_x'], 0, $sx, $sy, $chart->pen_colors['black'], $chart->canvas['ttf_font'], $text );
			$sy += 15; $text = 'BULLISH:';
			imagettftext( $chart->image, $chart->default['font_size_x'], 0, $sx, $sy, $chart->pen_colors['black'], $chart->canvas['ttf_font'], $text );
			// $sy += 15; $text = 'x-SMA - n-window => avg odds for positive p_max spikes for all bullish conditions';
			// imagettftext( $chart->image, $chart->default['font_size_x'], 0, $sx, $sy, $chart->pen_colors['black'], $chart->canvas[1]['ttf_font'], $text );
			foreach ( $bbd_tuning['bullish'] as $key => $value ) {
				$sy += 15; $text = sprintf( '%s => %.4f', $key, $value );
				imagettftext( $chart->image, $chart->default['font_size_x'], 0, $sx, $sy, $chart->pen_colors['black'], $chart->canvas['ttf_font'], $text );
			}
			$sx = 325;
			$sy = 100; $text = 'BEARISH:';
			imagettftext( $chart->image, $chart->default['font_size_x'], 0, $sx, $sy, $chart->pen_colors['black'], $chart->canvas['ttf_font'], $text );
			foreach ( $bbd_tuning['bearish'] as $key => $value ) {
				$sy += 15; $text = sprintf( '%s => %.4f', $key, $value );
				imagettftext( $chart->image, $chart->default['font_size_x'], 0, $sx, $sy, $chart->pen_colors['black'], $chart->canvas['ttf_font'], $text );	
			}

			unset( $bbd_tuning );
			
		} else {
		
			$candle_series = array( 'open' => $study['open'], 
				'high' => $study['high'],
				'low' => $study['low'], 
				'close' => $study['close'], 
				'x_axis' => 0, 
				'y_axis' => 0, 
				'color_up' => 'green', 
				'color_down' => 'red', 
				'transparency' => 10, 
				'show_values' => FALSE, 
			);
			$line_series[1] = array( 'series' => $ma['bullish'], 'x_axis' => 0, 'y_axis' => 0, 'color' => 'green', );
			$line_series[2] = array( 'series' => $ma['bearish'], 'x_axis' => 0, 'y_axis' => 0, 'color' => 'red', );
			//$line_series[3] = array( 'series' => $this->atr_band['below'], 'x_axis' => 0, 'y_axis' => 0, 'color' => 'red', );
			$symbol_series[1] = array( 'values' => $symbol_bbd['bullish'], 'shape' => 'triangle-up', 'position' => $study['low'], 'pos_offset' => -20, 'show_values' => TRUE, 'transparency' => 50, 'precision' => 1, );
			$symbol_series[2] = array( 'values' => $symbol_bbd['bearish'], 'shape' => 'triangle-down', 'position' => $study['high'], 'pos_offset' => 20, 'show_values' => TRUE, 'transparency' => 50, 'precision' => 1, );
			//var_dump( $symbol_atr ); exit();
			$chart->add_candlestick_series( $candle_series );
			$chart->add_line_series( $line_series[1] );
			$chart->add_line_series( $line_series[2] );
			$chart->add_symbol_series( $symbol_series[1] );
			$chart->add_symbol_series( $symbol_series[2] );
			//$chart->save_chart();
			//exit();

			/* 
			* For all symbols on chart, show min max intervals for the first confidence level in $study_data['conf_level']
			*/
			foreach ( $symbol_bbd['bullish'] as $key => $value ) {
				$row = ceil( $value / $study_data['multiple'] ) * $study_data['multiple']; // note that type casting to string, like (string) floor( $value / ...) will not help refernece array keys correctly. I've already tried this, and php converts a string to an integer unless you explicitely cast it inside the square bracket either like: $this->s_table[(string) $row] or $this->s_table["$row"]
				//echo "key=$key, row=$row, value=$value, study_data[multiple]={$study_data['multiple']}<br/>";
				if ( array_key_exists( (string) $row, $this->s_table['bullish'] ) ) { // this 'if-statement' is included because sometimes the values of p_atr occur in the holding period for which the full study cannot be done. Holding period is the 'trailing edge' which supposed to include extreme price readings. The readings end before the start of the holding period.lot confidence interval.
					$line_series = array();
					$sx = $key;
					$ex = $sx + $study_data['holding_period'];
					/// plot ext_max range
					$sy = $study['close'][$key] + $study['close'][$key] * $this->s_table['bullish'][(string) $row]['ext_max_from'] / 100;
					$ey = $study['close'][$key] + $study['close'][$key] * $this->s_table['bullish'][(string) $row]['ext_max_to'] / 100;
					$chart->draw_rectangle( array( 'sx' => $sx, 'sy' => $sy, 'ex' => $ex, 'ey' => $ey, 'color' => 'green', 'transparency' => 90, ) );
					/// plot average of the ext_max
					$ext_max_avg = $study['close'][$key] + $study['close'][$key] * $this->s_table['bullish'][(string) $row]['ext_max_avg'] / 100;
					$temp[$sx] = $ext_max_avg;
					$temp[$ex] = $ext_max_avg;
					$line_series[1] = array( 'series' => $temp, 'color' => 'green', );
					$chart->place_text( array( 'sx' => $ex + 1, 'sy' => $ext_max_avg, 'text' => sprintf( '%.2f', $ext_max_avg ), 'color' => 'green', 'ttf_font' => 'assets/arial.ttf' ) );
					/// plot furthest boundary of std dev from the ext_max_avg.
					$temp[$sx] = $ext_max_avg + $study['close'][$key] * ( $this->s_table['bullish'][(string) $row]['ext_max_std_dev'] ) / 100;
					$temp[$ex] = $temp[$sx];
					$line_series[2] = array( 'series' => $temp, 'color' => 'green', 'style' => 'dash', );
					//echo sprintf( 'symbol_atr[above]: bar_no=%s, row=%s, csv->close_p=%s, ext_min_from=%s%%, sy=%s, ext_min_to=%s%%, ey=%s, avg=%s%%, avg_p=%s, std_d=%s%%, std_d_p=%s<br/>', 
					//	$key, $row, $study['close'][$key], $this->s_table['bullish'][(string) $row]['ext_min_from'], $sy, $this->s_table['bullish'][(string) $row]['ext_min_to'], $ey, $this->s_table['bullish'][(string) $row]['ext_min_avg'], $line_series[1][$key], $this->s_table['bullish'][(string) $row]['ext_min_std_dev'], $line_series[2][$key] );
					/// plot ext_min range
					$sy = $study['close'][$key] + $study['close'][$key] * $this->s_table['bullish'][(string) $row]['ext_min_from'] / 100;
					$ey = $study['close'][$key] + $study['close'][$key] * $this->s_table['bullish'][(string) $row]['ext_min_to'] / 100;
					$chart->draw_rectangle( array( 'sx' => $sx, 'sy' => $sy, 'ex' => $ex, 'ey' => $ey, 'color' => 'green', 'transparency' => 50, 'hollow' => TRUE ) );
					/// plot ext_min average 
					$ext_min_avg = $study['close'][$key] + $study['close'][$key] * $this->s_table['bullish'][(string) $row]['ext_min_avg'] / 100;
					$temp[$sx] = $ext_min_avg;
					$temp[$ex] = $ext_min_avg;
					$line_series[3] = array( 'series' => $temp, 'color' => 'green', );
					$chart->place_text( array( 'sx' => $ex + 1, 'sy' => $ext_min_avg, 'text' => sprintf( '%.2f', $ext_min_avg ), 'color' => 'green', 'ttf_font' => 'assets/aaargh.ttf' ) );
					/// plot furthest boundary of std dev from the ext_min_avg.
					$temp[$sx] = $ext_min_avg - $study['close'][$key] * ( $this->s_table['bullish'][(string) $row]['ext_min_std_dev'] ) / 100;
					$temp[$ex] = $temp[$sx];
					$line_series[4] = array( 'series' => $temp, 'color' => 'green', 'style' => 'dash', );
					// plot all line series
					foreach( $line_series as $line ) {
						//var_dump( $line );
						$chart->add_line_series( $line );
					}
					unset( $temp );
					//exit();

				}
			}
			//var_dump( $this->s_table['bullish'] );
			
			foreach ( $symbol_bbd['bearish'] as $key => $value ) {
				$row = floor( $value / $study_data['multiple'] ) * $study_data['multiple'];
				if ( array_key_exists( (string) $row, $this->s_table['bearish'] ) ) { 
					$line_series = array();
					$sx = $key;
					$ex = $sx + $study_data['holding_period'];
					/// plot ext_min range
					$sy = $study['close'][$key] + $study['close'][$key] * $this->s_table['bearish'][(string) $row]['ext_min_from'] / 100;
					$ey = $study['close'][$key] + $study['close'][$key] * $this->s_table['bearish'][(string) $row]['ext_min_to'] / 100;
					$chart->draw_rectangle( array( 'sx' => $sx, 'sy' => $sy, 'ex' => $ex, 'ey' => $ey, 'color' => 'red', 'transparency' => 90, ) );
					/// plot average of ext_min
					$ext_min_avg = $study['close'][$key] + $study['close'][$key] * $this->s_table['bearish'][(string) $row]['ext_min_avg'] / 100;
					$temp[$sx] = $ext_min_avg;
					$temp[$ex] = $ext_min_avg;
					$line_series[1] = array( 'series' => $temp, 'color' => 'red', );
					$chart->place_text( array( 'sx' => $ex + 1, 'sy' => $ext_min_avg, 'text' => sprintf( '%.2f', $ext_min_avg ), 'color' => 'red', 'ttf_font' => 'assets/arial.ttf' ) );
					/// plot furthest boundary of std dev form ext_min_avg.
					$temp[$sx] = $ext_min_avg - $study['close'][$key] * ( $this->s_table['bearish'][(string) $row]['ext_min_std_dev'] ) / 100;
					$temp[$ex] = $temp[$sx];
					$line_series[2] = array( 'series' => $temp, 'color' => 'red', 'style' => 'dash', );
					//echo sprintf( 'symbol_atr[above]: bar_no=%s, row=%s, csv->close_p=%s, ext_min_from=%s%%, sy=%s, ext_min_to=%s%%, ey=%s, avg=%s%%, avg_p=%s, std_d=%s%%, std_d_p=%s<br/>', 
					//	$key, $row, $study['close'][$key], $this->s_table['bearish'][(string) $row]['ext_min_from'], $sy, $this->s_table['bearish'][(string) $row]['ext_min_to'], $ey, $this->s_table['bearish'][(string) $row]['ext_min_avg'], $line_series[1][$key], $this->s_table['bearish'][(string) $row]['ext_min_std_dev'], $line_series[2][$key] );
					/// plot ext_max range (hollow rectangles)
					$sy = $study['close'][$key] + $study['close'][$key] * $this->s_table['bearish'][(string) $row]['ext_max_from'] / 100;
					$ey = $study['close'][$key] + $study['close'][$key] * $this->s_table['bearish'][(string) $row]['ext_max_to'] / 100;
					$chart->draw_rectangle( array( 'sx' => $sx, 'sy' => $sy, 'ex' => $ex, 'ey' => $ey, 'color' => 'red', 'transparency' => 50, 'hollow' => TRUE ) );
					/// plot average of the resultant ext_max_avg
					$ext_max_avg = $study['close'][$key] + $study['close'][$key] * $this->s_table['bearish'][(string) $row]['ext_max_avg'] / 100;
					$temp[$sx] = $ext_max_avg;
					$temp[$ex] = $ext_max_avg;
					$line_series[3] = array( 'series' => $temp, 'color' => 'red', );
					$chart->place_text( array( 'sx' => $ex + 1, 'sy' => $ext_max_avg, 'text' => sprintf( '%.2f', $ext_max_avg ), 'color' => 'red', 'ttf_font' => 'assets/aaargh.ttf' ) );
					/// plot furthest boundary of std dev form ext_max_avg.
					$temp[$sx] = $ext_max_avg + $study['close'][$key] * $this->s_table['bearish'][(string) $row]['ext_max_std_dev'] / 100;
					$temp[$ex] = $temp[$sx];
					$line_series[4] = array( 'series' => $temp, 'color' => 'red', 'style' => 'dash', );
					// plot all line series
					foreach( $line_series as $line ) {
						//var_dump( $line );
						$chart->add_line_series( $line );
					}
					unset( $temp );

				}
			}

			/// Place annotation on chart as well.
			$annotation = sprintf( 'Bulls to Bears delta (BBd), Symbol: %s, BBd tuning: Bullish=%s Bearish=%s, Holding Per.=%02ud, Conf. Int.=%03u%%', $symbol_name, $bbd_data['bullish']['shortname'], $bbd_data['bearish']['shortname'], $study_data['holding_period'], $study_data['conf_level'] );
			$chart->place_text( array( 'sx' => $xmin + 1, 'sy' => $ymax, 'text' => $annotation, 'vert_algn' => 'up', 'font_size' => 12, 'ttf_font' => 'assets/arial.ttf', ) );
			//$chart->save_chart();

		}
		
		/*
		* INDICATOR PANEL: plot bullish and bearish bbd's on the chart below the main one
		*/
		$path = sprintf( '%s%s_%s_chart2.png', $study_data['study_path'], $symbol_name, $study_name );
		$canvas[2] = array( 'path' => $path, 'percent_chart_area' => 87, 'symbol' => $symbol_name, 'width' => 1600, 'height' => 270, 'img_background' => 'gray', 'chart_background' => 'white', );
		$im = imagecreatetruecolor( $canvas[1]['width'], $canvas[1]['height'] + $canvas[2]['height'] ); /// this is background image that holds the p-chart and the indicator chart.
		imagecopymerge( $im, $chart->image, 0, 0, 0, 0, $canvas[1]['width'], $canvas[1]['height'], 100 );
		unset( $chart ); /// to save memory

		///figure ymin and ymax for the slope_diff series
		$ymax = array();
		$ymin = array();
		$temp = array();
		foreach( $bbd as $values ) {
			$temp = array_slice( $values, -$study_data['chart_bars'] );
			$ymax[] = ceil( max( $temp ) * 1.01 / 5 ) * 5;
			$ymin[] = floor( min( $temp ) * 0.99 / 5 ) * 5;
		}
		// foreach( $slope as $values ) {
			// $temp = array_slice( $values, -$study_data['chart_bars'] );
			// $ymax[] = ceil( max( $temp ) * 1.01 / 5 ) * 5;
			// $ymin[] = floor( min( $temp ) * 0.99 / 5 ) * 5;
		// }
		$ymax = max( $ymax );
		$ymin = min ($ymin );
		
		$y_spread = $ymax - $ymin;
		$y_major_interval = Calc::myCeil( $y_spread / 10, 2 );
		//var_dump( $ymax, $ymin, $y_major_interval, $xmax, $xmin ); exit();

		$x_axis = array( 'show' => TRUE, 
				'min' => $xmin, 'max' => $xmax, 
				'y_intersect' => $xmax, 
				'major_tick_size' => 8, 'minor_tick_size' => 4, 
				'categories' => $study['date_value'], 
				'major_interval' => 5, 'minor_interval_count' => 1, 
				'axis_color' => 'black', 
				'show_major_grid' => TRUE, 'show_minor_grid' => TRUE, 
				'major_grid_style' => 'dash', 'minor_grid_style' => 'dash', 
				'major_grid_color' => 'blue', 'minor_grid_color' => 'gray', 
				'major_grid_scale' => 2, 'minor_grid_scale' => 1, 
				'show_major_values' => TRUE, 'show_minor_values' => TRUE, 
				'print_offset_major' => -15, 'print_offset_minor' => -15,
				'font_size' => 9,  
				'precision' => 0,  
				'font_angle' => 90,  
			); 
		$y_axis = array( 'show' => TRUE, 
					'min' => $ymin, 'max' => $ymax, 
					'x_intersect' => $ymin, 
					'major_tick_size' => 8, 'minor_tick_size' => 4, 
					'categories' => NULL, 
					'major_interval' => $y_major_interval, 'minor_interval_count' => 2, 
					'axis_color' => 'black', 
					'show_major_grid' => TRUE, 'show_minor_grid' => TRUE, 
					'major_grid_style' => 'dash', 'minor_grid_style' => 'dash', 
					'major_grid_color' => 'gray', 'minor_grid_color' => 'gray', 
					'major_grid_scale' => 1, 'minor_grid_scale' => 1, 
					'show_major_values' => TRUE, 'show_minor_values' => TRUE, 
					'print_offset_major' => 7, 'print_offset_minor' => 7,
					'font_size' => 10,  
					'precision' => 1,  
					'font_angle' => 0,  
				); 

		$chart = new Chart( array( $canvas[2], $x_axis, $y_axis ) );

		$line_series = array();
		$line_series[1] = array( 'series' => $bbd['bullish'], 'x_axis' => 0, 'y_axis' => 0, 'color' => 'green', );
		$line_series[2] = array( 'series' => $bbd['bearish'], 'x_axis' => 0, 'y_axis' => 0, 'color' => 'red', );
		$y_zero_line = array( $xmin => 0, $xmax => 0, );
		$line_series[3] = array( 'series' => $y_zero_line, 'x_axis' => 0, 'y_axis' => 0, 'color' => 'blue', );
		foreach( $line_series as $line ) {
			$chart->add_line_series( $line );
		}
		//imagecolortransparent( $chart2->image, $chart2->pen_colors['white'] );

		/// Place annotation on indicator panel.
		$annotation = sprintf( 'BBd values: bullish=%.2f bearish=%.2f', $bbd['bullish'][$end], $bbd['bearish'][$end] );
		$chart->place_text( array( 'sx' => $xmin + 1, 'sy' => $ymax, 'text' => $annotation, 'vert_algn' => 'up', 'font_size' => 12, 'ttf_font' => 'assets/arial.ttf', ) );
		
		///Add this indicator panels into one.
		imagecopymerge( $im, $chart->image, 0, $canvas[1]['height'], 0, 0, $canvas[2]['width'], $canvas[2]['height'], 100 );
		unset( $chart );
		if ( $study_data['full_study'] ) {
			$path = sprintf( '%s%s_%s_FULL.png', $study_data['study_path'], $symbol_name, $study_name );
		} else {
			$path = sprintf( '%s%s_%s_chart.png', $study_data['study_path'], $symbol_name, $study_name );
		}
		imagepng( $im, $path );
		
		//$chart->save_chart();

		
		/* 
		* Buld html output and save on disk.
		*/
		$this->output = "<div class=\"$study_name $symbol_name\"><div class=\"quote_box\"><div class=\"symbol\">$symbol_name</div><div class=\"quote\">";
		$this->output .= sprintf( '%s O=%.2f H=%.2f L=%.2f C=%.2f V=%s', date( 'j-M-y', $csv->last['date_value'] ), $csv->last['open'], $csv->last['high'], $csv->last['low'], $csv->last['close'], number_format( $csv->last['volume'] ) ); 
		$this->output .= "</div><div class=\"study_data\">Bulls to Bears delta (BBd) Study<br/>BBd tuning: Bullish={$bbd_data['bullish']['shortname']}, Bearish={$bbd_data['bearish']['shortname']} Current value: Bullish={$bbd['bullish'][$end]}, Bearish={$bbd['bearish'][$end]}</div></div><div class=\"chart\"><img src=\"";
		$this->output .= $path;
		$this->output .= "\" alt=\"Chart for $symbol_name\" ></div></div>";
		
		// $fh = fopen( $study_data['study_path'] . $symbol_name . '_' . $study_name . '.html', 'w' );
		// fwrite( $fh, $this->output );
		// fclose( $fh );
		
		//echo $this->output;
		//exit();
	} // end __construct
	
	
	function __toString() {
		return $this->output;
	}

	private function create_s_table( $csv, $bbd_data, $study_data, $side ) {
		$this->s_table[$side] = array();
		global $symbol_name, $study_name;
		/* 
		* Do a full study for bbd bullish and bearish tunings. Bullish and bearish bbd's supposed to differ by x-sma and n-window. Figure ext_max and ext_min for the given 'holding_period'. 
		*/
		$first_key = array();
		$ma = trader_ma( $csv->close, $bbd_data[$side]['sma'], TRADER_MA_TYPE_SMA );
		$arr_keys = array_keys( $ma );
		$first_key['ma'] = $arr_keys[0];
		//var_dump( $ma ); exit();

		// $ma['bearish'] = trader_ma( $csv->close, $bbd_data['bearish']['sma'], TRADER_MA_TYPE_SMA );
		// $arr_keys = array_keys( $ma['bearish'] );
		// $first_key['ma_bearish'] = $arr_keys[0];
		
		$bbd = Calc::compute_BBD( array( 'values' => &$csv->close, 'sma' => $ma, 'bbd_size' => $bbd_data[$side]['n'], ) );
		$arr_keys = array_keys( $bbd );
		$first_key['bbd'] = $arr_keys[0];
		//var_dump( $bbd ); exit();

		// $bbd['bearish'] = Calc::compute_BBD( array( 'values' => &$csv->close, 'sma' => $ma['bearish'], 'bbd_size' => $bbd_data['bearish']['n'], ) );
		// $arr_keys = array_keys( $bbd['bearish'] );
		// $first_key['bbd_bearish'] = $arr_keys[0];
		
		$ext_max = Calc::lookup_Extreme( array( 'values' => &$csv->close, 'extreme' => 'max', 'bars' => $study_data['holding_period'], 'prec' => 2, ) );
		$arr_keys = array_keys( $ext_max );
		$first_key['ext_max'] = $arr_keys[0];
		
		$ext_min = Calc::lookup_Extreme( array( 'values' => &$csv->close, 'extreme' => 'min', 'bars' => $study_data['holding_period'], 'prec' => 2, ) );
		$arr_keys = array_keys( $ext_min );
		$first_key['ext_min'] = $arr_keys[0];
		//var_dump( $first_key ); exit();
		$start = max( $first_key );
		$end = count( $csv->date_value ) - 1 - $study_data['holding_period'];
		
		/*
		* Trim all resultant arrays to be equal length. Remove 'holding_period' from end as the bars there do not fully define subsequent p-action.
		*/
		$ma = array_slice( $ma, $start - $first_key['ma'], -$study_data['holding_period'], TRUE );
		//$ma['bearish'] = array_slice( $ma['bearish'], $start - $first_key['ma_bearish'], -$study_data['holding_period'], TRUE );
		$bbd = array_slice( $bbd, $start - $first_key['bbd'], -$study_data['holding_period'], TRUE );
		//$bbd['bearish'] = array_slice( $bbd['bearish'], $start - $first_key['bbd_bearish'], -$study_data['holding_period'], TRUE );
		$ext_max = array_slice( $ext_max, $start - $first_key['ext_max'], NULL, TRUE );
		$ext_min = array_slice( $ext_min, $start - $first_key['ext_min'], NULL, TRUE );
		unset( $first_key );
		//var_dump( $ma, $bbd, $ext_max, $ext_min ); 
		//exit();
		
		/*
		* Filter needed bullish and bearish conditions for bbd. Record raw p_ext min and max readings for each inclination of the indicator (i.e. for bullish condition, record p_ext_max and p_ext_min) into s-table for scalar stats "on the fly". Mark all p_extremes that go "your way" (i.e. bullish indicator - bullish p_ext) for odds calculations (i.e. how many times p goes "your way". The key when using filters such as this one is to be certain and consistent on what would constitude a bullish condition, i.e. reading of strictly > 0 or reading >= 0. Chose one and use only it. Otherwise you will have overlaps of the independent variable in your stat table.
		* Discretize filtered independent and resulting min and max P readings for pivot tables. 
		*/
		$rows_population = array();
		$columns_population = array( 'ext_max' => array(), 'ext_min' => array(), );
		for ( $key = $start; $key < $end; $key++ ) {
			
			switch ( $side ) {
			
				case 'bullish':
					$counter = FALSE; /**< keeps track of favorable hits in p_ext_max and p_ext_min. For example, p_ext_max supposed to be positive for bullish condition and vice versa */
					if ( $bbd[$key] <= 0 && $bbd[$key + 1] > 0 ) {
						$rows_population[$key + 1] = ceil( $bbd[$key + 1] / $study_data['multiple'] ) * $study_data['multiple'];
						//echo sprintf( 'bar no=%s, bbd_value=%s <br/>', $key+1, $rows_population[$key + 1] );
						if ( $ext_max[$key + 1] > 0 ) {
							$columns_population['ext_max'][$key + 1] = ceil( $ext_max[$key + 1] / $study_data['multiple2'] ) * $study_data['multiple2'];
							$counter = TRUE;
						} else {
							$columns_population['ext_max'][$key + 1] = floor( $ext_max[$key + 1] / $study_data['multiple2'] ) * $study_data['multiple2'];
						}
						if ( $ext_min[$key + 1] > 0 ) {
							$columns_population['ext_min'][$key + 1] = ceil( $ext_min[$key + 1] / $study_data['multiple2'] ) * $study_data['multiple2'];
						} else {
							$columns_population['ext_min'][$key + 1] = floor( $ext_min[$key + 1] / $study_data['multiple2'] ) * $study_data['multiple2'];
						}
						$this->s_table[$side][(string) $rows_population[$key + 1]]['ext_max_std_dev'][] = $ext_max[$key + 1];
						$this->s_table[$side][(string) $rows_population[$key + 1]]['ext_max_avg'][] = $ext_max[$key + 1];
						$this->s_table[$side][(string) $rows_population[$key + 1]]['ext_min_std_dev'][] = $ext_min[$key + 1];
						$this->s_table[$side][(string) $rows_population[$key + 1]]['ext_min_avg'][] = $ext_min[$key + 1];
						$this->s_table[$side][(string) $rows_population[$key + 1]]['ext_max_odds'][] = (int) $counter;
						$this->s_table[$side][(string) $rows_population[$key + 1]]['ext_min_odds'][] = 0;
					}
				break;
				
				case 'bearish':
					$counter = FALSE; 
					if ( $bbd[$key] > 0 && $bbd[$key + 1] <= 0 ) {
						$rows_population[$key + 1] = floor( $bbd[$key + 1] / $study_data['multiple'] ) * $study_data['multiple'];
						if ( $ext_min[$key + 1] >= 0 ) {
							$columns_population['ext_min'][$key + 1] = ceil( $ext_min[$key + 1] / $study_data['multiple2'] ) * $study_data['multiple2'];
						} else {
							$columns_population['ext_min'][$key + 1] = floor( $ext_min[$key + 1] / $study_data['multiple2'] ) * $study_data['multiple2'];
							$counter = TRUE;
							//$result[$key + 1] = $ext_min[$key + 1];
						}
						if ( $ext_max[$key + 1] > 0 ) {
							$columns_population['ext_max'][$key + 1] = ceil( $ext_max[$key + 1] / $study_data['multiple2'] ) * $study_data['multiple2'];
						} else {
							$columns_population['ext_max'][$key + 1] = floor( $ext_max[$key + 1] / $study_data['multiple2'] ) * $study_data['multiple2'];
						}
						$this->s_table[$side][(string) $rows_population[$key + 1]]['ext_max_std_dev'][] = $ext_max[$key + 1];
						$this->s_table[$side][(string) $rows_population[$key + 1]]['ext_max_avg'][] = $ext_max[$key + 1];
						$this->s_table[$side][(string) $rows_population[$key + 1]]['ext_min_std_dev'][] = $ext_min[$key + 1];
						$this->s_table[$side][(string) $rows_population[$key + 1]]['ext_min_avg'][] = $ext_min[$key + 1];
						$this->s_table[$side][(string) $rows_population[$key + 1]]['ext_max_odds'][] = 0;
						$this->s_table[$side][(string) $rows_population[$key + 1]]['ext_min_odds'][] = (int) $counter;

					}
			
			}
			
			
			//echo sprintf( 'bar no=%s, bbd_value=%s <br/>', $key+1, $rows_population[$key + 1] );
			
		}
		//echo 'rows_population from create_s_table f'; var_dump( $rows_population );
		//var_dump( $rows_population, $result, $columns_population['ext_min'] ); exit();
		//Calc::dump_CSV( array( 'source' => &$result, 'filename' => sprintf( $study_data['study_path'].'%s_%s_a-table.csv', $symbol_name, $study_name ), ) );
		//var_dump( $this->s_table );
		//exit();
		
		/*
		* Calculate all scalar stats in the s-table
		*/
		foreach ( $this->s_table[$side] as $row => $line ) {
			$this->s_table[$side][(string) $row]['ext_max_std_dev'] = stats_standard_deviation( $line['ext_max_std_dev'] );
			$this->s_table[$side][(string) $row]['ext_max_avg'] = array_sum( $line['ext_max_avg'] ) / count( $line['ext_max_avg'] );
			$this->s_table[$side][(string) $row]['ext_min_std_dev'] = stats_standard_deviation( $line['ext_min_std_dev'] );
			$this->s_table[$side][(string) $row]['ext_min_avg'] = array_sum( $line['ext_min_avg'] ) / count( $line['ext_min_avg'] );
			$this->s_table[$side][(string) $row]['ext_max_odds'] = array_sum( $line['ext_max_odds'] ) / count( $line['ext_max_odds'] );
			$this->s_table[$side][(string) $row]['ext_min_odds'] = array_sum( $line['ext_min_odds'] ) / count( $line['ext_min_odds'] );
		}
		//var_dump( $rows_population );
		//var_dump( $this->s_table );
		//var_dump( $bbd_tuning );
		//exit();

		
		/*
		* Pivot 1, 2: bbd-ext_max, bbd-ext_min. These tables are built to figure confidence intervals
		*/
		Calc::create_Pivot( array( 
			'name' => 'bbd-ext_max', 
			'rows_population' => $rows_population, 
			'columns_population' => $columns_population['ext_max'], 
			//'dump_csv' => sprintf( $study_data['study_path'].'%s_%s_%s-ext_max.csv', $symbol_name, $study_name, $side ),
		) );
		//var_dump( $rows_population ); 
		//exit();
		Calc::create_Pivot( array( 
			'name' => 'bbd-ext_min', 
			'rows_population' => $rows_population, 
			'columns_population' => $columns_population['ext_min'], 
			//'dump_csv' => sprintf( $study_data['study_path'].'%s_%s_%s-ext_min.csv', $symbol_name, $study_name, $side ),
		) );
		//exit();
		
		/*
		*	Convert p-tables into %-distribution. Remove total column and total row as they will be in a way of calculating confidence intervals.
		*/
		foreach ( Calc::$pivot_table['bbd-ext_max'] as $row => $line ) {
			if ( (string) $row <> 'total' ) {
				$row_total = Calc::$pivot_table['bbd-ext_max'][$row]['total'];
				unset( Calc::$pivot_table['bbd-ext_max'][$row]['total'] );
				unset( $line['total'] );
				$data = array(); // collect number of times each column value occurred for calculation of std. dev. and average
				foreach ( $line as $column => $value ) {
					Calc::$pivot_table['bbd-ext_max'][$row][$column] = round( $value / $row_total * 100, 2 );
				}
			}
		}
		unset( Calc::$pivot_table['bbd-ext_max']['total'] );
		//Calc::dump_CSV( array( 'source' => $this->s_table['bbd_bullish'], 'name' => $this->s_table_header['name'].'bullish', 'filename' => $this->s_table_header['url'], ) );
		//var_dump( Calc::$pivot_table['bbd-ext_max'] );
		//exit();
		foreach ( Calc::$pivot_table['bbd-ext_min'] as $row => $line ) {
			if ( (string) $row <> 'total' ) {
				$row_total = Calc::$pivot_table['bbd-ext_min'][$row]['total'];
				unset( Calc::$pivot_table['bbd-ext_min'][$row]['total'] );
				unset( $line['total'] );
				$data = array(); // collect number of times each column value occurred for calculation of std. dev. and average
				foreach ( $line as $column => $value ) {
					Calc::$pivot_table['bbd-ext_min'][$row][$column] = round( $value / $row_total * 100, 2 );
				}
			}
		}
		unset( Calc::$pivot_table['bbd-ext_min']['total'] );
		//var_dump( Calc::$pivot_table['bbd-ext_min'] );
		
		
		/* 
		* Build confidence intervals and add them as last two columns into the s-table
		*/
		$limit = ( 100 - $study_data['conf_level'] ) / 2; // %-odds to trim from each side of distribution
		//var_dump( $this->s_table );
		foreach ( Calc::$pivot_table['bbd-ext_max'] as $row => $line ) {
			for ( $i = 0; $i < $limit && $data = each( $line ); ) {
				$i += $data['value'];
				$index = $data['key'];
			}
			$this->s_table[$side][(string) $row]['ext_max_from'] = $index;
			$line = array_reverse( $line, TRUE );
			for ( $i = 0; $i < $limit && $data = each( $line ); ) {
				$i += $data['value'];
				$index = $data['key'];
			}
			$this->s_table[$side][(string) $row]['ext_max_to'] = $index;
		}
		foreach ( Calc::$pivot_table['bbd-ext_min'] as $row => $line ) {
			for ( $i = 0; $i < $limit && $data = each( $line ); ) {
				$i += $data['value'];
				$index = $data['key'];
			}
			$this->s_table[$side][(string) $row]['ext_min_from'] = $index;
			$line = array_reverse( $line, TRUE );
			for ( $i = 0; $i < $limit && $data = each( $line ); ) {
				$i += $data['value'];
				$index = $data['key'];
			}
			$this->s_table[$side][(string) $row]['ext_min_to'] = $index;
		}
		//exit();

		Calc::dump_CSV( array( 'source' => $this->s_table[$side], 'name' => $this->s_table_header[$side]['name'], 'filename' => $this->s_table_header[$side]['url'], ) );
		//exit();
					
	}
	
} // end class ATR_P


?>