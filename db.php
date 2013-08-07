<?php
class MyDB {

  private $rLink;
	private $rRes;
//Установление соединения с СУБД на $sHost:$sPort и выбора БД $sDbase
	function Connect($sDbase='', $sHost='mysql.hostinger.ru', $sLogin='', $sPass='', $sPort = 3306)
	{
		$this->rLink = mysqli_connect($sHost, $sLogin, $sPass, $sDbase, $sPort);
		//mysqli_select_db($sDbase, $this->rLink);
		return $this->rLink;
	}
//Выполнение запроса 
	function Query($sSql)
	{
		if(!$this->rLink)$this->Connect();
		$this->rRes = mysqli_query($this->rLink, $sSql);
		if (!$this->rRes)
		{
			return false;
		}
		return $this->rRes;
	}
//Анализ (A) и исправление (C) запроса
	function Analyse($sSql,$sMode="R")
	{
		if($sMode=="A"){
			preg_match_all("'",$sSql,$matches1);
			preg_match("where/\s+/1",$sSql,$matches2);
			preg_match("like/\s+/1",$sSql,$matches3);
			$matches=$matches1.$matches2.$matches3;
			return $matches;
		}
		else if($sMode=="R"){
			if(preg_match("where/\s+/1",$sSql)>0)return false;
			if(preg_match("like/\s+/1",$sSql)>0)return false;
			return str_replace("'",'"',$sSql);
		}
		return $this->rRes;
	}
	
	function Disconnect(){
		return mysqli_close($this->rLink);
	}
	}
?>
