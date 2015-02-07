<?php

/**
* 
* @class LIN_REG_SLOPE
* 
* @brief Calculates linear regression values, finds linear regression slope for a given vanule of 'n'. The class also calculates difference in slopes. The s-table is built by applying a filter for change in slope in linear regression lines from positive to negative for bearish conditions and from negative to positive for bullish. Difference in slopes are noted and filed in the s-table.
* In essence the class builds the series for a linear regression based on the closing prices, then finds slopes of linear regression lines formed by the value of 'n', i.e. finds slopes of linear regression lines 14 bars long, then, to build s-table, looks into the change in slope of the built linear regression lines from  negative to positive for bullish conditions and from positive to negative for bearish. The class also finds difference in slopes of the linear regression line for each new bar and references them instead of raw slopes of linear regression lines. lin_reg_slope2.class does exactnly that it takes resulting slope and files it in the s-table. Reason I made filing of s-tables for the slope difference is because I think you get more varied population of the differences in slope as independed variable vs. resulting slope of after the change in slope of the linear regression line. You can use lin_reg_slope2.class if you want to reference raw slopes instead of slope differences.
*
* @result Scalar stats table (s-table), price chart with plotted bullish and bearish linear regression lines, marked conditions and observed P_max(from, to) and P_min(from, to) intervals plotted on the chart.
* 
* @param (string) $symbol_name Symbol name to be displayed in the quote box.
* @param (string) $study_name Name of the study.
* @param (array) $study_data[keys explained below]
		(string) 'study_path' Directory where to save study results and files. Must end with forward slash. Example: "assets/"
		(array) 'bullish' => array( 1 => array( 14, ), ) Outer array keys are just a number of a linear regression line to plot. They represent just a sequential number. I don't see a reason why you would want to plot two lin_reg lines for the same bullish condition. So it is prudent to have just one key in there. Inner array values are length in bars for linear regression lines.
		(array) 'bearish' => array( 3 => array( 8, ), ) 
		(int) 'chart_bars' (int) desired number of bars to be displayed on chart. The study is done for the entire csv set, but only the chart_bars get displayed.
		(float) 'multiple' descrete interval to round up the independent readings to (difference in slope readings).
		(float) 'multiple2' descrete interval to round up the dependent readings to (P ext_min and P ext_max readings).
		(int) 'holding_period' number of bars to lookup extreme price readings in.
		(int) 'conf_level' confidence interval to present P ext_max and P ext_min "from" "to".

* @result ...
		s-table(s) for max and min peaks saved on disk (s-table stands for table containing scalar statistics and confidence intervals). If the 'full_study' flag is used all of the calculated s-tables get saved.
		.png image of the chart.
		$this->output gets filled with html code that presents results of the study. 
* 
* @version 130525
* @author Alex Kay (MGWebGroup)
* 
* Contents:
* __construct() - all calcs for the study, creates chart, all necessary reference tables saved on disk and study output html
* __toString() - returns html output for the study when the class's instance gets printed.
* create_s_table() - Creates bullish and bearish s-tables for the bullish and bearish tunings of the indicator.
* 
*/

class LIN_REG_SLOPE {

	public $s_table = array(); 
	private $s_table_header = array( 'bullish' => array( 'name' => NULL, 'url' => NULL ), 'bearish' => array( 'name' => NULL, 'url' => NULL ) );
	//private $study = array( 'date_value' => array(), 'open' => array(), 'high' => array(), 'low' => array(), 'close' => array(), );	/**< Holds OHLCV values for this study only */
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
			for ( $i = 1; $i <= 1; $i++ ) { // $sma_size = 3; $sma_size <= 10; $sma_size++
				$study_data['bullish'][$i] = array();
				for ( $j = 3; $j <= 50; $j++ ) { // $bbr_size = 5; $bbr_size <=15; $bbr_size++
					$study_data['bullish'][$i][] = $j; 
				}
			}
			//var_dump( $study_data['bullish'] ); exit();
			$study_data['bearish'] = array();
			for ( $i = 1; $i <= 1; $i++ ) { // $sma_size = 4; $sma_size <= 11; $sma_size++
				$study_data['bearish'][$i] = array();
				for ( $j = 3; $j <= 50; $j++ ) { // $bbr_size = 7; $bbr_size <=17; $bbr_size++
					$study_data['bearish'][$i][] = $j; 
				}
			}
			//var_dump( $study_data['bearish'] ); exit();
			$indicator_tuning = array( 'bullish' => array(), 'bearish' => array() ); /** < stores ranking of each bullish and bearish tuning. For example: $key = '03-08', $value = 0.95 */
		}
		$indicator_data = array(); /** < holds passed bbd_data: sma and n values for bullish and bearish bbd's. This variable is set up as array because of situations when sets of values are checked for most effective bbd tuning. */

		for ( $indicator_data['bullish']['m'] = 0, $indicator_data['bearish']['m'] = 0; $indicator_data['bullish']['buffer'] = each( $study_data['bullish'] ), $indicator_data['bearish']['buffer'] = each( $study_data['bearish'] ); ) {
			$indicator_data['bullish']['m'] = $indicator_data['bullish']['buffer']['key'];
			$indicator_data['bearish']['m'] = $indicator_data['bearish']['buffer']['key'];
			for ( $indicator_data['bullish']['n'] = 0, $indicator_data['bearish']['n'] = 0; $indicator_data['bullish']['buffer1'] = each( $indicator_data['bullish']['buffer']['value'] ), $indicator_data['bearish']['buffer1'] = each( $indicator_data['bearish']['buffer']['value'] ); ) {
				$indicator_data['bullish']['n'] = $indicator_data['bullish']['buffer1']['value'];
				$indicator_data['bearish']['n'] = $indicator_data['bearish']['buffer1']['value'];
				//echo "bullish sma={$indicator_data['bullish']['m']}, bearish sma={$indicator_data['bearish']['m']}, bullish n={$indicator_data['bullish']['n']}, bearish n={$indicator_data['bearish']['n']}";				
				//var_dump( $indicator_data ); 
				//exit();
				$this->s_table_header['bullish']['name'] = sprintf( '%s_%s_s-table_bullish%02u-%02u_c%03u_d%02u_m1%.2f_m2%.2f', $symbol_name, $study_name, $indicator_data['bullish']['m'], $indicator_data['bullish']['n'], $study_data['conf_level'], $study_data['holding_period'], $study_data['multiple'], $study_data['multiple2'] ); /** < Used to put name inside s-table in cell A1 when using Calc::dump_CSV() */
				$this->s_table_header['bearish']['name'] = sprintf( '%s_%s_s-table_bearish%02u-%02u_c%03u_d%02u_m1%.2f_m2%.2f', $symbol_name, $study_name, $indicator_data['bearish']['m'], $indicator_data['bearish']['n'], $study_data['conf_level'], $study_data['holding_period'], $study_data['multiple'], $study_data['multiple2'] ); /** < Used to put name inside s-table in cell A1 when using Calc::dump_CSV() */
				//var_dump( $name ); exit();
				$this->s_table_header['bullish']['url'] = $study_data['study_path'] . $this->s_table_header['bullish']['name'] . '.csv';
				$this->s_table_header['bearish']['url'] = $study_data['study_path'] . $this->s_table_header['bearish']['name'] . '.csv';
				$indicator_data['bullish']['shortname'] = sprintf( '%02u-%02u', $indicator_data['bullish']['m'], $indicator_data['bullish']['n'] ); // these arrays store name of the bullisn and bearish combinations for the best tuning tables.
				$indicator_data['bearish']['shortname'] = sprintf( '%02u-%02u', $indicator_data['bearish']['m'], $indicator_data['bearish']['n'] );				
				
				/*
				* bullish s-table exists in 'study_path'? If not, create.
				*/
				if ( !file_exists( $this->s_table_header['bullish']['url'] ) ) {
					$this->create_s_table( $csv, $indicator_data, $study_data, 'bullish' );
				}	else {
					/*
					* s-table older than holding period for the study data? Create new s-table again to include newly received data. When the s-table is refreshed periodically, new values from the holding period get incorporated. The comparison below does not account for non-trading days. It just looks at number of calendar days passed since last modification times of the s-table files.
					*/
					$file_stats = stat( $this->s_table_header['bullish']['url'] );
					if ( ( $timestamp - $file_stats['mtime'] ) / 86400 >= $study_data['holding_period'] ) {
						$this->create_s_table( $csv, $indicator_data, $study_data, 'bullish' );
					} else {
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
					$this->create_s_table( $csv, $indicator_data, $study_data, 'bearish' );
				}	else {
					$file_stats = stat( $this->s_table_header['bearish']['url'] );
					if ( ( $timestamp - $file_stats['mtime'] ) / 86400 >= $study_data['holding_period'] ) {
						$this->create_s_table( $csv, $indicator_data, $study_data, 'bearish' );
					} else {
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
				* Figure out weigted average favorable odds for all readings of the bullish indicator and for all readings of the bearish indicator according to the following formula:
				* Weighted average is taken as sum( odds * p_ext_avg ) / sum( p_ext_avg )
				* This calc accounts not only for the odds of needed direction of the p_ext, but also for its magnitude.
				*/
				$indicator_tuning['bullish'][$indicator_data['bullish']['shortname']]['weighted_product'] = 0; /** < These vars store intermediate sums for weighted avg calcs */
				$indicator_tuning['bullish'][$indicator_data['bullish']['shortname']]['ext_max_sum'] = 0; 
				$indicator_tuning['bearish'][$indicator_data['bearish']['shortname']]['weighted_product'] = 0;
				$indicator_tuning['bearish'][$indicator_data['bearish']['shortname']]['ext_min_sum'] = 0;
				foreach ( $this->s_table['bullish'] as $row => $line ) {
					$indicator_tuning['bullish'][$indicator_data['bullish']['shortname']]['weighted_product'] += $line['ext_max_odds'] * $line['ext_max_avg'];
					//echo "row=$row, ext_max_odds={$this->s_table[(string) $row]['ext_max_odds']}, ext_max_avg={$this->s_table[(string) $row]['ext_max_avg']} product=". $this->s_table[(string) $row]['ext_max_odds'] * $this->s_table[(string) $row]['ext_max_avg'];
					$indicator_tuning['bullish'][$indicator_data['bullish']['shortname']]['ext_max_sum'] += $line['ext_max_avg'];
				}
				foreach ( $this->s_table['bearish'] as $row => $line ) {
					$indicator_tuning['bearish'][$indicator_data['bearish']['shortname']]['weighted_product'] += $line['ext_min_odds'] * $line['ext_min_avg'];
					$indicator_tuning['bearish'][$indicator_data['bearish']['shortname']]['ext_min_sum'] += $line['ext_min_avg'];
				}
				//var_dump( $indicator_tuning['bullish'][$indicator_data['bullish']['shortname']], $indicator_tuning['bullish'][$indicator_data['bullish']['shortname']]['ext_max_sum'] );
				//var_dump( array_sum( $indicator_tuning['bearish'][$indicator_data['bearish']['shortname']] ), $indicator_tuning['bearish'][$indicator_data['bearish']['shortname']]['ext_min_sum'] );
				//exit();
				$indicator_tuning['bullish'][$indicator_data['bullish']['shortname']] = $indicator_tuning['bullish'][$indicator_data['bullish']['shortname']]['weighted_product'] / $indicator_tuning['bullish'][$indicator_data['bullish']['shortname']]['ext_max_sum'];
				$indicator_tuning['bearish'][$indicator_data['bearish']['shortname']] = $indicator_tuning['bearish'][$indicator_data['bearish']['shortname']]['weighted_product'] / $indicator_tuning['bearish'][$indicator_data['bearish']['shortname']]['ext_min_sum'];

			} // end for ( $bbd['n'] )
		} // end for ( $bbd['m'] )
		
		arsort( $indicator_tuning['bullish'], SORT_NUMERIC );
		arsort( $indicator_tuning['bearish'], SORT_NUMERIC );
		//unset( $indicator_data );
		//var_dump( $indicator_tuning );
		//var_dump( $this->s_table );
		//exit();
		
		/*
		* Figure out the needed study size to calculate alert symbols on and trim the uploaded OHLCV data to a the study size
		*/
		$study_data['study_bars'] = Calc::myCeil( $study_data['chart_bars'] + max( array( $indicator_data['bullish']['n'], $indicator_data['bearish']['n'], ) ) + max( array( $indicator_data['bullish']['n'], $indicator_data['bearish']['n'] ) ) + 3, 10 );
		$study['date_value'] = array_slice( $csv->date_value, -$study_data['study_bars'] ); // array_slice renumbers keys from 0 to count() - 1 in the new sliced out array
		$study['open'] = array_slice( $csv->open, -$study_data['study_bars'] );
		$study['high'] = array_slice( $csv->high, -$study_data['study_bars'] );
		$study['low'] = array_slice( $csv->low, -$study_data['study_bars'] );
		$study['close'] = array_slice( $csv->close, -$study_data['study_bars'] );
		//$study['volume'] = array_slice( $csv->volume, -$study_data['study_bars'] );

		/*
		* Calculate needed indicators for the study size
		*/
		$lin_reg['bullish'] = trader_linearreg( $study['close'], $indicator_data['bullish']['n'] );
		$lin_reg['bearish'] = trader_linearreg( $study['close'], $indicator_data['bearish']['n'] );

		$slope['bullish'] = Calc::lookup_slope( array( 'values' => $lin_reg['bullish'], 'bars' => $indicator_data['bullish']['n'], 'multiplier' => 100, ) );
		$slope['bearish'] = Calc::lookup_slope( array( 'values' => $lin_reg['bearish'], 'bars' => $indicator_data['bearish']['n'], 'multiplier' => 100, ) );
		//unset( $lin_reg );
		
		$slope_diff['bullish'] = Calc::lookup_slope( array( 'values' => $slope['bullish'], 'bars' => 1, 'multiplier' => 1, ) );
		$slope_diff['bearish'] = Calc::lookup_slope( array( 'values' => $slope['bearish'], 'bars' => 1, 'multiplier' => 1, ) );
		//unset( $slope );

		$end = count( $study['date_value'] ) - 1;
		$start = $end - $study_data['chart_bars'];
		
		//$result = array(); // debug array
		$symbol_lin_reg = array( 'bullish' => array(), 'bearish' => array(), );
		
		/*
		* Figure all conditions that require display of ext_max and ext_min P ranges. These values are "raw" meaning they are not discretized. They also will be displayed as raw values on the chart and discretized later when plotting c-levels from- to- intervals. Note that the way you filter, i.e. make comparisons for your focus conditions must be the same as the one used to built the s-table.
		*/
		for ( $key = $start; $key < $end; $key++ ) {
			$study['date_value'][$key] = date( 'm/d/y', $study['date_value'][$key] );

			if ( $slope['bullish'][$key] <= 0 && $slope['bullish'][$key + 1] > 0 ) {
				$symbol_lin_reg['bullish'][$key + 1] = $slope_diff['bullish'][$key + 1];
			}
			if ( $slope['bearish'][$key] >= 0 && $slope['bearish'][$key + 1] < 0 ) {
				$symbol_lin_reg['bearish'][$key + 1] = $slope_diff['bearish'][$key + 1];
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
		$canvas[1] = array( 'path' => $path, 'percent_chart_area' => 87, 'symbol' => $symbol_name, 'width' => 1600, 'height' => 900, 'img_background' => 'blue', 'chart_background' => 'white', );
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
			$sy = 85; $text = sprintf( 'full_study flag is set to TRUE. Your best ranking lin_reg tunings for the symbol %s are:', $symbol_name );
			imagettftext( $chart->image, $chart->default['font_size_x'], 0, $sx, $sy, $chart->pen_colors['black'], $chart->canvas['ttf_font'], $text );
			$sy += 15; $text = 'BULLISH:';
			imagettftext( $chart->image, $chart->default['font_size_x'], 0, $sx, $sy, $chart->pen_colors['black'], $chart->canvas['ttf_font'], $text );
			foreach ( $indicator_tuning['bullish'] as $key => $value ) {
				$sy += 15; $text = sprintf( '%s => %.4f', $key, $value );
				imagettftext( $chart->image, $chart->default['font_size_x'], 0, $sx, $sy, $chart->pen_colors['black'], $chart->canvas['ttf_font'], $text );
			}
			$sx = 325;
			$sy = 100; $text = 'BEARISH:';
			imagettftext( $chart->image, $chart->default['font_size_x'], 0, $sx, $sy, $chart->pen_colors['black'], $chart->canvas['ttf_font'], $text );
			foreach ( $indicator_tuning['bearish'] as $key => $value ) {
				$sy += 15; $text = sprintf( '%s => %.4f', $key, $value );
				imagettftext( $chart->image, $chart->default['font_size_x'], 0, $sx, $sy, $chart->pen_colors['black'], $chart->canvas['ttf_font'], $text );	
			}

			unset( $indicator_tuning );
			
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
			$line_series[1] = array( 'series' => $lin_reg['bullish'], 'x_axis' => 0, 'y_axis' => 0, 'color' => 'blue', );
			$line_series[2] = array( 'series' => $lin_reg['bearish'], 'x_axis' => 0, 'y_axis' => 0, 'color' => 'yellow', );
			$symbol_series[1] = array( 'values' => $symbol_lin_reg['bullish'], 'shape' => 'triangle-up', 'position' => $study['low'], 'pos_offset' => -20, 'show_values' => TRUE, 'transparency' => 50, 'precision' => 1, );
			$symbol_series[2] = array( 'values' => $symbol_lin_reg['bearish'], 'shape' => 'triangle-down', 'position' => $study['high'], 'pos_offset' => 20, 'show_values' => TRUE, 'transparency' => 50, 'precision' => 1, );
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
			foreach ( $symbol_lin_reg['bullish'] as $key => $value ) {
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
					$std_dev = $ext_max_avg + $study['close'][$key] * ( $this->s_table['bullish'][(string) $row]['ext_max_std_dev'] ) / 100;
					$temp[$sx] = $std_dev;
					$temp[$ex] = $std_dev;
					$line_series[2] = array( 'series' => $temp, 'color' => 'green', 'style' => 'dash', );
					$chart->place_text( array( 'sx' => $sx, 'sy' => $std_dev, 'text' => sprintf( '%.2f', $std_dev ), 'color' => 'green', 'ttf_font' => 'assets/aaargh.ttf', 'vert_algn' => 'down' ) );
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
					$std_dev = $ext_min_avg - $study['close'][$key] * ( $this->s_table['bullish'][(string) $row]['ext_min_std_dev'] ) / 100;
					$temp[$sx] = $std_dev;
					$temp[$ex] = $std_dev;
					$line_series[4] = array( 'series' => $temp, 'color' => 'green', 'style' => 'dash', );
					$chart->place_text( array( 'sx' => $sx, 'sy' => $std_dev, 'text' => sprintf( '%.2f', $std_dev ), 'color' => 'green', 'ttf_font' => 'assets/aaargh.ttf', 'vert_algn' => 'down' ) );
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
			
			foreach ( $symbol_lin_reg['bearish'] as $key => $value ) {
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
					$std_dev = $ext_min_avg - $study['close'][$key] * ( $this->s_table['bearish'][(string) $row]['ext_min_std_dev'] ) / 100;
					$temp[$sx] = $std_dev;
					$temp[$ex] = $std_dev;
					$line_series[2] = array( 'series' => $temp, 'color' => 'red', 'style' => 'dash', );
					$chart->place_text( array( 'sx' => $sx, 'sy' => $std_dev, 'text' => sprintf( '%.2f', $std_dev ), 'color' => 'red', 'ttf_font' => 'assets/aaargh.ttf', 'vert_algn' => 'down' ) );
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
					$std_dev = $ext_max_avg + $study['close'][$key] * $this->s_table['bearish'][(string) $row]['ext_max_std_dev'] / 100;
					$temp[$sx] = $std_dev;
					$temp[$ex] = $std_dev;
					$line_series[4] = array( 'series' => $temp, 'color' => 'red', 'style' => 'dash', );
					$chart->place_text( array( 'sx' => $sx, 'sy' => $std_dev, 'text' => sprintf( '%.2f', $std_dev ), 'color' => 'red', 'ttf_font' => 'assets/aaargh.ttf', 'vert_algn' => 'down' ) );
					// plot all line series
					foreach( $line_series as $line ) {
						//var_dump( $line );
						$chart->add_line_series( $line );
					}
					unset( $temp );

				}
			}

			/// Place annotation on chart as well.
			$annotation = sprintf( 'Liner Regression Slope (LRS) Study, Symbol: %s, Linear regression line, bars: Bullish=%s Bearish=%s, Holding Per.=%02ud, Conf. Int.=%03u%%', $symbol_name, $indicator_data['bullish']['n'], $indicator_data['bearish']['n'], $study_data['holding_period'], $study_data['conf_level'] );
			$chart->place_text( array( 'sx' => $xmin + 1, 'sy' => $ymax, 'text' => $annotation, 'vert_algn' => 'up', 'font_size' => 12, 'ttf_font' => 'assets/arial.ttf', ) );
			//$chart->save_chart();

		}
		
		/*
		* INDICATOR PANEL: plot bullish and bearish bbd's on the chart below the main one
		*/
		$path = sprintf( '%s%s_%s_chart2.png', $study_data['study_path'], $symbol_name, $study_name );
		$canvas[2] = array( 'path' => $path, 'percent_chart_area' => 87, 'symbol' => $symbol_name, 'width' => 1600, 'height' => 270, 'img_background' => 'blue', 'chart_background' => 'white', );
		$im = imagecreatetruecolor( $canvas[1]['width'], $canvas[1]['height'] + $canvas[2]['height'] ); /// this is background image that holds the p-chart and the indicator chart.
		imagecopymerge( $im, $chart->image, 0, 0, 0, 0, $canvas[1]['width'], $canvas[1]['height'], 100 );
		unset( $chart ); /// to save memory

		///figure ymin and ymax for the slope_diff series
		$ymax = array();
		$ymin = array();
		$temp = array();
		foreach( $slope_diff as $values ) {
			$temp = array_slice( $values, -$study_data['chart_bars'] );
			$ymax[] = ceil( max( $temp ) * 1.01 / 5 ) * 5;
			$ymin[] = floor( min( $temp ) * 0.99 / 5 ) * 5;
		}
		foreach( $slope as $values ) {
			$temp = array_slice( $values, -$study_data['chart_bars'] );
			$ymax[] = ceil( max( $temp ) * 1.01 / 5 ) * 5;
			$ymin[] = floor( min( $temp ) * 0.99 / 5 ) * 5;
		}
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
					'show_major_grid' => FALSE, 'show_minor_grid' => FALSE, 
					'major_grid_style' => 'dash', 'minor_grid_style' => 'dash', 
					'major_grid_color' => 'gray', 'minor_grid_color' => 'gray', 
					'major_grid_scale' => 1, 'minor_grid_scale' => 1, 
					'show_major_values' => TRUE, 'show_minor_values' => TRUE, 
					'print_offset_major' => 7, 'print_offset_minor' => 7,
					'font_size' => 10,  
					'precision' => 1,  
					'font_angle' => 0,  
				); 

		$chart = new Chart( array( $canvas[2], $x_axis, $y_axis, ) );

		$line_series = array();
		$y_zero_line = array( $xmin => 0, $xmax => 0, );
		$line_series[0] = array( 'series' => $y_zero_line, 'x_axis' => 0, 'y_axis' => 0, 'color' => 'blue', );
		$line_series[1] = array( 'series' => $slope['bullish'], 'color' => 'blue', );
		$line_series[2] = array( 'series' => $slope['bearish'], 'color' => 'yellow', );
		$line_series[3] = array( 'series' => $slope_diff['bullish'], 'color' => 'blue', 'style' => 'dash', );
		$line_series[4] = array( 'series' => $slope_diff['bearish'], 'color' => 'yellow', 'style' => 'dash', );
		foreach( $line_series as $line ) {
			$chart->add_line_series( $line );
		}
		
		/// Place annotation on indicator panel.
		$annotation = sprintf( 'Solid => lin_reg_line slope, Dashed => slope diff; Slope: bullish=%.2f bearish=%.2f; Slope_diff bullish=%.2f bearish=%.2f', $slope['bullish'][$end], $slope['bearish'][$end], $slope_diff['bullish'][$end], $slope_diff['bearish'][$end] );
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
		//var_dump( $path ); exit();
		//$chart->save_chart();

		
		/* 
		* Buld html output and save on disk.
		*/
		$this->output = "<div class=\"$study_name $symbol_name\"><div class=\"quote_box\"><div class=\"symbol\">$symbol_name</div><div class=\"quote\">";
		$this->output .= sprintf( '%s O=%.2f H=%.2f L=%.2f C=%.2f V=%s', date( 'j-M-y', $csv->last['date_value'] ), $csv->last['open'], $csv->last['high'], $csv->last['low'], $csv->last['close'], number_format( $csv->last['volume'] ) ); 
		$this->output .= "</div><div class=\"study_data\">Linear Regression Slope (LRS) Study<br/>LRS tuning: Bullish={$indicator_data['bullish']['n']}, Bearish={$indicator_data['bearish']['n']} Current value: Bullish={$slope_diff['bullish'][$end]}, Bearish={$slope_diff['bearish'][$end]}</div></div><div class=\"chart\"><img src=\"";
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

	private function create_s_table( $csv, $indicator_data, $study_data, $side ) {
		$this->s_table[$side] = array();
		global $symbol_name, $study_name;
		/* 
		* Do a full study for bbd bullish and bearish tunings. Bullish and bearish bbd's supposed to differ by x-sma and n-window. Figure ext_max and ext_min for the given 'holding_period'. 
		*/
		$first_key = array();
		$lin_reg = trader_linearreg( $csv->close, $indicator_data[$side]['n'] );
		$arr_keys = array_keys( $lin_reg );
		$first_key['lin_reg'] = $arr_keys[0];
		//var_dump( $ma ); exit();

		$slope = Calc::lookup_slope( array( 'values' => $lin_reg, 'bars' => $indicator_data[$side]['n'], 'multiplier' => 100, ) );
		$arr_keys = array_keys( $slope );
		$first_key['slope'] = $arr_keys[0];
		//var_dump( $bbd ); exit();

		$slope_diff = Calc::lookup_slope( array( 'values' => $slope, 'bars' => 1, 'multiplier' => 1, ) );
		$arr_keys = array_keys( $slope_diff );
		$first_key['slope_diff'] = $arr_keys[0];
		//var_dump( $bbd ); exit();
		
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
		$lin_reg = array_slice( $lin_reg, $start - $first_key['lin_reg'], -$study_data['holding_period'], TRUE );
		$slope = array_slice( $slope, $start - $first_key['slope'], -$study_data['holding_period'], TRUE );
		$slope_diff = array_slice( $slope_diff, $start - $first_key['slope'], -$study_data['holding_period'], TRUE );
		$ext_max = array_slice( $ext_max, $start - $first_key['ext_max'], NULL, TRUE );
		$ext_min = array_slice( $ext_min, $start - $first_key['ext_min'], NULL, TRUE );
		unset( $first_key );
		//var_dump( $slope, $slope_diff ); 
		//exit();
		
		/*
		* Filter needed bullish and bearish conditions. Record raw p_ext min and max readings for each inclination of the indicator (i.e. for bullish condition, record p_ext_max and p_ext_min) into s-table for scalar stats "on the fly". Mark all p_extremes that go "your way" (i.e. bullish indicator - bullish p_ext) for odds calculations (i.e. how many times p goes "your way". The key when using filters such as this one is to be certain and consistent on what would constitude a bullish condition, i.e. reading of strictly > 0 or reading >= 0. Chose one and use only it. Otherwise you will have overlaps of the independent variable in your stat table.
		* Discretize filtered independent and resulting min and max P readings for pivot tables. 
		*/
		$rows_population = array();
		$columns_population = array( 'ext_max' => array(), 'ext_min' => array(), );
		for ( $key = $start; $key < $end; $key++ ) {
			
			switch ( $side ) {
			
				case 'bullish':
					$counter = FALSE; /**< keeps track of favorable hits in p_ext_max and p_ext_min. For example, p_ext_max supposed to be positive for bullish condition and vice versa */
					if ( $slope[$key] <= 0 && $slope[$key + 1] > 0 ) {
						$rows_population[$key + 1] = ceil( $slope_diff[$key + 1] / $study_data['multiple'] ) * $study_data['multiple'];
						if ( $rows_population[$key + 1] == 0 ) $rows_population[$key + 1] = 0; // fixes -0 issue that converts to string and than is different than a 0.
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
					if ( $slope[$key] > 0 && $slope[$key + 1] <= 0 ) {
						$rows_population[$key + 1] = floor( $slope_diff[$key + 1] / $study_data['multiple'] ) * $study_data['multiple'];
						if ( $rows_population[$key + 1] == 0 ) $rows_population[$key + 1] = 0; // fixes -0 issue that converts to string and than is different than a 0.
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
		//var_dump( $indicator_tuning );
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