<?php
require('class_database.php');

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
     * out: 0 on fail, the newly created ID field on success
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
            return $this->DB->newID();
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


?>