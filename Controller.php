<?php
/**
 * コントローラー
 *
 * @package
 * @author         2016/08/06 Yoshinori.Okamura
 * @copyright
 * @version CVS:
 */
//------------------------------------------------------------------------------
// smart移行の対応
//------------------------------------------------------------------------------
$starty_dir = $_SERVER["REQUEST_URI"] === '@MediaTop' ? '../../../' : '../../';
//------------------------------------------------------------------------------
// Defineの定義。アクセス元からの相対ファイル指定。以後、PREFIX値を確定
//------------------------------------------------------------------------------
require_once $starty_dir . 'devel/SideBizzManage/Define/define.php';
//------------------------------------------------------------------------------
// Smarty他、composerで入れたパッケージの読み込み
//------------------------------------------------------------------------------
require PREFIX . '/vendor/autoload.php';
//------------------------------------------------------------------------------
// コマンドラインの判別 php_sapi_name() で判別する
//------------------------------------------------------------------------------
if (php_sapi_name() == 'cli') {
    if (!isset($argv[1])) {
        echo "コマンド : echo \"a=0&b=2\"|./index.php \"<コントローラー名>\" \"a=1&b=2\" \"aaa/bbb/cccc\"" . "\n";
        echo 'POSTは左側 GETは右側';
        echo '_loginid=<ログインID> をGETに指定することでそのログインIDでログイン可能' . "\n";
        exit;
    }
    $ControllerName = $argv[1] . 'Controller';
    $Controller = new $ControllerName($argv);
    exit;
}
//------------------------------------------------------------------------------
//新テク。仮運用中
//------------------------------------------------------------------------------
ini_set('error_reporting', E_ALL);
ini_set('log_errors', 'On');
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    // エラーを例外に変換する
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});
set_exception_handler(function ($e) {
    // display_errorsの値によって処理を変更する
    if (DEBUG) {
        $ObjLogFile = fopen(LOGDIR . '/Debug_' . date('Ymd') . '.log', 'a');
        fwrite($ObjLogFile, date('Y-m-d[H:i:s]') . ': ' . $e . "\n");
        unset($ObjLogFile);
        die('<pre>' . $e . '</pre>');
    } else {
        // エラーログに保存なりなんなりしてエラー画面表示
        $Controller = new SystemController(500, $e);
        exit;
    }
});
//------------------------------------------------------------------------------
// TOPページの処理 TopPageControllerを使用
//------------------------------------------------------------------------------
//$hatena=strlen($_SERVER["QUERY_STRING"]) > 0  ? '?' : '';
$URI = explode('?', $_SERVER["REQUEST_URI"]) [0];
if ($URI == "/index.php" || $URI == "/") {
    $Controller = new TopPageController();
    exit;
}
//------------------------------------------------------------------------------
// WEBルーティングの実施 Slimではなく、自作することにする
//------------------------------------------------------------------------------
$Route = AppRoute($URI);
$ControllerName = $Route ? $Route . 'Controller' : null;
//------------------------------------------------------------------------------
// 該当するページが無い場合は404ページに遷移して終了
//------------------------------------------------------------------------------
if ($ControllerName == null) $Controller = new SystemController(404);
else $Controller = new $ControllerName();
exit;
/**
 * ルーティングを判定する
 *
 * <pre> </pre>
 *
 * @author 2009/4/22 Yoshinori.Okamura
 * @param Regix:false→正規表現無し true→正規表現あり
 * @param URI:リクエストURI
 * @param Args:検索文字
 * @return true,false
 */
function AppRoute($URI = null) {
    //------------------------------------------------------------------------------
    // URIと一致するリクエストを探す
    //------------------------------------------------------------------------------
    $one_check = array_search($URI, route_reqest_uri);
    //------------------------------------------------------------------------------
    // 初期動作不良防止
    //------------------------------------------------------------------------------
    if (route_reqest_uri[0] == $URI && route_reqest_kubun[0] == 'Direct') return route_action_name[$one_check];
    //------------------------------------------------------------------------------
    // URIと一致するリクエストがあり、属性Directの場合はルーティングが確定する
    //------------------------------------------------------------------------------
    if ($one_check > 0 && route_reqest_kubun[$one_check] == 'Direct') return route_action_name[$one_check];
    //------------------------------------------------------------------------------
    // ループさせて正規表現ルーティングをチェックする
    //------------------------------------------------------------------------------
    $i = 0;
    $regix_action_name = null; //正規表現検索のアクション名
    foreach (route_reqest_uri as $route_reqest_uri_args) {
        if (route_reqest_kubun[$i] == "Direct") {
            $i++;
            continue;
        }
        mb_eregi($route_reqest_uri_args, $URI, $results);
        if (count($results) > 0) {
            //正規表現に引っかかったケース
            $regix_action_name = route_action_name[$i];
            break;
        }
        $i++;
    }
    return $regix_action_name;
}
