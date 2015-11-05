<?php

$auth_token = 'bd1e5a35c5b22d563b8e2701021c8745';

$accounts_file = 'accounts.csv';
$accounts_handle = fopen($accounts_file, "r");

$file_count = 0;
while (false !== ($row = fgetcsv($accounts_handle, 5000))) {

	// get file list
	$url = 'https://crm.zoho.com/crm/private/json/Attachments/getRelatedRecords?authtoken='.$auth_token.'&newFormat=1&scope=crmapi&parentModule=Accounts&id='.$row[0];

	// Initialise a cURL handle
	$ch = curl_init();

	// Set any other cURL options that are required
	curl_setopt($ch, CURLOPT_HEADER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_COOKIESESSION, TRUE);
	curl_setopt($ch, CURLOPT_FRESH_CONNECT, TRUE);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_URL, $url);

	$response = curl_exec($ch);  // Execute a cURL request
	curl_close($ch);    	 // Closing the cURL handle

	// decode response
	$response = json_decode($response);

	// if results found
	if (property_exists($response->response, 'result')) {

		// set files
		$files = $response->response->result->Attachments->row;
		if (!is_array($files)) $files = array($files);

		// if files exist
		if (sizeof($files) > 0) {
			
			// create output directory for account
			$match = array(' ','.','"',"'",',','-','&','-','é');
			$replace = array('-','','','','','','and','','e');
			$output_dir = 'output/'.strtolower(str_replace($match, $replace, $row[1]));
			if (!file_exists($output_dir)) mkdir($output_dir, 0777, true);

			// iterate through files
			foreach ($files as $file) {

				$file_count++;

				$array = $file->FL;
				$file_id = $array[0]->content;
				$file_name = $array[1]->content;

				// download file
				$url = 'https://crm.zoho.com/crm/private/xml/Accounts/downloadFile?authtoken='.$auth_token.'&scope=crmapi&id='.$file_id;

				// Initialise a cURL handle
				$ch = curl_init();

				// Set any other cURL options that are required
				curl_setopt($ch, CURLOPT_HEADER, FALSE);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
				curl_setopt($ch, CURLOPT_COOKIESESSION, TRUE);
				curl_setopt($ch, CURLOPT_FRESH_CONNECT, TRUE);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
				curl_setopt($ch, CURLOPT_URL, $url);

				$response = curl_exec($ch);  // Execute a cURL request
				curl_close($ch);    	 // Closing the cURL handle

				// write file
				file_put_contents($output_dir."/".$file_name, $response, FILE_APPEND);

				// echo status
				echo "Downloaded file #".$file_count.": ".$file_name." (".$row[1].")\n";

				// sleep – per docs, we can request only 15 downloadFile calls per 5 min
				sleep(30);
			}
		}
	}
}



?>