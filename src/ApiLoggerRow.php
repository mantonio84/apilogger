<?php

namespace Mantonio84\ApiLogger;
use Illuminate\Support\Arr;

class ApiLoggerRow {
	
	public $created_at;
	protected $reader;
	protected $data;
	
	public function __construct(int $created_at, \Closure $reader){
		$this->reader=$reader;
		$this->created_at=$created_At;
	}
	
	public function __get($name){
		$this->fill();
		return array_key_exists($name, $this->data) ? $this->data[$name] : null;
	}
	
	protected function fill(){
		if (is_null($this->data)){
			$this->data = Arr::wrap(call_user_func($this->reader));
		}
	}
}