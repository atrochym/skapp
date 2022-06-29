<?php

class Validate {

	private $_inputData = [];
	private $_validData;
	public $_error;
	public $_fieldFail;

	public function add($fieldName, $input, $options)
	{
		$this->_inputData[$fieldName] = [
			'input' => trim($input),
			'options' => trim($options)
		];
	}

	public function getValid()
	{
		foreach ($this->_inputData as $fieldName => $element)
		{
			$options = $this->parseOptions($element['options']);
			$filer = $options['dataType'] . 'Filter';
			$this->_fieldFail = $fieldName;

			if (!method_exists($this, $filer))
			{
				throw new Exception('Validate :: unknown dataType ' . $filer);
			}

			if (!$options['require'] && strlen($element['input']) == 0)
			{
				continue;
			}

			$input = $this->{$filer}($element['input']);

			if ($input === false)
			{
				$this->_error = $fieldName . ' type data missmatch';
				return false;
			}

			if ($options['minLenght'])
			{
				$result = $this->minLenghtFilter($input, $options['minLenght']);

				if($result === false)
				{

					$this->_error = $fieldName . ' shorter than ' . $options['minLenght'];
					return false;
				}
			}

			if ($options['maxLenght'])
			{
				$result = $this->maxLenghtFilter($input, $options['maxLenght']);

				if($result === false) {

					$this->_error = $fieldName . ' longer than ' . $options['maxLenght'];
					return false;
				}
			}

			if ($options['require'])
			{
				if (strlen($input) < 1) {

					$this->_error = $fieldName . ' is required';
					return false;
				}
			}

			$this->{$fieldName} = $input;
			$this->_validData[$fieldName] = &$this->{$fieldName};
		}

		return true;
	}

	public function getValidData()
	{
		if ($this->_error)
		{
			return false;
		}

		return $this->_validData;
	}

	private function maxLenghtFilter($input, $lenght)
	{
		if (strlen($input) > $lenght)
		{
			return false;
		}
	}

	private function minLenghtFilter($input, $lenght)
	{
		if (strlen($input) > 0 && strlen($input) < $lenght)
		{
			return false;
		}
	}

	private function urlFilter($input) {
		
		if (filter_var($input, FILTER_VALIDATE_URL) == false)
		{
			return false;
		}

		return $input;
	}

	private function loginFilter($input)
	{
		if (preg_match('/^[[:alnum:]]+[-.\_]?[[:alnum:]]+$/', $input) == 0)
		{
			return false;
		}

		return $input;
	}

	private function fullnameFilter($input)
	{
		if (preg_match('/^[[:alpha:]]+\s{1}[[:alpha:]]+$/u', $input) == 0)
		{
			return false;
		}

		return $input;
	}

	private function phoneFilter($input)
	{
		if (preg_match('/^\+?\d*\s?\d*$/', $input) == 0)
		{
			return false;
		}

		return $input;
	}
	
	private function emailFilter($input)
	{
		if (filter_var($input, FILTER_VALIDATE_EMAIL) == false)
		{
			return false;
		}

		return $input;
	}

	private function dateFilter($input)
	{
		if (strlen($input) <> 10)
		{
			return false;
		}
		$parts = explode('-', $input);
		
		if (checkdate($parts[1], $parts[2], $parts[0]) === false) 
		{
			return false;
		}

		return $input;
	}
	
	private function datetimeFilter($input)
	{
		if (strlen($input) <> 19)
		{
			return false;
		}

		$parts = explode(' ', $input);
		$date = explode('-', $parts[0]);
		$time = $parts[1];
		
		if (checkdate($date[1], $date[2], $date[0]) === false) 
		{
			return false;
		}

		if (preg_match('/^(0?\d|1\d|2[0-3]):[0-5]\d:[0-5]\d$/', $time) == 0)
		{
			return false;
		}

		return $input;
	}

	private function floatFilter($input)
	{
		if (preg_match('/^\d*\.{1}\d{2}$/', $input) == 0)
		{
			return false;
		}

		return $input;
	}
	
	private function integerFilter($input)
	{
		if (preg_match('/^\d*$/', $input) == 0)
		{
			return false;
		}

		return $input;
	}

	private function intervalFilter($input)
	{
		if (preg_match('/^\d*\-?\d*$/', $input) == 0)
		{
			return false;
		}

		return $input;
	}
	
	private function alnumFilter($input)
	{
		if (ctype_alnum($input) == 0)
		{
			return false;
		}

		return $input;
	}
	
	private function textFilter($input)
	{
		return htmlspecialchars(strip_tags($input));
	}
	
	private function passwordFilter($input)
	{
		if (preg_match('/^(\d{2,}|\w{2,})[\d\w!@#$%^&*_\-=+,.]*$/', $input) == 0)
		{
			return false;
		}

		return $input;

	}

	private function parseOptions($options)
	{
		$options = explode(' ', $options);
		$result = [];
	
		asort($options);
		$arrayKey = array_search('require', $options);
	
		if ($arrayKey !== false)
		{
			$result['require'] = true;
			unset($options[$arrayKey]);
		} 
		else
		{
			$result['require'] = false;
		}
	
		foreach ($options as $option)
		{	
			if (is_numeric($option) && !isset($result['minLenght']))
			{	
				$result['minLenght'] = $option;
				continue;
	
			} 
			elseif (is_numeric($option))
			{	
				$result['maxLenght'] = $option;
				continue;
			}
	
			$result['dataType'] = $option;
		}
	
		$result['minLenght'] ??= false;
		$result['maxLenght'] ??= false;

		return $result;
	}
}

?>