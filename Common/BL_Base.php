<?php
require PREFIX . '/Define/stdafx.php';
/**
 * BL_Base
 *
 * @package
 * @author         2016/08/06 Yoshinori.Okamura
 * @copyright
 * @version CVS:
 */
class BL_Base extends CommonBase {
    /**
     * 戻るデータ文字列
     * @access	public
     * @var
     */
    public $Data;
    /**
     * アクションネーム
     * @access	public
     * @var
     */
    public $Action;
    /**
     * データベース
     * @access      public
     * @var
     */
    public $db;
    /**
     * BissinesLogic 参照・基底クラス
     *
     * BissinesLogic参照に於ける共通基底クラスとして機能する。
     *
     * @package bgent
     * @author 2016/5/20 Yoshinori.Okamura(
     * @copyright
     * @version git:
     */
    public function __construct($ActionName = null) {
        //--------------------------------------------------------------------------
        // スーパークラスのコンストラクタ
        //--------------------------------------------------------------------------
        parent::__construct();
        //--------------------------------------------------------------------------
        // ActionNameを定義
        //--------------------------------------------------------------------------
        $this->Action = $ActionName;
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
     * @author 2009/4/22 Yoshinori.Okamura(ウェブエックス株式会社 コマンディングオフィサー)
     * @param String $debug_msg デバックメッセージ
     * @param Mix $output_args 値を出力させたい変数又は配列
     * @return true,false （余り意味は無い）
     */
    public function action_debug($debug_msg = null, $output_args = null, $ActionName = null) {
        //--------------------------------------------------------------------------
        // 実際はcom_debugのラッパー。個別に調査するときに使用する
        //--------------------------------------------------------------------------
        return $this->com_debug($debug_msg, $output_args, $this->Action);
    }
    /**
     * スマートカラム用の関数。
     *
     * <pre> 概要:第1引数はデバッグメッセージ
     *       第2引数は指定した変数/配列をvar_dumpする</pre>
     *
     * @author 2009/4/22 Yoshinori.Okamura(ウェブエックス株式会社 コマンディングオフィサー)
     * @param String $debug_msg デバックメッセージ
     * @param Mix $output_args 値を出力させたい変数又は配列
     * @return true,false （余り意味は無い）
     */
    //文字列を配列に変換
    protected function smart_clum_to_array($string = null) {
        if (!$string) return array();
        $array = array();
        foreach (explode(DMT, rtrim(ltrim($string, DMT), DMT)) as $line_args) $array[] = ($line_args == 'null' || $line_args == 'NULL') ? null : $line_args;
        return $array;
        //return $string==null ? array() : explode(DMT, rtrim(ltrim($string, DMT), DMT));
        
    }
    //配列を文字列に変換
    protected function smart_clum_to_string($array = null) {
        if (!is_array($array)) {
            return false;
        }
        $return = DMT . implode(DMT, $array) . DMT;
        return $return == '<><>' ? '' : $return;
    }
    //検索文言を作成
    protected function smart_clum_search($string = null) {
        return $string == null ? null : ' like \'%' . DMT . $string . DMT . '%\' ';
    }
    //新規カラム形態を生成
    protected function smart_clum_new($string = null) {
        return $string == null ? null : '\'' . DMT . $string . DMT . '\'';
    }
    //カラムに値を追加
    protected function smart_clum_add($array = array(), $string = null) {
        if ($string == null) {
            return false;
        }
        if (!is_array($array)) $array = array();
        array_push($array, $string);
        return $array;
    }
    //カラムから値を削除
    protected function smart_clum_del($array, $string = null) {
        if (!is_array($array) || $string == null) {
            return false;
        }
        return array_values(array_diff($array, array($string)));
    }
    //カラム内全 like検索 引数:カラムまるごと
    protected function smart_clum_line_search($name = null, $string = null) {
        if (!$string || !$name) return null;
        $r_ary = array();
        foreach ($this->smart_clum_to_array($string) as $line_args) {
            $r_ary[] = ' ' . $name . ' ' . $this->smart_clum_search($line_args);
        }
        return ' (' . implode(" or ", $r_ary) . ') ';
    }
    //カラムから値を削除
    protected function smart_clum_decode($string = null) {
        if ($string == null) {
            return false;
        }
        return preg_replace("/＜＞/", '<>', $string);
    }
    //クラスの終わり
    
}
