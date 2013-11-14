<?php
/*  Base Database Class to be used by apps
 * */
require('class_config.php');

class Database {
    
    protected $_link, $_result, $_numRows, $_affectedRows;
    
    public function __construct() {
        $Config = new Config();
        $this->_link = new mysqli($Config->server, $Config->user, $Config->password, $Config->dbase);
    }
    
    public function __destruct() {
        $this->disconnect();
    }
    
    public function disconnect() {
        $this->_link->close();
    }
    
    public function query($sql) {
        $this->_result = $this->_link->query($sql);
        $this->_numRows = mysqli_num_rows($this->_result); // save num rows of result 
        $this->_affectedRows = mysqli_affected_rows($this->_link); // the num of rows affected 
    }
    
    public function numRows() {
        return $this->_numRows;
    }
    
    /*  After a select query is run, we need to be able to get the num of resultant data rows. This fun will return 
     *  an array of that resultant info. To use it, call it directly after using "this->DB->query($sql)". Have a 
     *  look at api_iprospect's getUsers() method to see it in use
     */
    public function rows() {
        $rows = array();
        for ($i=0; $i < $this->numRows(); $i++) { 
            $rows[] = mysqli_fetch_assoc($this->_result);
        }
        return $rows;
    }
	
	public function queryStatus() {
		return $this->_result;
	}
	
    public function affectedRows() {
        return ($this->_affectedRows == -1) ? 0 : $this->_affectedRows;
    }

    public function newID() {
        return mysqli_insert_id($this->_link);
    }
}
?>