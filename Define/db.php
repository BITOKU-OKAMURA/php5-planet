<?php
//------------------------------------------------------------------------------
// パラメータ名のDefine
//------------------------------------------------------------------------------
define("User",0);//ユーザネーム
define("Pass",1);//パスワード
define("Host",2);//接続ホスト
define("Port",3);//接続ポート
define("Name",4);//DB名
define('Persistent',5);//永続接続の有効無効
define('Timeout',7);//タイムアウト秒数

//------------------------------------------------------------------------------
// dbconnect_d 接続情報
//------------------------------------------------------------------------------
define('dbconnect_d', array(
    'd',//ユーザネーム
    'J',//パスワード
    '127.0.0.1',//接続ホスト
    '5432',//接続ポート
    'd',//DBネーム
    true,//永続接続の有効無効
    5,//タイムアウト秒数
));

