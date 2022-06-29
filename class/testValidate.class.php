<?php


// $validate = new testValidate();
// $validate->add('orderDate', $_POST['odrer-date'], 'date');
// $validate->add('url', $_POST['url'], 'url 5 100 required');


// $data = [
// 	'order-date' => $_POST['odrer-date'],

// ];

// $input->orderDate;

class XtestValidate {

	private $inputData = [];
	private $success = false;


	public function add($fieldName, $input, $options) {

		$this->inputData[$fieldName] = [
			'input' => $input,
			'options' => $options
		];
	}

	public function getValid() {

		foreach ($this->inputData as $element) {

			$options = $this->parseOptions($element['options']);

			

		}
	}

	private function parseOptions($options) {

		$options = explode(' ', $options);
		$result = [];
	
		asort($options);		
		$arrayKey = array_search('require', $options);
	
		if ($arrayKey !== false) {
			$result['require'] = true;
			unset($options[$arrayKey]);
		} else {
			$result['require'] = false;
		}
	
		foreach ($options as $option) {
	
			if (is_numeric($option) && !isset($result['minLenght'])) {
	
				$result['minLenght'] = $option;
				continue;
	
			} elseif (is_numeric($option)) {
	
				$result['maxLenght'] = $option;
				continue;
			}
	
			$result['dataType'] = $option;
		}
	
		return $result;
	}



	public function alpha($input, $minLenght=1, $maxLenght=500) {
		if ($minLenght && $this->lenght($input, $minLenght, $maxLenght) && ctype_alpha($input)) {
			return $input;
		}

		return false;
	}

	public function alphaNumeric($input, $minLenght=1, $maxLenght=500) {
		if (($minLenght !== null && $this->lenght($input, $minLenght, $maxLenght)) && ctype_alnum($input)) {
			return $input;
		}
		return false;
	}

	public function numeric($input, $minLenght=1, $maxLenght=10) {
		if ($minLenght && $this->lenght($input, $minLenght, $maxLenght) && ctype_digit($input)) {
			return $input;
		}
		return false;
	}
	
	public function string($input, $minLenght=1, $maxLenght=500) {
		if ($minLenght && $this->lenght($input, $minLenght, $maxLenght) && ctype_print($input)) {
			return $input;
		}
		return false;
	}

	public function email($input, $minLenght=8, $maxLenght=60) {
		if ($minLenght && $this->lenght($input, $minLenght, $maxLenght) && filter_var($input, FILTER_VALIDATE_EMAIL)) {
			return $input;
		}
		return false;
	}
	
	public function login($input, $minLenght=4, $maxLenght=30) {
		if ($minLenght && $this->lenght($input, $minLenght, $maxLenght) && preg_match('/^[[:alnum:]][a-zA-Z]+[-.\_]{0,1}[[:alnum:]]+$/', $input)) {
			return $input;
		}
		return false;
	}

	public function nameSurname($input, $minLenght=4, $maxLenght=30) {
			if ($minLenght && $this->lenght($input, $minLenght, $maxLenght) && preg_match('/^[[:alpha:]]+\s{1}+[[:alpha:]]+$/u', $input)) {
			return $input;
		}
		return false;
	}




	private function lenght($input, $min, $max) {
		if (mb_strlen($input) >= $min && mb_strlen($input) <= $max) {
			return true;
		}
		return false;
	}
}

?>