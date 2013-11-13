<?php
/*
 * Big idea: we spilt the system into 3 main sectiont: RESTful application in front, API Class behind that, and DB class behind that.
 * 
 * 
 * API class
 *         user
 *             register
 *             authenticate
 *             add
 *             view
 *            del (low prio)
 *             update (low prio)
 *             list all (low prio) 
 
 *             
 *         topics
 *             add
 *             view
 *             del (low prio)
 *             update (low prio)
 *             list all (low prio)
 *                 comments
 *                     add
 *                     view
 *                     delete
 *                         reply
 *                             view
 *                             add
 * 
 *
 * JSON_Util class
 *         get_request_method($data) // dynamically named. uses 
 *         put_request_method($data) // dynamically named
 *         post_request_method($data) // dynamically named 
 *         del_request_method($data) // dynamically named
 *         process_request(request method $request_method)
 *     
 * 
 * 
 * 
 * psuedo code:
 *     if we have request data, 
 *         get the url ( eg http://app/users/register )
 *         instead of using a big switch case of url mappings to decide what to do, lets map our php class objects and methods to mirror our urls. ie:
 *                 http://app/users/register will map to $Users->register(). Try to use dynamically named vars.
 *         if req_method = POST, run the add method of the current 'collection' url (the first segment, eg user)
 *             remember that user/register on the url must map to user/add in backend
 *             user/authenticate = post user name and password to /user, and recieve back a user exists msg   
 * 
 * if no request data
 *        if req_method = GET,
 *             if more than 2 url params are loaded, bomb out with err
 *             if only a collection url is loaded, return the 'list all'
 *             if we have 2 urls
 *                     view(item)
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
    
    /* in:  1)  Collection part of URL
     *      2)  Object, if it a single JSON obj was sent from client
     *          Array containing Objects, if multiple objects were sent from client
     * 
     *  
     * out: 0 on fail, 1 on success
     * 
     * Used for POST
     * */
    public function add($sCollection, $oJSON) {
        //run a rest util that converts the JSON data to an array. Rest util should be able to accept an array as well as obj
        // todo later: run a api util that checks the keys of an array, and tests to see if they exist in a db
        // build the query up
        // run query
        // todo later: test for query run failure
        $sQry = '';
        
        if ( isset($oJSON->{$sCollection}[0]) ) { // If the JSON obj name == the collection url
            $arrData    = (array)$oJSON->{$sCollection}[0]; // Return 0th array (who's val is an obj) from objects member (who's val should be the Collection url) 
            $sFields    = '';
            $sVals      = '';
            
            foreach ($arrData as $key => $value) { // TODO: This currently makes everything a str via the quotes. you should cater for non-strings too
                $sFields.= $key . ',';
                $sVals.= '"' . $value . '",';
            }
            
            $sFields    = substr_replace($sFields, ')', -1);
            $sVals      = substr_replace($sVals, ')', -1);
            $sQry       = 'INSERT into ' . $sCollection . ' (' . $sFields . ' VALUES (' . $sVals;
            $this->DB->query($sQry);
            return $this->DB->queryStatus();
        } else {
            //TODO: this runs if the JSON obj name doesnt equal the collection url. make a good err mesg and return a http status code
        }
    }

    /* in:  1)  Collection part of URL    
     *      2)  PHP Object containing Data for users:name and users:password 
     * 
     * out: authentication status message
     * 
     * Used for POST
     * name and password are sent to the server inside a users JSON obj. If user exists we recieve a positive reply
     * */
    public function authenticate($sCollection, $oJSON) {
        // todo later: test for query run failure
        $result     = 0;
        $sQry       = '';
        
        if ( isset($oJSON->{$sCollection}[0]) ) { // If the JSON obj name == the collection url
            $arrData    = (array)$oJSON->{$sCollection}[0]; // Return 0th array (who's val is an obj) from objects member (who's val should be the Collection url) 
            $sQry       =   'SELECT * FROM ' . $sCollection . 
                            ' WHERE name = "' . $oJSON->users[0]->name . '" AND password = "' . $oJSON->users[0]->password . '"';
            $this->DB->query($sQry); // TODO: do err checking
            $result = $this->DB->numRows();
        } else {
            //TODO: this runs if the JSON obj name doesnt equal the collection url. make a good err mesg and return a http status code
        }
        return $result;
    }

    /* in:  1)  Collection part of URL
     *      2)  Item part of URL    
     *  
     * out: JSON data for current Item
     * 
     * Used for GET
     * Returns data for a an single item in JSON format
     * */
    public function view($sCollection, $sItem) {
        // todo later: test for query run failure
        $result = NULL;
        $sQry   =   'SELECT * FROM ' . $sCollection . 
                    ' WHERE id = ' . $sItem . '';
        $this->DB->query($sQry); // TODO: do err checking
        $result = RESTUtil::arrayToJSONString($this->DB->rows() , $sCollection);
        return $result;
    } 
    
    /* in:  1)  Collection part of URL
     *      2)  Item part of URL
     * 
     * out: Number of affected rows
     * 
     */
    public function delete($sCollection, $sItem) {
        $sQry = 'DELETE FROM ' . $sCollection . 
                ' WHERE id = ' . $sItem . '';
        $this->DB->query($sQry);
        return $this->DB->affectedRows();
    } 
    
    /* in:  1)  PHP Object containing all item's data
     *      2)  Collection part of URL
     *      3)  Item part of URL
     * 
     * out  Affected rows
     * 
     */
    public function update($oJSON, $sCollection, $sItem) {
        // todo later: test for query run failure
        // todo later: Currently we need to send all the item's data. Improve on this by making it update only what we send.
        $result = 0;
        $sQry   = '';
        if ( isset($oJSON->{$sCollection}[0]) ) { // If the JSON obj name == the collection url
            $arrData        = (array)$oJSON->{$sCollection}[0]; // Return 0th array (who's val is an obj) from objects member (who's val should be the Collection url) 
            $sFields        = '';
            $sVals          = '';
            $sUpdateString  = '';
            
            foreach ($arrData as $key => $value) { // TODO: This currently makes everything a str via the quotes. you should cater for non-strings too
                $sUpdateString.=  $key . ' = "' . $value . '", '; 
            }
            $sUpdateString  = substr_replace($sUpdateString, '', -2);
            $sQry           =   'UPDATE ' . $sCollection . '
                                SET ' . $sUpdateString .'  
                                WHERE id = ' . $sItem;
            $this->DB->query($sQry); // TODO: do err checking
            $result = $this->DB->affectedRows();
        } else {
            //TODO: this runs if the JSON obj name doesnt equal the collection url. make a good err mesg and return a http status code
        }
        return $result;
    } 

    /* in:  1)  Collection part of URL
     *      2)  result limiter     
     *  
     * out: JSON data for current Collection
     * 
     * Used for GET
     * Retrives all data for the current Collection
     * */
    public function listAll($sCollection, $limit = 20) {
        // todo later: test for query run failure
        //TODO: do research on the best way to link the user to more results 
        $result =   NULL;
        $sQry   =   'SELECT * FROM ' . $sCollection . 
                    ' LIMIT 0 , ' . $limit . '';
        $this->DB->query($sQry); // TODO: do err checking
            
        $result = RESTUtil::arrayToJSONString($this->DB->rows() , $sCollection);
        return $result;
    }
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
        $sCollection    = self::getURICollection();
        $oJson          = '';
        
        if ( isset($JSONPOST) && !empty($JSONPOST) ) { // if JSON Obj posted via ajax POST
            $sJson = stripslashes($JSONPOST[$sCollection]);
            $oJson = json_decode($sJson);
        } elseif ( (file_get_contents('php://input')) ) { // if JSON data sent as raw input
            $post_vars = (file_get_contents('php://input'));  
            $oJson = json_decode($post_vars);
        }
        return $oJson;
    }
    
    /* in: 
     * out: lowercase extension eg '.json'
     * */
    public static function getURIExtension() {
        // TODO: clean up
        $ext = NULL;
        if ( strpos($_SERVER['REQUEST_URI'], '.') ) {
            $ext = strtolower(substr(basename($_SERVER['REQUEST_URI']), strpos(basename($_SERVER['REQUEST_URI']), '.')) ); // eg '.json'
        }
        return $ext;
    }
    
    /* in: 
     * out: lowercase data type eg 'json'
     * */
    public static function getDataType() {
        // TODO: clean up
        $sExt   = 'json'; //default
        $sBase  = self::getURIExtension();
        
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
        return ( isset($request_parts[1]) && $request_parts[1] !=='') ? $request_parts[1] : 0;
    }

    /* in:
     * out: first 'Item' part of URL, eg for the URL www.myapp.com/user/244.json we would return '244' 
     * */
    public static function getURIItem1() {
        $sItem1         = 0;
        $request_parts  = explode('/', $_SERVER['REQUEST_URI']);
        if ( isset($request_parts[1]) ) {
            $sItem1 = str_replace(RESTUtil::getURIExtension(), '', $request_parts[1]); //remove extension, eg '33.json' will change to '33'
        }
        return $sItem1;
    }

    /* in:
     * out: second 'Item' part of URL, eg for the URL www.myapp.com/user/comment/reply.json we would return 'reply' 
     * */
    public static function getURIItem2() {
        $sItem2         = 0;    
        $request_parts  = explode('/', $_SERVER['REQUEST_URI']);
        if ( isset($request_parts[2]) ) {
            $sItem2 = str_replace(RESTUtil::getURIExtension(), '', $request_parts[2]); //remove extension, eg '33.json' will change to '33'
        }
        return $sItem2;
    }

    /* in:
     * out: third 'Item' part of URL, eg for the URL www.myapp.com/user/comment/reply/22.json we would return '22' 
     * */
    public static function getURIItem3() {
        $sItem3         = 0;    
        $request_parts  = explode('/', $_SERVER['REQUEST_URI']);
        if ( isset($request_parts[3]) ) {
            $sItem3 = str_replace(RESTUtil::getURIExtension(), '', $request_parts[3]); //remove extension, eg '33.json' will change to '33'
        }
        return $sItem3;
    }
    
    /* in:
     * out: var containing request data that was sent to server
     * 
     * Made to return data wether it is sent as a POST, or as raw input
     * */
    public function getRequestData() {
        $data = NULL;
        if ( $this->sRequestType == 'post' ) {
            if ( isset($_POST) && !empty($_POST) ) { // if JSON Obj posted via ajax POST
                $data = $_POST;
            } elseif ( file_get_contents('php://input') )  { // if JSON data sent as raw input
                $data = file_get_contents('php://input');    
            }
        } elseif ( $this->sRequestType == 'put' ) { // if JSON data sent as raw input
            $data = file_get_contents('php://input'); 
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
                if ( (self::getURICollection() === 'users') && (self::getURIItem2() === 'authenticate') ) { // POST /users/authenticate
                    // try to authenticate
                    $sCollection    = self::getURICollection();
                    $oJSON          = self::JSONtoPHPObj($_POST);
                    $myEntity->authenticate($sCollection, $oJSON) ? ( self::sendResponse(200, 'User exists') ) : ( self::sendResponse(404, 'User Not Found') );
                } else {
                    // POST /users
                    $sCollection    = self::getURIItem2() ? self::getURIItem2() : self::getURICollection(); // if app.com/comments, then use 'comments'. If app.com/comments/reply, then use 'reply'
                    $oJSON          = self::JSONtoPHPObj($_POST);
                    $myEntity->add($sCollection, $oJSON) ? ( self::sendResponse(200, 'Item created') ) : ( self::sendResponse(403, 'Unable to create Item') ); 
                }
            } elseif ( $this->sRequestType == 'put' ) {
                if ( self::getURIItem1() ) { // PUT /users/22
                    $sCollection    = self::getURIItem3() ? self::getURIItem2() : self::getURICollection(); // If app.com/comments/reply, then use 'reply'. If app.com/comments, then use 'comments'. 
                    $sItem          = self::getURIItem3() ? self::getURIItem3() : self::getURIItem2();
                    $oJSON          = (json_decode($this->RequestData));
                    $myEntity->update($oJSON, $sCollection, $sItem) ? ( self::sendResponse(200, 'Item updated') ) : ( self::sendResponse(304, 'Item not updated') );
                }
            }
        } else {
            if ( $this->sRequestType == 'get' ) {
                if ( !self::getURIItem2() ) { // if we only have a Colection URL and nothing else eg GET /users/
                    $sCollection    = self::getURICollection(); // if app.com/comments, then use 'comments'. If app.com/comments/reply, then use 'reply'
                    $result         = $myEntity->listAll($sCollection); // returns NULL if no data found 
                    $result ? ( self::sendResponse(200, $result) ) : ( self::sendResponse(404, 'Could not find Item') );
                } else { // if we have more than 1 URL param eg: GET /users/22 or GET /comments/replies or GET comments/replies/11
                    $sCollection    = self::getURIItem3() ? self::getURIItem2() : self::getURICollection(); // if app.com/comments, then use 'comments'. If app.com/comments/reply, then use 'reply'
                    $sItem          = self::getURIItem3() ? self::getURIItem3() : self::getURIItem2();
                    
                    $result         = $myEntity->view($sCollection, $sItem);//die(var_dump($result)); // returns NULL if no data found
                    $result         = !($result) ? $myEntity->listAll($sItem) : $result; // if result is empty (could not get singular data item), try listAll
                    
                    $result ? ( self::sendResponse(200, $result) ) : ( self::sendResponse(404, 'Could not find Item') );
                }
            } elseif ( $this->sRequestType == 'delete' ) {
                if ( self::getURIItem1() ) {
                    $sCollection   = self::getURIItem3() ? self::getURIItem2() : self::getURICollection(); // If app.com/comments/reply, then use 'reply'. If app.com/comments, then use 'comments'. 
                    $sItem         = self::getURIItem3() ? self::getURIItem3() : self::getURIItem2();
                    $myEntity->delete($sCollection, $sItem) ? ( self::sendResponse(200, 'Item Deleted') ) : ( self::sendResponse(403, 'Could not delete Item') );
                }
            }
        }
    }
    
    /* in: an Array 
     * out: json string
     * */    
    public static function arrayToJSONString($theArray, $sTheCollection = '') {
        $result = NULL;
        if ( !empty($theArray) ) {
            $sCollection                 = empty($sTheCollection) ? self::getURICollection() : $sTheCollection;
            $aNewArr[$sCollection]       = $theArray;
            $oNewObj                     = (object)$aNewArr;
            $result                      = json_encode($oNewObj);
        }
        return $result;
    }
    
    public static function sendResponse($statusCode = 200, $responseBody = ''){
        header('HTTP/1.1 ' . $statusCode . ' ' . self::getStatusCodeMessage($statusCode)  );
        header('Content-type: ' . 'application/' . self::getDataType());
        echo $responseBody;
    }
    
    public static function getStatusCodeMessage($statusCode) {
        $codes = Array(  
            200 => 'OK',  
            201 => 'Created',  
            202 => 'Accepted',  
            203 => 'Non-Authoritative Information',  
            204 => 'No Content',  
            300 => 'Multiple Choices',  
            302 => 'Found',  
            304 => 'Not Modified',  
            400 => 'Bad Request',  
            401 => 'Unauthorized',  
            403 => 'Forbidden',  
            404 => 'Not Found',  
            405 => 'Method Not Allowed',  
            406 => 'Not Acceptable',  
            409 => 'Conflict',  
            410 => 'Gone',  
            415 => 'Unsupported Media Type',  
            500 => 'Internal Server Error',  
            501 => 'Not Implemented'
        );  
        return (isset($codes[$statusCode])) ? $codes[$statusCode] : '';  
    }  
    
    
}
/*** RESTUtils file section END  ***/








/*** API file section START  ***/



/*** API file section END  ***/






/* Tests start */
/*
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

$e = new Entity;*/

// $e->add();




$REST = new RESTUtil;
$sEntityName = RESTUtil::getURICollection();
    // ${$sEntityName . '_class'} = new Entity();
$sEntity = new Entity;
$REST->process($sEntity);


/* Tests end */


?>