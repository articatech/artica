<?php

class postfix_regex_compile{
	private $pattern;


	public function postfix_regex_compile($pattern){
		$this->pattern=$pattern;
		
	}
	
	public function return_regex(){
		$pattern=$this->pattern;
		$pattern_t="   $pattern";
	
		if(preg_match("#regex:\s+(.+)#", trim($pattern),$re)){
			$re[1]=$this->regex_fix($re[1]);
			return trim($re[1]);
		}
		if(preg_match("#regex:(.+)#", trim($pattern),$re)){
			$re[1]=$this->regex_fix($re[1]);
			return trim($re[1]);
		}
	
		if(preg_match("#^regex\s+(.+)#", trim($pattern),$re)){
			$re[1]=$this->regex_fix($re[1]);
			return trim($re[1]);
		}
		if(preg_match("#^regex(.+)#", trim($pattern),$re)){
			$re[1]=$this->regex_fix($re[1]);
			return trim($re[1]);
		}
	
	
	
		if(strpos($pattern_t, '[')>0){
			$pattern=$this->regex_fix($pattern);
			return $pattern;
		}
	
		if(strpos($pattern_t, '.*')>0){
			$pattern=$this->regex_fix($pattern);
			return $pattern;
		}
	
		if(strpos($pattern_t, '*')>0){
			return $this->string_to_regex(trim($pattern));
		}
	
	
	
		return $pattern;
	}
	
	private function regex_fix($pattern){
		$pattern=str_replace('s.fr','s\.fr',$pattern);
		$pattern=str_replace('e.com','e\.com',$pattern);
		$pattern=str_replace('].com',']\.com',$pattern);
		$pattern=str_replace('].eu',']\.eu',$pattern);
		$pattern=str_replace('].ru',']\.ru',$pattern);
		$pattern=str_replace('].info',']\.info',$pattern);
		$pattern=str_replace(']+.com',']+\.com',$pattern);
		$pattern=str_replace(']+.cf',']+\.cf',$pattern);
		$pattern=str_replace(']+.net',']+\.net',$pattern);
		$pattern=str_replace(']+.me',']+\.me',$pattern);
		$pattern=str_replace(']+.co.vu',']+\.co\.vu',$pattern);
		return $pattern;
	}
	
	public function string_to_regex($pattern){
		if(trim($pattern)==null){return null;}
		$pattern=str_replace("/", "\/", $pattern);
		$pattern=str_replace(".", "\.", $pattern);
		$pattern=str_replace("-", "\-", $pattern);
		$pattern=str_replace("[", "\[", $pattern);
		$pattern=str_replace("]", "\]", $pattern);
		$pattern=str_replace("(", "\(", $pattern);
		$pattern=str_replace(")", "\)", $pattern);
		$pattern=str_replace("$", "\$", $pattern);
		$pattern=str_replace("?", "\?", $pattern);
		$pattern=str_replace("#", "\#", $pattern);
		$pattern=str_replace("{", "\{", $pattern);
		$pattern=str_replace("}", "\}", $pattern);
		$pattern=str_replace("^", "\^", $pattern);
		$pattern=str_replace("!", "\!", $pattern);
		$pattern=str_replace("*", ".*?", $pattern);
	
		return $pattern;
	
	}	
	
}