<?php

class Timer
{
	private array $timers = [];
	private int $precision = 2;
	private string $unit = 'ms';

	public function start(string $name = 'default'): void
	{
		if (isset($this->timers[$name]))
		{
			throw new Exception('Timer :: timer name exist');
		}

		$this->timers[$name]['start'] = hrtime(true);
	}

	public function end(string $name = 'default'): float
	{
		$this->exist($name);

		$this->timers[$name]['end'] = hrtime(true);
		$this->timers[$name]['result'] = $this->timers[$name]['end'] - $this->timers[$name]['start'];
		
		return $this->get($name);
	}

	public function get(string $name = 'default'): float
	{
		$this->exist($name);

		switch ($this->unit)
		{
			case 'ns':
				$divide = 1;
				break;
			case 'ms':
				$divide = 1000000;
				break;
			case 's':
				$divide = 1000000000;
				break;
		}

		return round($this->timers[$name]['result'] / $divide, $this->precision);
	}

	public function setPrecision(int $precision): void
	{
		$this->precision = $precision;
	}

	public function setUnit(string $unit): void
	{
		if ($unit !== 's' && $unit !== 'ms' && $unit !== 'ns')
		{
			throw new Exception('Timer :: wrong unit');
		}

		$this->unit = $unit;
	}

	private function exist(string $name): bool
	{
		if (!isset($this->timers[$name]))
		{
			throw new Exception('Timer :: timer name missing');
		}

		return true;
	}
}