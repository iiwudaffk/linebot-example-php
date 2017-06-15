<?php
$access_token = getenv('LINEBOT_CHANNEL_TOKEN');
$secert_channel = getenv('LINEBOT_CHANNEL_SECRET');
$linebot_url = getenv('LINEBOT_API_ENDPOINT_BASE');

// Get POST body content
$content = file_get_contents('php://input');
// Parse JSON
$events = json_decode($content, true);
// Remember last user 10 times
$userstack = array();
// Remember last text receive 10 times
$text_receive_stack = array();
// Remember last text sent 10 times
$text_sent_stack = array();
// Validate parsed JSON data
if (!is_null($events['events'])) {
	// Loop through each event
	foreach ($events['events'] as $event) {
		// Reply only when message sent is in 'text' format
		if ($event['type'] == 'message' && $event['message']['type'] == 'text') {
			// Get text sent
			$text_receive = $event['message']['text'];
			// Get replyToken
			$replyToken = $event['replyToken'];

			// Get last user
			$current_user = '';
			if($event['source']['type'] == 'user'){
				$current_user = $event['source']['userId'];				
			}
			
			
			// ** remember value ** //
			array_push($userstack,$current_user);
			if(count($userstack)>10){
				array_pop($userstack);
			}
			
			array_push($text_receive_stack,$text_receive);				
			if(count($text_receive_stack)>10){
				array_pop($text_receive_stack);
			}
			
			//regular expression
			$text = selectMode($text_receive,$current_user);
						
			array_push($text_sent_stack,$text);				
			if(count($text_sent_stack)>10){
				array_pop($text_sent_stack);
			}
						
			// Build message to reply back
			$messages = [
				'type' => 'text',
				'text' => $text
			];
			
			// Make a POST Request to Messaging API to reply to sender
			$url = 'https://api.line.me/v2/bot/message/reply';
			$data = [
				'replyToken' => $replyToken,
				'messages' => [$messages],
			];
			$post = json_encode($data);
			$headers = array('Content-Type: application/json', 'Authorization: Bearer ' . $access_token);

			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			$result = curl_exec($ch);
			curl_close($ch);

			echo $result . "\r\n";
		}
	}
}

function selectMode($text_receive,$current_user){
	$text = '';
			$us = '';
			foreach($userstack as $u)
			{
			  $us = $us.','. $u;
			}
			$us = '('.$us.')';
			
			$rs = '';
			foreach($text_receive_stack as $r)
			{
			  $rs = $rs.','. $r;
			}
			$rs = '('.$rs.')';
			
			$ss = '';
			foreach($text_sent_stack as $s)
			{
			  $ss = $ss.','. $s;
			}
			$ss = '('.$ss.')';
			$copy_sents = $text_sent_stack;
			$firstvalue = array_pop($copy_sents);
			
				//** debug case **//
			switch ($text_receive) {
				case 'user':
					$text = $current_user;
					break;
				case 'users':
					$text = $us;
					break;
				case 'textsent':
					$text = $firstvalue;
					break;
				case 'textsent2':
					$text = $ss;
					break;
				case 'textreceive':
					$text = $text_receive;
					break;
				case 'textreceive2':
					$text = $rs;
					break;
				case 'write':
					writeText($text_receive.'555');
					$text = 'write function';
					break;
				case 'read':
					$text = readText(1);
					break;
				case 'clear':
					clearFile();
					$text = 'clear file ja';
					break;
				default: 
					$text = getMessage($text_receive,$current_user);
					break;
			}
	return $text;
}

function getMessage($text_receive,$current_user){
	$result = '';
	
	if(preg_match("/กิน|ข้าว|หิว/i", $text_receive)){
		$result = getPrefix().getRestaurant().getSuffix();
	} 
	
	
	
	else {
		$result = '';
	}
	
	writeText($result);
	return $result;
}

function writeText($val){
	$myfile = fopen("log.txt", "a") or die("Unable to open file!");
	$txt = $val."\n";
	fwrite($myfile, $txt);
	fclose($myfile);
}

function readText($line){
	$t = '';
	$start = false;
	$myfile = fopen("log.txt", "r") or die("Unable to open file!");
	// Output one line until end-of-file
	while(!feof($myfile)) {		
		if($start){
			$t = $t.','.fgets($myfile);
		}else{
			$t = $t.fgets($myfile);
		}
		$start = true;		
	}
	fclose($myfile);
	$t = '('.$t.')';
	return $t;
}

function clearFile(){
	$fh = fopen( 'log.txt', 'w' );
	fclose($fh);
}

function getRestaurant(){
	$string = file_get_contents('data.json');
	$a = json_decode($string, true);
	$index = array_rand($a,1);
	$result = $a[$index];	
	return $result;
}

function getPrefix(){
	$string = file_get_contents('prefix.json');
	$a = json_decode($string, true);
	$index = array_rand($a,1);
	$result = $a[$index];	
	return $result;
}

function getSuffix(){
	$string = file_get_contents('suffix.json');
	$a = json_decode($string, true);
	$index = array_rand($a,1);
	$result = $a[$index];	
	return $result;
}

echo "OK";