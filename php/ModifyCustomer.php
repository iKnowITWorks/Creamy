<?php
/**
	The MIT License (MIT)
	
	Copyright (c) 2015 Ignacio Nieto Carvajal
	
	Permission is hereby granted, free of charge, to any person obtaining a copy
	of this software and associated documentation files (the "Software"), to deal
	in the Software without restriction, including without limitation the rights
	to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
	copies of the Software, and to permit persons to whom the Software is
	furnished to do so, subject to the following conditions:
	
	The above copyright notice and this permission notice shall be included in
	all copies or substantial portions of the Software.
	
	THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
	IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
	FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
	AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
	LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
	OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
	THE SOFTWARE.
*/

// dependencies
require_once('CRMDefaults.php');
require_once('LanguageHandler.php');
require_once('DbHandler.php');
require('Session.php');
// variables
$lh = \creamy\LanguageHandler::getInstance();
$user = \creamy\CreamyUser::currentUser();

// check required fields
$validated = 1;
if (!isset($_POST["name"])) {
	$validated = 0;
}
if (!isset($_POST["customer_type"])) {
	$validated = 0;
}
if (!isset($_POST["customerid"])) {
	$validated = 0;
}

if ($validated == 1) {
	$db = new \creamy\DbHandler();

	// get name (mandatory), customer id and customer type
	$name = $_POST["name"];
	$name = stripslashes($name);
	$name = $db->escape_string($name);
	$customerid = $_POST["customerid"];
	$customerid = stripslashes($customerid);
	$customerid = $db->escape_string($customerid);
	$customerType = $_POST["customer_type"];
	$customerType = stripslashes($customerType);
	$customerType = $db->escape_string($customerType);
	$createdByUser = $user->getUserId();
	
	// email
	$email = NULL; if (isset($_POST["email"])) { 
		$email = $_POST["email"]; 
		$email = stripslashes($email);
		$email = $db->escape_string($email);
	}
	// phone
	$phone = NULL; if (isset($_POST["phone"])) { 
		$phone = $_POST["phone"];
		$phone = stripslashes($phone);
		$phone = $db->escape_string($phone); 
	}
	// mobile phone
	$mobile = NULL; if (isset($_POST["mobile"])) { 
		$mobile = $_POST["mobile"];
		$mobile = stripslashes($mobile);
		$mobile = $db->escape_string($mobile); 
	}
	// id_number
	$id_number = NULL; if (isset($_POST["id_number"])) { 
		$id_number = $_POST["id_number"]; 
		$id_number = stripslashes($id_number);
		$id_number = $db->escape_string($id_number);
	} 
	// address
	$address = NULL; if (isset($_POST["address"])) { 
		$address = $_POST["address"]; 
		$address = stripslashes($address);
		$address = $db->escape_string($address);
	}
	
	// city
	$city = NULL; if (isset($_POST["city"])) { 
		$city = $_POST["city"]; 
		$city = stripslashes($city);
		$city = $db->escape_string($city);
	}
	
	// state
	$state = NULL; if (isset($_POST["state"])) { 
		$state = $_POST["state"]; 
		$state = stripslashes($state);
		$state = $db->escape_string($state);
	}
	
	// ZIP code
	$zipcode = NULL; if (isset($_POST["zipcode"])) { 
		$zipcode = $_POST["zipcode"]; 
		$zipcode = stripslashes($zipcode);
		$zipcode = $db->escape_string($zipcode);
	}
	
	// country
	$country = NULL; if (isset($_POST["country"])) { 
		$country = $_POST["country"]; 
		$country = stripslashes($country);
		$country = $db->escape_string($country);
	}
	
	// website
	$website = NULL; if (isset($_POST["website"])) { 
		$website = $_POST["website"]; 
		$website = stripslashes($website);
		$website = $db->escape_string($website);
	}	
	
	// birthdate
	$birthdate = NULL; if (isset($_POST["birthdate"])) { 
		$birthdate = $_POST["birthdate"]; 
		$birthdate = stripslashes($birthdate);
		$birthdate = $db->escape_string($birthdate);
	}

	// marital status
	$maritalstatus = 0; if (isset($_POST["maritalstatus"])) { 
		$maritalstatus = $_POST["maritalstatus"]; 
		$maritalstatus = stripslashes($maritalstatus);
		$maritalstatus = $db->escape_string($maritalstatus);
	}
	if ($maritalstatus < 1 || $maritalstatus > 5) $maritalstatus = 0;
	
	// gender
	$gender = NULL; if (isset($_POST["gender"])) { 
		$gender = $_POST["gender"]; 
		$gender = stripslashes($gender);
		$gender = $db->escape_string($gender);
	}
	if ($gender < 0 || $gender > 1) $gender = NULL;
	
	// product type
	$productType = NULL; if (isset($_POST["productType"])) { 
		$productType = $_POST["productType"]; 
		$productType = stripslashes($productType);
		$productType = $db->escape_string($productType);
	}
	
	// notes
	$notes = NULL; if (isset($_POST["notes"])) { 
		$notes = $_POST["notes"]; 
		$notes = stripslashes($notes);
		$notes = $db->escape_string($notes);
	}
	
	// no enviar email
	$donotsendemail = 0; if (isset($_POST["donotsendemail"])) { 
		$donotsendemail = 1;
	}

	// modify customer
	$result = $db->modifyCustomer($customerType, $customerid, $name, $email, $phone, $mobile, $id_number, $address, $city, $state, $zipcode, $country, $birthdate, $maritalstatus, $productType, $donotsendemail, $createdByUser, $gender, $notes, $website);
	// return result
	if ($result === true) { ob_clean(); print CRM_DEFAULT_SUCCESS_RESPONSE; }
	else { ob_clean(); $lh->translateText("unable_modify_customer"); } 
} else { ob_clean(); $lh->translateText("some_fields_missing"); }
?>