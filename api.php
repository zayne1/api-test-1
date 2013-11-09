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
require('class/database.php');

class Entity {
	protected $DB;
	protected $JSONData;
    
    public function __construct() {
        $this->DB = new Database();
    }
	
	/* Accepts: JSON DATA
	 * Returns: 0 on fail, 1 on success
	 * 
	 * 
	 * */
	public function add($JSONData) {
		//run a rest util that converts the JSON data to an array
		// todo later: run a api util that checks the keys of an array, and tests to see if they exist in a db
		// build the query up
		// run query
		// todo later: test for query run failure
		
		$sql = 		"INSERT INTO user (title, name, surname, cellphone, email, date, address, password)
                    VALUES ('$_POST[title]', '$_POST[name]', '$_POST[surname]', '$_POST[cellphone]', '$_POST[email]', '$_POST[date]', '$_POST[address]', '$_POST[password]')";
            $this->DB->query($sql);
	}
	 
	public function view() {;} 
	public function delete() {;} 
	public function update() {;} 
	public function listAll() {;} 
} 
?>