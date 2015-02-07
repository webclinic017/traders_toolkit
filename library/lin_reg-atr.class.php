<?php

/**
* 
* @class LIN_REG_ATR
* 
* @brief Calculates location of Price vs. lin-reg line measured in ATR's and draws symbol series, confidence levels and scalar statistics such as average and std. dev. on chart. 
		Creates library tables necessary for calculations and stores on disk for future reference.
* 
* @result saved chart on disk with symbols designating p measured in ATR's vs. reg line; ATR bands; Lin reg line.
		c-tables for max and min peaks saved on disk (c-table stands for confidence intervals tables)
		s-tables for max and min peaks saved on disk (s-table stands for table containing scalar statistics)
		$this->output gets filled with html code that presents results of the study. 
* 
* @version 130306
* @author Alex Kay (MGWebGroup)
* 
* Contents:
* __construct() - all calcs for the study, creates chart, all necessary reference tables saved on disk and study output html
* __toString() - returns html output for the study when the class's instance gets printed.
* 
*/

class LIN_REG_ATR {

	public $lin_reg = array();
	public $atr = array();
	public $atr_band = array( 'above' => array(), 'below' => array() ); /**< linear regression above and below 'atr_threshold' */
	public $symbol_atr = array( 'above' => array(), 'below' => array() ); /**< filtered p_atr position if above or below 'atr_threshold'. */
	public $ext_max = array(); /**< Max increase (extreme) in P over the holding period */
	public $ext_min = array(); /**< Max decrease (extreme) in P over the holding period */
	public $c_table = array(); /**< limits library for the given confidence (c-level) */
	public $s_table = array(); /**< table that holds scalar stats (i.e. median, avg, std. dev.) for each p-atr level. */
	private $study = array( 'date_value' => array(), 'open' => array(), 'high' => array(), 'low' => array(), 'close' => array(), );	/**< Holds OHLCV values for this study only */
	private $output = NULL; /**< html code for the study output */
	
	function __construct( $symbol_name, $study_name, $study_data, $csv ) {

		/*
		* Initial conditions
		*/
		//global $calc;
		$timestamp = time();
		$c_level = $study_data['conf_level'];
		$name['c-table'] = sprintf( '%s_%s_c%u_d%u_m1%s_m2%s', $symbol_name, $study_name, $c_level, $study_data['holding_period'], $study_data['multiple'], $study_data['multiple2'] );
		$name['s-table'] = sprintf( '%s_%s_s-table', $symbol_name, $study_name );
		$file_location['c-table'] = $study_data['study_path'] . $name['c-table'] . '.csv';
		$file_location['s-table'] = $study_data['study_path'] . $name['s-table'] . '.csv';

		/*
		* c-tables exist in 'study_path'? If not, create c-tables and overwrite/create s-tables.
		*/
		if ( file_exists( $file_location['c-table'] ) && file_exists( $file_location['s-table'] ) ) {
			/// Check if c-table is older than the holding period. Reason for this is that holding period is not analyzed, and you may get unseen independent values inside the holding period. WHen the c-table is refreshed periodically, new values from the holding period get incorporated. The comparison below does not account for non-trading days. It just looks at number of calendar days passed since last modification times of the c-table and s-table files.
			$file_stats = stat( $file_location['c-table'] );
			//var_dump( date( 'c', $file_stats['mtime'] ) ); exit();
			if ( ( $timestamp - $file_stats['mtime'] ) / 86400 <= $study_data['holding_period'] ) {
				if ( ( $fh = fopen( $file_location['c-table'], 'r' ) ) !== FALSE) {
					$data = array();
					$row = 0;
					while ( ( $data = fgetcsv( $fh, 0, ',' ) ) !== FALSE) {
						$this->c_table[(string) $data[0]] = array( 'ext_max_from' => $data[1], 'ext_max_to' => $data[2], 'ext_min_from' => $data[3], 'ext_min_to' => $data[4] );
						if ( !$row ) $first_key = $data[0];
						$row++;
						//var_dump( $data );
					}
					fclose($fh);
				}
				unset( $this->c_table[$first_key] );
				//var_dump( $this->c_table );
				//exit();
			}
			$file_stats = stat( $file_location['s-table'] );
			if ( ( $timestamp - $file_stats['mtime'] ) / 86400 <= $study_data['holding_period'] ) {
				if ( ( $fh = fopen( $file_location['s-table'], 'r' ) ) !== FALSE) {
					$data = array();
					$row = 0;
					while ( ( $data = fgetcsv( $fh, 0, ',' ) ) !== FALSE) {
						$this->s_table[(string) $data[0]] = array( 'ext_max_std_dev' => $data[1], 'ext_max_avg' => $data[2], 'ext_min_std_dev' => $data[3], 'ext_min_avg' => $data[4] );
						if ( !$row ) $first_key = $data[0];
						$row++;
					}
					fclose($fh);
				}
				unset( $this->s_table[$first_key] );
				//var_dump( $this->s_table ); exit();
			}
		} else {
		
			/* 
			* Do a full study for lin-reg, atr, atr-bands to calc out entire stats. Figure ext_max and ext_min for the given 'holding_period'. 
			*/
			$this->lin_reg = trader_linearreg( $csv->close, $study_data['linreg'] ); 
			$arr_keys = array_keys( $this->lin_reg );
			$first_key['lin_reg'] = $arr_keys[0];

			$this->atr = trader_atr( $csv->high, $csv->low, $csv->close, $study_data['atr'] );
			$arr_keys = array_keys( $this->atr );
			$first_key['atr'] = $arr_keys[0];
			
			$this->ext_max = Calc::lookup_Extreme( array( 'values' => $csv->close, 'extreme' => 'max', 'bars' => $study_data['holding_period'], 'prec' => 2, ) );
			$arr_keys = array_keys( $this->ext_max );
			$first_key['ext_max'] = $arr_keys[0];
			
			$this->ext_min = Calc::lookup_Extreme( array( 'values' => $csv->close, 'extreme' => 'min', 'bars' => $study_data['holding_period'], 'prec' => 2, ) );
			$arr_keys = array_keys( $this->ext_min );
			$first_key['ext_min'] = $arr_keys[0];

			$start = max( $first_key );
			$end = count( $csv->date_value ) - 1 - $study_data['holding_period'];
			
			/*
			* Trim all resultant arrays to be equal length. Remove 'holding_period' from end as the bars there do not fully define subsequent p-action.
			*/
			$this->lin_reg = array_slice( $this->lin_reg, $start - $first_key['lin_reg'], -$study_data['holding_period'], TRUE );
			$this->atr = array_slice( $this->atr, $start - $first_key['atr'], -$study_data['holding_period'], TRUE );
			$this->ext_max = array_slice( $this->ext_max, $start - $first_key['ext_max'], NULL, TRUE );
			$this->ext_min = array_slice( $this->ext_min, $start - $first_key['ext_min'], NULL, TRUE );
			//var_dump( $this->lin_reg, $this->atr, $this->ext_max, $this->ext_min ); exit();
			
			/*
			* Calculate how many ATR's P is from reg. line. Descretize observed ranges for independent range (p_atr) by 'multiple', and for dependent range (ext_max, ext_min) by
			*   'multiple2'.
			*/
			$rows_population = array();
			for ( $key = $start; $key <= $end; $key++ ) {
				$value = ( $csv->close[$key] - $this->lin_reg[$key] ) / $this->atr[$key];
				if ( $value < 0 ) {
					$rows_population[$key] = floor( $value / $study_data['multiple'] ) * $study_data['multiple'];
				} else {
					$rows_population[$key] = ceil( $value / $study_data['multiple'] ) * $study_data['multiple'];
				}
				$this->ext_max[$key] = ceil( $this->ext_max[$key] / $study_data['multiple2'] ) * $study_data['multiple2'];
				$this->ext_min[$key] = floor( $this->ext_min[$key] / $study_data['multiple2'] ) * $study_data['multiple2'];

				/*
				$result[$key]['date'] = $this->study['date_value'][$key]; // debug array gets filled out.
				$result[$key]['open'] = $this->study['open'][$key];
				$result[$key]['high'] = $this->study['high'][$key];
				$result[$key]['low'] = $this->study['low'][$key];
				$result[$key]['close'] = $csv->close[$key];
				$result[$key]['volume'] = $this->study['volume'][$key];
				$result[$key]['lin-reg'] = $this->lin_reg[$key];
				$result[$key]['atr'] = $this->atr[$key];
				$result[$key]['p_atr'] = $rows_population[$key];
				$result[$key]['ext_max'] = $this->ext_max[$key];
				$result[$key]['ext_min'] = $this->ext_min[$key];
				// $result[$key]['rows_population'] = $rows_population[$key];
				*/
			}
			//Calc::dump_CSV( array( 'source' => &$result, 'filename' => sprintf( $study_data['study_path'].'%s_%s_a-table.csv', $symbol_name, $study_name ), ) );
			//exit();
			
			/*
			* Pivot 1, 2: p_atr vs. ext_max, ext_min, count values of ext_max, ext_min. This will build spectrum of distribution of ext_max as a result of variations in $rows_population
			*/
			$columns_population = $this->ext_max;
			Calc::create_Pivot( array( 
				'name' => 'p_atr-ext_max', 
				'rows_population' => $rows_population, 
				'columns_population' => $columns_population, 
				//'dump_csv' => sprintf( $study_data['study_path'].'%s_%s_p-ext_max.csv', $symbol_name, $study_name ),
			) );
			//var_dump( $rows_population ); exit();
			$columns_population = $this->ext_min;
			Calc::create_Pivot( array( 
				'name' => 'p_atr-ext_min', 
				'rows_population' => $rows_population, 
				'columns_population' => $columns_population, 
				//'dump_csv' => sprintf( $study_data['study_path'].'%s_%s_p-ext_min.csv', $symbol_name, $study_name ),
			) );
			//exit();
			
			/*
			*	Convert p-tables into %-distribution, and calc out s-tables (std dev. and average). Calculate %-distribution for each row (p_atr) in the pivot 1, 2 tables in order to 
				work out confidence intervals.
			* Remove total column and total row as they will be in a way of calculating confidence intervals.
			*/
			foreach ( Calc::$pivot_table['p_atr-ext_max'] as $row => $line ) {
				if ( (string) $row <> 'total' ) {
					$row_total = Calc::$pivot_table['p_atr-ext_max'][$row]['total'];
					unset( Calc::$pivot_table['p_atr-ext_max'][$row]['total'] );
					unset( $line['total'] );
					$data = array(); // collect number of times each column value occurred for calculation of std. dev. and average
					foreach ( $line as $column => $value ) {
						Calc::$pivot_table['p_atr-ext_max'][$row][$column] = round( $value / $row_total * 100, 2 );
						while ( $value > 0 ) {
							$data[] = (float) $column;
							$value--;
						}
					}
					if ( !empty( $data ) ) { 
						$this->s_table[$row]['ext_max_std_dev'] = stats_standard_deviation( $data );
						$this->s_table[$row]['ext_max_avg'] = array_sum( $data ) / count( $data );
					} else {
						$this->s_table[$row]['ext_max_std_dev'] = 0;
						$this->s_table[$row]['ext_max_avg'] = 0;
					}
				}
			}
			unset( Calc::$pivot_table['p_atr-ext_max']['total'] );
			//Calc::dump_CSV( array( 'source' => $this->s_table, 'name' => $name['s-table'], 'filename' => $file_location['s-table'], ) );
			//exit();
			foreach ( Calc::$pivot_table['p_atr-ext_min'] as $row => $line ) {
				if ( (string) $row <> 'total' ) {
					$row_total = Calc::$pivot_table['p_atr-ext_min'][$row]['total'];
					unset( Calc::$pivot_table['p_atr-ext_min'][$row]['total'] );
					unset( $line['total'] );
					$data = array();
					foreach ( $line as $column => $value ) {
						Calc::$pivot_table['p_atr-ext_min'][$row][$column] = round( $value / $row_total * 100, 2 );
						while ( $value > 0 ) {
							$data[] = (float) $column;
							$value--;
						}
					}
					if ( !empty( $data ) ) { 
						$this->s_table[$row]['ext_min_std_dev'] = stats_standard_deviation( $data );
						$this->s_table[$row]['ext_min_avg'] = array_sum( $data ) / count( $data );
					} else {
						$this->s_table[$row]['ext_min_std_dev'] = 0;
						$this->s_table[$row]['ext_min_avg'] = 0;
					}
				}
			}
			unset( Calc::$pivot_table['p_atr-ext_min']['total'] );

			Calc::dump_CSV( array( 'source' => $this->s_table, 'name' => $name['s-table'], 'filename' => $file_location['s-table'], ) );
			//exit();
			
			/* 
			* Build c-tables (confidence interval tables): $table[C]
			*/
			$limit = ( 100 - $c_level ) / 2; // %-odds to trim from each side of distribution
			//$this->c_table = array();
			foreach ( Calc::$pivot_table['p_atr-ext_max'] as $row => $line ) {
				for ( $i = 0; $i <= $limit && $data = each( $line ); ) {
					$i += $data['value'];
					$index = $data['key'];
				}
				$this->c_table[$row]['ext_max_from'] = $index;
				$line = array_reverse( $line, TRUE );
				for ( $i = 0; $i <= $limit && $data = each( $line ); ) {
					$i += $data['value'];
					$index = $data['key'];
				}
				$this->c_table[$row]['ext_max_to'] = $index;
			}
			foreach ( Calc::$pivot_table['p_atr-ext_min'] as $row => $line ) {
				for ( $i = 0; $i <= $limit && $data = each( $line ); ) {
					$i += $data['value'];
					$index = $data['key'];
				}
				$this->c_table[$row]['ext_min_from'] = $index;
				$line = array_reverse( $line, TRUE );
				for ( $i = 0; $i <= $limit && $data = each( $line ); ) {
					$i += $data['value'];
					$index = $data['key'];
				}
				$this->c_table[$row]['ext_min_to'] = $index;
			}
			Calc::dump_CSV( array( 'source' => $this->c_table, 'name' => $name['c-table'], 'filename' => $file_location['c-table'], ) );
			//exit();
			
		} // end if()
	
		//var_dump( $this->s_table ); exit();
		/*
		* Trim uploaded OHLCV data to a the study size (500 bars = 2 years)
		*/
		$study_data['study_bars'] = Calc::myCeil( $study_data['chart_bars'] + max( array( $study_data['linreg'], $study_data['atr'], ) ) + 3, 10 );

		$this->study['date_value'] = array_slice( $csv->date_value, -$study_data['study_bars'] ); // array_slice renumbers keys from 0 to count() - 1 in the new sliced out array
		$this->study['open'] = array_slice( $csv->open, -$study_data['study_bars'] );
		$this->study['high'] = array_slice( $csv->high, -$study_data['study_bars'] );
		$this->study['low'] = array_slice( $csv->low, -$study_data['study_bars'] );
		$this->study['close'] = array_slice( $csv->close, -$study_data['study_bars'] );
		//$this->study['volume'] = array_slice( $csv->volume, -$study_data['study_bars'] );

		/*
		* Indicators and symbols to show on chart
		*/
		$this->lin_reg = trader_linearreg( $this->study['close'], $study_data['linreg'] );
		$this->atr = trader_atr( $this->study['high'], $this->study['low'], $this->study['close'], $study_data['atr'] );
		
		$end = count( $this->study['date_value'] ) - 1;
		$start = $end - $study_data['chart_bars'];
		
		$result = array();
		for ( $key = $start; $key <= $end; $key++ ) {
			$this->study['date_value'][$key] = date( 'm/d/y', $this->study['date_value'][$key] );
			$this->atr_band['above'][$key] = $this->lin_reg[$key] + $this->atr[$key] * $study_data['atr_threshold'];
			$this->atr_band['below'][$key] = $this->lin_reg[$key] - $this->atr[$key] * $study_data['atr_threshold'];
			
			$value = ( $this->study['close'][$key] - $this->lin_reg[$key] ) / $this->atr[$key];
			if ( $value >= $study_data['atr_threshold'] ) {
				$symbol_atr['above'][$key] = $value;
			}
			if ( $value <= -$study_data['atr_threshold'] ) {
				$symbol_atr['below'][$key] = $value;
			}
			/*
			$result[$key]['bar_no'] = $key;
			$result[$key]['date'] = $this->study['date_value'][$key];
			$result[$key]['open'] = $this->study['open'][$key];
			$result[$key]['high'] = $this->study['high'][$key];
			$result[$key]['low'] = $this->study['low'][$key];
			$result[$key]['close'] = $this->study['close'][$key];
			$result[$key]['volume'] = $this->study['volume'][$key];
			$result[$key]['lin-reg'] = $this->lin_reg[$key];
			$result[$key]['atr'] = $this->atr[$key];
			$result[$key]['atr_above'] = $this->atr_band['above'][$key];
			$result[$key]['atr_below'] = $this->atr_band['below'][$key];
			$result[$key]['symb_atr_above'] = ( isset( $symbol_atr['above'][$key] ) )? $symbol_atr['above'][$key] : NULL;
			$result[$key]['symb_atr_below'] = ( isset( $symbol_atr['below'][$key] ) )? $symbol_atr['below'][$key] : NULL;

			Calc::dump_CSV( array( 'source' => &$result, 'filename' => sprintf( $study_data['study_path'].'%s_%s_a1-table.csv', $symbol_name, $study_name ), ) );
			*/
		}
		
		/*
		* Add human readable dates to the end of the $this->study['date_value'] for the holding period
		*/
		$key--;
		$value = strtotime( $this->study['date_value'][$key] ) + 86400;
		for ( $i = $study_data['holding_period']; $i > 0; $value += 86400 ) {
			//echo $this->study['date_value'][$key] . "<br/>";
			if ( $csv->is_trading_day( $value ) ) {
				$key++;
				$this->study['date_value'][$key] = date( 'm/d/y', $value );
				$i--;
			}
		}
		/*
		* Figure out chart boundaries as well as major and minor steps so that to achieve nice chart presentation.
		*/
		$xmax= $end + $study_data['holding_period'];
		$xmin = $xmax - $study_data['chart_bars'] - $study_data['holding_period'];
		$temp = array_slice( $this->study['high'], -$study_data['chart_bars'] );
		$ymax = ceil( max( $temp ) * 1.01 / 5 ) * 5;
		$temp = array_slice( $this->study['low'], -$study_data['chart_bars'] );
		$ymin = floor( min( $temp ) * 0.99 / 5 ) * 5;
		unset( $temp );
		$y_spread = $ymax - $ymin;
		$y_major_interval = Calc::myCeil( $y_spread / 10, 5 );

		/* 
		*  Charts
		*/
		$path = sprintf( '%s%s_%s_chart.png', $study_data['study_path'], $symbol_name, $study_name );
		$canvas = array( 'path' => $path, 'percent_chart_area' => 87, 'symbol' => $symbol_name, 'width' => 1600, 'height' => 900, 'img_background' => 'gray', 'chart_background' => 'gray', );
		$x_axis = array( 'show' => TRUE, 
				'min' => $xmin, 'max' => $xmax, 
				'y_intersect' => $xmax, 
				'major_tick_size' => 8, 'minor_tick_size' => 4, 
				'categories' => $this->study['date_value'], 
				'major_interval' => 5, 'minor_interval_count' => 5, 
				'axis_color' => 'black', 
				'show_major_grid' => TRUE, 'show_minor_grid' => TRUE, 
				'major_grid_style' => 'dash', 'minor_grid_style' => 'dash', 
				'major_grid_color' => 'blue', 'minor_grid_color' => 'gray', 
				'major_grid_scale' => 2, 'minor_grid_scale' => 1, 
				'show_major_values' => TRUE, 'show_minor_values' => FALSE, 
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
		$chart = new Chart( array( $canvas, $x_axis, $y_axis ) );

		$candle_series = array( 'open' => $this->study['open'], 
			'high' => $this->study['high'],
			'low' => $this->study['low'], 
			'close' => $this->study['close'], 
			'x_axis' => 0, 
			'y_axis' => 0, 
			'color_up' => 'green', 
			'color_down' => 'red', 
			'transparency' => 10, 
			'show_values' => FALSE, 
		);
		$line_series[1] = array( 'series' => $this->lin_reg, 'x_axis' => 0, 'y_axis' => 0, 'color' => 'blue', );
		$line_series[2] = array( 'series' => $this->atr_band['above'], 'x_axis' => 0, 'y_axis' => 0, 'color' => 'red', );
		$line_series[3] = array( 'series' => $this->atr_band['below'], 'x_axis' => 0, 'y_axis' => 0, 'color' => 'red', );
		$symbol_series[1] = array( 'values' => $symbol_atr['above'], 'shape' => 'circle', 'position' => $this->study['high'], 'pos_offset' => +20, 'show_values' => TRUE, 'transparency' => 50, 'precision' => 1, );
		$symbol_series[2] = array( 'values' => $symbol_atr['below'], 'shape' => 'circle', 'position' => $this->study['low'], 'pos_offset' => -20, 'show_values' => TRUE, 'transparency' => 50, 'precision' => 1, );
		//var_dump( $symbol_atr ); exit();
		$chart->add_candlestick_series( $candle_series );
		$chart->add_line_series( $line_series[1] );
		$chart->add_line_series( $line_series[2] );
		$chart->add_line_series( $line_series[3] );
		$chart->add_symbol_series( $symbol_series[1] );
		$chart->add_symbol_series( $symbol_series[2] );
		
		/* 
		* For all symbols on chart, show min max intervals for the first confidence level in $study_data['conf_level']
		*/
		//$c_level = $study_data['conf_level'][0];
		//Calc::dump_CSV( array( 'source' => &$symbol_atr['above'], 'filename' => sprintf( $study_data['study_path'].'%s_%s_test1-table.csv', $symbol_name, $study_name ), ) );
		foreach ( $symbol_atr['above'] as $key => $value ) {
			$row = ceil( $value / $study_data['multiple'] ) * $study_data['multiple']; // note that type casting to string, like (string) floor( $value / ...) 
				//will not help refernece array keys correctly. I've already tried this, and php converts a string to an integer unless you explicitely cast it inside the square bracket either like: $this->c_table[(string) $row] or $this->c_table["$row"]
			//echo "key=$key, row=$row, value=$value, study_data[multiple]={$study_data['multiple']}<br/>";
			if ( array_key_exists( (string) $row, $this->c_table ) ) { // this 'if-statement' is included because sometimes the values of p_atr occur in the holding period for which the full study cannot be done. Holding period is the 'trailing edge' which supposed to include extreme price readings. The readings end before the start of the holding period.lot confidence interval.
				$sx = $key;
				$ex = $sx + $study_data['holding_period'];
				/// plot ext_min range
				$sy = $this->study['close'][$key] + $this->study['close'][$key] * $this->c_table[(string) $row]['ext_min_from'] / 100;
				$ey = $this->study['close'][$key] + $this->study['close'][$key] * $this->c_table[(string) $row]['ext_min_to'] / 100;
				$chart->draw_rectangle( array( 'sx' => $sx, 'sy' => $sy, 'ex' => $ex, 'ey' => $ey, 'color' => 'red', 'transparency' => 90, ) );
				/// plot average of the resultant peaks for the given level of p_atr
				$line_series[1] = array();
				$ext_min_avg = $this->study['close'][$key] + $this->study['close'][$key] * $this->s_table[(string) $row]['ext_min_avg'] / 100;
				$line_series[1][$sx] = $ext_min_avg;
				$line_series[1][$ex] = $ext_min_avg;
				$chart->add_line_series( array( 'series' => $line_series[1], 'color' => 'red', ) );
				/// plot closest boundary of std dev.
				$line_series[2] = array();
				//$line_series[2][$sx] = $ext_min_avg + $this->study['close'][$key] * ( $this->s_table[(string) $row]['ext_min_avg'] + $this->s_table[(string) $row]['ext_min_std_dev'] ) / 100;
				$line_series[2][$sx] = $ext_min_avg + $this->study['close'][$key] * ( $this->s_table[(string) $row]['ext_min_std_dev'] ) / 100;
				$line_series[2][$ex] = $line_series[2][$sx];
				$chart->add_line_series( array( 'series' => $line_series[2], 'color' => 'red', 'style' => 'dash', ) );
				//echo sprintf( 'symbol_atr[above]: bar_no=%s, row=%s, csv->close_p=%s, ext_min_from=%s%%, sy=%s, ext_min_to=%s%%, ey=%s, avg=%s%%, avg_p=%s, std_d=%s%%, std_d_p=%s<br/>', 
				//	$key, $row, $this->study['close'][$key], $this->c_table[(string) $row]['ext_min_from'], $sy, $this->c_table[(string) $row]['ext_min_to'], $ey, $this->s_table[(string) $row]['ext_min_avg'], $line_series[1][$key], $this->s_table[(string) $row]['ext_min_std_dev'], $line_series[2][$key] );
			}
		}
		//var_dump( $this->c_table );
		foreach ( $symbol_atr['below'] as $key => $value ) {
			$row = floor( $value / $study_data['multiple'] ) * $study_data['multiple']; 
			if ( array_key_exists( (string) $row, $this->c_table ) ) {
				$sx = $key;
				$ex = $sx + $study_data['holding_period'];
				$sy = $this->study['close'][$key] + $this->study['close'][$key] * $this->c_table[(string) $row]['ext_max_from'] / 100;
				$ey = $this->study['close'][$key] + $this->study['close'][$key] * $this->c_table[(string) $row]['ext_max_to'] / 100;
				$chart->draw_rectangle( array( 'sx' => $sx, 'sy' => $sy, 'ex' => $ex, 'ey' => $ey, 'color' => 'green', 'transparency' => 90, ) );
				/// plot average of the resultant peaks for the given level of p_atr
				$line_series[1] = array();
				$ext_max_avg = $this->study['close'][$key] + $this->study['close'][$key] * $this->s_table[(string) $row]['ext_max_avg'] / 100;
				$line_series[1][$sx] = $ext_max_avg;
				$line_series[1][$ex] = $ext_max_avg;
				$chart->add_line_series( array( 'series' => $line_series[1], 'color' => 'green', ) );
				/// plot closest boundary of std dev.
				$line_series[2] = array();
				$line_series[2][$sx] = $ext_max_avg - $this->study['close'][$key] * ( $this->s_table[(string) $row]['ext_max_std_dev'] ) / 100;
				$line_series[2][$ex] = $line_series[2][$sx];
				$chart->add_line_series( array( 'series' => $line_series[2], 'color' => 'green', 'style' => 'dash', ) );
				//echo sprintf( 'symbol_atr[below]: bar_no=%s, row=%s, csv->close_p=%s, ext_max_from=%s%%, sy=%s, ext_max_to=%s%%, ey=%s, avg=%s%%, avg_p=%s, std_d=%s%%, std_d_p=%s<br/>', 
				//	$key, $row, $this->study['close'][$key], $this->c_table[(string) $row]['ext_max_from'], $sy, $this->c_table[(string) $row]['ext_max_to'], $ey, $this->s_table[(string) $row]['ext_max_avg'], $line_series[1][$key], $this->s_table[(string) $row]['ext_max_std_dev'], $line_series[2][$key] );
			}
		}
		
		$chart->save_chart();
		
		$this->output = "<div class=\"$study_name $symbol_name\"><div class=\"quote_box\"><div class=\"symbol\">$symbol_name</div><div class=\"quote\">";
		$this->output .= sprintf( '%s O=%.2f H=%.2f L=%.2f C=%.2f V=%s', date( 'j-M-y', $csv->last['date_value'] ), $csv->last['open'], $csv->last['high'], $csv->last['low'], $csv->last['close'], number_format( $csv->last['volume'] ) ); 
		$this->output .= "</div></div><div class=\"chart\"><img src=\"";
		$this->output .= sprintf( '%s%s_%s_chart.png', $study_data['study_path'], $symbol_name, $study_name );
		$this->output .= "\" alt=\"Chart for $symbol_name\" ></div></div>";
		
		// $fh = fopen( $study_data['study_path'] . $symbol_name . '_' . $study_name . '.html', 'w' );
		// fwrite( $fh, $this->output );
		// fclose( $fh );
		
		//echo $this->output;
		
	} // end __construct
	
	function __toString() {
		return $this->output;
	}

} // end class ATR_P


?>