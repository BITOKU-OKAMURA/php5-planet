<?php
use Ramsey\Uuid\Uuid;
require PREFIX . '/Define/stdafx.php';
/**
 * ActionBase
 *
 * @package
 * @author         2016/08/06 Yoshinori.Okamura
 * @copyright
 * @version CVS:
 */
class ActionBase extends CommonBase {
    /**
     * アクションネーム ～ControlloerにControlloerを抜く。メインtmplateや、BL名、Viewクラス名もこの命名規則に準する
     * @access	public
     * @var
     */
    protected $ActionName;
    /**
     * 確定したreqest Slimの残骸として _REQESTの代わりとして使用
     * @access	public
     * @var
     */
    protected $request;
    /**
     * パラメータのチェック設定
     * @access	public
     * @var		array		$aryAvtionChk
     */
    //パラメータ名は名前
    /*
    #正規表現（数字）						(^\-?[0-9]+)
    #正規表現（英字）						([a-zA-Z]+)
    #正規表現（英数字）						([a-zA-Z0-9]+)
    #正規表現（メールアドレス）					(^[^@]+@[^.]+\..+)
    #正規表現（英数字記号）						([a-zA-Z\\'0-9\^\\+-/\,\.:\?=|&*\(\)_]+)
    #正規表現（数字とドット「.」※IPアドレス用）			(([0-9]){1,3}\.([0-9]){1,3}\.([0-9]){1,3}\.([0-9]){1,3})
    #正規表現（（YYYY/MM/DD or YYYY-MM-DD or YYYY.MM.DD）日付用）	([0-9][0-9][0-9][0-9][\/\-\.][0-9]([0-9]?)[\/\-\.][0-9]([0-9]?)
    #正規表現（（HH:MM:SS or HH:MM）時間用）			([0-9]([0-9]?)\:[0-9]([0-9]?)(\:?)([0-9]*)
    #[GET|POST|ALL] [非存在エラー] [チェック方式] [チェック内容] [文字列長 Mはノーチェック] [エラー出力]
    #t_showType_t_10 showType NO ITEM Alarm.showType.item M PARAM_ERROR
    t_showType_t_10 showType NO REG (^\-?[0-9]+) 5 VALUE_ERROR
    #t_showType_t_10 showType NO SPRIT 1<>2<>3 M PARAM_ERROR
    REG|SPRIT|FILTER_VAR|NOCHECK
    */
    protected $ChkParam = array();
    /**
     * Action 参照・基底クラス
     *
     * Action参照に於ける共通基底クラスとして機能する。
     *
     * @package bgent
     * @author 2016/5/20 Yoshinori.Okamura(CLOWNCONSULTING, Inc)
     * @copyright CLOWNCONSULTING, Inc
     * @version git:
     */
    public function __construct($ControllerName, $cli = null) {
        //--------------------------------------------------------------------------
        // スーパークラスのコンストラクタ
        //--------------------------------------------------------------------------
        parent::__construct();
        //------------------------------------------------------------------------------
        // バッチ対策
        //------------------------------------------------------------------------------
        ini_set('memory_limit', -1);
        //------------------------------------------------------------------------------
        // 旧BGENTと分けるために、セッションはここで発行 ※セッションファイルの中身はrootで見るしかない。
        //------------------------------------------------------------------------------
        $coccke_max = 2147353200 - time();
        ini_set('session_cookie_httponly', 1);
        ini_set('session.gc_probability', 0);
        ini_set('session.gc_divisor', 1000);
        ini_set('session.gc_maxlifetime', $coccke_max);
        ini_set('session.cookie_lifetime', $coccke_max);
        //ini_set('cookie_lifetime', 0);
        //ini_set('session.gc_maxlifetime', 24*60*30);
        if (session_status() == PHP_SESSION_DISABLED || session_status() == PHP_SESSION_NONE && php_sapi_name() != 'cli') {
            ini_set('session.use_trans_sid', 1);
            session_start();
        }
        //--------------------------------------------------------------------------
        // php.ini がちゃんと効いていないかもしれないらしいのでここで定義
        //--------------------------------------------------------------------------
        mb_language("Japanese");
        mb_internal_encoding("UTF-8");
        //--------------------------------------------------------------------------
        // コントローラーネームからアクションネームに移行
        //--------------------------------------------------------------------------
        $this->ActionName = str_replace("Controller", "", $ControllerName);
        //--------------------------------------------------------------------------
        // 処理の開始(コピペ元用)
        //--------------------------------------------------------------------------
        //$this->action_debug(__FUNCTION__.' Start.');
        //--------------------------------------------------------------------------
        // セッションの基幹となるUUIDを付与
        //--------------------------------------------------------------------------
        if (!isset($_SESSION['UUID'])) {
            ob_start();
            echo Uuid::uuid4();
            $_SESSION['UUID'] = ob_get_contents();
            ob_end_clean();
        }
        //--------------------------------------------------------------------------
        // 最終アクセス時刻を更新
        //--------------------------------------------------------------------------
        $_SESSION['LAST_ACCESS'] = date('Y-m-d H:i');
        //--------------------------------------------------------------------------
        // コンソール実行時のエラー対策
        //--------------------------------------------------------------------------
        if (!isset($_SERVER["REQUEST_METHOD"])) {
            $_SERVER["REQUEST_METHOD"] = null;
            $_SERVER["REQUEST_URI"] = null;
        }
        //--------------------------------------------------------------------------
        // POSTの場合はGETに転送して終了 しかしファイルがある場合は当然ユーザには辛抱してもらう
        //--------------------------------------------------------------------------
        $post_flg = strpos($_SERVER["REQUEST_URI"], 'update') !== false || strpos($_SERVER["REQUEST_URI"], 'pApi') !== false ? false : true;
        if (count($_FILES) < 1 && mb_strtolower($_SERVER["REQUEST_METHOD"]) == 'post' && $post_flg == true) {
            $_SESSION['POST'] = $_POST;
            $this->action_debug(__FUNCTION__ . '◇_POST to Session Copy Complete.', $_SESSION['POST']);
            $this->action_debug('同一URLに転送を実施', $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
            header('Location: //' . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
            exit;
        } else {
            //--------------------------------------------------------------------------
            // GETメソッドで セッションにPOSTがあった場合は、元のPOSTに戻す
            //--------------------------------------------------------------------------
            if (isset($_SESSION)) {
                if (isset($_SESSION["POST"]) && $post_flg == true) {
                    $_POST = $_SESSION["POST"];
                }
                unset($_SESSION["POST"]);
            }
        }
        //--------------------------------------------------------------------------
        // ログイン状態の確認
        //--------------------------------------------------------------------------
        $_SESSION["is_auth"] = false;
        if (isset($_SESSION["LOGIN"]["id"]) && (int)$_SESSION["LOGIN"]["id"] > 0) {
            $_SESSION["is_auth"] = true;
        }
        //--------------------------------------------------------------------------
        // パンドラにuuidがある場合のみ更にUUIDをチェック
        //--------------------------------------------------------------------------
        if (isset($_POST["uuid"])) {
            if ($_POST["uuid"] != $_SESSION['UUID']) {
                $_SESSION["is_auth"] = false;
                $_SESSION['UUID'] = null;
            }
        }
        //grep '4146f83d-5866-4373-b615-9db02b876eed'  /tmp/fcgi_temp/sess_* ini_get('session.save_path')
        //--------------------------------------------------------------------------
        // is_auth がfalseなら配列を削除
        //--------------------------------------------------------------------------
        if ($_SESSION["is_auth"] != true) unset($_SESSION["LOGIN"]);
        //--------------------------------------------------------------------------
        // 手動実行の入力定義
        //--------------------------------------------------------------------------
        if (php_sapi_name() == 'cli') {
            //------------------------------------------------------------------------------
            // コマンド実行なので詳細エラーを出力
            //------------------------------------------------------------------------------
            ini_set('error_reporting', E_ALL);
            ini_set('display_errors', 1);
            //------------------------------------------------------------------------------
            // CLI専用ログインファイルの読み込み
            //------------------------------------------------------------------------------
            require PREFIX . '/Util/LoginAcount/cli_login.php';
            //--------------------------------------------------------------------------
            // 引数不足のフォロー
            //--------------------------------------------------------------------------
            if (!is_array($cli)) {
                $cli = array(null, null, null . null);
            }
            $cli[2] = isset($cli[2]) ? $cli[2] : null;
            $cli[3] = isset($cli[3]) ? $cli[3] : null;
            //--------------------------------------------------------------------------
            // POSTは標準入力から echo 'a=1&b=2の形で実施'
            //--------------------------------------------------------------------------
            foreach (explode('&', trim(fgets(STDIN))) as $stdin_args) {
                $name = explode('=', $stdin_args) [0]??null;
                $value = explode('=', $stdin_args) [1]??null;
                if ($name) {
                    $this->request['post']["$name"] = $value;
                }
            }
            unset($stdin_args);
            //--------------------------------------------------------------------------
            // GETは第1引数(arg[2])  'a=1&b=2の形で実施'
            //--------------------------------------------------------------------------
            foreach (explode('&', $cli[2]) as $get_args) {
                $name = explode('=', $get_args) [0]??null;
                $value = explode('=', $get_args) [1]??null;
                if ($name) {
                    $this->request['get']["$name"] = $value;
                }
            }
            unset($get_args);
            //--------------------------------------------------------------------------
            // URIは第2引数(arg[3])  'aaa/bbb/ccccの形で実施'
            //--------------------------------------------------------------------------
            $this->request['uri'] = explode('/', $cli[3]);
            //--------------------------------------------------------------------------
            // 偽ログインIDがGET で _loginid=<ログインID>から Util/LoginAcount/cli_login.php から拾ってくる。
            //--------------------------------------------------------------------------
            if (!isset($this->request['get'])) {
                $_SESSION["LOGIN"] = unserialize($cli_login[1]);
            } else {
                if (isset($this->request['get']['_loginid'])) {
                    $_SESSION["LOGIN"] = unserialize($cli_login[(int)$this->request['get']['_loginid']]);
                } else {
                    $_SESSION["LOGIN"] = unserialize($cli_login[1]);
                }
            }
            //--------------------------------------------------------------------------
            // CLIは無条件でログイン済
            //--------------------------------------------------------------------------
            if (!isset($_SESSION["LOGIN"]["id"])) {
                $this->action_debug('◆ コマンド実行時のパラメータ・エラー発生');
                exit;
            }
            $_SESSION["is_auth"] = true;
        } else {
            //--------------------------------------------------------------------------
            // Webリクエストの場合は、ここでリクエスト内容を確定 Slimのreqestと同様の扱い
            //--------------------------------------------------------------------------
            $this->request['get'] = $_GET??null;
            $this->request['post'] = $_POST??null;
            $this->request['uri'] = null;
            if (count($_FILES) > 0) {
                $this->request['file'] = $_FILES;
            }
            if (!isset($_SERVER['QUERY_STRING']) || !isset($_SERVER["REQUEST_URI"])) {
                die('正当なレスポンスを取得出来なかったので処理を中断します。「更新」ボタンを押下してください。');
            }
            //-------------------------------------------------------------------------------------------
            // URIの区切りを展開 QUERY_STRINGは除外するようにする
            //-------------------------------------------------------------------------------------------
            $i = 0;
            foreach (explode('/', strlen($_SERVER['QUERY_STRING']) > 0 ? explode('?', $_SERVER["REQUEST_URI"]) [0] : $_SERVER["REQUEST_URI"]) as $key => $uri_args) {
                if (!$uri_args && strlen($uri_args) < 1) {
                    continue;
                }
                $this->request['uri'][$i] = explode('?', $uri_args) [0];
                $i++;
            }
        }
        //--------------------------------------------------------------------------
        // ※品質向上のため、_SERVERはここで消す。BLで使う用事があるならなおさらここに記載
        //--------------------------------------------------------------------------
        $this->request['method'] = $_SERVER["REQUEST_METHOD"]??null;
        $this->request['host'] = $_SERVER["HTTP_HOST"]??null;
        $this->request['referer'] = $_SERVER["HTTP_REFERER"]??null;
        $this->request['user_agent'] = $_SERVER["HTTP_USER_AGENT"]??null;
        $this->request['uuid'] = $_SESSION['UUID'];
        $this->request['remote_addr'] = $_SERVER['REMOTE_ADDR']??null;
        $this->request['back_url'] = $_SERVER["REQUEST_URI"]??'/';
        unset($_SERVER, $_GET, $_POST, $_REQUEST);
        //$this->action_debug('リクエスト内容($this->request):',$this->request);
        //--------------------------------------------------------------------------
        // 本番等の例外は除いて レファラが無い場合は処理を中断
        //--------------------------------------------------------------------------
        if (!$this->request['referer'] && php_sapi_name() != 'cli') {
            unset($_SESSION["LOGIN"]);
        }
        //--------------------------------------------------------------------------
        // メディアとマイページ、管理画面で整合性をとる
        //--------------------------------------------------------------------------
        if ($_SESSION["is_auth"] && php_sapi_name() != 'cli') {
            if (strpos($this->ActionName, 'Mypage') !== false || strpos($this->ActionName, 'Media') !== false) {
                //prefixが含まれれば管理画面でログイン中
                if (isset($_SESSION["LOGIN"]["prefix"])) {
                    unset($_SESSION["LOGIN"]);
                    exit;
                }
            } else {
                //prefixが含まれ無ければメディアでログイン中
                if (!isset($_SESSION["LOGIN"]["prefix"]) || !$_SESSION["LOGIN"]["prefix"]) {
                    unset($_SESSION["LOGIN"]);
                    exit;
                }
            }
            //--------------------------------------------------------------------------
            // 代理店の整合性をとる。$_SESSION['LOGIN']['agent_mode']がキー
            //--------------------------------------------------------------------------
            if (strpos($this->ActionName, 'Agent') !== false) {
                if ((!isset($_SESSION['LOGIN']['agent_mode']) || !$_SESSION['LOGIN']['agent_mode'])) {
                    unset($_SESSION["LOGIN"]);
                    exit;
                }
            } else {
                if (isset($_SESSION['LOGIN']['agent_mode'])) {
                    unset($_SESSION["LOGIN"]);
                    exit;
                }
            }
        }
        //--------------------------------------------------------------------------
        // 処理の終了(コピペ元用)
        //--------------------------------------------------------------------------
        //$this->action_debug(__FUNCTION__.'◇ Complete.');
        
    }
    /**
     * Smartyにて表示
     *
     * <pre></pre>
     *
     *
     * @author 2017/1/12 Yoshinori.Okamura
     * @param
     */
    protected function SmartyRader($ActionName, $Data) {
        //--------------------------------------------------------------------------
        // コンソール呼び出しの場合の処理
        //--------------------------------------------------------------------------
        if (php_sapi_name() == 'cli') {
            echo '================ ' . $ActionName . ' Result ==============' . "\n";
            echo '$Data:' . "\n";
            var_dump($Data);
            echo "\n";
            echo "\n";
            exit;
        }
        //--------------------------------------------------------------------------
        // 基本的な設定 templates_c/* を chmod 666 を定期的にかけること！
        //--------------------------------------------------------------------------
        $smarty = new Smarty();
        $smarty->left_delimiter = '<!--{';
        $smarty->right_delimiter = '}-->';
        $smarty->template_dir = PREFIX . '/templates';
        $smarty->compile_dir = '/tmp/fcgi_temp/templates_c';
        //------------------------------------------------------------------------------
        // 本番と開発で挙動を変える
        //------------------------------------------------------------------------------
        if (!DEBUG) {
            ini_set('zlib.output_compression', 1);
            ini_set('zlib.output_compression_level', 9);
        } else {
            ini_set('zlib.output_compression', 0);
            $smarty->clearAllCache();
        }
        //--------------------------------------------------------------------------
        // エラーチェック:払い出しDataを作成
        //--------------------------------------------------------------------------
        if (!isset($_SESSION)) $_SESSION["LOGIN"] = null; //ハンドルできない不正URL対策
        $Error["LOGIN"] = $_SESSION["LOGIN"];
        $Error["meta_file"] = null;
        $Error["title_file"] = null;
        $Error["Action"] = null;
        //--------------------------------------------------------------------------
        // エラーチェック:Dataがnullの場合はシステムエラー画面に飛ばす
        //--------------------------------------------------------------------------
        if ($Data === FATAL_ERROR) {
            http_response_code(500);
            $smarty->assign("Data", $Error);
            $smarty->display('System/500.tpl');
            exit;
        }
        //--------------------------------------------------------------------------
        // エラーチェック:パラメータエラー
        //--------------------------------------------------------------------------
        if ($Data === PARAM_ERROR) {
            http_response_code(404);
            $smarty->assign("Data", $Error);
            $smarty->display('System/404.tpl');
            exit;
        }
        //--------------------------------------------------------------------------
        // 正常終了:指定したテンプレートを表示
        //--------------------------------------------------------------------------
        header('Cache-Control: max-age=' . expire_seconds);
        $smarty->assign("Data", $Data);
        $smarty->display($ActionName . '.tpl');
    }
    /**
     * AJAXのJSON出力にて表示
     *
     * <pre></pre>
     *
     *
     * @author 2017/1/12 Yoshinori.Okamura
     * @param
     */
    protected function AjaxRader($Data = null) {
        //--------------------------------------------------------------------------
        // コンソール呼び出しの場合の処理
        //--------------------------------------------------------------------------
        if (php_sapi_name() == 'cli') {
            echo '================ ' . $Data["Action"] . ' Result ==============' . "\n";
            echo '$Data:' . "\n";
            var_dump($Data);
            echo "\n";
            echo "\n";
            exit;
        }
        //--------------------------------------------------------------------------
        // 何はともあれヘッダを出力
        //--------------------------------------------------------------------------
        header("Content-Type: application/json; charset=utf-8");
        header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        // HTTP/1.1
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Cache-Control: post-check=0, pre-check=0', FALSE);
        //クリックジャッキング対策
        header('X-Frame-Options: SAMEORIGIN');
        //header('Access-Control-Allow-Origin: *');
        //header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');
        //header('Access-Control-Allow-Origin *;');
        //header('Access-Control-Allow-Methods "POST, GET, OPTIONS";');
        //header('Access-Control-Allow-Headers "Origin, Authorization, Accept";');
        //header('Access-Control-Allow-Credentials true;');
        //--------------------------------------------------------------------------
        // エラーチェック:Dataがnullの場合はシステムエラー画面に飛ばす
        //--------------------------------------------------------------------------
        if ($Data === FATAL_ERROR) {
            http_response_code(500);
            echo '';
            exit;
        }
        //--------------------------------------------------------------------------
        // エラーチェック:パラメータエラー
        //--------------------------------------------------------------------------
        if ($Data === PARAM_ERROR) {
            http_response_code(404);
            echo '';
            exit;
        }
        //--------------------------------------------------------------------------
        // AJAXを出力して終了
        //--------------------------------------------------------------------------
        http_response_code(200);
        echo json_encode($Data, JSON_PRETTY_PRINT);
        exit;
    }
    /* デストラクタ */
    public function __destruct() {
        //--------------------------------------------------------------------------
        // スーパークラスのデストラクタ
        //--------------------------------------------------------------------------
        parent::__destruct();
    }
    /**
     * アクション単位でデバッグ・ログを出力する
     *
     * <pre> 概要:第1引数はデバッグメッセージ
     *       第2引数は指定した変数/配列をvar_dumpする</pre>
     *
     * @author 2009/4/22 Yoshinori.Okamura(Miyako Corp)
     * @param String $debug_msg デバックメッセージ
     * @param Mix $output_args 値を出力させたい変数又は配列
     * @return true,false （余り意味は無い）
     */
    protected function action_debug($debug_msg = null, $output_args = null, $ActionName = null) {
        //--------------------------------------------------------------------------
        // 実際はcom_debugのラッパー。個別に調査するときに使用する
        //--------------------------------------------------------------------------
        return $this->com_debug($debug_msg, $output_args, $this->ActionName);
    }
    /**
     * aryAvtionChk を作る
     *
     * <pre></pre>
     *
     *
     * @author 2016/5/12 Yoshinori.Okamura(CLOWNCONSULTING, Inc)
     * @param Array $request コントローラから 引き渡された リクエスト チェックルールは各メンバ関数で定義済
     */
    /*
    
        $this->ChkParam=array(
            'sex'           => 'POST NO NOCHECK - M OK',
            'start_botton'  => 'POST NO NOCHECK - M OK'
        );
    
    */
    protected function MakearyAvtionChk($request) {
        //-------------------------------------------------------------------------------------------
        // 各メンバのルール一行に対して処理を行う
        //-------------------------------------------------------------------------------------------
        $i = 0;
        $return = array('result' => '', 'result_detail' => '',);
        $aryCheckParam = isset($request['ChkParam']) ? $request['ChkParam'] : $this->ChkParam;
        //-------------------------------------------------------------------------------------------
        // チェック用のコネクションを生成
        //-------------------------------------------------------------------------------------------
        $dbconn = pg_connect(" host=" . dbconnect_dairiten[2] . " port=" . dbconnect_dairiten[3] . " dbname=" . dbconnect_dairiten[4] . " user=" . dbconnect_dairiten[0] . " password=" . dbconnect_dairiten[1]);
        foreach ($aryCheckParam as $input_name => $record_args) {
            //-------------------------------------------------------------------------------------------
            // 入力配列の展開
            //-------------------------------------------------------------------------------------------
            $AryChkClum = explode(' ', $record_args);
            //-------------------------------------------------------------------------------------------
            // チェック結果の初期値を設定
            //----------------------------------------------------------------------------------------
            $error_flg = true; //error_flgを設定。最初は0
            $error_msg = $AryChkClum[5]; //error文字列
            //-------------------------------------------------------------------------------------------
            // チェックパラメタを配列化 #[GET|POST|ALL] [非存在エラー] [チェック方式] [チェック内容] [文字列長 Mはノーチェック] [対外的なエラー出力]
            //-------------------------------------------------------------------------------------------
            //0:[GET|POST|ALL]
            //1:[非存在エラー]
            //2:[チェック方式]
            //3:[チェック内容]
            //4:[文字列長 Mはノーチェック]
            //5:[エラー出力]
            //[エラー出力] 処理を中段する場合は 'PARAM_ERROR'を代入。ヴァリテーションバック時は VARITETION_BACKを代入
            //             エラー処理をしない場合は 'NO_ERROR'と表記する事。
            //-------------------------------------------------------------------------------------------
            // ①POST/GET/ALLのチェック
            //-------------------------------------------------------------------------------------------
            //各項目の値を定義(無いものはnullを明示的に代入)
            $get_value = !isset($request['get'][$input_name]) ? null : $request['get'][$input_name];
            $post_value = !isset($request['post'][$input_name]) ? null : $request['post'][$input_name];
            $all_value = !$get_value ? $post_value : $get_value;
            $value = null;
            //設定によってvalueを定義
            switch ($AryChkClum[0]) {
                case 'GET':
                    $value = $get_value;
                break;
                case 'POST':
                    $value = $post_value;
                break;
                case 'ALL':
                    $value = $all_value;
                break;
                default:
                    $value = null;
                break;
            }
            //-------------------------------------------------------------------------------------------
            // 配列、非配列別に文字列チェックを実施
            //-------------------------------------------------------------------------------------------
            $value_array = array();
            if (is_array($value)) {
                foreach ($value as $sub_balue) {
                    $error_flg = $this->vice_array_check($AryChkClum, $sub_balue);
                    //-------------------------------------------------------------------------------------------
                    // ⑤エラー時の処理 オペランドは $AryChkClum[5]
                    //-------------------------------------------------------------------------------------------
                    if ($error_flg == true) {
                        $error_msg = 'OK';
                    } else {
                        if ($AryChkClum[5] == 'NO_ERROR') {
                            $error_msg = 'OK';
                        } else {
                            $error_msg = $AryChkClum[5];
                        }
                    }
                    $value_array[] = pg_escape_string($this->com_string_escape($sub_balue));
                }
                unset($value);
                //-------------------------------------------------------------------------------------------
                // チェック済のパラメータを代入 SQLサニタイズされないので注意 ここだけ強制サニタイズ
                //-------------------------------------------------------------------------------------------
                $return[$input_name] = array('name' => $input_name, 'value' => $value_array, 'pg_value' => null, 'error' => $error_msg);
            } else {
                $error_flg = $this->vice_array_check($AryChkClum, $value);
                //-------------------------------------------------------------------------------------------
                // ⑤エラー時の処理 オペランドは $AryChkClum[5]
                //-------------------------------------------------------------------------------------------
                if ($error_flg == true) {
                    $error_msg = 'OK';
                } else {
                    if ($AryChkClum[5] == 'NO_ERROR') {
                        $error_msg = 'OK';
                    } else {
                        $error_msg = $AryChkClum[5];
                        $return['result_detail'].= $input_name . ':' . $error_msg . "\n";
                    }
                }
                //-------------------------------------------------------------------------------------------
                // チェック済のパラメータを代入 SQLサニタイズされないので注意
                //-------------------------------------------------------------------------------------------
                $return[$input_name] = array('name' => $input_name, 'value' => pg_escape_string($this->com_string_escape($value)), 'pg_value' => pg_escape_string($this->com_search_escape($value)), 'error' => $error_msg);
            }
            //-------------------------------------------------------------------------------------------
            // パラメータエラー、バリテーションバックが混じっていればresultを更新
            //-------------------------------------------------------------------------------------------
            if ($error_msg != 'OK' || $return['result'] == '') {
                $return['result'] = $error_msg;
            }
        }
        unset($i);
        //-------------------------------------------------------------------------------------------
        // リクエストURIを引き継ぎ
        //-------------------------------------------------------------------------------------------
        $return['uri'] = $request['uri']??'';
        $return['referer'] = $request['referer']??''; //API処理用
        //-------------------------------------------------------------------------------------------
        // ファイルの場合は別枠で処理
        //-------------------------------------------------------------------------------------------
        if (isset($request['file'])) {
            if (count($request['file']) > 0) {
                $return['file'] = $request['file'];
            }
        }
        //-------------------------------------------------------------------------------------------
        // 律儀にコネクションを切断
        //-------------------------------------------------------------------------------------------
        pg_close($dbconn);
        unset($dbconn);
        return $return;
    }
    /**
     * POSTの配列入力用に入力文字チェックを関数化する
     *
     * <pre></pre>
     *
     *
     * @author 2017/1/12 Yoshinori.Okamura
     * @param
     * @引数 $AryChkClum:チェック配列 , $value:検査対象文字列
     * @戻り値 bool形($error_flg)
     */
    private function vice_array_check($AryChkClum = null, $value = null) {
        if (!$AryChkClum) {
            return false;
        }
        $error_flg = true; //error_flgを設定。最初は0
        //-------------------------------------------------------------------------------------------
        // ③チェック方式に従ってチェックを実施　オペランドは $AryChkClum[3]
        //-------------------------------------------------------------------------------------------
        //REG:正規表見 SPRIT:<>区切りのスプリットのどれかの完全一致しないとエラー FILTER_VAR: php5.3のfilter_varを実行 NOCHECK:無条件でOK
        switch ($AryChkClum[2]) {
            case 'REG':
                mb_eregi($AryChkClum[3], (string)$value, $results);
                if (count($results) > 0) {
                    $error_flg = true;
                } else {
                    $error_flg = false;
                }
            break;
            case 'SPRIT':
                $one_check = array_search((string)$value, explode('<>', $AryChkClum[3]));
                if ($one_check > 0) {
                    $error_flg = true;
                } else {
                    $error_flg = false;
                }
            break;
            case 'FILTER_VAR':
                $operand = null;
                switch ($AryChkClum[3]) {
                    case 'FILTER_VALIDATE_IP';
                    $operand = FILTER_VALIDATE_IP;
                break;
                default:
                break;
            }
            $results = filter_var($value, $operand);
            if ($results != false) {
                $error_flg = true;
            } else {
                $error_flg = false;
            }
        break;
        case 'KANA':
            mb_regex_encoding("UTF-8");
            if (mb_ereg("^[ア-ン゛゜ァ-ォャ-ョー「」、]+$", (string)$value)) {
                $error_flg = true;
            } else {
                $error_flg = false;
            }
        break;
        case 'NOCHECK':
        default:
        break;
    }
    //-------------------------------------------------------------------------------------------
    // ④文字列長さチェック オペランドは $AryChkClum[4]
    //-------------------------------------------------------------------------------------------
    if ($AryChkClum[4] != 'M' && (int)$AryChkClum[4] > 0) {
        if (strlen($value) > (int)$AryChkClum[4]) {
            $error_flg = false;
        }
    }
    //-------------------------------------------------------------------------------------------
    // ②値が無い場合の対応 非存在エラー免除のためここに記載
    //-------------------------------------------------------------------------------------------
    if ($value == null) {
        $error_flg = $AryChkClum[1] == 'YES' ? false : true;
    }
    return $error_flg;
}
}
