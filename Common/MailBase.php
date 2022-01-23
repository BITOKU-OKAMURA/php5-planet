<?php
require PREFIX . '/Define/stdafx.php';
/*
//------------------------------------------------------------------------------
// パラメータ名のDefine
//------------------------------------------------------------------------------
define("User",0);//ユーザネーム
define("Pass",1);//パスワード
define("Host",2);//接続ホスト
define("Port",3);//接続ポート
define("Name",4);//DB名
*/
/**
 * Mail基底クラス
 *
 * @package
 * @author 2009/4/23 Yoshinori.Okamura
 * @copyright
 * @version CVS:
 */
class MailBase extends CommonBase {
    /**
     *
     * @access      public
     * @var         array           $db
     */
    protected $PHPMailer;
    /**
     * メールのテンプレート名
     * @access      public
     * @var         array           $db
     */
    public $mail_template_name;
    /**
     * メールのテンプレート内容
     * @access      public
     * @var         array           $db
     */
    public $mail_template_body;
    /**
     * 宛先
     * @access      public
     * @var         array           $db
     */
    protected $send_adress;
    /**
     * CC
     * @access      public
     * @var         array           $db
     */
    protected $cc_adress;
    /**
     * BCC
     * @access      public
     * @var         array           $db
     */
    protected $bcc_adress;
    /**
     * メールの掲題
     * @access      public
     * @var         array           $db
     */
    protected $subject;
    /**
     * コンストラクタ
     *
     *
     *
     *
     * @param $mail_template_name メールのテンプレート名
     * @param $send_adress        宛先のメール名
     * @param $subject           メールの掲題
     * @return 成功
     */
    public function __construct($mail_template_name = null, $send_adress = null, $cc_adress = null, $subject = null, $bcc_adress = null) {
        //--------------------------------------------------------------------------
        // 引数チェック
        //--------------------------------------------------------------------------
        if ($mail_template_name == null || $send_adress == null || $subject == null || strpos($send_adress, '@hotmail.com') !== false) return false;
        //--------------------------------------------------------------------------
        // スーパークラスのコンストラクタ
        //--------------------------------------------------------------------------
        parent::__construct();
        //--------------------------------------------------------------------------
        // ウェブエックス対大企業の特別対応。メールアドレス書き換え
        //--------------------------------------------------------------------------
        //$send_adress=str_replace('qcells@web-x.co.jp',DEBUG_TO,$send_adress);
        //--------------------------------------------------------------------------
        // 終端クラスなので例外でハンドルする
        //--------------------------------------------------------------------------
        try {
            //--------------------------------------------------------------------------
            // コネクションハンドルの初期設定
            //--------------------------------------------------------------------------
            $this->PHPMailer = new PHPMailer();
            //--------------------------------------------------------------------------
            // 引数をメンバ変数に反映
            //--------------------------------------------------------------------------
            $debug_sub = '【デバッグモード】' . $subject;
            $this->send_adress = DEBUG ? DEBUG_TO : $send_adress;
            $this->cc_adress = DEBUG ? null : $cc_adress;
            $this->bcc_adress = DEBUG ? null : $bcc_adress;
            $this->subject = $subject;
            $this->subject = DEBUG ? $debug_sub : $subject;
            $this->mail_template_name = $mail_template_name;
            $this->mail_template_body = file_get_contents(MAIL_TEMP_DIR . '/' . $mail_template_name . '.txt');
            $this->mal_logging('◇ Mail送信時本当の宛先=>', $send_adress);
            $this->mal_logging('◇ Mail送信時本当のCC=>', $cc_adress);
            $this->mal_logging('◇ Mail送信時本当のBCC=>', $bcc_adress);
        }
        catch(PDOException $e) {
            $this->mal_logging('◆ Mail送信時例外発生=>', $e->getMessage());
            trigger_error($e->getMessage(), E_USER_WARNING);
        }
        return;
    }
    /**
     * メイルを送信する
     *
     * @param $mail_template_name メールのテンプレート名
     * @param $send_adress        宛先のメール名
     * @param $subject           メールの掲題
     * @return 成功
     ※ $ex_from 送信者情報 [メイルアドレス<>送信者名]
     */
    public function send_mail($mail_body = null, $ex_from = null) {
        //--------------------------------------------------------------------------
        // 引数チェック
        //--------------------------------------------------------------------------
        if ($mail_body == null) {
            return false;
        }
        //--------------------------------------------------------------------------
        // インジェクション対策の復旧
        //--------------------------------------------------------------------------
        $mail_body = str_replace('\n', "\n", $mail_body);
        //--------------------------------------------------------------------------
        // 業腹だが、例外でしかハンドリングできない。
        //--------------------------------------------------------------------------
        try {
            $this->mal_logging('宛先', $this->send_adress);
            $this->mal_logging('CC', $this->cc_adress);
            $this->mal_logging('BCC', $this->bcc_adress);
            $this->mal_logging('題名', $this->subject);
            $this->mal_logging('本文', $mail_body);
            //--------------------------------------------------------------------------
            // 送信者
            //--------------------------------------------------------------------------
            $this->PHPMailer->From = !$ex_from ? MAIL_FROM : explode('<>', $ex_from) [0];
            $this->PHPMailer->Sender = !$ex_from ? MAIL_FROM : explode('<>', $ex_from) [0];
            $this->PHPMailer->FromName = !$ex_from ? mb_encode_mimeheader(FROM_NAME) : mb_encode_mimeheader(explode('<>', $ex_from) [1]);
            $this->mal_logging('送信者名', $this->PHPMailer->FromName);
            $this->mal_logging('送信者アドレス', $this->PHPMailer->From);
            $this->mal_logging('テンプレート名', $this->mail_template_name);
            //--------------------------------------------------------------------------
            // 宛先、CC
            //--------------------------------------------------------------------------
            $this->PHPMailer->ClearAddresses();
            $this->PHPMailer->ClearBCCs();
            $this->PHPMailer->ClearCCs();
            $this->PHPMailer->addAddress($this->send_adress);
            if ($this->cc_adress) {
                foreach (explode(';', $this->cc_adress) as $line_args) $this->PHPMailer->AddCC($line_args);
            }
            //--------------------------------------------------------------------------
            // (メルマガ用)BCCの処理
            //--------------------------------------------------------------------------
            if ($this->bcc_adress) {
                foreach (explode(';', $this->bcc_adress) as $line_args) $this->PHPMailer->AddBcc($line_args);
            }
            $this->PHPMailer->Subject = mb_encode_mimeheader($this->subject);
            //HTMLメールの有効無効
            $this->PHPMailer->isHTML(explode('_', $this->mail_template_name) [0] === 'HTML' ? true : false);
            //--------------------------------------------------------------------------
            // 本文:ISO-2022-JP
            //--------------------------------------------------------------------------
            $this->PHPMailer->Encoding = "7bit";
            $this->PHPMailer->CharSet = 'ISO-2022-JP';
            $this->PHPMailer->Body = mb_convert_encoding(str_replace("\xc2\xa0", " ", $mail_body), "JIS", "UTF-8");
            $this->PHPMailer->send();
            //--------------------------------------------------------------------------
            // 添付ファイル
            //--------------------------------------------------------------------------
            //$this->PHPMailer->addAttachment('testfile.pdf');
            
        }
        catch(PDOException $e) {
            $this->mal_logging('◆ Mail送信時例外発生=>', $e->getMessage());
            trigger_error($e->getMessage(), E_USER_WARNING);
        }
        return;
    }
    /* デストラクタ */
    public function __destruct() {
        //--------------------------------------------------------------------------
        // スーパークラスのデストラクタ
        //--------------------------------------------------------------------------
        parent::__destruct();
    }
    /**
     * phpmailerのログを出力する
     *
     * <pre> 概要:DBのログを取得していないという苦情があったので
     * 、db_loggingというメンバ名にして仕事しますアピールを実施。</pre>
     *
     * @author 2009/4/22 Yoshinori.Okamura
     * @param String $debug_msg デバックメッセージ
     * @param Mix $output_args 値を出力させたい変数又は配列
     * @return true,false （余り意味は無い）
     */
    protected function mal_logging($debug_msg = null, $output_args = null) {
        //--------------------------------------------------------------------------
        // 実際はcom_debugのラッパー。個別に調査するときに使用する
        //--------------------------------------------------------------------------
        return $this->com_debug($debug_msg, $output_args, 'Mail_Debug');
    }
}
