<?php
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
                    $newID          = $myEntity->add($sCollection, $oJSON); 
                    $newID ? ( self::sendResponse(200, $newID) ) : ( self::sendResponse(403, 'Unable to create Item') ); 
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
                    
                    $result         = $myEntity->view($sCollection, $sItem); // returns NULL if no data found
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
?>