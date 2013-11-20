<html>
	<script type="text/javascript" src="js/jquery-1.7.1.min.js"></script>
	<script type="text/javascript">
	  
	   /* This script is used to test the JS AJAX sending of JSON data to our API */
	  
		$(document).ready(function() {
		    
		    /* Create JSON data using curly brace notation */
    		    var sTheName = 'bcbcbc'
    			var jsonObj1 = {
        						    "users": [
        						        {
        						            "name": sTheName,
        						            "surname": "eeee",
        						            "password": "mypass2"
        						        }
                                    ]
                                };
                console.log(jsonObj1);
    			var sPostData1 = JSON.stringify(jsonObj1);
			
			/* Create JSON data by creating JS objects & arrays */
    			oUser1 = new Object;
        			oUser1.name = sTheName;
        			oUser1.surname = 'wewe';
        			oUser1.password = 'xcxc';
    			
    			aUsers = new Array(aUsers);
    			
    			oData1 = new Object;
                    oData1.users = users;
    			console.log(oData1);
    			var sPostData2 = JSON.stringify(oData1);
			
            $.ajaxSetup ({ cache: false });
            
            $.ajax({ 
            	type: "POST",
            	cache: false,
            	// url: "http://localhost/api-tests/user/22.json",
            	url: "http://localhost/users",
            	data: sPostData1
            }).done(function(result) {
        		console.log(result);
                            
            }).fail(function() { 
                ;
            }).always(function() {
                ;
            });
        });
    </script>
</html>