<html lang="ru" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <title>Справочный диалоговый модуль</title>
    <script type="text/javascript" src="jquery.js"></script>
    <script type="text/javascript">
  var lastQ="";
	function addQA(data,status){
		document.getElementById("history").innerHTML='<div class="q"><p><b>Вопрос: </b>'+lastQ+'</p></div><div class="a"><p><b>Ответ: </b>'+((status=="success")?data:'статус: '+status)+'</p></div>'+document.getElementById("history").innerHTML; 
	}
	function findA(){
		lastQ=document.getElementById("Question").value;
		debugFlag=((document.getElementById("Debug").checked)?"true":"false");
		$.post("analyzer.php",{"Q":lastQ,"D":debugFlag},addQA);
	}
	function ask(lastQ){
		debugFlag=((document.getElementById("Debug").checked)?"true":"false");
		$.post("analyzer.php",{"Q":lastQ,"D":debugFlag},addQA);
	}
	
    </script>
    <style type="text/css">
    body{
	background-color:#333;
	font-family: Arial, Helvetica, sans-serif; 
	font-size: 20px; 
	color: #FFFFFF; 
    }
    a{
	color:white;
    }
    p{
	margin:0px;
	text-indent:30px;
	
    }
    input{
	font-size:20px;
	width:100%;
	margin:5px; 
	border-radius:10px;
	padding:2px 10px;
    }
    div{
	position: absolute; 
	left: 50%; 
	margin-left: -400px;
	padding: 10px; 
	background-color: #339933; 
	width: 800px; 
	text-align: center; 
	border-radius:10px;
	overflow:hidden;
    }
    td{
	text-align: center;
    }
    .a{
	position:relative;
	background-color:#dfd;
	width: 779px; 
	border:solid 1px #393;
	border-radius:0px;
	color: #333; 
	text-align:justify;
    }
    .q{
	position:relative;
	background-color:#afa;
	width: 779px; 
	border:solid 1px #393;
	border-radius:0px;
	color: #333; 
	text-align:justify;
    }
    .main{
	top:20px;
    }
    .history{
	top:350px;
    }
    </style>
</head>
    
<body>
    <div class="main" style="">
	<table width="100%">
	<tr>
	<td width="256">
		<img src="logo.png"/>
	</td>
	<td>
        <h1>Справочный диалоговый модуль</h1>
		<h2>Задайте вопрос</h2>
		<br />
		<input id="Debug" type="checkbox" style="display:inline;"/>Режим отладки
		<br />
		<input id="Question" type="text" value="Введите вопрос" x-webkit-speech="x-webkit-speech"/>
		<br />
		<input type="button" value="Задать вопрос" onclick="findA();"/>
	</td>
	</tr>
	</table>
    </div>
	<div id="history" class="history">
		Здесь отображается история вопросов и ответов
	</div>
</body>
</html>
