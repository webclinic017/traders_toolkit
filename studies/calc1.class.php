<?php

/**
* 
* @class Calc
* 
* @brief Collection of static methods to calculate various indicators, statistical functions and so on.
* 
* @version 121023
* @author Alex Kay (MGWebGroup)
* 
* @contents:
* 01. compute_SMA() - Computes single moving average.
* 02. compute_BBR() - Calculate Bulls-to-Bears ratio.
* 03. compute_BBI() - Compute Bulls-to-Bears Index.
* 04. compute_BBD() - Compute Bulls-to-Bears Index.
* 05. lookup_Extreme() - determine the highest|lowest change (in percent) from the current price n bars ahead.
* 06. lookup_End_Delta() - lookup percent change of the value at nth bar.
* 07. lookup_slope() - compute slope of values over n bars.
* 08. invest_Amount() - calculates the value of investment for each bar over n bars and returns max|min found.
* 09. create_Pivot() - creates pivot table, which may include additional columns with further analysis.
* 10. delete_Pivot() - deletes specified pivot table.
* 11. dump_CSV() - dumps specified array as a .csv file. Heading of the .csv file is taken from array keys.
* 12. load_CSV() - dumps specified array as a .csv file. Heading of the .csv file is taken from array keys.
* 13. myCeil() - runds a value to a multiple.
* 14. factorial - calculates factorial of a value.
* 15. std_Dev() - calculates standard deviation of a population.
* 16. median() - Calculates median value of a set of values.
* 
*/

class Calc {

	/* 
	*  Data Members 
	*/
	
	// public $date_value = array();
	// public $open = array();
	// public $high = array();
	// public $low = array();
	// public $close = array();
	// public $volume = array();
	// public $last = array( 'date_value' => NULL, 'formatted_date' => NULL, 'open' => NULL, 'high' => NULL, 'low' => NULL, 'close' => NULL, 'volume' => NULL );
	
	public static $pivot_table = array();

	private $errors = array( 
		'20' => 'CSV file not passed.',
		'22' => 'Source OHLCV file not found.',
		'24' => 'Source OHLCV is smaller than the length of requested SMA calculation.',
		'26' => 'No source array to print.',
		'28' => 'Source array not passed to the compute_SMA function.',
		'30' => 'Array of source values not passed to compute_BBR or compute_BBI or compute_BBD function.',
		'32' => 'Array of SMA not passed to compute_BBR or compute_BBI or compute_BBD function.',
		'34' => 'Not enough values in the SMA array to calculate requested length of the BBR or BBI or BBd.',
		'36' => 'Array of BBR values to calculate not passed to compute_BBI function.',
		'38' => 'Array of source values to look up extremes or slopes upon not passed to lookup_Extreme or lookup_slope function',
		'40' => 'Function lookup_Extreme and lookup_slope will not calculate bar length less than or equal to 0.',
		'42' => 'Specify kind of extreme to calculate for the lookup_Extreme function.',
		'44' => 'Length of values array in the lookup_Extreme and lookup_slope function must be at least one bar longer than number of bars specified.',
		'46' => 'rows_population array not passed to the pivot function.',
		'48' => 'columns_population array not passed to the pivot function.',
		'50' => 'Specified name of the pivot table already exists.',
		'52' => 'No source array specified for the dump_CSV function.',
		'54' => 'Specify either a path or path/filename.csv for the dump_CSV function.',
		'56' => 'Argument for the source array passed to the dump_CSV function is not an array.',
		'58' => 'Array of source values not passed to invest_Amount function.',
		'60' => 'Specify kind of extreme to lookup P/L for the invest_Amount function.',
		'62' => 'Function invest_Amount will not calculate bar length less than or equal to 0.',
		'64' => 'Specify non-negative amount to invest for the invest_Amount function.',
		'66' => 'Length of values array in the invest_Amount function must be at least one bar longer than number of bars specified.',
		'68' => 'No number passed to compute factorial on to factorial() function.',
		'70' => 'Too big of a number passed to factorial() function.',
		'72' => 'Negative number passed to factorial() function.',
		'74' => 'Array of source values not passed to std_Dev() function.',
		'76' => 'Array of source values not passed to median() function.',
		'78' => 'Source values must be passed as an array to median() function.',
		'80' => '\'rows_population\' must be an simple array',
		'82' => '\'columns_population\' must be an simple array',
		'84' => 'Not suported pivot table calc action: ',
		
	);
	
	public static $result = array();
	
	/* 
	*  Methods 
	*/

	public static function compute_SMA( $args ) { // $args = array( 'values' => &$example->close, ['SMA_size' => 40,] )
		// Returns array with computed single moving averages
		
		$sma_size = ( !isset( $args['SMA_size'] ) || $args['SMA_size'] <= 0 )? 10 : (int) $args['SMA_size'];
		//$sma_column = ( !isset( $args['SMA_column'] ) )? '10-sma' : strip_tags( htmlentities( $args['SMA_name'], ENT_QUOTES ) );
		//$sma_value = (!isset( $args['SMA_value'] ) )? 'close' : strtolower( strip_tags( htmlentities( $args['SMA_value'], ENT_QUOTES ) ) );

		try {
			if ( !isset( $args['values'] ) || empty( $args['values'] ) ) throw new CustomException( $this->errors['28'] );
			$arrlength = count( $args['values'] );
			if ( $sma_size > $arrlength ) throw new CustomException( $this->errors['24'] );
		} catch ( CustomException $e ) {
			if (DEBUG) echo $e;
			return;
		}

		//$sma = array( $arrlength - $sma_size );
		$arr_keys = array_keys( $args['values'] );
		$row = $arr_keys[0];
		//var_dump( $sma_size ); exit();
		$arr = array_slice( $args['values'], 0, $sma_size );
		$sma[$row + $sma_size - 1] = array_sum( $arr ) / $sma_size;
		//var_dump( $sma ); exit();
		while ( ($row + $sma_size) < $arrlength ) {
			$sma[] = $sma[$row + $sma_size - 1] - $args['values'][$row] / $sma_size + $args['values'][$row + $sma_size] / $sma_size;
			//echo 'sma='.$sma[$row + 1].' value to drop='.$args['values'][$row] / $sma_size.' value to add='.$args['values'][$row + $sma_size] / $sma_size.'<br />';
			$row++;
		}
		//var_dump( $sma ); exit();
		return $sma;
	
	}


	public static function compute_BBR( $args ) { // $args = array( 'values' => &$example->close, 'sma' => &$sma, [ 'bbr_size' => 5, ] [ 'prec' => 3, ] )
		// Returns array with computed Bulls-to-Bears ratio. Optional parameter 'prec' specifies number of digits after decimal point. Default is 3.
		try {
			if ( !isset( $args['values'] ) || empty( $args['values'] ) ) throw new CustomException( $this->errors['30'] );
			if ( !isset( $args['sma'] ) || empty( $args['sma'] ) ) throw new CustomException( $this->errors['32'] );
			if ( !isset( $args['bbr_size'] ) || empty( $args['bbr_size'] ) ) $bbr_size = 8; else $bbr_size = $args['bbr_size'];
			if ( !isset( $args['prec'] ) || $args['prec'] < 0 || $args['prec'] > 6 || empty( $args['prec'] ) ) $args['prec'] = 3;
			$arrlength = count( $args['sma'] );
			if ( $bbr_size > $arrlength ) throw new CustomException( $this->errors['34'] );
			
		} catch ( CustomException $e ) {
			if (DEBUG) echo $e;
			return;
		}
		
		$arr_keys = array_keys( $args['sma'] );
		$row = $arr_keys[0];
		$last_key = $row + $arrlength - 1;
		$sma_offset = 0;
		while ( ( $row + $bbr_size - 1 ) <= $last_key ) {
			$pos_arr = array();
			$neg_arr = array();
			$values_arr = array_slice( $args['values'], $row, $bbr_size );
			$sma_arr = array_slice( $args['sma'], $sma_offset, $bbr_size );
			foreach ( $sma_arr as $key => $sma_value ) {
				$diff = $values_arr[$key] - $sma_arr[$key];
				if ($diff >= 0) $pos_arr[] = $diff; else $neg_arr[] = $diff;
			}
			//var_dump( $pos_arr, $neg_arr ); exit();
			if ( empty( $pos_arr ) ) { 
				$bbr_arr[$row + $bbr_size - 1] = 0;
			} elseif ( empty( $neg_arr ) ) {
			  $bbr_arr[$row + $bbr_size - 1] = '#DIV/0';
			} else { 
				$bbr_arr[$row + $bbr_size - 1] = round( array_sum( $pos_arr ) / abs( array_sum( $neg_arr ) ), $args['prec'] );
			}
			$row++;
			$sma_offset++;
		}
		return $bbr_arr;

	}

	
	public static function compute_BBI( $args ) { // $args = array( 'values' => &bbr, [ 'mult' => 2, ] ). 
	// returns Bulls-to-Bears Index. Optional parameter 'mult' rounds up BBR to a base multiple. Values will be unrounded to 5, if 'mult' => 0
		
		try {
			if ( !isset( $args['values'] ) || empty( $args['values'] ) ) throw new CustomException( $this->errors['36'] );
			if ( !isset( $args['mult'] ) || $args['mult'] <= 0 ) $args['mult'] = 5;
		} catch ( CustomException $e ) {
			if (DEBUG) echo $e;
			return;
		}
		
		foreach ( $args['values'] as $key => $bbr_value ) {
			if ($bbr_value != '#DIV/0' ) {
				$bbi_value = 100 - 100 / ( 1 + $bbr_value );
				$bbi_value = self::myCeil( $bbi_value, $args['mult'] );
				$bbi_arr[$key] = $bbi_value;
			} else {
				$bbi_arr[$key] = $bbr_value;
			}
		}
		
		return $bbi_arr;
		
	}

	
	/**
	* 
	* @public compute_BBD( @args = array( 'values' => &$example->close, 'sma' => &$sma, [ 'bbd_size' => 5, ] [ 'prec' => 3, ] ) )
	* 
	* Computes "Bulls-to-Bears Difference" by taking (closing P - MA value) for bar 1 + (closing P - MA value) for bar 2 + ... + (closing P - MA value) for bar n.
	*
	* @param $args['values'] Array of Price values keyed by bar number. 
	* @param $args['sma'] Array of x-bars moving average.	
	* @param $args['bbd_size'] (optional) Size of "window" to compute BBd for (default is 8 bars). 
	* @param $args['prec'] (optional) Number of decimal digits to round computed BBd values to (defalut 3 digits after decimal point).
	* 
	* @result $bbd_arr Array of computed values for the "Bulls-to-Bears Delta" rounded to the specified precision.
	* 
	*/
	public static function compute_BBD( $args ) { 
		try {
			if ( !isset( $args['values'] ) || empty( $args['values'] ) ) throw new CustomException( $this->errors['30'] );
			if ( !isset( $args['sma'] ) || empty( $args['sma'] ) ) throw new CustomException( $this->errors['32'] );
			if ( !isset( $args['bbd_size'] ) || empty( $args['bbd_size'] ) || $args['bbd_size'] == 0 ) $bbd_size = 8; else $bbd_size = $args['bbd_size'];
			if ( !isset( $args['prec'] ) || $args['prec'] < 0 || $args['prec'] > 6 || empty( $args['prec'] ) ) $args['prec'] = 3;
			$arrlength = count( $args['sma'] );
			if ( $bbd_size > $arrlength ) throw new CustomException( $this->errors['34'] );
			
		} catch ( CustomException $e ) {
			if (DEBUG) echo $e;
			return;
		}
		
		$bbd_arr = array();
		$arr_keys = array_keys( $args['sma'] );
		$row = $arr_keys[0];
		$last_key = $row + $arrlength - 1;
		$bbd_arr[$row + $bbd_size - 1] = 0;
		for ( $i = $bbd_size, $sma_offset = 0; $i > 0; $i--, $sma_offset++ ) {
			$bbd_arr[$row + $bbd_size - 1] += $args['values'][$row + $sma_offset] - $args['sma'][$row + $sma_offset];  
		}
		while ( ( $row + $bbd_size - 1 ) < $last_key ) {
			$bbd_arr[] = $bbd_arr[$row + $bbd_size - 1] + $args['values'][$row + $bbd_size] - $args['sma'][$row + $bbd_size] - ( $args['values'][$row] - $args['sma'][$row] ); 
			$row++;
		}
		return $bbd_arr;

	}
	
	
	public static function lookup_Extreme( $args ) { // $args = array ( 'values' => &$example->close, 'extreme' => 'max | min', 'bars' => 3, [ 'prec' => 2, ] )
		// Looks up percentage difference of the highest or lowest value in an array over so many 'bars' forward from the current bar.
		
		try {
			if ( !isset( $args['values'] ) || empty( $args['values'] ) ) throw new CustomException( $this->errors['38'] );
			if ( !isset( $args['extreme'] ) || empty( $args['extreme'] ) ) throw new CustomException( $this->errors['42'] );
			if ( !isset( $args['bars'] ) || empty( $args['bars'] ) || $args['bars'] <= 0 ) throw new CustomException( $this->errors['40'] );
			if ( !isset( $args['prec'] ) || $args['prec'] < 0 || $args['prec'] > 6 ) $args['prec'] = 2;
			$arrlength = count( $args['values'] );
			if ( ( $arrlength - $args['bars'] ) < 1 ) throw new CustomException( $this->errors['44'] );
			
		} catch ( CustomException $e ) {
			if (DEBUG) echo $e;
			return;
		}
		
		$extreme = array();
		$arr_keys = array_keys( $args['values'] );
		$row = $arr_keys[0];

		while ( ( $row + $args['bars'] ) < $arrlength ) {
			
			$diff_arr = array();
			for ( $i = $args['bars']; $i >= 1; $i--) {
				$diff_arr[$i] = ( $args['values'][$row + $i] - $args['values'][$row] ) / $args['values'][$row] * 100;
				$temp = $row + $i;
				//echo "value at bar $temp: ( {$args['values'][$row + $i]} - value at bar $row: {$args['values'][$row]} ) / {$args['values'][$row]} * 100 = {$diff_arr[$i]} <br />";
			}
			switch ( $args['extreme'] ) {
				case 'max':
					$extreme[$row] = round( max($diff_arr), $args['prec'] );
					break;
				case 'min':
					$extreme[$row] = round( min($diff_arr), $args['prec'] );
					break;
			}
			//echo "extreme = {$extreme[$row]}, bar= $row<br/>";
			$row++;
		}
		
		return $extreme;
		
	}

	
	public static function lookup_End_Delta( $args ) { // $args = array ( 'values' => &$example->close, 'bars' => 3, ['prec' => 2, ] )
		//Returns array of percent differences of the value at so many 'bars' from the current value.
		
		try {
			if ( !isset( $args['values'] ) || empty( $args['values'] ) ) throw new CustomException( $this->errors['38'] );
			if ( !isset( $args['bars'] ) || empty( $args['bars'] ) || $args['bars'] <= 0 ) throw new CustomException( $this->errors['40'] );
			if ( !isset( $args['prec'] ) || $args['prec'] < 0 || $args['prec'] > 6 ) $args['prec'] = 2;
			$arrlength = count( $args['values'] );
			if ( ( $arrlength - $args['bars'] ) < 1 ) throw new CustomException( $this->errors['44'] );
			
		} catch ( CustomException $e ) {
			if (DEBUG) echo $e;
			return;
		}

		$arr_keys = array_keys( $args['values'] );
		$row = $arr_keys[0];
		$last_key = $row - 1 + count( $arr_keys );
		//var_dump( $last_key, $args['values'] ); exit();

		while ( ( $row + $args['bars'] ) <= $last_key ) {
			
			$extreme[$row] = round( ( $args['values'][$row + $args['bars']] - $args['values'][$row] ) / $args['values'][$row] * 100, $args['prec'] );

			$row++;
		}
		
			return $extreme;
	
	}
	
	/**
	* 
	* @public lookup_slope( @args =  array( 'values' => &$example->close, 'bars' => 3, ['multiplier' => 1, ] ['prec' => 2, ] ) )
	* 
	* Computes slope between values seprated by n bars and rounds up result to the specified 'prec'
	*
	* @param 'multiplier' number to multiply the slope values by before they get rounded to the 'prec' digits. Use this parameter to get percent slope by setting it to 100. Default is 1.
	* 
	* @result array of values with computed slopes.
	* 
	*/
	public static function lookup_slope( $args ) { 
		
		try {
			if ( !isset( $args['values'] ) || empty( $args['values'] ) ) throw new CustomException( $this->errors['38'] );
			if ( !isset( $args['bars'] ) || empty( $args['bars'] ) || $args['bars'] <= 0 ) throw new CustomException( $this->errors['40'] );
			if ( !isset( $args['multiplier'] ) || empty( $args['multiplier'] ) || $args['multiplier'] <= 0 ) $args['multiplier'] = 1;
			if ( !isset( $args['prec'] ) || $args['prec'] < 0 || $args['prec'] > 6 ) $args['prec'] = 2;
			$arrlength = count( $args['values'] );
			if ( ( $arrlength - $args['bars'] ) < 1 ) throw new CustomException( $this->errors['44'] );
			
		} catch ( CustomException $e ) {
			if (DEBUG) echo $e;
			return;
		}

		$arr_keys = array_keys( $args['values'] );
		$row = $arr_keys[0];
		$last_key = $row - 1 + count( $arr_keys );
		//var_dump( $last_key, $args['values'] ); exit();

		while ( ( $row + $args['bars'] ) <= $last_key ) {
			
			$extreme[$row + $args['bars']] = round( ( $args['values'][$row + $args['bars']] - $args['values'][$row] ) / $args['bars'] * $args['multiplier'], $args['prec'] );

			$row++;
		}
		
			return $extreme;
	
	}
	
	
	public static function invest_Amount( $args ) { // $args = array ( 'values' => &$example->close, 'extreme' => 'max | min', 'bars' => 3, 'amount' => 10000, ['commissions' => 0.00, ] )
		// Returns array of max or min returns over so many 'bars' for an 'amount' invested into 'values'
		
		try {
			if ( !isset( $args['values'] ) || empty( $args['values'] ) ) throw new CustomException( $this->errors['58'] );
			if ( !isset( $args['extreme'] ) || empty( $args['extreme'] ) ) throw new CustomException( $this->errors['60'] );
			if ( !isset( $args['bars'] ) || empty( $args['bars'] ) || $args['bars'] <= 0 ) throw new CustomException( $this->errors['62'] );
			if ( !isset( $args['amount'] ) || $args['amount'] < 0 ) throw new CustomException( $this->errors['64'] );
			if ( !isset( $args['commissions'] ) || empty( $args['commissions'] ) || $args['commissions'] < 0 ) $args['commissions'] = 0; 
			$arrlength = count( $args['values'] );
			if ( ( $arrlength - $args['bars'] ) < 1 ) throw new CustomException( $this->errors['66'] );
			
		} catch ( CustomException $e ) {
			if (DEBUG) echo $e;
			return;
		}

		$arr_keys = array_keys( $args['values'] );
		$row = $arr_keys[0];
		
		$extreme = array();

		while ( ( $row + $args['bars'] ) < $arrlength ) {
			
			$diff_arr = array();
			$principal = floor( ( $args['amount'] - $args['commissions'] ) / $args['values'][$row] ) * $args['values'][$row];
			$quantity = floor( ( $args['amount'] - $args['commissions'] ) / $args['values'][$row] );
			for ( $i = $args['bars']; $i >= 1; $i--) {
				$diff_arr[$i] = $quantity * $args['values'][$row + $i] - $principal;
				$temp = $row + $i;
				//echo "bar= $row, close= {$args['values'][$row]}, principal= $principal, quantity= $quantity, P/L over bar $i= {$diff_arr[$i]}<br />";
			}
			switch ( $args['extreme'] ) {
				case 'max':
					$extreme[$row] = round( max($diff_arr), 2 );
					break;
				case 'min':
					$extreme[$row] = round( min($diff_arr), 2 );
					break;
			}
			//echo "extreme = {$extreme[$row]}, bar= $row<br/>";
			$row++;
		}
		
		return $extreme;
		
	}

	/**
	* 
	* @public create_Pivot( @args = array( 'name' => 'bbi_vs_odds', 'rows_population' => &$rows_population, 'columns_population' => &$columns_population,
	*		['rows_sort' => 'asc' | 'desc', ] ['columns_sort' => 'asc' | 'desc', ] 
	*		['calc_action' => 'sum' | 'avg' | 'cnt',] ['sum_field' => <formula or variable>,] 
	*		['rows_total' = 'total' ] ['columns_total' = 'total' ]
	*		['addl_columns' => array( 'col_name1' => <formula>, 'col_name2' => <formula> ) ,]
	*		['dump_csv' => 'filename'] 
	*		) );
	* 
	* Creates a two-dimensional table with row and column summaries condensed from the 'rows_population' and the 'columns_population'. Keys of both population arrays need to match and
			both arrays need to be of equal length. The function takes each row population value, finds all column values that match this row value. For example, the population arrays:
			! row | column ! will yield the following initial pivot table: 	! row | column               !
			!  01 |    a   !																								!  01 | bar numbers for a, b !
			!  01 |    b   !																								!  02	| bar number for c     !
			!  02 |    c   !																								!  03 | bar number for d     !
			!  03 |    d   !
			The initial pivot table is then operated on by taking bar numbers for each cell and either counting them (default action, 'calc_action' is omitted), or summing up values found
			in another table with the same bar numbers (use 'sum_field'), or calculating average of values keyed under same bar numbers in a table of your choice. More functions for 
			processing bar numbers will be added later.
			Total for initial columns and rows is figured during calculation of the initial table and totals for each row and each column are added automatically. This feature was
			first planned to be optional, but I included it for now as automatic. You can customize titles for the 'total' columns by setting the required parameter (see below).
			After the total columns and rows are added, additional columns and rows are added by specifying 'addl_columns' and 'addt'l rows' parameters (see below).
	
	* @param $args['name'] (optional) (string) Index for the pivot table and its name that will be in the first cell of the .csv dump.
			All pivot tables are stored in $this->pivot_table[$index]. Default = 1.
	* @param $args['rows_population'], $args['columns_population'] (mixed) The function determines if values in these arrays are numeric, boolean or string. PHP stores array keys
			as either integer or strings. String values for keys that look like integers, i.e. "-1", would be converted to (int) -1. Float value -1.5 would become (int) -1, boolean TRUE
			would become (int) 1, and "test" would be left as "test". This presents a problem for when you have float values in your row or column populations. Hence is the reason for
			determining value type so that the values from the row_ and column populations would be converted to string values which will become keys in row_ and column_summaries.
		@param $args['rows_sort'], $args['columns_sort'] (optional) The sort order of rows and columns. Default 'asc'.
		@param $args['calc_action'] (optional) Calc action that needs to be performed in each cell on all bars inside the initial pivot table. Default = 'cnt', which is count number
			of bars in each cell. Supported values are enumerated in $table['supported_calc_actions'].
		@param $args['sum_field'] (optional) Must contain a string with either a variable name, i.e. '$this->pivot_table[$table_name][$row][$i]', or expression. Strings that are
			passed inside this parameter are evaluated using php's eval() function. This parameter should be used when you need to pull values from other pivot tables. Remember that 
			each cell is iterated through the array of bar numbers that are stored in it. Each bar number is stored in $bar_number. Default '0'. 
		@param $args['rows_total'], $args['columns_total'] (optinal) Names for rows totals column and column totals row. Totals are cacluated as automatic. Default is 'total'.
		@param $args['addl_columns'] (optional) (array) Specify additional columns to be added to the pivot table after the 'rows_total' column.
			Should be used if you need to cite values from other tables or perform additional calculations either on this pivot table or other tables.
			The keys of the 'addl_columns' array will be column titles. Statements stored as values of the array will be plugged in into the php's eval() function.
		@param $args['addl_rows'] is not supported because it would require to iterate through each column of the table and most likely iterate through all of the prior rows in the table.
			This implementation seems impractical, because your rows_population should already describe the variable you are interested in, and you could easily iterate through the 
			ready pivot and perform additional calculations form the parent function. 
	* @param $args['dump_csv'] (optional) pass either path in the form of 'assets/study/', or path and filename as 'assets/study/my_file.txt'. Specify path relative to the location
			of the function calling this class. If only the path is specified, the function will automatically build the file name as '$args['name'].csv'.
	* 
	* @result (array) a two-dimensional pivot table.
	* 
	*/
	public static function create_Pivot( $args ) {
		$table['supported_calc_actions'] = array( 'sum', 'avg', 'cnt', );
		try {
			if ( !isset( $args['name'] ) || empty( $args['name'] ) ) $args['name'] = 1;
			//if ( array_key_exists( $args['name'], self::$pivot_table ) ) throw new CustomException( $this->errors['50'] );
			$table['name'] = htmlentities( strip_tags( strtolower( trim( $args['name'] ) ), ENT_QUOTES ) );
			$table['csv_headings'] = $table['name'];
			self::$pivot_table[$table['name']] = array();
			if ( !isset( $args['rows_population'] ) || empty( $args['rows_population'] ) ) throw new CustomException( $this->errors['46'] );
			if ( !is_array( $args['rows_population'] ) ) throw new CustomException( $this->errors['80'] );
			$arr_keys = array_keys( $args['rows_population'] );
			if ( is_numeric( $arr_keys[0] ) || is_bool( $arr_keys[0] ) ) {
				$table['sort_type_rows'] = SORT_NUMERIC;
			} else {
				$table['sort_type_rows'] = SORT_STRING;
			}
			if ( !isset( $args['columns_population'] ) || empty( $args['columns_population'] ) ) throw new CustomException( $this->errors['48'] );
			if ( !is_array( $args['columns_population'] ) ) throw new CustomException( $this->errors['82'] );			
			$arr_keys = array_keys( $args['columns_population'] );
			if ( is_numeric( $arr_keys[0] ) || is_bool( $arr_keys[0] ) ) {
				$table['sort_type_columns'] = SORT_NUMERIC;
			} else {
				$table['sort_type_columns'] = SORT_STRING;
			}
			if ( !isset( $args['rows_sort'] ) || empty( $args['rows_sort'] ) ) { 
				$table['rows_sort'] = 'asc'; 
			} elseif ( $args['rows_sort'] <> 'asc' || $args['rows_sort'] <> 'desc' )  {
				$table['rows_sort'] = 'asc';
			}
			if ( !isset( $args['columns_sort'] ) || empty( $args['columns_sort'] ) ) { 
				$table['columns_sort'] = 'asc'; 
			} elseif ( $args['columns_sort'] <> 'asc' || $args['columns_sort'] <> 'desc' )  {
				$table['columns_sort'] = 'asc';
			}
			if ( !isset( $args['calc_action'] ) || empty( $args['calc_action'] ) ) {
				$table['calc_action'] = 'cnt';
			} elseif ( !in_array( $args['calc_action'], $table['supported_calc_actions'] ) ) { 
				throw new CustomException( $this->errors['84'] . $args['calc_action'] );
			} else {
				$table['calc_action'] = $args['calc_action'];
			}
			
			if ( !isset( $args['sum_field'] ) || empty( $args['sum_field'] ) ) {
				$table['sum_field'] = '0';
			}
			if ( !isset( $args['rows_total'] ) || empty( $args['rows_total'] ) ) { 
				$table['rows_total'] = 'total';
			} else {
				$table['rows_total'] = htmlentities( strip_tags( trim( $args['rows_total'] ) ), ENT_QUOTES );
			}
			if ( !isset( $args['columns_total'] ) || empty( $args['columns_total'] ) ) { 
				$table['columns_total'] = 'total';
			} else {
				$table['columns_total'] = htmlentities( strip_tags( trim( $args['columns_total'] ) ), ENT_QUOTES );
			}
			if ( isset( $args['addl_columns'] ) && !empty( $args['addl_columns'] ) && is_array( $args['addl_columns'] ) ) $table['addl_colums'] = $args['addl_columns'];
			//if ( isset( $args['addl_rows'] ) && !empty( $args['addl_rows'] ) && is_array( $args['addl_rows'] ) ) $table['addl_rows'] = $args['addl_columns'];
		} catch (CustomException $e) {
			if (DEBUG) echo $e;
			return;
		}
		//echo 'args[rows_population]'; var_dump( $args['rows_population'] );
		$rows_summary = array_values( array_unique( $args['rows_population'], $table['sort_type_rows'] ) );
		//echo 'rows_summary'; var_dump( $rows_summary );
		if ( $table['rows_sort'] == 'asc' ) {
			asort( $rows_summary, $table['sort_type_rows'] );
		} else {
			arsort( $rows_summary, $table['sort_type_rows'] );
		}
		$columns_summary = array_values( array_unique( $args['columns_population'], $table['sort_type_columns'] ) );
		if ( $table['columns_sort'] == 'asc' ) {
			asort( $columns_summary, $table['sort_type_columns'] );
		} else {
			arsort( $columns_summary, $table['sort_type_columns'] );		
		}
		foreach ( $columns_summary as $key ) {
			if ( is_numeric( $key ) && $key == 0 ) $key = 0; // gets rid of -0 values that might be present
			$new_line[(string) $key] = NULL;
		}
		$arr_keys = array_keys( $new_line );
		$table['csv_headings'] .= ',' . implode( ',', $arr_keys ) . ',' . $table['rows_total'];
		// prepare initial pivot table (contains either list of bars in each cell or NULL)
		foreach ($rows_summary as $key => $group) {
			self::$pivot_table[$table['name']][(string) $group] = $new_line;
			while ( $bar = array_search( $group, $args['rows_population'] ) ) {
				if( is_numeric( $args['columns_population'][$bar] ) &&  $args['columns_population'][$bar] == 0 ) $args['columns_population'][$bar] = 0; // gets rid of -0 values that might be present
				self::$pivot_table[$table['name']][(string) $group][(string) $args['columns_population'][$bar]][] = $bar;
				unset( $args['rows_population'][$bar] );
			}
		}
		//calculate each cell in the table
		$pivot_grand_total = 0;
		$column_totals = $new_line;
		foreach ( self::$pivot_table[$table['name']] as $row => $line ) {
			$row_total = 0;
			foreach ( $line as $column => $bars ) {
				switch ( $table['calc_action'] ) {
					case 'sum':
					$value = 0;
					foreach ( $bars as $i => $bar_number ) {
						$value += eval( $table['sum_field'] ); //self::$pivot_table['bbi_vs_odds'][$row][1];
					}
					break;
					
					case 'avg':
					$n = count( $bars );
					$sum = 0;
					foreach ( $bars as $i => $bar_number ) {
						$sum += eval( $table['sum_field'] );
					}
					$value = $sum / $n;
					break;

					case 'cnt':
					default:
						$value = count( $bars );
					break;
				}
				
				self::$pivot_table[$table['name']][$row][$column] = $value;
				// total summaries
				$row_total += $value;
				$column_totals[$column] += $value;
			}
			self::$pivot_table[$table['name']][$row][$table['rows_total']] = $row_total;
			$pivot_grand_total += $row_total;
			// create additional columns
			if ( isset( $table['addl_columns'] ) ) {
				$table['csv_headings'] .= ',' . implode( ',', array_keys( $table['addl_columns'] ) );
				foreach ( $table['addl_columns'] as $col_name => $formula ) {
					$value = eval( $formula );
					self::$pivot_table[$table['name']][$row][$col_name] = $value;
					$column_totals[$col_name] += $value;
				}
			}
		}
		self::$pivot_table[$table['name']][$table['columns_total']] = $column_totals;
		self::$pivot_table[$table['name']][$table['columns_total']][$table['rows_total']] = $pivot_grand_total;
		
		//start dumping csv file if request is set
		if ( isset( $args['dump_csv'] ) && !empty( $args['dump_csv'] ) ) {
			$table['csv_headings'] .= "\n";
			$table['csv_location'] = pathinfo( $args['dump_csv'] );
			if ( empty( $table['csv_location']['basename'] ) ) $table['csv_location']['basename'] = $table['name'] . '.csv';
			$fh = fopen( $table['csv_location']['dirname'] . '/' . $table['csv_location']['basename'], 'wb' );
			fwrite( $fh, $table['csv_headings'] );
			foreach ( self::$pivot_table[$table['name']] as $row => $line ) {
				fwrite( $fh, $row.',' );
				foreach ( $line as $column => $value ) {
					fwrite( $fh, $value.',' );
				}
				fwrite( $fh, "\n" );
			}
			fclose( $fh );
		}
	}
	
	
	public static function delete_Pivot( $args ) { // $args = array( 'name' => 'bbi_vs_odds' )
		if ( !isset( $args['name'] ) || empty( $args['name'] ) ) { 
			return ( FALSE );
		} else {
			$table['name'] = strtolower( strip_tags( htmlentities( $args['name'], ENT_QUOTES ) ) );
			unset( self::$pivot_table[$table['name']] );
		}
	}
	
	
	/* 
	*  Service Functions
	*/
	
	/**
	* 
	* @public dump_CSV( @args = array( 'source' => &$src, [ 'name' => 'name of csv table', ] [ 'filename' => '[path]/<name of csv file to dump.csv>', ] ) );
	* 
	* Takes a one- or two-dimensional source array and saves it as a .csv file. 
	* 
	* @param $args['source'] (array) source array. THe function determines if array is a one- or two-dimensional, and throws an error if the 'source' is not an array.
	* @param $args['name'] (optional) This parameter specifies the name of the table to be put as the first value of the csv heading. Puts in default '_table_name_' if not specified.
	    Otherwise will make it lowercase, strip all html tags, and convert remaining special characters into html entities. 
	* @param['filename'] (optional) Specify either a path or path/filename.ext to save the .csv file under. Defaults to the parent script's directory that called this function
			and tablename.csv ($args['name']) if none specified. You also can only specify a path and the function will add table_name.csv automatically. Be aware
			that sometimes if you only specify the path like 'assets/' without file name. The function will not parse this correctly, so include path/filename.csv.
	* 
	* @result saved .csv file. Returns full path/filename where the file was saved to.
	* 
	*/
	public static function dump_CSV( $args ) { 
		try {
			if ( !isset( $args['source'] ) || empty( $args['source'] ) ) throw new CustomException( $this->errors['52'] );
			if ( is_array( $args['source'] ) ) { 
				$rows_summary = array_keys( $args['source'] );
				if ( !is_array(  $args['source'][$rows_summary[0]] ) ) {
					$columns_summary = array( 0 => 'values', );
				} else {
					$columns_summary = array_keys( $args['source'][$rows_summary[0]] );
				}
			} else {
				throw new CustomException( $this->errors['56'] );
			}
			if ( !isset( $args['name'] ) || empty( $args['name'] ) ) { 
				$table_name = htmlentities( '_table_name_' );
			} else {
				$table_name = htmlentities( strip_tags( strtolower( $args['name'] ) ), ENT_QUOTES );
			}
			if ( !isset( $args['filename'] ) || empty( $args['filename'] ) ) {
				$file['csv_location'] = pathinfo( $_SERVER['SCRIPT_FILENAME'] );
				$file['csv_location']['basename'] = $table_name . '.csv';
			} else {
				$file['csv_location'] = pathinfo( $args['filename'] );
			}
			if ( empty( $file['csv_location']['basename'] ) ) $file['csv_location']['basename'] = $table_name . '.csv';
		}
		catch ( CustomException $e ) {
			if (DEBUG) echo $e;
			return;
		}
		//echo $file['csv_location']['dirname'] . '/' . $file['csv_location']['basename']; exit();
		$fh = fopen( $file['csv_location']['dirname'] . '/' . $file['csv_location']['basename'], 'wb' );
		$csv_headings = $table_name.','.implode( ',', $columns_summary )."\n";
		fwrite( $fh, $csv_headings );
		foreach ( $args['source'] as $row => $line ) {
			fwrite( $fh, $row );
			if ( is_array( $line ) ) {
				foreach ( $line as $column => $value ) {
					fwrite( $fh, ','.$value );
				}
			} else {
				fwrite( $fh, ','.$line );
			}
			fwrite( $fh, "\n" );
		}
		fclose( $fh );
		
		return $file['csv_location']['dirname'] . '/' . $file['csv_location']['basename'];
		
	}
	
	
	/**
	* 
	* @public load_CSV( @args = array( 'header' => (array) $fields, 'filename' => '<some_name.csv>', ['remove_header'] => TRUE | FALSE, ) )
	* 
	* Loads up specified .csv file into a multidimensional array. If 'remove_header' is specified, first line of the read .csv file gets discarded.
	* Resulting multidensional array gets coded as element[0] => array( 'header_field1', 'header_field2', ... ).
	* 
	* @param $args['header'] array of fields that serve as key to reading the csv file.
	* @param $args['filename'] path with the file name to the .csv file.
	* @param $args['remove_header'] (optional) Specifies if first read line from the csv file gets discarded.
	* 
	* @result multidemensional array of the format element[0] => array( 'header_field1' => <data0.1>, 'header_field2' => <data0.2>, ... ), element[1] => array( 'header_field1' => <data1.1>, 'header_field2' => <data1.2>, ... ).
	*   or FALSE on error.
	* 
	*/
	public static function load_CSV( $args ) {
	
	}
	
	
	public static function myCeil( $x, $c ) { // $x number to round up away from 0, $c = multibple
		$result =  ( ( $y = $x/$c ) == ( $y = (int) $y ) )? $x : ( $x >= 0 ? ++$y : --$y ) * $c;
		return $result;
	}
	
	
	public static function factorial( $args ) { // $args = <number> positive and not more than 170
		//echo !isset( $args ); exit;
		try {
 			if ( !isset( $args ) ) throw new CustomException( $this->errors['68'] );
			if ( $args > 170 ) throw new CustomException( $this->errors['70']." Number passed = $args." );
			if ( $args < 0 ) throw new CustomException( $this->errors['72'] );
		} catch ( CustomException $e ) {
			if (DEBUG) echo $e;
			return;
		}
		
		if ( $args == 0 || $args == 1 ) { 
			$result = 1;
		} else {
			$result = 1;
			for ( $i = 1; $i <= $args; $i++ ) {
				$result = $result * $i;
			}
		}
		
		return $result;
		
	}


	public static function std_Dev( $args ) {
		// $args = array( value1, value2, ...  )
		$result = NULL;
		try {
			if ( !isset( $args ) || empty( $args ) ) throw new CustomException( $this->errors['74'] );
		} catch ( CustomException $e ) {
			if (DEBUG) echo $e;
			return;
		}
		$n = count( $args );
		if ( $n == 1 ) return 0;
		// calculate average
		$avg = array_sum( $args ) / $n;
		// square all values of the source array
		foreach ( $args as $key => $value ) {
			$args[$key] = pow( $value, 2 );
		}
		//find average of the squares
		$avg_of_squares = array_sum( $args ) / $n;
		$result = $avg_of_squares - pow( $avg, 2);
		return $result;
	}
	

	public static function median( $args ) {
		$result = NULL;
		try {
			if ( !isset( $args ) || empty( $args ) ) throw new CustomException( $this->errors['76'] );
			if ( !is_array( $args ) ) throw new CustomException( $this->errors['78'] );
			$n = count($args);
			if ( $n == 1 ) return current( $args );
		} catch ( CustomException $e ) {
			if (DEBUG) echo $e;
			return;
		}
		
		sort($args);
    $h = intval($n / 2);
		if($n % 2 == 0) {
          $result = ($args[$h] + $args[$h-1]) / 2;
        } else {
          $result = $args[$h];
        }
		
		return $result;
	}

	
}
	

?>
