<?php

// gnavi api を叩く php を読み込む
require_once "gnavi.php";

// BOT の Channel ID, Channel Secret, MID を入力
const $channel_id = "< Channel ID>";
const $channel_secret = "< Channel Secret >";
const $bot_mid = "< BOT MID >";

//変数群
const $log_file = "< log file >

//time zone
date_default_timezone_set('Asia/Tokyo');

// 関数群
//ユーザ情報取得する関数
function getDisplayName($to_mid){
    global $channel_id;
    global $channel_secret;
    global $bot_mid;
    global $displayname;
    $user_profiles_url = curl_init("https://trialbot-api.line.me/v1/profiles?mids=${to_mid}");
    curl_setopt($user_profiles_url, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($user_profiles_url, CURLOPT_HTTPHEADER, array(
        "X-Line-ChannelID: $channel_id",
        "X-Line-ChannelSecret: $channel_secret",
        "X-Line-Trusted-User-With-ACL: $bot_mid"
    ));
    $user_profiles_output = curl_exec($user_profiles_url);
    $user_json_obj = json_decode($user_profiles_output);
    $displayname = $user_json_obj->contacts{0}->displayName;
    curl_close($user_profiles_url);
}

//POSTするデータを作成する関数
function create_post_data($to_mid, $post_content){
    // toChannelとeventTypeは固定値なので、変更不要。
    global $post_data;
    $post_data = [
        "to"=>[$to_mid],
        "toChannel"=>"1383378250",
        "eventType"=>"138311608800106203",
        "content"=>$post_content
    ];
}

//相手に会話する内容をPOSTする関数
function post($post_data){
    global $channel_id;
    global $channel_secret;
    global $bot_mid;
    $post_url = curl_init("https://trialbot-api.line.me/v1/events");
    curl_setopt($post_url, CURLOPT_POST, true);
    curl_setopt($post_url, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($post_url, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($post_url, CURLOPT_POSTFIELDS, json_encode($post_data));
    curl_setopt($post_url, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json; charser=UTF-8',
        "X-Line-ChannelID: $channel_id",
        "X-Line-ChannelSecret: $channel_secret",
        "X-Line-Trusted-User-With-ACL: $bot_mid"
    ));
    $result = curl_exec($post_url);
    curl_close($post_url);
}

// 相手からメッセージ受信
$recieve_json_string = file_get_contents('php://input');
$recieve_jsonObj = json_decode($recieve_json_string);
$to = $recieve_jsonObj->{"result"}[0]->{"content"}->{"from"};
$text = $recieve_jsonObj->{"result"}[0]->{"content"}->{"text"};
$content_type = $recieve_jsonObj->{"result"}[0]->{"content"}->{"contentType"};
$latitude = $recieve_jsonObj->{"result"}[0]->{"content"}->{"location"}->{"latitude"};
$longitude = $recieve_jsonObj->{"result"}[0]->{"content"}->{"location"}->{"longitude"};
$op_type = $recieve_jsonObj->{"result"}[0]->{"content"}->{"opType"};
$params = $recieve_jsonObj->{"result"}[0]->{"content"}->{"params"};

//DisplayName 取得
//date, mid, displayName, text, contentType, Location をログ出力
getDisplayName($to);
file_put_contents($log_file, date("Y/m/d H:i:s") . " mid:${to}, displayName:${displayname}, text:${text}, contentType:${content_type}, latitude:${latitude}, longitude:${longitude}" . PHP_EOL, FILE_APPEND);

//会話処理
if( $op_type === 4 ){
    // 友達登録時に会話する
    getDisplayName($params[0]);
    $response_format_text = ['contentType'=>1,"toType"=>1,"text"=>"${displayname}さん、登録ありがとうございます。\n位置情報を送信することで、周囲300mからランダムに飲食店を検索し表示します。"];
    create_post_data($params[0], $response_format_text);
    post($post_data);
} else if( $op_type === 8 ){
    // ブロック時はなにもせず正常終了する
    exit(0);
} else {
    // 取得した経度, 緯度を gnavi_search に投げて、お店の情報を得る
    $shop_info = gnavi_search($latitude, $longitude);
    $response_format_text = ['contentType'=>1,"toType"=>1,"text"=>"${shop_info}"];
    create_post_data($to, $response_format_text);
    post($post_data);
}
