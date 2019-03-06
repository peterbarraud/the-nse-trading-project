
<?php

class RestfulApp {
	function run(){
		// first remove the leading / at the start of the PATH_INFO server variable
		// credit: https://stackoverflow.com/a/1252710/4672179
		$pos = strpos($_SERVER['PATH_INFO'],'/');
		$clean_path_info = substr_replace($_SERVER['PATH_INFO'], '', $pos, strlen('/'));
		$callback_parts = explode("/",$clean_path_info);
		$callback = $callback_parts[0];
		array_shift($callback_parts);
		call_user_func_array($callback, $callback_parts);
	}
}

$restapp = new RestfulApp();
$restapp->run();


class Stock {

	function __construct($line, $csv_struct) {
		// making sure that the csv structure is in the same order as the fields in the csv file
		ksort($csv_struct);
		foreach ($csv_struct as $fieldindex => $fieldname) {
			// we need to EQ field to only gat that data but we don't want to send this field to client
			// since anyway the only records we're getting are the EQ series
			if ($fieldname !== 'SERIES'){
				$this->{trim($fieldname)} = trim($line[$fieldindex]);
			}
		}
	}
}

class Stocks {
	public $DATE = '';	// index = 2
	public $items = [];
	private $exclude_fields = array("DATE1", "PREV_CLOSE", "AVG_PRICE", "DELIV_QTY", "DELIV_PER", "LAST_PRICE", "TURNOVER_LACS");
	function __construct() {
		$csv_struct = array();
		//download_bhavdata() but not on localhost
		if (substr( $_SERVER['HTTP_HOST'], 0, strlen("localhost") ) !== "localhost"){
			file_put_contents('download_bhavdata.log', 0);
			$this->download_bhavdata();
		}
		$file_handle = fopen('sec_bhavdata_full.csv', 'r');
		$title_row = true;
		while (!feof($file_handle) ) {
			$line = fgetcsv($file_handle, 1024);
			if ($title_row){
				$title_row = false;
				//get the csv structure
				$counter = 0;
				for ($counter; $counter<sizeof($line); $counter++) {
					$field =  trim($line[$counter]);
					if (!in_array($field, $this->exclude_fields)){
						$csv_struct[$counter] = $field;
					}
				}
			} else {
				if ($line[2] != ''){
					$this->DATE = $line[2];
				}
				if (trim($line[1]) === 'EQ'){
					$stock = new Stock($line, $csv_struct);
					array_push($this->items, $stock);
				}
			}
		}
		fclose($file_handle);
	}


	private function download_bhavdata(){
		file_put_contents('download_bhavdata.log', 1, FILE_APPEND);
		$handle = curl_init();
		file_put_contents('download_bhavdata.log', 2, FILE_APPEND);
		$url = "https://www.nseindia.com/products/content/sec_bhavdata_full.csv";
		// Set the url
		curl_setopt($handle, CURLOPT_URL, $url);
		// Set the result output to be a string.
		curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
		file_put_contents('download_bhavdata.log', 3, FILE_APPEND);

		$output = curl_exec($handle);
		unlink('sec_bhavdata_full.csv');
		file_put_contents('sec_bhavdata_full.csv', $output);
		curl_close($handle);
		
	}
}

function getnsedata(){
	$stocks = new Stocks();
	echo json_encode($stocks);

}


?>
