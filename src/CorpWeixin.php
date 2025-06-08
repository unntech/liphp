<?php
declare (strict_types = 1);

namespace LiPhp;

use LiPhp\Library\wxBizMsgCrypt;

class CorpWeixin
{
    protected string $token = '';
    protected string $encodingAesKey = '';
    protected wxBizMsgCrypt $wxMsgCrypt;
    protected string $appId = '';
    protected int $timestamp = 0;
    public string $msgtype = 'text';   //('text','image','location')
    public array $msg = array();
    public string $ToUserName, $FromUserName;
    public string $AgentID;

    public function __construct(string $token, string $encodingAesKey = '', string $appId = '', string $AgentID = '')
    {
        $this->token = $token;
        $this->encodingAesKey = $encodingAesKey;
        $this->appId = $appId;
        $this->AgentID = $AgentID;
        $this->wxMsgCrypt = new WXBizMsgCrypt($token, $encodingAesKey, $appId);
        $this->timestamp = time();
    }

    public function setToUserName(string $ToUserName): void
    {
        $this->ToUserName = $ToUserName;
    }

    public function setFromUserName(string $FromUserName): void
    {
        $this->FromUserName = $FromUserName;
    }

    public function setAgentID(string $AgentID): void
    {
        $this->AgentID = $AgentID;
    }

    public function VerifyURL()
    {
        $errCode = $this->wxMsgCrypt->VerifyURL($_GET['msg_signature'], $_GET['timestamp'], $_GET['nonce'], $_GET['echostr'], $sEchoStr);
        if ($errCode == 0) {
            echo $sEchoStr;
        }else{
            print("ERR: " . $errCode . "\n\n");
        }
        exit(0);
    }

    public static function postData(): bool|array
    {
        $postStr = file_get_contents("php://input");
        if(empty($postStr)){
            return false;
        }
        return (array)simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
    }

    public function getMsg(): int
    {
        $postStr = file_get_contents("php://input");
        if(empty($postStr)){
            return -1;
        }
        $postArr = (array)simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
        $this->ToUserName = $postArr['ToUserName'];
        $this->AgentID = $postArr['AgentID'];
        $this->wxMsgCrypt->setReceiveId($this->ToUserName);

        $get_msg_signature = $_GET['msg_signature'] ?? '';
        $get_timestamp = $_GET['timestamp'] ?? '';
        $get_nonce = $_GET['nonce'] ?? '';

        $sMsg = "";  // 解析之后的明文
        $errCode = $this->wxMsgCrypt->decryptMsg($get_msg_signature, $get_timestamp, $get_nonce, $postStr, $sMsg);
        if ($errCode == 0) {
            $this->msg = (array)simplexml_load_string($sMsg, 'SimpleXMLElement', LIBXML_NOCDATA);
            $this->msgtype = strtolower($this->msg['MsgType']);
            $this->FromUserName = $this->msg['FromUserName'] ?? '';
            return 0;
        }else{
            return $errCode;
        }
    }

    /**
     * 回复文本消息
     * @param string $text
     * @return string
     */
    public function makeText(string $text = ''): string
    {
        $CreateTime = $this->timestamp;
        $FromUserName = $this->FromUserName ?? ''; //获取发送方帐号（OpenID）
        $ToUserName = $this->ToUserName ?? ''; //获取接收方账号
        $msgId =  $_SERVER['UNIQUE_ID'] ?? uniqid();
        $textTpl = "<xml><ToUserName><![CDATA[{$FromUserName}]]></ToUserName><FromUserName><![CDATA[{$ToUserName}]]></FromUserName><CreateTime>{$CreateTime}</CreateTime><MsgType><![CDATA[text]]></MsgType><Content><![CDATA[%s]]></Content><MsgId>{$msgId}</MsgId><AgentID>{$this->AgentID}</AgentID></xml>";
        return sprintf($textTpl, $text);
    }

    /**
     * 输入回复消息
     * @param string $data
     * @return void
     */
    public function reply(string $data): void
    {
        $nonce = LiComm::createNonceStr(8);

        $errCode = $this->wxMsgCrypt->encryptMsg($data, (string)$this->timestamp, $nonce, $enData);
        if($errCode == 0){
            echo $enData;
        }else{
            print("ERR: " . $errCode . "\n\n");
        }
    }
}