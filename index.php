<?php
header('Access-Control-Allow-Origin: *');
# First of all we read the clientid and clientsecret from the config.ini
# Make sure that the config.ini is protected from unauthorized access
$ini_array = parse_ini_file("config.ini");
define('CLIENTID', $ini_array['clientid']);
define('CLIENTSECRET', $ini_array['clientsecret']);
define('CHANNELNAME', $ini_array['channelname']);
define('REDIRECT_URI', $ini_array['redirect_uri']);
define('CODE', $ini_array['code']);
define('ACCESSTOKEN', getAccessToken());
$ini_array = parse_ini_file("config.ini");
define('USER_ACCESSTOKEN', $ini_array['USER_ACCESSTOKEN']);
define('USER_REFRESHTOKEN', $ini_array['USER_REFRESHTOKEN']);



# Currently all functionality is encapsulated in distinct functions
# To get the relevant data the name of the function needs to be supplied as param
# so for example twitch.php&function=getStreamData
# We will use an RMI invocation afterwards if the function is included in the valid functions array
# or abort if it's not included
# So make sure to add your functions to the array if you add new ones

$validFunctions = array("info","topclips","user","latestfollowers","followercount","auth","refreshToken","subscriptions","latestSubscriber", "subscriptionCount");
$functName = $_REQUEST['f'];

if(in_array($functName,$validFunctions))
{
   
 $functName();
}
else{
    echo "You don't have permission to call that function so back off!";
    exit();
}

function getAccessToken()
{
$url = 'https://id.twitch.tv/oauth2/token';
$data = array('client_id' => CLIENTID, 'client_secret' => CLIENTSECRET, 'grant_type'=>'client_credentials');


$options = array(
    'http' => array(
        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
        'method'  => 'POST',
        'content' => http_build_query($data)
    )
);
$context  = stream_context_create($options);
$result = file_get_contents($url, false, $context);
if ($result === FALSE) 
{ /* Handle error */ }

$data = json_decode($result); 
return $data->access_token; 
}
function getAccessTokenFromCode()
{

$url = 'https://id.twitch.tv/oauth2/token';
$data = array('client_id' => CLIENTID, 'client_secret' => CLIENTSECRET,'grant_type' => 'authorization_code','code'=>CODE,'redirect_uri'=>REDIRECT_URI);


$options = array(
    'http' => array(
        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
        'method'  => 'POST',
        'content' => http_build_query($data)
    )
);
$context  = stream_context_create($options);
$result = file_get_contents($url, false, $context);

if ($result === FALSE) 
{ /* Handle error */ }
echo $result;
$resultdata = json_decode($result); 

global $USER_ACCESSTOKEN, $USER_REFRESHTOKEN;
if($resultdata->access_token === NULL) 
{

}
else
{
    config_set("config.ini","USER_ACCESSTOKEN",$resultdata->access_token );
    config_set("config.ini","USER_REFRESHTOKEN",$resultdata->refresh_token );
    config_set("config.ini","UPDATED",time() );
}



return $result; 
}
function validateUserAccestoken()
{
$ch = curl_init();


$accesstoken =USER_ACCESSTOKEN;
curl_setopt($ch, CURLOPT_URL, "https://id.twitch.tv/oauth2/validate");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

$headers = array();
$headers[] = "Authorization: Bearer {$accesstoken}";
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$result = curl_exec($ch);
if (curl_errno($ch)) {
    echo 'Error:' . curl_error($ch);
}
curl_close($ch);
echo $result;
return $result;
}
function auth()
{
$url = 'https://id.twitch.tv/oauth2/authorize';
$data = array('client_id' => CLIENTID,'redirect_uri'=>REDIRECT_URI, 'response_type' => 'code','scope' => 'channel:read:subscriptions channel_subscriptions');


$options = array(
    'http' => array(
        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
        'method'  => 'GET',
        'content' => http_build_query($data)
    )
);
$context  = stream_context_create($options);
$result = file_get_contents($url, false, $context);
if ($result === FALSE) 
{ /* Handle error */ }

echo $result;
return $result;
}
function test()
{
    echo "<pre>";
print_r($GLOBALS);
echo "</pre>";
echo "<br> start<br>";
$USER_ACCESSTOKEN = USER_ACCESSTOKEN;
$USER_REFRESHTOKEN = USER_REFRESHTOKEN;

echo $USER_ACCESSTOKEN;
echo $USER_REFRESHTOKEN;
    return;
}
function info()
{
$ch = curl_init();
$channelname = CHANNELNAME;
$clientid = CLIENTID;
$accesstoken =ACCESSTOKEN;

curl_setopt($ch, CURLOPT_URL, "https://api.twitch.tv/helix/search/channels?query={$channelname}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

$headers = array();
$headers[] = "Client-Id: {$clientid}";
$headers[] = "Authorization: Bearer {$accesstoken}";
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$result = curl_exec($ch);
if (curl_errno($ch)) {
    echo 'Error:' . curl_error($ch);
}
curl_close($ch);
$json = json_decode($result); 
$data =  $json->data[0]; 
echo json_encode($data); 
}

function topclips()
{
$ch = curl_init();
$channelname = CHANNELNAME;
$clientid = CLIENTID;

curl_setopt($ch, CURLOPT_URL, "https://api.twitch.tv/kraken/clips/top?channel={$channelname}&period=all");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

$headers = array();
$headers[] = "Client-Id: {$clientid}";
$headers[] = "Accept: application/vnd.twitchtv.v5+json";
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$result = curl_exec($ch);
if (curl_errno($ch)) {
    echo 'Error:' . curl_error($ch);
}
curl_close($ch);
echo $result ; 
}
function refreshToken()
{

    $url = 'https://id.twitch.tv/oauth2/token';
    $data = array('client_id' => CLIENTID, 'client_secret' => CLIENTSECRET,'grant_type' => 'refresh_token','refresh_token'=>USER_REFRESHTOKEN);
    
    
    $options = array(
        'http' => array(
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data)
        )
    );
    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    
    if ($result === FALSE) 
    { /* Handle error */ }
   
    $resultdata = json_decode($result); 
    if($resultdata->access_token === NULL) 
    {
    
    }
    else
    {
        config_set("config.ini","USER_ACCESSTOKEN",$resultdata->access_token );
        config_set("config.ini","USER_REFRESHTOKEN",$resultdata->refresh_token );
        config_set("config.ini","UPDATED",time() );
    }
    
    
    
}
function user()
{
$ch = curl_init();
$channelname = CHANNELNAME;
$clientid = CLIENTID;

curl_setopt($ch, CURLOPT_URL, "https://api.twitch.tv/kraken/users?login={$channelname}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

$headers = array();
$headers[] = "Client-Id: {$clientid}";
$headers[] = "Accept: application/vnd.twitchtv.v5+json";
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$result = curl_exec($ch);
if (curl_errno($ch)) {
    echo 'Error:' . curl_error($ch);
}
curl_close($ch);
$json = json_decode($result); 
$data =  $json->users[0]; 
echo json_encode($data);
return json_encode($data);
}
function getuserid()
{
$ch = curl_init();
$channelname = CHANNELNAME;
$clientid = CLIENTID;

curl_setopt($ch, CURLOPT_URL, "https://api.twitch.tv/kraken/users?login={$channelname}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

$headers = array();
$headers[] = "Client-Id: {$clientid}";
$headers[] = "Accept: application/vnd.twitchtv.v5+json";
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$result = curl_exec($ch);
if (curl_errno($ch)) {
    echo 'Error:' . curl_error($ch);
}
curl_close($ch);
$json = json_decode($result); 
$data =  $json->users[0]; 

$json = json_decode( json_encode($data), true);
$userid=$json['_id'];
return $userid;
}


function latestfollowers()
{
$ch = curl_init();
$channelname = CHANNELNAME;
$clientid = CLIENTID;
$accesstoken =ACCESSTOKEN;

$userid=getuserid();


curl_setopt($ch, CURLOPT_URL, "https://api.twitch.tv/helix/users/follows?to_id={$userid}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

$headers = array();
$headers[] = "Client-Id: {$clientid}";
$headers[] = "Authorization: Bearer {$accesstoken}";
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$result = curl_exec($ch);
if (curl_errno($ch)) {
    echo 'Error:' . curl_error($ch);
}
curl_close($ch);
 

$json = json_decode($result); 
$data =  $json->data; 
echo json_encode($data);
return json_encode($data); 
}
function followercount()
{
$ch = curl_init();
$channelname = CHANNELNAME;
$clientid = CLIENTID;
$accesstoken =ACCESSTOKEN;

$userid=getuserid();


curl_setopt($ch, CURLOPT_URL, "https://api.twitch.tv/helix/users/follows?to_id={$userid}&first=1");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

$headers = array();
$headers[] = "Client-Id: {$clientid}";
$headers[] = "Authorization: Bearer {$accesstoken}";
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$result = curl_exec($ch);
if (curl_errno($ch)) {
    echo 'Error:' . curl_error($ch);
}
curl_close($ch);
 

$json = json_decode($result); 
$data =  $json->total; 
echo $data ;
return $data; 
}
function subscriptions()
{
refreshToken();
$ch = curl_init();
$channelname = CHANNELNAME;
$clientid = CLIENTID;
$userid=getuserid();
$USER_ACCESSTOKEN = USER_ACCESSTOKEN;

curl_setopt($ch, CURLOPT_URL, "https://api.twitch.tv/helix/subscriptions?broadcaster_id={$userid}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

$headers = array();
$headers[] = "Client-Id: {$clientid}";
$headers[] = "Authorization: Bearer {$USER_ACCESSTOKEN}";
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$result = curl_exec($ch);
if (curl_errno($ch)) {
    echo 'Error:' . curl_error($ch);
}
curl_close($ch);
$json = json_decode($result); 
echo json_encode($json->data); 
return json_encode($json->data); 
}
function latestSubscriber()
{
refreshToken();
$ch = curl_init();
$channelname = CHANNELNAME;
$clientid = CLIENTID;
$userid=getuserid();
$USER_ACCESSTOKEN = USER_ACCESSTOKEN;

curl_setopt($ch, CURLOPT_URL, "https://api.twitch.tv/kraken/channels/{$userid}/subscriptions?limit=1&direction=desc");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

$headers = array();
$headers[] = "Client-Id: {$clientid}";
$headers[] = "Authorization: OAuth {$USER_ACCESSTOKEN}";
$headers[] = "Accept: application/vnd.twitchtv.v5+json";
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$result = curl_exec($ch);
if (curl_errno($ch)) {
    echo 'Error:' . curl_error($ch);
}
curl_close($ch);

$json = json_decode($result); 
$latestUser =$json->subscriptions[0];
echo $latestUser->user->display_name; 
return $latestUser->user->display_name; 
}
function subscriptionCount()
{
refreshToken();
$ch = curl_init();
$channelname = CHANNELNAME;
$clientid = CLIENTID;
$userid=getuserid();
$USER_ACCESSTOKEN = USER_ACCESSTOKEN;

curl_setopt($ch, CURLOPT_URL, "https://api.twitch.tv/kraken/channels/{$userid}/subscriptions?limit=1&direction=desc");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

$headers = array();
$headers[] = "Client-Id: {$clientid}";
$headers[] = "Authorization: OAuth {$USER_ACCESSTOKEN}";
$headers[] = "Accept: application/vnd.twitchtv.v5+json";
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$result = curl_exec($ch);
if (curl_errno($ch)) {
    echo 'Error:' . curl_error($ch);
}
curl_close($ch);

$json = json_decode($result); 
echo json_encode($json->_total); 
return json_encode($json->_total); 
}
function config_set($config_file, $key, $value) {
    $config_data = parse_ini_file($config_file, true);
    $config_data[$key] = $value;  
    write_php_ini($config_data, $config_file);
}
function write_php_ini($array, $file)
{
    $res = array();
    foreach($array as $key => $val)
    {
        if(is_array($val))
        {
            $res[] = "[$key]";
            foreach($val as $skey => $sval) $res[] = "$skey = ".(is_numeric($sval) ? $sval : '"'.$sval.'"');
        }
        else $res[] = "$key = ".(is_numeric($val) ? $val : '"'.$val.'"');
    }
    safefilerewrite($file, implode("\r\n", $res));
}

function safefilerewrite($fileName, $dataToSave)
{    if ($fp = fopen($fileName, 'w'))
    {
        $startTime = microtime(TRUE);
        do
        {            $canWrite = flock($fp, LOCK_EX);
           // If lock not obtained sleep for 0 - 100 milliseconds, to avoid collision and CPU load
           if(!$canWrite) usleep(round(rand(0, 100)*1000));
        } while ((!$canWrite)and((microtime(TRUE)-$startTime) < 5));

        //file was locked so now we can store information
        if ($canWrite)
        {            fwrite($fp, $dataToSave);
            flock($fp, LOCK_UN);
        }
        fclose($fp);
    }

}
?>