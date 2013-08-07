<?php
//соединение с БД
require_once("db.php");
$dbc=new MyDB();
$dbc->Connect();

require_once("auxilary.php");

//массивы в порядке их наполнения
$templates=array();  	//шаблоны, на которые похож заданный вопрос
$insertions=array();		//переменные элементы вопроса (предмет, окончания)
$synonims=array();		//синонимы предмета вопроса
$templateSynonims=array();	//синонимы шаблона вопроса
$subnet=array();		//список связей подсети ответа
$blocks=array();		//блоки ответных предложений
$answers=array();		//ответные предложения

$synonimityLimit=0.5;		//предел синонимичности
$debug=($_POST["D"]=="true");			//пошаговое объяснение полученного результата

$Question=$_POST["Q"];		//вопрос

$lastObj="";		//объект вопроса

if($debug){
	error_reporting(E_ERROR|E_WARNING);
}
else{
	error_reporting(E_ERROR);
}
//ПРЕДСТОИТ распознать попытку получения развернутого ответа на предыдущий вопрос

//уменьшение первой буквы при необходимости
if(substr($Question,2,2)==strtolowerRU(substr($Question,2,2))){
	$Question=strtolowerRU(substr($Question,0,2)).substr($Question,2);
}
//удаление знаков препинания в конце
for(;;){
	if(isPunctuation($Question[strlen($Question)-1])){
		$Question=substr($Question,0,strlen($Question)-1);
	}
	else break;
}

$Question=SQLsafety($Question);

//определение структуры вопроса
$Qres=$dbc->Query("SELECT Вопрос FROM Шаблоны WHERE '".$Question."' LIKE ".replaceCodesInSQL("Вопрос","%")." ORDER BY CHAR_LENGTH(Вопрос) DESC");
if(!is_bool($Qres)) while($item = mysqli_fetch_array($Qres))
{
	$item["Направление"]="Прямой";
	array_push($templates,$item);
}

$Qres=$dbc->Query("SELECT Обратный_вопрос,Вопрос FROM Шаблоны WHERE '".$Question."' LIKE ".replaceCodesInSQL("Обратный_вопрос","%")." ORDER BY CHAR_LENGTH(Обратный_вопрос) DESC");
if(!is_bool($Qres)) while($item = mysqli_fetch_array($Qres))
{
	$item["Направление"]="Обратный";
	array_push($templates,$item);
}
if($debug){
	echo "Подходящие шаблоны вопроса: ";
	print_r($templates);
}
foreach($templates as $templateGroup){
	$insertions=array();		//переменные элементы вопроса (предмет, окончания)
	$synonims=array();		//синонимы предмета вопроса
	$templateSynonims=array();	//синонимы шаблона вопроса
	$subnet=array();		//список связей подсети ответа
	$blocks=array();		//блоки ответных предложений

	//выделение предмета вопроса
	$QuestionTemplate=$templateGroup[0];
	$Qlen=strlen($Question);
	$Tlen=strlen($QuestionTemplate);
	$Ti=0;
	$Qi=0;
	for($Ti=0;$Ti<$Tlen;$Ti++){
		if($QuestionTemplate[$Ti]==$Question[$Qi]){
			$Qi++;
		}
		elseif($QuestionTemplate[$Ti]=="["){
			$insertionName="";
			$Ti++;
			for(;$QuestionTemplate[$Ti]!="]";$Ti++){
				$insertionName.=$QuestionTemplate[$Ti];
			}
			$Ti++;
			$insertionValue="";
			for(;!($Question[$Qi]==$QuestionTemplate[$Ti] && ($QuestionTemplate[$Ti+1]==$Question[$Qi+1]||$QuestionTemplate[$Ti+1]=="[")) && $Qi<$Qlen;$Qi++){
				$insertionValue.=$Question[$Qi];
			}
			$newinsertion=array($insertionName=>$insertionValue);
			$insertions=array_merge($insertions,$newinsertion);
			$Qi++;
		}
	}
	
	if($templateGroup["Направление"]=="Прямой"){
		$lastObj=$insertions["А"];
	}
	else{
		$lastObj=$insertions["Б"];
	}
	if($debug){	
		echo "<br/>Элементы вопроса (в. т.ч. предмет вопроса): ";
		print_r($insertions);
	}
	
	//выбор подсети ответа
	//ПРЕДСТОИТ привести предмет вопроса к начальной форме
	//ПРЕДСТОИТ найти синонимы предмета вопроса
	if($templateGroup["Направление"]=="Прямой"){
		array_push($synonims,$insertions["А"]);
	}
	else{
		array_push($synonims,$insertions["Б"]);
	}
	if($debug){	
		echo "<br/>Синонимы предмета вопроса: ";
		print_r($synonims);
	}
	
	//поиск синонимов шаблона вопроса
	array_push($templateSynonims,$templateGroup["Вопрос"]);
	/*$query='
	with Шаблончик(Шаблон1,Шаблон2,Синонимичность)
	as (select Шаблон1,Шаблон2,Подобие  
	   from Подобие
	   where Шаблон1 = "'.$templateGroup["Вопрос"].'" 
	union all
	   select Подобие.Шаблон1, Подобие.Шаблон2, Шаблон.Синонимичность*Подобие.Подобие
	   from Подобие 
		 inner join Шаблончик on Шаблон.Шаблон2 = Подобие.Шаблон1) 
	select Шаблон1,Шаблон2,Cинонимичность 
	from Шаблончик
	';*/
	$len=1;
	for($i=0;$i<$len;$i++){
		$Qres=$dbc->Query("SELECT Шаблон2 FROM Подобие WHERE Шаблон1 = '".$templateSynonims[$i]."' AND Подобие > ".$synonimityLimit." ORDER BY Подобие;");
		if(!is_bool($Qres)) while($item = mysqli_fetch_array($Qres))
		{
			if(!in_array($item[0],$templateSynonims)){
				$len++;
				array_push($templateSynonims,$item[0]);
			}
		}
	}
	if(count($templateSynonims)<2){
		$Qres=$dbc->Query("SELECT Шаблон1 FROM Подобие WHERE Шаблон2 = '".$templateSynonims[0]."' AND Подобие > ".$synonimityLimit." ORDER BY Подобие;");
		if(!is_bool($Qres)) while($item = mysqli_fetch_array($Qres))
		{
			if(!in_array($item[0],$templateSynonims)){
				$len++;
				array_push($templateSynonims,$item[0]);
			}
		}
		$len=count($templateSynonims);
		for($i=1;$i<$len;$i++){
			$Qres=$dbc->Query("SELECT Шаблон2 FROM Подобие WHERE Шаблон1 = '".$templateSynonims[$i]."' AND Подобие > ".$synonimityLimit." ORDER BY Подобие;");
			if(!is_bool($Qres)) while($item = mysqli_fetch_array($Qres))
			{
				if(!in_array($item[0],$templateSynonims)){
					$len++;
					array_push($templateSynonims,$item[0]);
				}
			}
		}
	}
	if($debug){	
		echo "<br/>Синонимы шаблона вопроса: ";
		print_r($templateSynonims);
	}
	//выбор в подсеть ответа связей с синонимичным шаблоном инцедентных синонимам предмета вопроса
	$condition="";
	if($templateGroup["Направление"]=="Прямой"){
		//$condition.="AND А = ".lemmatize($insertions["А"]);
		//echo "<br/>";
		//echo lemmatize($insertions["А"]);
		if($debug){
			foreach($synonims as $synonim){
				echo "<br/>Единственное число:";
				echo "<br/> - Именительный падеж: ".setCase($synonim,"И",true);
				echo "<br/> - Родительный падеж: ".setCase($synonim,"Р",true);
				echo "<br/> - Дательный падеж: ".setCase($synonim,"Д",true);
				echo "<br/> - Винительный падеж: ".setCase($synonim,"В",true);
				echo "<br/> - Творительный падеж: ".setCase($synonim,"Т",true);
				echo "<br/> - Предложный падеж: ".setCase($synonim,"П",true);
				echo "<br/>Множественное число:";
				echo "<br/> - Именительный падеж: ".setCase($synonim,"МИ",true);
				echo "<br/> - Родительный падеж: ".setCase($synonim,"МР",true);
				echo "<br/> - Дательный падеж: ".setCase($synonim,"МД",true);
				echo "<br/> - Винительный падеж: ".setCase($synonim,"МВ",true);
				echo "<br/> - Творительный падеж: ".setCase($synonim,"МТ",true);
				echo "<br/> - Предложный падеж: ".setCase($synonim,"МП",true);
				echo "<br/>";
			}
		}
		$searchQ=SumStrsInArray("Связи.Вопрос = '",$templateSynonims,"'"," OR ");
		$condition="SELECT Связи.А,Шаблоны.Ответ,Связи.Б,Шаблоны.Падеж_А,Шаблоны.Падеж_Б,Шаблоны.Падеж_доп FROM Связи INNER JOIN Шаблоны ON Связи.Вопрос = Шаблоны.Вопрос WHERE (".$searchQ.") AND (".SumStrsInArray("Связи.А = '",$synonims,"'"," OR ").") ORDER BY Связи.А,Шаблоны.Ответ;";
		//echo "<br/>";
		//echo $condition;
		$Qres=$dbc->Query($condition);
		if(!is_bool($Qres)) while($item = mysqli_fetch_array($Qres))
		{
			array_push($subnet,$item);
		}
		if(count($subnet)==0){
			$Qres=$dbc->Query("SELECT Связи.Б,Шаблоны.Обратный_ответ,Связи.А,Шаблоны.Обратный_падеж_Б,Шаблоны.Обратный_падеж_А,Шаблоны.Обратный_падеж_доп FROM Связи INNER JOIN Шаблоны ON Связи.Вопрос = Шаблоны.Вопрос WHERE (".$searchQ.") AND (".SumStrsInArray("Связи.Б = '",$synonims,"'"," OR ").") ORDER BY Связи.Б,Шаблоны.Обратный_ответ;");
			if(!is_bool($Qres)) while($item = mysqli_fetch_array($Qres))
			{
				array_push($subnet,$item);
			}
			if(count($subnet)!=0){
				$templateGroup["Направление"]="Обратный";
				$insertions["Б"]=$insrtions["А"];
			}
		}
	}
	else{
		$condition.="SELECT Связи.Б,Шаблоны.Обратный_ответ,Связи.А,Шаблоны.Обратный_падеж_Б,Шаблоны.Обратный_падеж_А,Шаблоны.Обратный_падеж_доп FROM Связи INNER JOIN Шаблоны ON Связи.Вопрос = Шаблоны.Вопрос WHERE (".$searchQ.") AND (".SumStrsInArray("Связи.Б = '",$synonims,"'"," OR ").") ORDER BY Связи.Б,Шаблоны.Обратный_ответ;";
		//$condition.="AND Б = ".lemmatize($insertions["Б"]);
		//echo "<br/>";
		//echo lemmatize($insertions["Б"]);
		$Qres=$dbc->Query($condition);
		if(!is_bool($Qres)) while($item = mysqli_fetch_array($Qres))
		{
			array_push($subnet,$item);
		}
		if(count($subnet)==0){
			$Qres=$dbc->Query("SELECT Связи.А,Шаблоны.Ответ,Связи.Б,Шаблоны.Падеж_А,Шаблоны.Падеж_Б,Шаблоны.Падеж_доп FROM Связи INNER JOIN Шаблоны ON Связи.Вопрос = Шаблоны.Вопрос WHERE (".$searchQ.") AND (".SumStrsInArray("Связи.А = '",$synonims,"'"," OR ").") ORDER BY Связи.А,Шаблоны.Ответ;");
			if(!is_bool($Qres)) while($item = mysqli_fetch_array($Qres))
			{
				array_push($subnet,$item);
			}
			if(count($subnet)!=0){
				$templateGroup["Направление"]="Прямой";
				$insertions["А"]=$insrtions["Б"];
			}
		}
	}
	if($debug){
		echo "<br/>Подсеть ответа: ";
		print_r($subnet);
	}
	
	//объединение однотипных связей подсети в перечисления
	$blocks[0]=$subnet[0];
	$blocks[0][2]=array(setCase($subnet[0][2],$subnet[0][4]));
	
	$blocki=0;
	for($i=1;$i<count($subnet);$i++){
		if($subnet[$i][0]==$blocks[$blocki][0] && $subnet[$i][1]==$blocks[$blocki][1]){
			//echo "<br>".$subnet[$i][2]." + ".$subnet[$i][4]." -> ".setCase($subnet[$i][2],$subnet[$i][4]);
			array_push($blocks[$blocki][2],setCase($subnet[$i][2],$subnet[$i][4]));
		}
		else{
			$newBlock=$subnet[$i];
			$newBlock[2]=array(setCase($newBlock[2],$newBlock[4]));
			array_push($blocks,$newBlock);
			$blocki++;
		}
	}
	if($debug){
		echo "<br/>Блоки ответных предложений: ";
		print_r($blocks);
	}
	
	//формирование предложений
	foreach($blocks as $block){
		if($templateGroup["Направление"]=="Прямой"){
			$insertions["А"]=$block[0];
			switch(count($block[2])){
			case 1:
				$insertions["Б"]=$block[2][0];
				break;
			case 2:
				$insertions["Б"]=$block[2][0]." и ".$block[2][1];
				break;
			default:
				$insertions["Б"]=sumStrsInArray("",$block[2],"",", ");
				break;
			}
		}
		else{
			$insertions["Б"]=$block[0];
			switch(count($block[2])){
			case 1:
				$insertions["А"]=$block[2][0];
				break;
			case 2:
				$insertions["А"]=$block[2][0]." и ".$block[2][1];
				break;
			default:
				$insertions["А"]=sumStrsInArray("",$block[2],"",", ");
				break;
			}
		}
		if(substr(getCase($block[0]),0,2)=="М"){
			$insertions["Г1"]="ют";
			$insertions["Г2"]="ят";
			$insertions["Г3"]="гут";
		}
		else{
			$insertions["Г1"]="ет";
			$insertions["Г2"]="ит";
			$insertions["Г3"]="жет";
		}
		$answer=insertAll($block[1],$insertions);
		if(strlen($answer)>3)
			array_push($answers,$answer);
	}
	if($debug){
		echo "<br/>Ответ: ";
		print_r($answers);
		echo "<br/>";
	}
	
}

//проверка на наличие ответных предложений
if(count($answers)==0){
	array_push($answers,$lastObj." - неизвестный мне объект");
}
//вывод ответных предложений с оформлением с большой буквы и точкой на конце
foreach($answers as $answer){

	if(strlen($answer)>3){
		//выделение ключевых слов
		$words=explode(" ",$answer);
		if($debug){
			echo "<br/>Поиск ключевых слов: ";
		}
		for($len=2;$len>=0;$len--){
			if($debug){
				echo "<br/> - Длина: ".($len+1)." слова из ".count($words);
			}
			for($i=0;$i<count($words)-$len;$i++){
				if($debug){
					echo "<br/> ----- ".$i;
				}
				if(substr($words[$i],0,3)=="<a "){
					for(;(substr($words[$i],strlen($words[$i])-4)!="</a>") && ($i<(count($words)-$len));$i++);
				}
				else{
					$search=setCase(sumStrsInArray("",$words,""," ",$i,$i+$len),"И");
					if($debug){
						echo "<br/> --- ".$search;
					}
					$Qres=$dbc->Query("SELECT А,Шаблон FROM Связи WHERE А='".$search."'");
					if(!is_bool($Qres)){
						$item = mysqli_fetch_array($Qres);
						$words[$i]="<a href='#' onclick='ask(&#34;".str_replace("[А]",$item[0],$item[1])."&#34;')>".$words[$i];
						$words[$i+$len]=$words[$i+$len]."</a>";
						$i+=$len;
					}
					else{
						$Qres=$dbc->Query("SELECT Связи.Б,Шаблоны.Обратный_вопрос FROM Связи INNER JOIN Шаблоны ON Связи.Вопрос = Шаблоны.Вопрос WHERE  Связи.Б='".$search."'");
						if(!is_bool($Qres)){
							$item = mysqli_fetch_array($Qres);
							$words[$i]="<a href='#' onclick=ask('".str_replace("[Б]",$item[0],$item[1])."')>".$words[$i];
							$words[$i+$len]=$words[$i+$len]."</a>";
							$i+=$len;
						}
					}
				}
			}
		}
		if($answer[0]=="<"){
			$pos=0;
			for($pos=1;$pos<strlen($answer);$pos++){
				if($answer[$pos]==">"){
					$pos++;
					$answer=substr($answer,0,$pos).strtoupperRU(substr($answer,$pos,2)).substr($answer,$pos+2);
					break;
				}
			}
		}
		$answer=strtoupperRU(substr($answer,0,2)).substr($answer,2);
		
		for(;;){
			if(isPunctuation($answer[strlen($answer)-1])){
				$answer=substr($answer,0,strlen($answer)-1);
			}
			else break;
		}
		$answer=$answer.".";
		if($debug)echo "<br/>";
		echo $answer."</p><p>";
	}
}

exit;

?>
