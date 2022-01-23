<?php
require PREFIX . '/Define/stdafx.php';
/**
 * CommonBase
 *
 * @package
 * @author
 * @copyright
 * @version CVS:
 */
class CommonBase {
    /**
     * デバッグログのパス
     * @access	public
     * @var
     */
    protected $DEBUG_LOG;
    protected $ERROR_LOG;
    /**
     * Common 参照・基底クラス
     *
     * Action参照に於ける共通基底クラスとして機能する。
     *
     * @package bgent
     * @author 2016/5/20 Yoshinori.Okamura(CLOWNCONSULTING, Inc)
     * @copyright CLOWNCONSULTING, Inc
     * @version git:
     */
    public function __construct() {
        //------------------------------------------------------------------------------
        // ログフォルダが無ければ作成
        //------------------------------------------------------------------------------
        if (!file_exists(LOGDIR)) {
            mkdir(LOGDIR, 0777);
        }
        //------------------------------------------------------------------------------
        // デバッグ/エラーログファイルを定義
        //------------------------------------------------------------------------------
        $this->DEBUG_LOG = LOGDIR . '/Debug_' . date('Ymd') . '.log';
        $this->ERROR_LOG = LOGDIR . '/Error_' . date('Ymd') . '.log';
        //------------------------------------------------------------------------------
        // デバッグモードの定義。
        //------------------------------------------------------------------------------
        if (file_exists(PREFIX . '/Debug')) {
            // デバッグモード
            ini_set('display_errors', 1);
            ini_set('error_log', $this->DEBUG_LOG);
            ini_set('opcache.enable', 0);
        } else {
            // 本番モード
            ini_set('display_errors', 0);
            ini_set('error_log', $this->ERROR_LOG);
        }
    }
    /**
     * デバッグ・ログを出力する
     *
     * <pre> 概要:第1引数はデバッグメッセージ
     *       第2引数は指定した変数/配列をvar_dumpする</pre>
     *
     * @author 2009/4/22 Yoshinori.Okamura(Miyako Corp)
     * @param String $debug_msg デバックメッセージ
     * @param Mix $output_args 値を出力させたい変数又は配列
     * @return true,false （余り意味は無い）
     */
    protected function com_debug($debug_msg = null, $output_args = null, $ActionName = null) {
        //--------------------------------------------------------------------------
        //           デバッグメッセージが無ければfalseでreturn
        //--------------------------------------------------------------------------
        if (!$debug_msg) {
            return false;
        }
        //--------------------------------------------------------------------------
        //         出力変数、配列があるばあいはサーチする
        //--------------------------------------------------------------------------
        if ($output_args) {
            ob_start();
            var_dump($output_args);
            $buf = ob_get_contents();
            ob_end_clean();
            $debug_msg.= "\n" . $buf . "\n";
            $debug_msg = str_replace("\n\n", "\n", $debug_msg);
        }
        //--------------------------------------------------------------------------
        //         メッセージを生成する
        //--------------------------------------------------------------------------
        $Mes = date('Y-m-d [H:i:s]') . $ActionName . ': ' . $debug_msg . "\n";
        //--------------------------------------------------------------------------
        //         コンソール実行の場合はコンソールに出力
        //--------------------------------------------------------------------------
        if (php_sapi_name() == 'cli') {
            printf("%s", $Mes);
            return true;
        }
        //--------------------------------------------------------------------------
        //                     本番モードなら処理をしない
        //--------------------------------------------------------------------------
        if (!DEBUG) {
            return true;
        }
        //--------------------------------------------------------------------------
        //                        umaskを変更する
        //--------------------------------------------------------------------------
        umask(0111);
        //--------------------------------------------------------------------------
        //                    ログにデバッグを出力する
        //--------------------------------------------------------------------------
        $LOGFILE = !$ActionName ? $this->DEBUG_LOG : LOGDIR . '/' . $ActionName . '_' . date('Ymd') . '.log';
        if (!$ObjLogFile = fopen($LOGFILE, 'a')) {
            return false;
        }
        fwrite($ObjLogFile, $Mes);
        //--------------------------------------------------------------------------
        //              特に意味は無いがtrueでreturnする
        //--------------------------------------------------------------------------
        return true;
    }
    /**
     * デストラクタ
     *
     *
     * @author ネットから拾ってきた
     * @param String $debug_msg デバックメッセージ
     * @param Mix $output_args 値を出力させたい変数又は配列
     * @return true,false （余り意味は無い）
     */
    public function __destruct() {
        //echo "デストラクタ";
        
    }
    /**
     * 文字のエスケープを実施(検索用)
     *
     * PHP標準のやつが動いていないので自作
     *
     * @author ネットから拾ってきた
     * @param String $debug_msg デバックメッセージ
     * @param Mix $output_args 値を出力させたい変数又は配列
     * @return true,false （余り意味は無い）
     */
    public function com_search_escape($value) {
        /* sql関連をサニタイズ */
        $value = preg_replace("/;/", '&#59;', $value);
        $value = preg_replace("/\"/", '&quot;', $value);
        $value = preg_replace("/</", '&lt;', $value);
        $value = preg_replace("/>/", '&gt;', $value);
        //$value=preg_replace("/&/", '&gt;', $value);
        //$value=preg_replace("/\//", '&#47;', $value);
        //$value=preg_replace("/ /", '&nbsp;', $value);
        $value = preg_replace("/'/", '&rsquo;', $value);
        //$value=preg_replace("/:/", '58;', $value);
        $value = preg_replace("/--/", '&#45;&#45;', $value);
        $value = preg_replace("/\t/", '&#9;', $value);
        //$value=preg_replace("/\n/", '&#10;', $value);
        //$value=preg_replace("/\r/", '&#13;', $value);
        $value = preg_replace("/select /", 'select 　', $value);
        $value = preg_replace("/delete /", 'delete 　', $value);
        $value = preg_replace("/insert /", 'insert 　', $value);
        $value = preg_replace("/update /", 'update 　', $value);
        $value = preg_replace("/create /", 'create 　', $value);
        $value = preg_replace("/alter /", 'alter 　', $value);
        $value = preg_replace("/drop/", 'drop 　', $value);
        $value = preg_replace("/SELECT /", 'select 　', $value);
        $value = preg_replace("/DELETE /", 'delete 　', $value);
        $value = preg_replace("/INSERT /", 'insert 　', $value);
        $value = preg_replace("/UPDATE /", 'update 　', $value);
        $value = preg_replace("/CREATE /", 'create 　', $value);
        $value = preg_replace("/ALTER /", 'alter 　', $value);
        $value = preg_replace("/DROP /", 'drop 　', $value);
        //$value=preg_replace("/\,/", '，', $value);
        return $value;
    }
    /**
     * 文字のエスケープを実施
     *
     * PHP標準のやつが動いていないので自作
     *
     * @author ネットから拾ってきた
     * @param String $debug_msg デバックメッセージ
     * @param Mix $output_args 値を出力させたい変数又は配列
     * @return true,false （余り意味は無い）
     */
    public function com_string_escape($value) {
        /* sql関連をサニタイズ */
        $value = preg_replace("/;/", '；', $value);
        $value = preg_replace("/\"/", '”', $value);
        $value = preg_replace("/'/", '’', $value);
        $value = preg_replace("/</", '＜', $value);
        $value = preg_replace("/>/", '＞', $value);
        $value = preg_replace("/select /", 'select 　', $value);
        $value = preg_replace("/delete /", 'delete 　', $value);
        $value = preg_replace("/insert /", 'insert 　', $value);
        $value = preg_replace("/update /", 'update 　', $value);
        $value = preg_replace("/create /", 'create 　', $value);
        $value = preg_replace("/alter /", 'alter 　', $value);
        $value = preg_replace("/drop /", 'drop 　', $value);
        $value = preg_replace("/SELECT /", 'select 　', $value);
        $value = preg_replace("/DELETE /", 'delete 　', $value);
        $value = preg_replace("/INSERT /", 'insert 　', $value);
        $value = preg_replace("/UPDATE /", 'update 　', $value);
        $value = preg_replace("/CREATE /", 'create 　', $value);
        $value = preg_replace("/ALTER /", 'alter 　', $value);
        $value = preg_replace("/DROP /", 'drop 　', $value);
        $value = preg_replace("/＜＞/", '<>', $value);
        //$value=preg_replace("/\r\n/", '\n', $value);
        //$value=preg_replace("/\,/", '，', $value);
        return $value;
    }
    /**
     * 文字のエスケープを復元
     *
     * PHP標準のやつが動いていないので自作
     *
     * @author ネットから拾ってきた
     * @param String $debug_msg デバックメッセージ
     * @param Mix $output_args 値を出力させたい変数又は配列
     * @return true,false （余り意味は無い）
     */
    public function com_string_reverseesacpe($value) {
        /* sql関連をサニタイズ */
        $value = preg_replace("/&#59;/", ';', $value);
        $value = preg_replace("/&quot;/", '"', $value);
        //$value=preg_replace("/&lt;/", '<', $value);
        //$value=preg_replace("/&gt;/", '>', $value);
        //$value=preg_replace("/&/", '&gt;', $value);
        //$value=preg_replace("/\//", '&#47;', $value);
        $value = preg_replace("/&nbsp;/", ' ', $value);
        $value = preg_replace("/&rsquo;/", '\'', $value);
        //$value=preg_replace("/:/", '58;', $value);
        $value = preg_replace("/&#45;&#45;/", '--', $value);
        $value = preg_replace("/&#9;/", "\t", $value);
        return $value;
    }
    /**
     * ランダム文字列生成 (英数字)
     * $length: 生成する文字数
     */
    public function makeRandStr($length = 8) {
        $str = array_merge(range('0', '9'), range('A', 'Z'));
        $r_str = null;
        for ($i = 0;$i < $length;$i++) {
            $r_str.= $str[rand(0, count($str) - 1) ];
        }
        return $r_str;
    }
    /**
     * ファイルをダウンロード
     *
     * 引数はターゲットファイルへの相対又は絶対パス
     *
     * @author ネットから拾ってきた
     * @param String $debug_msg デバックメッセージ
     * @param Mix $output_args 値を出力させたい変数又は配列
     * @return true,false （余り意味は無い）←cakephpの場合であって本FWは重要。
     */
    public function download_file($path_file = null, $pdfout = false) {
        /* ファイルの存在確認 */
        if (!file_exists($path_file)) return false;
        /* オープンできるか確認 */
        if (!($fp = fopen($path_file, "r"))) return false;
        fclose($fp);
        /* ファイルサイズの確認 */
        //if (($content_length = filesize($path_file)) == 0)
        //    new SystemController(500);
        $content_length = filesize($path_file); //0バイトを許容する。
        //日本語ファイル名対応
        $rtf1 = explode('/', $path_file);
        $rtf2 = $rtf1[count($rtf1) - 1];
        $rtf3 = str_replace(' ', '_', $rtf2);
        if ($pdfout) {
            /* PDFのHTTPヘッダ送信 */
            header("Content-Disposition: inline; filename={$rtf3}");
            header("Content-Length: " . $content_length);
            header("Content-Type: application/pdf");
        } else {
            /* ダウンロード用のHTTPヘッダ送信 */
            header("Content-Disposition: attachment;filename=" . $rtf3);
            header("Content-Length: " . $content_length);
            header("Content-Type: application/octet-stream");
        }
        /* ファイルを読んで出力 */
        if (!readfile($path_file)) return false;
        else return true;
    }
}
