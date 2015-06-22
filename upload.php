<?php

/*
*	upload.php | Script to handle file uploads
*	Created On: 13 June, 2015
*	Derived From: http://abandon.ie/notebook/simple-file-uploads-using-jquery-ajax
*/

// function to generate random string
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

// initialize array
$data = array();

if(isset($_GET['files'])){

	// initialize var to keep track of errors  
    $error = false;
    $files = array();

	// set upload directory
	// NOTE: Change permissions of upload dir. to allow file uploads 
    $uploaddir = 'data/uploads/';
    
    // loop through selected files
    foreach($_FILES as $file){
    
    	// check extension of file
    	$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    	
    	if( $ext == 'json' ){
    
			// create directory to store file
			$dir_name = generateRandomString(10);
			mkdir("data/uploads/$dir_name");
	
			if(move_uploaded_file($file['tmp_name'], $uploaddir.$dir_name.'/'.basename($file['name']))){
				$files[] = $uploaddir.$dir_name.'/'.$file['name'];
			}else{
				$error = true;
			}
			
		}else{
			$error = true;
		}
    }
    $data = ($error) ? array('error' => 'There was an error uploading your files') : array('files' => $files);
}else{
    $data = array('success' => 'Form was submitted', 'formData' => $_POST);
}

echo json_encode($data);

?>