<?php
/**
*@version 130109
*  Error codes: 100-200
*/

class Test {

	/** 
	*  Data Members 
	*/
	
	public $result = NULL;
	public $name;

	private $errors = array( 
		'100' => 'Specify search string in the $args = array( \'string\' => <search string>, )',
		'102' => 'Specify list of urls to do search on in the $args = array( \'urls\' => array (<url1>, <url2>, ...) )',
		'104' => 'Specify $args = array( \'string\' => <search string>, \'urls\' => array (<url1>, <url2>, ...) )',
		'106' => 'Failed to open url = ',
		'108' => '\'urls\' element in the $args array must be an array, like this: $args = array( \'string\' => <search string>, \'urls\' => array (<url1>, <url2>, ...) )',
	);

	
	/** 
	*  Methods 
	*/
	
	function __construct( $test_name = 'unnamed test' ) {
		$this->name = $test_name;
	}
	
	/** 
	* Searches for a string on a web page by calling a successive chain of specified url's.
	*
	* @params $args = array( 'string' => <search string>, 'urls' => array( <url1>, <url2>, <url3>, ...  )  )
	* 
	* @result array( <url0 where the string was found>, <url1 where the string was found>, ... )
	* 
	*/
	
	public function seek_String( $args ) {
		
		$this->result = array();
		
		try {
			if( !isset( $args['string'] ) || empty( $args['string'] ) ) throw new CustomException( $this->errors['100'] );
			if( !isset( $args['urls'] ) || empty( $args['urls'] ) ) throw new CustomException( $this->errors['102'] );
			if( !is_array( $args ) ) throw new CustomException( $this->errors['104'] );
			if( !is_array( $args['urls'] ) ) throw new CustomException( $this->errors['108'] );
		} catch ( CustomException $e ) {
			if ( DEBUG ) echo $e;
			return;
		}
		
		foreach( $args['urls'] as $key => $url ) {
			try {
				if ( !( $stream = fopen( $url, 'r' ) ) ) throw new CustomException( $this->errors['106'].$url );
			} catch ( CustomException $e ) {
				if ( DEBUG ) echo $e;
				return;
			}
			$metadata = stream_get_meta_data( $stream );
			$contents = stream_get_contents( $stream );
			fclose( $stream );
			
			if ( strpos( $contents, $args['string'] ) ) $this->result[] = $url;
			
		}
	
		return $this->result;
		
	}


}



?>