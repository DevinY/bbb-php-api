<?php
/*

BigBlueButton open source conferencing system - http://www.bigbluebutton.org/
Copyright (c) 2012 BigBlueButton Inc. and by respective authors (see below).
This program is free software; you can redistribute it and/or modify it under the
terms of the GNU Lesser General Public License as published by the Free Software
Foundation; either version 3.0 of the License, or (at your option) any later
version.
BigBlueButton is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE. See the GNU Lesser General Public License for more details.
You should have received a copy of the GNU Lesser General Public License along
with BigBlueButton; if not, see <http://www.gnu.org/licenses/>.

Version:
Original version available at: https://github.com/petermentzer/bbb-api-php 

re-packed by Devin Yang:
*/

namespace Deviny\BigBlueButtonPhpApi;

class Bbb {
    private $securitySalt;				
    private $serverBaseUrl;			
    //Create Meeting參數
    private $name="";//會議室名稱
    private $meetingID="";
    private $attendeePW="";
    private $moderatorPW="";
    private $welcome="";
    private $moderatorOnlyMessage="";
    private $dialNumber="";
    private $voiceBridge="";
    private $webVoice="";
    private $logoutURL="";
    public $clientURL="";
    private $redirectClient="";
    private $maxParticipants="";
    private $record="false";
    private $duration=0;
    private $meta="";
    private $xml_data="";
    private $endTime="";
    private $meta_msg="";
    private $arrMeta=array();//儲存meta資料，可在info中被取得
    //===bbb 0.90新增的功能=====
    private $autoStartRecording="false";
    private $allowStartStopRecording="false";
    //=====Client Page=======
    private $clientPage="BigBlueButton.html";

    private $materials=array(); //url as value
    //=========Addional Properties======= 
    private $meetingType="";//meeting, class...
    private $roletype="";//teacher or student
    protected $j_no_type="";//Enum: student_id,tech_no,emp_no

    //加入會議室
    private $fullName="";
    private $userID="";
    private $password="";
    private $createTime="";
    private $webVoiceConf="";
    private $configToken="";
    private $avatarURL="";
    private $token="";
    //設定
    private $configXML="";
    private $xml_param="";
    //=======bbb added 1.1======
    private $webcamsOnlyForModerator="";
    //=======bbb added 2.0=======
    private $logo="";
    private $copyright="";
    private $muteOnStart="";

    //建構子，new 物件時執行，用來設定預設值
    function __construct($meeting_id="",$isEncrypt=false) {
        if(defined('DEFAULT_SECURITY_SALT')) $this->securitySalt = DEFAULT_SECURITY_SALT;
        if(defined('DEFAULT_SERVER_BASE_URL')) $this->serverBaseUrl = DEFAULT_SERVER_BASE_URL;
        $this->setModeratorPW("0");//老師身份
        $this->setAttendeePW("1");//學員身份
        if($isEncrypt){
            $meeting_id=$this->dec_str($meeting_id);
        }
        if(!empty($meeting_id)){
            $this->setMeetingId($meeting_id);
        }
    }

    //建立會議會功能
    function create_api(){
        if($this->meetingID == "") {
            die("Meeting Id is not set!!");
        }

        $creationUrl = $this->serverBaseUrl."api/create?";

        $params = 
            'name='.urlencode($this->name).
            '&meetingID='.urlencode($this->getMeetingId()).
            '&attendeePW='.urlencode($this->getAttendeePW()).
            '&moderatorPW='.urlencode($this->getModeratorPW()).
            '&dialNumber='.urlencode($this->dialNumber).
            '&voiceBridge='.urlencode($this->voiceBridge).
            '&webVoice='.urlencode($this->webVoice).
            '&logoutURL='.urlencode($this->getLogoutUrl()).
            '&maxParticipants='.urlencode($this->maxParticipants).
            '&record='.urlencode($this->getRecord()).
            '&autoStartRecording='.urlencode($this->getAutoStartRecording()).
            '&allowStartStopRecording='.urlencode($this->getAllowStartStopRecording()).
            '&meta_msg='.urlencode($this->getMeta_Msg()).	
            '&duration='.urlencode($this->getDuration());

        //配置Meta參數
        if(count($this->arrMeta)>0){
            foreach ($this->arrMeta as $k => $v) {
                $k = preg_replace('/\s(?=)/', '', $k);
                $v = preg_replace('/\s(?=)/', '', $v);
                $params.='&meta_'.$k.'='.$v;
            }
        }

        $welcomeMessage = $this->getWelcome();
        $params .= '&welcome='.urlencode($welcomeMessage);

        $moderatorOnlyMessage = $this->getModeratorOnlyMessage();
        if($moderatorOnlyMessage!="") $params .= '&moderatorOnlyMessage='.urlencode($moderatorOnlyMessage );
        if($this->logo!="") $params .= '&logo='.urlencode($this->getLogo());
        if($this->muteOnStart=="true") $params .= '&muteOnStart='.urlencode($this->getMuteOnStart());
        if($this->copyright!="") $params .= '&copyright='.urlencode($this->getCopyright());


        // Return the complete URL:
        return ( $creationUrl.$params.'&checksum='.sha1("create".$params.$this->securitySalt) );
    }
    //打包投影片$slides為array，內容為教材網址
    function slides($slides){
        $this->setMaterials($slides);
        $i = 0;
        $max = (int)count($slides);
        $this->xml_data = '<?xml version="1.0" encoding="UTF-8"?><modules><module name="presentation">';
        while($i < $max) {
            $this->xml_data .= '<document url="'.$slides[$i].'" />';
            $i++;
        }		
        $this->xml_data .= '</module></modules>';
        $this->setXml_Data($this->xml_data);
    }

    function create(){
        $obj_info=simplexml_load_string(file_get_contents($this->info()));
        $this->setCreateTime($obj_info->createTime);//設定建立時間
        $duration=$this->getDuration();
        $endTime=round($obj_info->createTime/1000)+($duration*60);
        $endTime = (array_key_exists('ce', $this->arrMeta))?$this->arrMeta['ce']:$endTime;//ce:課程結束時間
        $clientURL=$this->getClientUrl();
        $this->setMeta('endTime',$endTime); //return Sec

        if($obj_info->returncode!="SUCCESS"){
            $ch = curl_init($this->create_api());
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
            if(!empty($this->xml_data)){
                curl_setopt($ch, CURLOPT_POSTFIELDS, "$this->xml_data");
            }
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);
            curl_close($ch);
        }else{
            if($clientURL == ""){
                $clientUrl=sprintf("%sclient/%s",$this->getUrl(),$this->getClientPage());
                $this->setClientUrl($clientUrl);//設定客服端
            }
        }
    }
    private function getUrl(){
        return preg_replace('/(^https?:\/\/.*?\/)(.+)/uim', '$1', $this->getServerBaseUrl());
    }
    //取得所有會議室列表
    function meetings(){
        $joinUrl = $this->serverBaseUrl."api/getMeetings?";
        $params="";
        return ($joinUrl.$params.'&checksum='.sha1("getMeetings".$params.$this->securitySalt));
    }
    //取得目前執行中的教室總數
    function meetings_num(){
        $meetings_num=0;
        $joinUrl = $this->serverBaseUrl."api/getMeetings?";
        $params="";
        $getMeetingsUrl=$joinUrl.$params.'&checksum='.sha1("getMeetings".$params.$this->securitySalt);
        $obj_info=simplexml_load_string(file_get_contents($getMeetingsUrl));
        //		 var_dump($obj_info->meetings);
        $arrMeetings=$obj_info->meetings->meeting;
        //secrit給錯時，這裡會回應NULL，所以如是NULL就回應0必免程式出現錯誤
        if(is_null($arrMeetings)) return 0;
        foreach($arrMeetings as $i=>$v){
            //   echo $v->running;
            if($v->running=="true"){
                $meetings_num++;
            }
            //   echo $v->meetingID;
            //  echo $v->running;
        }
        return $meetings_num;
    }

    //加入會議室
    function join(){
        $this->create();
        $this->config();
        // Establish the basic join URL:
        $joinUrl = $this->serverBaseUrl."api/join?";
        // Add parameters to the URL:
        $params = 
            'meetingID='.urlencode($this->getMeetingId()).
            '&fullName='.urlencode($this->getFullName()).
            '&password='.urlencode($this->getPassword()).
            '&userID='.urlencode($this->userID).
            '&clientURL='.urlencode($this->getClientUrl()).
            //		'&avatarURL='.$this->avatarURL.
            '&webVoiceConf='.urlencode($this->webVoiceConf);		
        // Only use createTime if we really want to use it. If it's '', then don't pass it:
        if (((isset($this->createTime)) && ($this->createTime != ''))) {
            $params .= '&createTime='.urlencode($this->createTime);
        }
        if (!empty($this->token)) {
            $params .= '&configToken='.urlencode($this->token);
        }
        // Return the URL:
        return ($joinUrl.$params.'&checksum='.sha1("join".$params.$this->securitySalt));
    }
    function attendee($fullName="",$userID=""){
        $this->setFullName($fullName);
        $this->password=$this->attendeePW;
        return $this->join();
    }
    function moderator($fullName="",$userID=""){
        $this->setFullName($fullName);
        $this->password=$this->moderatorPW;
        return $this->join();
    }
    //結束會議
    function end(){
        $callName="end";
        $URL = $this->serverBaseUrl."api/$callName?";
        $params = 
            'meetingID='.urlencode($this->meetingID).
            '&password='.urlencode($this->moderatorPW);
        $params=$params.'&checksum='.sha1($callName.$params.$this->securitySalt);//從組params加上checksum
        return ($URL.$params);
    }
    //錄影 getRecordings
    public function recordings($meetingID="") {
        $callName="getRecordings";
        if(empty($meetingID)) {

            $meetingID=$this->meetingID;
        }
        $URL = $this->serverBaseUrl."api/$callName?";
        $params = 
            'meetingID='.urlencode($meetingID);
        $params=$params.'&checksum='.sha1($callName.$params.$this->securitySalt);//從組params加上checksum
        return ($URL.$params);
    }
    //
    public function recordingsObj($meeting_id=""){
        if(empty($meetingID)) $meetingID=$this->meetingID;
        $recordingsObj=simplexml_load_string(file_get_contents($this->recordings($meeting_id)));
        if("SUCCESS"==$recordingsObj->returncode){
            return $recordingsObj->recordings->recording;
        }
        else{
            return array();
        }
    }
    //取得會議室資料getMeetingInfo的網址
    public function info($meetingID=""){
        $callName="getMeetingInfo";
        if(empty($meetingID)) $meetingID=$this->meetingID;
        $URL= $this->serverBaseUrl."api/$callName?";
        $params = 
            'meetingID='.urlencode($this->meetingID).
            '&password='.urlencode($this->moderatorPW);
        $params=$params.'&checksum='.sha1($callName.$params.$this->securitySalt);//從組params加上checksum
        return ($URL.$params);
    }
    public function infoObj($meeting_id=""){
        if(empty($meetingID)) $meetingID=$this->meetingID;
        $infoObj=simplexml_load_string(file_get_contents($this->info($meeting_id)));
        if("SUCCESS"==$infoObj->returncode){
            return $infoObj;
        }
        else{
            return array();
        }
    }
    //取得系統預設的設定
    public function defaultConfig(){
        $callName="getDefaultConfigXML";
        $URL= $this->serverBaseUrl."api/$callName?";
        $params = "";
        $params=$params.'&checksum='.sha1($callName.$params.$this->securitySalt);//從組params加上checksum
        return ($URL.$params);
    }

    public function setConfigUrl($url="")
    {
        if(!empty($url)){
            $xml_data = file_get_contents($url);
            $this->setXml_Param($xml_data);
        }
        return $this;
    }

    //傳config.xml內容取得Token
    protected function config() {
        $xml_param = $this->getXml_Param();
        if(!empty($xml_param)) 
        {
            $URL = $this->serverBaseUrl."api/setConfigXML?";
            $params = 'configXML='.urlencode($this->getXml_Param()).'&meetingID='.urlencode($this->meetingID);
            $finalStr = 'setConfigXML'.$params.$this->securitySalt;
            $checksum = sha1($finalStr);
            $finalParams = $params.'&checksum='.$checksum;
            $xml = $this->load_xml_by_url($URL, $finalParams,"application/x-www-form-urlencoded");
            if( $xml && $xml->returncode == 'SUCCESS' ) {
                $this->setToken($xml->configToken);
                return (  $xml->configToken );
            }else{
                return false;
            }	
        }
        return false;
    }

    //isMeetingRunning
    public function isRunning($meetingID=""){
        if(empty($meetingID)) $meetingID=$this->meetingID;
        $recordingsUrl = $this->serverBaseUrl."api/isMeetingRunning?";
        $params = 
            'meetingID='.urlencode($meetingID);
        return ($recordingsUrl.$params.'&checksum='.sha1("isMeetingRunning".$params.$this->securitySalt));
    }
    //========加解密=========
    //openssl req -nodes -newkey rsa:2048 -keyout private.key
    function enc_str($source){
        //載入私鑰
        $fp=fopen(ENCRYPT_PRIVATE_KEY_PATH,"r");
        $priv_key=fread($fp,8192);
        fclose($fp);
        $res = openssl_get_privatekey($priv_key);
        openssl_private_encrypt($source,$crypttext,$res);
        //echo "<br/>String crypted: $crypttext";
        return urlencode(base64_encode($crypttext));
    }
    //openssl req -new -x509 -key private.key -out cert.pem
    function dec_str($crypttext,$isencode="n"){
        $fp=fopen (ENCRYPT_CERTIFCATE_PATH,"r");
        $pub_key=fread($fp,8192);
        fclose($fp);
        openssl_get_publickey($pub_key);
        /*
         * NOTE:  解密方式，先還原為加密的字串，再用公開的憑證進行解密
         */
        if("y"==$isencode){
            $crypttext=urldecode($crypttext);
        }
        openssl_public_decrypt(base64_decode($crypttext),$newsource,$pub_key);
        return $newsource;
    }

    function encodeURI($url) {
        // http://php.net/manual/en/function.rawurlencode.php
        // https://developer.mozilla.org/en/JavaScript/Reference/Global_Objects/encodeURI
        $unescaped = array(
            '%2D'=>'-','%5F'=>'_','%2E'=>'.','%21'=>'!', '%7E'=>'~',
            '%2A'=>'*', '%27'=>"'", '%28'=>'(', '%29'=>')'
        );
        $reserved = array(
            '%3B'=>';','%2C'=>',','%2F'=>'/','%3F'=>'?','%3A'=>':',
            '%40'=>'@','%26'=>'&','%3D'=>'=','%2B'=>'+','%24'=>'$'
        );
        $score = array(
            '%23'=>'#'
        );

        return strtr(urlencode($url), array_merge($reserved,$unescaped,$score));
    }
    function load_xml_by_url($url, $params, $contentType){

        if (extension_loaded('curl')) 
        {
            $ch = curl_init() or die ( curl_error() );
            $timeout = 10;
            curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false);	
            curl_setopt( $ch, CURLOPT_URL, $url );
            curl_setopt( $ch, CURLOPT_POST, true);
            curl_setopt( $ch, CURLOPT_POSTFIELDS, $params);
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
            curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, $timeout);
            curl_setopt( $ch, CURLOPT_HTTPHEADER, array("Content-type: ".$contentType)); // application/x-www-form-urlencoded; charset=UTF-8"));
            $data = curl_exec( $ch );
            curl_close( $ch );	

            if($data)
                return (new SimpleXMLElement($data));
            else
                return false;	
        }
        return null;	
    }

    //BBB設定 setter and gatter
    function setServerBaseUrl($v){ $this->serverBaseUrl=$v; return $this;} 
    function getServerBaseUrl(){ return $this->serverBaseUrl; }
    function setSecret($v){ $this->securitySalt=$v; return $this;}
    protected function getSecret(){ return $this->securitySalt; }//protected 子類別可以使用，但是不能被外部呼叫
    function setEndTime($v){ $this->endTime=$v; return $this;}
    function getEndTime(){ return $this->endTime; }
    function setMeta($k,$v){
        $this->arrMeta[$k] = $v;
        return $this;
    }
    function getMeta($k){ 
        return (array_key_exists($k,$this->arrMeta))?$this->arrMeta[$k]:'';
    }

    //BBB Create
    function setClient($v){
        $bbb_server_url = preg_replace('#(^https?://.*?/)(.+)#uim', '$1',$this->getServerBaseUrl());
        $this->clientURL=$bbb_server_url."client/".$v;
        return $this;
    }
    function setClientPage($v) {$this->clientPage = $v; return $this;}
    function getClientPage() { return $this->clientPage; }
    function setClientUrl($v){ $this->clientURL=$v; return $this;}
    function getClientUrl(){ return $this->clientURL; }
    function setRedirectClient($v){ $this->redirectClient=$v; return $this;}
    function getRedirectClient(){ return $this->redirectClient; }
    function setName($v){ $this->name=$v; return $this;}
    function getName(){ return $this->name; }
    function setVoiceBridge($v){ $this->voiceBridge=$v; return $this;}
    function getVoiceBridge(){ return $this->voiceBridge; }
    function setMeetingId($v){ $this->meetingID=$v; return $this;}
    function getMeetingId(){ return $this->meetingID; }
    function setMeetingType($v){ $this->meetingType=$v; return $this;}
    function getMeetingType(){ return $this->meetingType; }
    function setMaxParticipants($v){ $this->maxParticipants=$v; return $this;}
    function getMaxParticipants(){ return $this->maxParticipants; }
    function setLogoutUrl($v){ $this->logoutURL=$v; return $this;}
    function getLogoutUrl(){ return $this->logoutURL; }
    function setAttendeePW($v){ $this->attendeePW=$v; return $this;}
    function getAttendeePW(){ return $this->attendeePW; }
    function setModeratorPW($v){ $this->moderatorPW=$v; return $this;}
    function getModeratorPW(){ return $this->moderatorPW; }
    function setDuration($v){ 
        $this->duration=$v; 
        return $this;
    }
    function getDuration(){ return $this->duration; }

    function setLogo($v){ 
        if(is_bool($v)){ $this->logo = $v?'true':'false'; }
        $this->logo=$v; 
        return $this;
    }
    function getLogo(){ return $this->logo; }

    function setMuteOnStart($v){ 
        if(is_bool($v)){ $this->muteOnStart = $v?'true':'false'; }
        $this->muteOnStart=$v; 
        return $this;
    }
    function getMuteOnStart(){ return $this->muteOnStart; }

    function setCopyright($v){ 
        $this->copyright=$v; 
        return $this;
    }
    function getCopyright(){ return $this->copyright; }

    function setRecord($v){ 
        if(is_bool($v)){ $this->record = $v?'true':'false'; }
        $this->record=$v; 
        return $this;
    }
    function getRecord(){ return $this->record; }
    function setWelcome($v){ $this->welcome=$v; return $this;}
    function getWelcome(){ return $this->welcome; }
    function setModeratorOnlyMessage($v) { $this->moderatorOnlyMessage=$v; return $this;}
    function getModeratorOnlyMessage() { return $this->moderatorOnlyMessage; }
    function setXml_Data($v){ $this->xml_data=$v; return $this;}
    function getXml_Data(){ return $this->xml_data; }
    function setMeta_Msg($v){ $this->meta_msg=$v; return $this;}
    function getMeta_Msg(){ return $this->meta_msg; }
    function setRoleType($v){ $this->roletype=$v; return $this;}
    function getRoleType(){ return $this->roletype; }
    //BBB 0.90新增的功能
    function setAutoStartRecording($v){ 
        if(is_bool($v)){ $this->record = $v?'true':'false'; }
        $this->autoStartRecording = $v; 
        return $this;
    }
    function getAutoStartRecording(){ return $this->autoStartRecording; }
    function setAllowStartStopRecording($v){ 
        if(is_bool($v)){ $this->record = $v?'true':'false'; }
        $this->allowStartStopRecording=$v; 
        return $this;
    }
    function getAllowStartStopRecording(){ return $this->allowStartStopRecording; return $this;}

    //BBB Preupload
    function setMaterials($v){ $this->materials=$v; return $this;}
    function getMaterials(){$this->materials; return $this;}

    //Addional type of join user;
    protected function setJ_no_type($v){$this->j_no_type=$v; return $this;}
    protected function getJ_no_type(){return $this->j_no_type; return $this;}

    //BBB JoinMeeting Setter
    function setFullName($v){ $this->fullName=$v; return $this;}
    function getFullName(){ return $this->fullName; }
    function setUserID($v){ $this->userID=$v; return $this;}
    function getUserID(){ return $this->userID; }
    function setPassword($v){ $this->password=$v; return $this;}
    function getPassword(){ return $this->password; }
    function setCreateTime($v){ $this->createTime=$v; return $this;}
    function getCreateTime(){ return $this->createTime; }
    function setWebVoiceConf($v){ $this->webVoiceConf=$v; return $this;}
    function getWebVoiceConf(){ return $this->webVoiceConf; }
    function setConfigToken($v){ $this->configToken=$v; return $this;}
    function getConfigToken(){ return $this->configToken; }
    function setAvatarURL($v){ $this->avatarURL=$v; return $this;}
    function getAvatarURL(){ return $this->avatarURL; }
    function setToken($v){ $this->token=$v; return $this;}
    function getToken(){ return $this->token; }
    //加入會議室
    function setXml_Param($v){ 
        $this->xml_param=$v;
        // $this->xml_param=$this->encodeURI($v);
        return $this;
    }
    function getXml_Param()
    {
        if(@get_magic_quotes_runtime ()||@get_magic_quotes_gpc())
        {
            return stripslashes($this->xml_param);
        }else{
            return $this->xml_param;
        }
    }

}
