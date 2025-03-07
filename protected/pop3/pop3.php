<?php
class wspPop3
{
 var $fs;
 var $Error;
 
 function wspPop3()
 {
  $this->fs = false;
  $this->Error = '';
 }
 
 // читать из потока
function Read(&$buff, $lst=false)
{
	if (!$this->fs)
		{
		$this->Error = 'Read:Не открыт ящик';
		return false;
		}
  
	if(feof($this->fs))
		{
		$this->Error = 'Read:Нет данных в потоке';
		return false;
		}
  
	//  while(!preg_match("/\r\n/", $str))
	$str = fgets($this->fs);

	if($str[0] !== '+')
		{ 
		$this->Error = 'Read:Ошибка: '.$str;
		return false;
		}
  
	if (!$lst) 
		{
		$buff = $str;
		return true;
		}

	while(!preg_match("/.*(\r\n\.\r\n).*/", $str))
		$str = $str.fgets($this->fs);

  	//$str=substr($str,0,strrpos($str,'.'));

	$buff = explode("\r\n", $str);
	while((count($buff) > 1)and($buff[count($buff)-1] != '.'))
		unset($buff[count($buff)-1]);
	if ($buff[count($buff)-1] == '.')
		unset($buff[count($buff)-1]);

	return true;
}
 
// запись в поток и возврат вывода
function Write($snd, &$buff)
{
	if (!$this->fs)
		{
		$this->Error = 'Write:Не открыт ящик';
		return false;
		}

	$cmd = explode(' ', $snd);
	foreach($cmd as $key => $val)
		$cmd[$key] = strtoupper(trim($val));
  
	$buff = '';
	$snd = trim($snd)."\r\n";
	if(!fwrite($this->fs, $snd))
		{
		$this->Error = 'Write:Ошибка отсылки команды серверу';
		return false;
		};
  
	$lst = ((($cmd[0] == "RETR")and(intval($cmd[1]) > 0))or
		(($cmd[0] == "LIST")and(!isset($cmd[1])))or
        (($cmd[0] == "TOP")and(intval($cmd[1]) > 0)and(intval($cmd[2]) > 0)));
	
	return $this->Read($buff, $lst);   
 }
 
// открыть ящик
function Open($srv, $usr, $pwd, $auto=true, $port=110, $tm=30)
{
	if($this->fs)
		{
		if(!$auto)
			{
			$this->Error = 'Open:Подключение уже выполнено';
			return false;
			};
		$this->Close();
		};

	$this->fs = fsockopen($srv, $port, $errno, $errstr, $tm);
	if(!$this->fs)
		{
		$this->Error = 'Open:Ошибка подключения к почтовому серверу '.$errstr.' ('.$errno.')';
		return false;
		};
  
	$inf = null;
	if (!$this->Read($inf))
		{
		$this->Close();
		$this->Error = 'Open:Ошибка получения информации от сервера';
		return false;
		};
  
	$buff = null;
	$snd = "USER ".$usr;
	if(!$this->Write($snd, $buff))
		return false;

	$snd = "PASS ".$pwd;
	if(!$this->Write($snd, $buff))
		return false;
  
	return true; 
 }

// закрыть работу с ящиком
function Close()
{
	$buff = null;
	if($this->fs)
		{
		$this->Write("QUIT", $buff);
		fclose($this->fs);
		}
	$this->fs = false;
}
 
// список писем
function Lst(&$lst)
{
	$buff = null;
	if(!$this->Write("LIST", $buff))
		return false;
  
	for($i=1; $i<count($buff); $i++)
		$lst[$i] = explode(' ', $buff[$i], 2);

	return true;
}
 
 // чтение письма
function GetMail($id, &$head, &$text, $del=false)
{
	$buff = null;
	if(!$this->Write("RETR ".$id, $buff))
		return false;
  
	$i = 1;
	while(strlen($buff[$i]) > 0)
		$head[] = $buff[$i++];
	$i++;
	while($i < count($buff))
		$text[] = $buff[$i++];
     
	if($del)
		$this->Write("DELE ".$id, $buff);
	
	return true;
 }
 
 // Удаление письма
 function DelMail($id, $del=false)
 {
  $buff = null;
  if($del)
   if ($this->Write("DELE ".$id, $buff))
	return true;
 return false;
 }
 
};
?> 