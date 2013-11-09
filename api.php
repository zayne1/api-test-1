<?php
echo 'x';
var_dump($_SERVER['REQUEST_URI']);
$request_parts = explode('/', $_SERVER['REQUEST_URI']);
var_dump($request_parts);
$file_type     = $_GET['type'];
var_dump($file_type);
echo basename($_SERVER['REQUEST_URI']);
echo '---'.$request_parts['user'];
echo '==='.$_SERVER['REQUEST_METHOD'];

switch($request_parts) {
    case ($request_parts[2] == 'user'):
        echo 'zzz';
        break;
    case ($request_parts[2] == 'man'):
        echo 'yyy';
        break;
    default:
        echo $request_parts;
}

$request_method = $_SERVER['REQUEST_METHOD'];
echo '*************'.$request_method;
$data = array();
switch ($request_method)  
    {  
        case 'GET':  
            $data = $_GET;  
            break;  
        case 'POST':  
            $data = $_POST;  
            break;  
        case 'PUT':  
            parse_str(file_get_contents('php://input'), $put_vars);
            $data = $put_vars;  
            break;  
    }  
	

	echo var_dump($data);
	
	echo '\r\n<br>';
	echo '\r\n<br>';
	// $x = json_decode($data);
	// echo var_dump($x);
	echo $data['name'];
	
	echo '\r\n<br>';
	echo '\r\n<br>';
	echo var_dump($_POST);
	
	// echo 'DATA2:'. ($data['name']);

?>