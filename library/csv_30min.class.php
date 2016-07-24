<?php 

/**
* 
* @class CSV
* 
* @brief Reads OHLCV data in .csv format. File name is composed by naming convention specified in the __construct() method.
* 
*  Read .csv file is split into 5 internal arrays (date_value, open, high, low, close, volume) and sorted in ascending order by date_value. This way the latest values are at the bottom of the array.
*   .csv heading is thrown away so that internal arrays contain only numerical data. Dates are stored as UNIX-Epoch (this is needed for some internal calc functions and for sorting).
*   $this->last['date_value', 'open', 'high', 'low', 'close', 'volume'] stores the latest values.
* 
* @version 130811
* @author Alex Kay (MGWebGroup)
* 
* Contents:
* 01. __construct() - Checks if .csv file exists, updates last quote and saves final file.
* 02. is_trading_day() - Checks if supplied timestamp falls on a weekend or a holiday.
* 03. download_csv() - Downloads .csv file from yahoo.com
* 
*/


class READ_CSV {

	/* 
	*  Data Members 
	*/

	public $file; 
	private $quote; /**< Holds quote information */
	public $date_value = array(); /**< OHLCV data arrays */
	public $open = array();
	public $high = array();
	public $low = array();
	public $close = array();
	public $volume = array();
	public $last = array( 'date_value' => NULL, 'formatted_date' => NULL, 'open' => NULL, 'high' => NULL, 'low' => NULL, 'close' => NULL, 'volume' => NULL );
	
	private $errors = array(  
		'100' => 'Symbol name to download csv data on not specified for the CSV class.',
		'102' => 'Specify a valid directory to download the csv data to.',
		'104' => 'Failed opening web-page with current quote from a provider.', 
		'106' => 'Could not find provider settings',
		'108' => 'Failed obtaining weekly historical quotes.',
		'110' => 'Failed obtaining daily historical quotes.',
		'112' => 'Start date for the quotes download not specified.',
	);
	
	public $result;
	
	
	/* 
	*  Methods 
	*/

	/**
	*
	* @public __constuct( @$args = array( 'symbol' => 'SPY', 'start_date' => '1-Jan-2002', 'path' => 'cache/', 'refresh' => 15, ) ).
	*
	* This function checks for existence of a .csv file with daily quotes and if found, checks the first line to be recent. It then builds an appropriate query string to a quotes provider
	*   and downloads daily quotes. After the .csv file is downloaded it also checks the web-page of a provider to find most recent quotes.
	*
	* @param (string) $args['symbol'] Symbol name to be looked up in the yahoo and google public quote system.  
	* @param (string) $args['start_date'] Starting date for the .csv file. Also ends up to be part of the .csv file name. The class wiil present the request with the start date specified in here to the quotes server. Actual start date may be different depending on what the quotes server responds with.
	* @param (string) $args['path'] Path to directory where to save the downloaded .csv file. Can be with or without the trailing forward slash.
	* @param (num) $args['refresh'] Time in minutes since last update of the .csv file before updating of the quote will be performed. I.e. if this time has not elapsed since last
			.csv file update, the quote will not be loaded. This setting does not affect downloading of the entire .csv file it it's missing. If $args['refresh_quote'] is 0 or
			omitted the quote will be updated every time this class is instantiated.
	*
	* @result (file) downloaded .csv file with quotes. File naming convention is: <SYMB>_<d|w>_<YYYYMMDD>.csv,  where <YYYYMMDD> is quote start date specified by @arg['start_date'].
	*   all dates within the .csv file must be in the YYYY-MM-DD format.
			The class also stores the OHLCV data inside itself.
	* 
	*/
	public function __construct ( $args = array( 'symbol' => 'SPY', 'start_date' => '1-Jan-2002', 'path' => '../assets/' ) ) { 
		//ini_set('date.timezone', "America/New_York");
		try {
			if ( !isset ( $args['symbol'] ) || empty( $args['symbol'] ) ) throw new CustomException ( $this->errors['100'] );
			if ( !isset( $args['start_date'] ) || empty( $args['start_date'] ) ) throw new CustomException ( $this->errors['112'] );
			if ( !isset( $args['path'] ) || empty( $args['path'] ) || !is_dir( $args['path'] ) ) throw new CustomException ( $this->errors['102'] );
			//if ( !isset( $args['refresh'] ) ) $args['refresh'] = 0;
		} catch ( CustomException $e ) {
			if ( DEBUG ) echo $e;
			return;
		}

		$timestamp = time();
		//var_dump( date( 'c', $timestamp ) ); exit();
		$this->quote['symbol'] = $args['symbol'];
		//$this->quote['current_date_ts'] = strtotime( date('Y-m-d', $timestamp ) . 'T00:00:00' . date( 'O', $timestamp ) ); /**< 'current_date_ts' converts timestamp to the value of the beginning of the day, i.e. 1-Feb-13T00:12:00MST would be 1-Feb-13T00:00:00MST */
		//var_dump( date( 'c', $this->quote['current_date_ts'] ) ); exit();
		//$this->quote['open'] = -1; /**< quote['open'] also acts as a success flag when OHLCV data is extracted from html page. */
	
		$this->file['name'] = $args['symbol'].'_30min_'.date( 'Ymd', strtotime( $args['start_date'] ) ).'.csv';
		$this->file['path'] = realpath( $args['path'] ) . '/';
		$this->file['csv_heading'] = "Date,Open,High,Low,Close,Volume\n";
		
		$this->file['exist_csv'] = fopen( $this->file['path'] . $this->file['name'], 'r+' );
		$this->file['stat'] = fstat( $this->file['exist_csv'] );
		/**< 'r+' Open for reading and writing; place the file pointer at the beginning of the file. */
		/**< 'w+' Open for reading and writing; place the file pointer at the beginning of the file and truncate the file to zero length. If the file does not exist, attempt to create it. */
		//var_dump( date( 'c', $this->file['stat']['mtime'] ) ); exit();
		
		/// get the latest date from the .csv file
		for ( $i = 0; $i <= 2; $i++ ) {
			$data = fgetcsv( $this->file['exist_csv'], 0, ',' );
			if ( !$i ) { 
				$i++; // skip first line (it's the heading)
			} else {
				$this->quote['file_date_ts'] = strtotime( $data[0] );
			}
		}
		//var_dump( date( 'c', $this->quote['file_date_ts'] ) ); exit();
		$pointer = ftell( $this->file['exist_csv'] ); // pointer must be at the "\n" character of the first quote string in the file that contains price information.
		//var_dump( $pointer ); exit();
		rewind( $this->file['exist_csv'] );
		$csv_file = fread( $this->file['exist_csv'], $this->file['stat']['size'] ); // fread() moves resource pointer to the end of file after the file has been read.
		//rewind( $this->file['exist_csv'] );
		fclose( $this->file['exist_csv'] );
		//var_dump( $csv_file ); exit();
		

		/* 
		* upload OHLCV into internal arrays
		*/
		$data = array();
		$data = str_getcsv( $csv_file, "\n" );
		//var_dump( $data ); exit();
		foreach ( $data as $key => $line ) { 
			$value = str_getcsv( $line, ',' );
			//var_dump( $value ); exit();
			if ( $key ) { // throw away headings at $key = 0
				$date_value[$key - 1] = strtotime( $value[0] );
				$open[$key - 1] = round( $value[1], 2 );
				$high[$key - 1] = round( $value[2], 2 );
				$low[$key - 1] = round( $value[3], 2 );
				$close[$key - 1] = round( $value[4], 2 );
				$volume[$key - 1]= (int) $value[5];
			}
		}

		array_multisort( $date_value, SORT_ASC, $open, $high, $low, $close, $volume );

		$last['date_value'] = end( $date_value );
		$last['open'] = end( $open );
		$last['high'] = end( $high );
		$last['low'] = end( $low );
		$last['close'] = end( $close );
		$last['volume'] = end( $volume );
		
	}
	
	

	
	/*
	* @private download_csv( @args = array( <start_timestamp>, <end_timestamp>, <hist_url> ) )
	*
	* Downloads historical csv data and adds first 5 columns of the heading supplied by yahoo on top. 
	* 
	* @param <hist_url> URL to download historical csv data from.
	* 
	* @result string with downloaded .csv data in my OHLCV format topped with my heading. Note that provider will provide their own specific headings.
	* 
	*/
	private function download_csv( $args ) {
		//* set query strings for a GET request */
		list( $a, $b, $c ) = explode( ',', date( 'n,j,Y', $args[0] ) );
		list($d, $e, $f) = explode( ',', ( date( 'n,j,Y', $args[1] ) ) );
		$vars_hist_daily = array ( 
								'a' => $a - 1,
								'b' => $b,
								'c' => $c,
								'g' => 'd',
								's' => $this->quote['symbol'],
								'ignore' => '.csv',

		);
		$query_hist_daily = http_build_query ( $vars_hist_daily );
		try {
			if ( !( $fh = fopen( $args[2] . '?' . $query_hist_daily, 'r' ) ) ) throw new CustomException( $this->errors['110'] );
		} catch ( CustomException $e ) {
			if ( DEBUG ) echo $e;
			return;
		}
		$output = $this->file['csv_heading'];
		$row = 0;
		//var_dump( $output );
		while ( $data = fgetcsv( $fh, 0, ',' ) ) { 
			//var_dump( $data );
			if ( $row ) $output .= implode( ',', array( $data[0], $data[1], $data[2], $data[3], $data[4], $data[5], ) ) . "\n";
			//var_dump( $output ); exit();
			$row++;
		}
		fclose( $fh );
		//var_dump( $output ); exit();
		return $output;
	}
	
	
} // end class

?>
