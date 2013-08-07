<?php
//удаление опасных символов из запроса
function SQLsafety($query){
  return str_replace("'","",$query);
}

//замена кодов в шаблонах на произвольную строку, записывается в SQL запросы
function replaceCodesInSQL($fieldName,$strToInsert){
	return "replace(replace(replace(replace(replace(replace(replace(replace(replace($fieldName,'[А]','$strToInsert'),'[Б]','$strToInsert'),'[Г1]','$strToInsert'),'[Г2]','$strToInsert'),'[Г3]','$strToInsert'),'[О]','$strToInsert'),'[Т]','$strToInsert'),'[М]','$strToInsert'),'[Ч]','$strToInsert')";
}

//сравнение окончания строки с базой окончаний
function compareStrWithCasesEndings($instr){
	return "'".$instr."' LIKE CONCAT('%',И) OR '".$instr."' LIKE CONCAT('%',Р) OR '".$instr."' LIKE CONCAT('%',Д) OR '".$instr."' LIKE CONCAT('%',В) OR '".$instr."' LIKE CONCAT('%',Т) OR '".$instr."' LIKE CONCAT('%',П) OR '".$instr."' LIKE CONCAT('%',МИ) OR '".$instr."' LIKE CONCAT('%',МР) OR '".$instr."' LIKE CONCAT('%',МД) OR '".$instr."' LIKE CONCAT('%',МВ) OR '".$instr."' LIKE CONCAT('%',МТ) OR '".$instr."' LIKE CONCAT('%',МП) ";
}

//лемматизация - приведение к начальной форме
function lemmatize($instr){
	$parts=explode(" ",$instr);
	$dbc=new MyDB();
	$dbc->Connect();
	$result="";
	foreach($parts as $part){
		$query="SELECT * FROM Окончания WHERE ".compareStrWithCasesEndings2($instr);
		
		$item = mysqli_fetch_array($endings);
		$result.=str_replace($item[1],$item[0],$part)." ";
	}
	return $result;
}
//получение идентификатора падежа по его номеру
function getCaseNum($id){
	switch($num){
		case "И":
			return 0;
			break;
		case "Р":
			return 1;
			break;
		case "Д":
			return 2;
			break;
		case "В":
			return 3;
			break;
		case "Т":
			return 4;
			break;
		case "П":
			return 5;
			break;
		case "МИ":
			return 6;
			break;
		case "МР":
			return 7;
			break;
		case "МД":
			return 8;
			break;
		case "МВ":
			return 9;
			break;
		case "МТ":
			return 10;
			break;
		case "МП":
			return 11;
			break;
		default:
			return 555;
			break;
	}
}
//получение номера падежа по его идентификатору
function getCaseId($num){
	switch($num){
		case 0:
			return "И";
			break;
		case 1:
			return "Р";
			break;
		case 2:
			return "Д";
			break;
		case 3:
			return "В";
			break;
		case 4:
			return "Т";
			break;
		case 5:
			return "П";
			break;
		case 6:
			return "МИ";
			break;
		case 7:
			return "МР";
			break;
		case 8:
			return "МД";
			break;
		case 9:
			return "МВ";
			break;
		case 10:
			return "МТ";
			break;
		case 11:
			return "МП";
			break;
		default:
			return "Ошибка";
			break;
	}
}
//изменение падежа и числа,
function setCase($instr,$case,$force=false,$stop="С"){
	//echo "<br/>".$instr;
	$parts=explode(" ",$instr);
	
	$dbc=new MyDB();
	$dbc->Connect();
	$noun=false;
	for($i=0;$i<count($parts);$i++){
		if($parts[$i]=="о" || $parts[$i]=="в" || $parts[$i]=="при" || $parts[$i]=="на"){
			//$case="П";
			//return $instr;
		}
		else if($parts[$i]=="из" || $parts[$i]=="из-за" || $parts[$i]=="без"){
			//$case="Р";
			//return $instr;
		}
		else if($parts[$i]=="под" || $parts[$i]=="над" || $parts[$i]=="за" || $parts[$i]=="перед" || $parts[$i]=="с"){
			//$case="Т";
			//return $instr;
		}
		else{
			$mark="";
			if(isPunctuation($parts[$i][strlen($parts[$i])-1])){
				$mark=$parts[$i][strlen($parts[$i])-1];
				$parts[$i]=substr($parts[$i],0,strlen($parts[$i])-1);
			}
		
			$query="SELECT * FROM Окончания WHERE ".compareStrWithCasesEndings($parts[$i]) ." ORDER BY CHAR_LENGTH(Т) DESC;";
			$endings=$dbc->Query($query);
			$item = mysqli_fetch_array($endings);
			for($j=0;$j<6;$j++){
				$tail=substr($parts[$i],strlen($parts[$i])-strlen($item[$j]),strlen($item[$j]));
				if($tail==$item[$j]){
					if($item["Часть_речи"]==$stop)
						$noun=true;
					if(!$force){
						if(substr($case,0,2)=="М"){
							$case=str_replace("М","",$case);
						}
						//echo "<br/>".getCaseId($j)." ".$case;
					}
					$parts[$i]=substr($parts[$i],0,strlen($parts[$i])-strlen($item[$j])).$item[$case];
					break;
				}
				$tail=substr($parts[$i],strlen($parts[$i])-strlen($item[$j+6]),strlen($item[$j+6]));
				if($tail==$item[$j+6]){
					if($item["Часть_речи"]==$stop)
						$noun=true;
					if(!$force){
						if(substr($case,0,2)!="М"){
							$case="М".$case;
						}
						//echo "<br/>".getCaseId($j)." ".$case;
					}
					$parts[$i]=substr($parts[$i],0,strlen($parts[$i])-strlen($item[$j+6])).$item[$case];
					break;
				}
			}
			$parts[$i]=$parts[$i].$mark;
			if($noun){
				if($mark==","){
					$parts[$i+1]=setCase($parts[$i+1]." ".$parts[$i+2],$case,false,"П");
					$parts[$i+2]="";
				}
				break;
			}
		}
	}
	//echo "<br/>";
	//print_r($parts);
	$result=SumStrsInArray("",$parts,""," ");
	//echo "<br/>".$result;
	return $result;
}
//получение числа и падежа
function getCase($instr){
	$parts=explode(" ",$instr);
	$dbc=new MyDB();
	$dbc->Connect();
	$noun=false;
	$result="";
	for($i=0;$i<count($parts);$i++){
		$mark="";
		if(isPunctuation($parts[$i][strlen($parts[$i])-1])){
			$mark=$parts[$i][strlen($parts[$i])-1];
			$parts[$i]=substr($parts[$i],strlen($parts[$i])-1);
		}
		$query="SELECT * FROM Окончания WHERE ".compareStrWithCasesEndings($parts[$i]) ." ORDER BY CHAR_LENGTH(Т);";
		$endings=$dbc->Query($query);
		$item = mysqli_fetch_array($endings);
		for($j=0;$j<6;$j++){
			$tail=substr($parts[$i],strlen($parts[$i])-strlen($item[$j]),strlen($item[$j]));
			if($tail==$item[$j]){
				if($item["Часть_речи"]=="С"){
					$noun=true;
				}
				$result=getCaseId($j);
				//echo "<br/>".$result." ".$item["Часть_речи"];
				break;
			}
			$tail=substr($parts[$i],strlen($parts[$i])-strlen($item[$j+6]),strlen($item[$j+6]));
			if($tail==$item[$j+6]){
				if($item["Часть_речи"]=="С"){
					$noun=true;
				}
				$result=getCaseId($j+6);
				//echo "<br/>".$result." ".$item["Часть_речи"];
				break;
			}
		}
		if($noun)break;
	}
	return $result;
}

//подстановка в шаблон подстановочных элементов
function insertAll($template,$insertions){

	foreach ($insertions as $Iname=>$Ivalue){
		$template=str_replace("[".$Iname."]",$Ivalue,$template);
	}
	return $template;
}
//проверка, является ли символ знаком препинания
function isPunctuation($inchr){
	$chr=$inchr;
	if(strlen($chr)>1){
		$chr=$chr[strlen($chr)-1];
	}
	return $chr=="?" || $chr=="!" || $chr=="," || $chr=="." || $chr==" ";
}
//перевод строки на русском в нижний регистр
function strtolowerRU($instr){
	return str_replace("А","а",str_replace("Б","б",str_replace("В","в",str_replace("Г","г",str_replace("Д","д",str_replace("Е","е",str_replace("Ё","ё",str_replace("Ж","ж",str_replace("З","з",str_replace("И","и",str_replace("Й","й",str_replace("К","к",str_replace("Л","л",str_replace("М","м",str_replace("Н","н",str_replace("О","о",str_replace("П","п",str_replace("Р","р",str_replace("С","с",str_replace("Т","т",str_replace("У","у",str_replace("Ф","ф",str_replace("Х","х",str_replace("Ц","ц",str_replace("Ч","ч",str_replace("Ш","ш",str_replace("Щ","щ",str_replace("Ъ","ъ",str_replace("Ы","ы",str_replace("Ь","ь",str_replace("Э","э",str_replace("Ю","ю",str_replace("Я","я",$instr)))))))))))))))))))))))))))))))));
}
//перевод строки на русском в верхний регистр
function strtoupperRU($instr){
	return str_replace("а","А",str_replace("б","Б",str_replace("в","В",str_replace("г","Г",str_replace("д","Д",str_replace("е","Е",str_replace("ё","Ё",str_replace("ж","Ж",str_replace("з","З",str_replace("и","И",str_replace("й","Й",str_replace("к","К",str_replace("л","Л",str_replace("м","М",str_replace("н","Н",str_replace("о","О",str_replace("п","П",str_replace("р","Р",str_replace("с","С",str_replace("т","Т",str_replace("у","У",str_replace("ф","Ф",str_replace("х","Х",str_replace("ц","Ц",str_replace("ч","Ч",str_replace("ш","Ш",str_replace("щ","Щ",str_replace("ъ","Ъ",str_replace("ы","Ы",str_replace("ь","Ь",str_replace("э","Э",str_replace("ю","Ю",str_replace("я","Я",$instr)))))))))))))))))))))))))))))))));
}
//объединение строк в массиве с добавлением перед каждой $delimb, после - $delimа и $delimс между строками
function SumStrsInArray($delimb="",$arr,$delima="",$delimc="",$si=0,$ei=-1){
	if($ei<0)$ei=count($arr)+$ei;
	$outstr=$delimb.$arr[$si].$delima;
	if($ei>$si)
	{
		$outstr.=$delimc;
		for($i=$si+1;$i<$ei;$i++){
			$outstr.=$delimb.$arr[$i].$delima.$delimc;
		}
		$outstr.=$delimb.$arr[$ei].$delima;
	}
	return $outstr;
}

?>
