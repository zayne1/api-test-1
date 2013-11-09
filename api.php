<?php
/*
 * Big idea: we spilt the system into 3 main sectiont: RESTful application in front, API Class behind that, and DB class behind that.
 * 
 * 
 * API class
 * 		user
 * 			register
 * 			authenticate
 * 			add
 *	 		view
 *			del (low prio)
 * 			update (low prio)
 * 			list all (low prio) 
 
 * 			
 * 		topics
 * 			add
 * 			view
 *	 		del (low prio)
 * 			update (low prio)
 * 			list all (low prio)
 * 				comments
 * 					add
 *	 				view
 * 					delete
 * 						reply
 * 							view
 * 							add
 * 
 *
 * JSON_Util class
 * 		get_request_method($data) // dynamically named. uses 
 * 		put_request_method($data) // dynamically named
 * 		post_request_method($data) // dynamically named 
 * 		del_request_method($data) // dynamically named
 * 		process_request(request method $request_method)
 * 	
 * 
 * 
 * 
 * psuedo code:
 * 	if we have request data, 
 * 		get the url ( eg http://app/users/register )
 * 		instead of using a big switch case of url mappings to decide what to do, lets map our php class objects and methods to mirror our urls. ie:
 * 			    http://app/users/register will map to $Users->register(). Try to use dynamically named vars.
 * 		if req_method = POST, run the add method of the current 'collection' url (the first segment, eg user)
 * 			remember that user/register on the url must map to user/add in backend
 * 			user/authenticate = post user name and password to /user, and recieve back a user exists msg   
 * 
 * if no request data
 *		if req_method = GET,
 * 			if more than 2 url params are loaded, bomb out with err
 * 			if only a collection url is loaded, return the 'list all'
 *	 		if we have 2 urls
 * 					view(item)
 * 		
 * */
 
/*** API file section start  ***/ 
require('class/database.php');

/* The Entity Class is meant to be a parent to our 'entitys' eg User, Topic, Comment, etc.
 * The idea is to have a ready made base that they can inherit from, which already contains lots of the functionality
 * */
class Entity {
	protected $DB;
	protected $JSONData;
    
    public function __construct() {
        $this->DB = new Database();
    }
	
	/* in: 	Object, if it a single JSON obj was sent from client
	 *		Array containing Objects, if multiple objects were sent from client
	 *  
	 * out: 0 on fail, 1 on success
	 * 
	 * */
	public function add() {
		//run a rest util that converts the JSON data to an array. Rest util should be able to accept an array as well as obj
		// todo later: run a api util that checks the keys of an array, and tests to see if they exist in a db
		// build the query up
		// run query
		// todo later: test for query run failure
		$sQry = '';
		$sCollection = RESTUtil::getURICollection();
		$arrData = RESTUtil::JSONtoPHPObj($_POST);
		$arrData = (array)$arrData;
		
		$sFields = '';
		$sVals = '';
		
		foreach ($arrData as $key => $value) { // TODO: This currently makes everything a str via the quotes. you should cater for non-strings too
			$sFields.= $key . ',';
			$sVals.= '"' . $value . '",';
		}
		
		$sFields = substr_replace($sFields, ')', -1);
		$sVals = substr_replace($sVals, ')', -1);
		
		$sQry = 'INSERT into ' . $sCollection . ' (' . $sFields . ' VALUES (' . $sVals;
        //$this->DB->query($sQry); // TODO: do err checking
	}
	 
	public function view() {;} 
	public function delete() {;} 
	public function update() {;} 
	public function listAll() {;} 
} 

/*** API file section END  ***/




/*** RESTUtils file section start  ***/
class RESTUtil {
	private $sRequestType;
	private $RequestData;
	
	function __construct($sRequestType = 'get') {
		$this->sRequestType = strtolower($_SERVER['REQUEST_METHOD']);
		$this->RequestData = $this->getRequestData();
	}
	
	/* in: JSON object from client
	 * out: PHP Object
	 * 
	 * Made to handle JSON data that is sent as a JSON object via an ajax POST, or as raw input  
	 * */
	public static function JSONtoPHPObj($JSONPOST) {
		$sCollection = self::getURICollection();
		$oJson = '';
		
		if ( isset($JSONPOST) && !empty($JSONPOST) ) { // if JSON Obj posted via ajax POST
		    $sJson = stripslashes($JSONPOST[$sCollection]);
		    $oJson = json_decode($sJson);
		} elseif ( (file_get_contents('php://input')) ) { // if JSON data sent as raw input
		    $post_vars = json_decode(file_get_contents('php://input'));
        	$oJson = $post_vars;
		}
		return $oJson;
	}
	
	/* in: 
	 * out: lowercase extension datatype
	 * */
	public static function getURIExtension() {
		// TODO: clean up
		$sBase = strtolower(basename($_SERVER['REQUEST_URI']));
		$sExt = 'json'; // Use JSON as default
		
		if ( strstr($sBase, 'json') ) {
			$sExt = 'json';
		} elseif ( strstr($sBase, 'xml') ) {
			$sExt = 'xml';
		} elseif ( strstr($sBase, 'rss') ) {
			$sExt = 'rss';
		}
		return $sExt;
	}
	
	/* in:
	 * out: 'Collection' part of URL, eg for the URL www.myapp.com/user/244.json we would return 'user' 
	 * */
	public static function getURICollection() {
		$request_parts = explode('/', $_SERVER['REQUEST_URI']);
		return ( isset($request_parts[2]) && $request_parts[2] !=='') ? $request_parts[2] : 0;
	}

	/* in:
	 * out: 'Item' part of URL, eg for the URL www.myapp.com/user/244.json we would return '244' 
	 * */
	public static function getURIItem() {
		$request_parts = explode('/', $_SERVER['REQUEST_URI']);
		return ( isset($request_parts[3]) && $request_parts[3] !=='') ? $request_parts[3] : 0;
	}
	
	/* in:
	 * out: var containing request data that was sent to server
	 * 
	 * Made to return data wether it is sent as a POST, or as raw input
	 * */
	public function getRequestData() {
		$data = '';
		if ( $this->sRequestType == 'post' ) {
			
			if ( isset($_POST) && !empty($_POST) ) { // if JSON Obj posted via ajax POST
				$data = $_POST;
			} elseif ( file_get_contents('php://input') )  { // if JSON data sent as raw input
				$data = json_decode(file_get_contents('php://input'));	
			}
			
		} elseif ( $this->sRequestType == 'put' ) {
			 parse_str(file_get_contents('php://input'), $put_vars);
            $data = $put_vars; 
		}
		return $data;
	}
	
	/* in: an object of class Entity (eg a User)
	 * out: 
	 * 
	 * decides which method will be run for the entity 
	 * */
	public function process(Entity $myEntity) {
		if ( isset($this->RequestData) && !empty($this->RequestData) ) {
			if ( $this->sRequestType == 'post' ) {
			$myEntity->add();	
			} elseif ( $this->sRequestType == 'delete' ) {
				;//TODO
			}
		} else {
			if ( $this->sRequestType == 'get' ) {
				;//TODO
			}
		}
	}
	
	
}
/*** RESTUtils file section END  ***/








/*** API file section START  ***/



/*** API file section END  ***/






/* Tests start */
class d {
		public $age = 0;
		public $title = 0;
	}
	
	$d1 = new d;
	$d2 = new d;
	$d1->age = 11;
	$d1->title = sir;
	$d2->age = 22;
	$d2->title = mam;

$e = new Entity;
// $e->add();




$REST = new RESTUtil;
$sEntityName = RESTUtil::getURICollection();
	// ${$sEntityName . '_class'} = new Entity();
$sEntity = new Entity;
$REST->process($sEntity);


/* Tests end */


?>