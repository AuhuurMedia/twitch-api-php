<?php
header('Access-Control-Allow-Origin: *');
# First of all we read the clientid and clientsecret from the config.ini
# Make sure that the config.ini is protected from unauthorized access
$ini_array = parse_ini_file("config.ini");
define('CLIENTID', $ini_array['clientid']);
define('CLIENTSECRET', $ini_array['clientsecret']);
define('CHANNELNAME', $ini_array['channelname']);
define('ACCESSTOKEN', getAccessToken());

# Currently all functionality is encapsulated in distinct functions
# To get the relevant data the name of the function needs to be supplied as param
# so for example twitch.php&function=getStreamData
# We will use an RMI invocation afterwards if the function is included in the valid functions array
# or abort if it's not included
# So make sure to add your functions to the array if you add new ones

$validFunctions = array("info","topclips","user","latestfollowers","followercount");
$functName = $_REQUEST['f'];

if(in_array($functName,$validFunctions))
{
   
 $functName();
}else{
    echo "You don't have permission to call that function so back off!";
    exit();
}

function getAccessToken()
{
$url = 'https://id.twitch.tv/oauth2/token';
$data = array('client_id' => CLIENTID, 'client_secret' => CLIENTSECRET,'grant_type' => 'client_credentials');


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
?>