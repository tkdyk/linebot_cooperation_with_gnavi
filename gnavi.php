<?php
/*****************************************************************************************
 　ぐるなびWebサービスのレストラン検索APIで緯度経度検索を実行しパースするプログラム
 　注意：緯度、経度、範囲の値は固定で入れています。
 　　　　アクセスキーはユーザ登録時に発行されたキーを指定してください。
*****************************************************************************************/

function gnavi_search($lat, $lon){
    //エンドポイントのURIとフォーマットパラメータを変数に入れる
    $uri   = "http://api.gnavi.co.jp/RestSearchAPI/20150630/";
    //APIアクセスキーを変数に入れる
    $acckey= "< gnavi access key >";
    //返却値のフォーマットを変数に入れる
    $format= "json";
    //範囲を変数に入れる
    $range = 1;
     
    //URL組み立て
    $url  = sprintf("%s%s%s%s%s%s%s%s%s%s%s%s", $uri, "?format=", $format, "&keyid=", $acckey, "&latitude=", $lat ,"&longitude=", $lon ,"&range=", $range, "&hit_per_page=100");
    //API実行
    $json = file_get_contents($url);
    //取得した結果をオブジェクト化
    $obj  = json_decode($json);
    
    //結果のうちお店をランダムに抽出
    $count = $obj->{"total_hit_count"};
    $max = $count - 1;

    //たまに empty 返すので empty だったらリトライ
    while(empty($shop_name)){
        $num = array_rand(range(1,$max),1);
        $shop_name = $obj->{"rest"}["$num"]->{'name'};
        $shop_station = $obj->{"rest"}["$num"]->{'access'}->{'station'};
        $shop_walk = $obj->{"rest"}["$num"]->{'access'}->{'walk'};
        $shop_pr_short = $obj->{"rest"}["$num"]->{'pr'}->{'pr_short'};
        $shop_category = $obj->{"rest"}["$num"]->{'category'};
        $shop_url = $obj->{"rest"}["$num"]->{'url'};
    }
    
    //抽出した店を表示
    $post = "${shop_name}\t${shop_station}${shop_walk}分\nカテゴリ: ${shop_category}\n${shop_url}";
    return $post;
}
?>
