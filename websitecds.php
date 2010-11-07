<?php

/******************************************************************************
* Copyright (c) 2009, http://www.webdigi.co.uk 
* Website Change Detection system
* All rights reserved.
*
* Redistribution and use in source and binary forms, with or without
* modification, are permitted provided that the following conditions are met:
*     * Redistributions of source code must retain the above copyright
*       notice, this list of conditions and the following disclaimer.
*     * Redistributions in binary form must reproduce the above copyright
*       notice, this list of conditions and the following disclaimer in the
*       documentation and/or other materials provided with the distribution.
*     * Neither the name of the <organization> nor the
*       names of its contributors may be used to endorse or promote products
*       derived from this software without specific prior written permission.
*
* THIS SOFTWARE IS PROVIDED BY http://www.webdigi.co.uk ''AS IS'' AND ANY
* EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
* WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
* DISCLAIMED. IN NO EVENT SHALL http://www.webdigi.co.uk  BE LIABLE FOR ANY
* DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
* (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
* LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
* ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
* (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
* SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*******************************************************************************/

/*************************************
 * Settings - Please EDIT values below
 **************************************/

/** 
 * Scan Password - This value has to be sent each time to run the code.
 * Please change from the default password to anything you like
 */ 
$scanPassword = "ChangeThisPasswordForSecurity";

/** 
 * Exclude File List - Important do not exclude files like PHP, ASP, JS, HTML etc
 * Seperate each entry with a semicolon ; 
 * Full filename including extension. [CASE INSENSITIVE]
 */ 
$excludeFileList = "error_log;backup.zip;"; 

/** 
 * Exclude Extension List - Important do not exclude file extensions like PHP, ASP, JS, HTML etc
 * Seperate each entry with a semicolon ; 
 * Only extension type. [CASE INSENSITIVE]
 */ 
$excludeExtensionList = "flv;log;txt"; 

/** 
 * Set emailAddressToAlert variable if you want an email alert from the server.
 */ 
$emailAddressToAlert = "";
$emailSubject = "IMPORTANT! Files in the server have changed";
$emailBody = "Hash value has changed. Please note that your hash value is no longer the same on server";

//On earlier than PHP5 will use code below to declare stripos function
if (!function_exists("stripos")) {
  function stripos($str,$needle,$offset=0)
  {
      return strpos(strtolower($str),strtolower($needle),$offset);
  }
}

/** 
 * Function which will return the hash after passing through all folders and subfolders within it. 
 * @param $dir - Starting folder to scan
 * @param $excludeFileList - List of filenames to exclude from scan
 * @param $excludeExtensionList - List of extensions to exclude from scan  
 * @return Final MD5 of all files scanned.
 */ 
function getMD5Hash($dir, $excludeFileList, $excludeExtensionList)
{
    if (!is_dir($dir))
    {
        return false;
    }
    
    $fileMD5list = array();
    $d = dir($dir);

    while (false !== ($entry = $d->read()))
    {
        if ($entry != '.' && $entry != '..')
        {
             if (is_dir($dir.'/'.$entry))
             {
                 $fileMD5list[] = getMD5Hash($dir.'/'.$entry, $excludeFileList, $excludeExtensionList);
             }
             else
             {
				if( stripos( $excludeFileList, $entry ) === false )  //dont scan files in exclude list
				{
					$extension = end(explode('.', $entry)); //get the file extension
					
					if( stripos( $excludeExtensionList, $extension ) === false  ) //dont scan extensions in exclude list
					{
						
						$fileMD5list[] = md5_file($dir.'/'.$entry); //Prepare list to MD5 only allowed
						
					}
					
				}
             }
         }
    }
	
    $d->close();
	
    return md5(implode('', $fileMD5list)); //Return final MD5 of all files
}

/**********************************************************
 * Start with logic of scanning and checking the code files
 ***********************************************************/

//Key steps in scan 
//STEP 1  - Check if the password is OK
	if (strcmp ( $_REQUEST["password"], $scanPassword )  != 0 )
	{
		echo "Failed to start as password is incorrect!";
		exit(0);
	}


//STEP 2  - Check if user has sent the myhash (otherwise treat as first run and send hash to user)
	if( $_REQUEST["myhash"] == "")
	{
		//make the hash and send to user
		$hashGenerated = getMD5Hash(".", $excludeFileList, $excludeExtensionList);
		echo "Current HASH:$hashGenerated";
		echo "<BR>Please use above with myhash get parameter in the next call if you want email alerts";
		exit(0);			
	}


//STEP 3 - If user has sent hash then compare with a new hash 
//         If the values are different then raise an ALARM

	$hashGenerated = getMD5Hash(".", $excludeFileList, $excludeExtensionList);

	if( strcmp ( $_REQUEST["myhash"], $hashGenerated )  != 0 )
	{
	
		//ALERT. CODE FILES IN THE SERVER HAVE CHANGED.
		//PERFORM WHATEVER ALERT HERE YOU WANT. EMAIL, SMS !!
		
		//A. MAIL
		if($emailAddressToAlert <> ""){
			
			//Add the new hash value to the email
			$emailBody = $emailBody . " New HASH:$hashGenerated";
		
			mail($emailAddressToAlert, $emailSubject, $emailBody); //Simple mail function for alert.
		
		}
	
		//B. RESPOND ON SCREEN TO USER
			echo "IMPORTANT!<BR>HASH CHECK has failed as the codebase has been altered.<BR>New HASH:$hashGenerated";
	}else{
	
		//HASH VALUE IS OK
			echo "HASH:$hashGenerated ";
			echo "<BR>All good! No change to the codebase is detected";
	
	}
	

?>