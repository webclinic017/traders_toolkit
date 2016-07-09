<?php
/**
* 
* @class SCORE
* 
* @brief Calculates score for a stock symbol based on Smith In The Black (SITB) criteria. Criteria are typical candlestick patterns (i.e. combinations of candlesticks) and candlestick shapes that are assigned weighed factors. The factors are then summed up and presented as a bearish or a bullish score. 
*   The score can be calculated for the bullish and bearish stances for a given number of bars back from the last bar. All the bars are passed to the class as candlestick ohlcv array via the $csv object.
*   The class uses the passed ohlcv data in the $csv object to calculate the weekly and monthly ohlcv bars. These higher timeframes are necessary for calculation of the score because it is based on multiple timeframes: monthly, weekly, and daily. The class calculates these higher time frames for the oldest bar first, applies the scoring patterns, than proceeds to the next daily bar, recalculates the higher timeframes, applies the scoring patterns, and so on, until it arrives to the latest bar. 
*
* @result Bullish or bearish score as a real positive or negative number.
* 
* @param (string) $symbol_name stock or ETF symbol name undergoing the scoring
* @param (string) $study_name Name of the study.
* @param (array) $study_data[keys explained below]
*		(string) 'study_path' Directory where to save study results and files. Must end with forward slash. Example: "assets/SITB_score/"
*		(int) 'bullish' how many bars back to evaluate the bullish score for.
*		(int) 'bearish' how many bars back to evaluate the bearish score for.
* 
* @version 150217
* 
* @author Alex Kay (MGWebGroup)
* 
* Contents:
* __construct() - 
* 
* 
* 
*/

class SCORE {

	private $patterns = array( 'SITB' => NULL );
	private $months = 2; /**< for how many months back to create monthly ohlcv array */

	private $output = 'NULL'; /**< html code for the study output */

	function __construct( $symbol_name, $study_name, $study_data, $csv ) {

		$timestamp = microtime( TRUE );

		// Evaluate bullish score
		if ( isset( $study_data['bullish'] ) && $study_data['bullish'] > 0 ) {
			for ( $i = $study_data['bullish']; $i>=0; $i-- ) {

				// shift $i bars back in time in the daily csv array.
				$index = $csv->last['key'] - $i;
				$pointer = new DateTime( date( 'c', $csv->date_value[$index] ) );

				// create monthly ohlcv array for $this->months back
				$study['monthly'] = array();
				for ( $j = $this->months; $j>=0; $j-- ) {
					$date = clone $pointer;
					$date->sub( new DateInterval( sprintf( 'P%sM', $j ) ) );
					$year = (int) $date->format( 'Y' );
					$month = (int) $date->format( 'n' );
					$days_in_month = ( $j )? (int) $date->format( 't' ) : $date->format('j'); // set end of the month to today's day in the month if in current month
					$date->setDate( $year, $month, 1 );
					$start_ts = $date->format( 'U' );
					while ( !$csv->is_trading_day( $start_ts ) ) {
						$start_ts += 86400;
					}
					$date->setDate( $year, $month, $days_in_month );
					$end_ts = $date->format( 'U' );
					while ( !$csv->is_trading_day( $end_ts ) ) {
						$end_ts -= 86400;
					}
					$keys = array_keys( $csv->date_value, $start_ts );
					$start_index = array_pop( $keys );
					$keys = array_keys( $csv->date_value, $end_ts );
					$end_index = array_pop( $keys );
					//echo sprintf( 'end index date=%s<br/>', date('c',$csv->date_value[$end_index]) );
					$bars_high = array_slice( $csv->high, $start_index, $end_index - $start_index + 1 );
					$bars_low = array_slice( $csv->low, $start_index, $end_index - $start_index + 1 );

					$study['monthly']['date_value'][] = $csv->date_value[$end_index];
					$study['monthly']['open'][] = $csv->open[$start_index];
					$study['monthly']['high'][] = max( $bars_high );
					$study['monthly']['low'][] = min( $bars_low );
					$study['monthly']['close'][] = $csv->close[$end_index];

				}
				echo sprintf( '%s:<br/>', $pointer->format( 'c' ) );
				foreach ($study['monthly']['date_value'] as $key => $value) {
					echo sprintf( '%s O=%.2f H=%.2f L=%.2f C=%.2f <br/>', date( 'c', $study['monthly']['date_value'][$key] ), $study['monthly']['open'][$key], $study['monthly']['high'][$key], $study['monthly']['low'][$key], $study['monthly']['close'][$key] );
				}
				echo '<br/>';
				//echo sprintf( '%s O=%.2f H=%.2f L=%.2f C=%.2f <br/>', date( 'c', $study['monthly']['date_value'][0] ), $study['monthly']['open'][0], $study['monthly']['high'][0], $study['monthly']['low'][0], $study['monthly']['close'][0] );

				//$dates = array_slice( $csv->date_value, $start_index, $end_index - $start_index );
				// foreach ( $bars_high as $key => $value ) {
				// 	echo sprintf('%s H=%.2f<br/>', date( 'c', $dates[$key] ), $bars_high[$key] );

				// }

				//$index = array_pop( array_keys( $csv->date_value, $start_ts ) );
				//var_dump($keys); exit();
				//echo sprintf( 'start_ts=%s is %s close P=%s <br/>', $csv->date_value[$start_index], date( 'c', $csv->date_value[$start_index] ), $csv->close[$start_index] );
				//echo sprintf( 'end_ts=%s is %s close P=%s <br/>', $csv->date_value[$end_index], date( 'c', $csv->date_value[$end_index] ), $csv->close[$end_index] );
				//echo date( 'c', $start_ts ) . '<br/>';
				//echo $pointer->format( 'c' ) . ' ' . $last->format( 'c' ) . '</br>';
				//unset( $pointer );
			}


			
		}

		

		// $study['date_value'] = array_slice( $csv->date_value, -$study_data['bullish'] );
		// foreach ( $study['date_value'] as $value ) {
		// 	echo date( 'c', $value ) . '</br>';
		// }

	}


	function __toString() {
		return $this->output;
	}



}