<?php
require PREFIX . '/Define/stdafx.php';
/**
 * DB基底クラス
 *
 * @package 
 * @author 2009/4/23 Yoshinori.Okamura
 * @copyright 
 * @version CVS:
 */
class DB_Base extends CommonBase {
    /**
     * DBハンドルオブジェクト用のメンバ変数
     * @access	public
     * @var		array		$db
     */
    protected $db;
    /**
     * DBデータベースの名前
     * @access	public
     * @var		array		$db
     */
    protected $DBName;
    /**
     * コンストラクタ
     *
     * <pre> 概要:DBに接続してオブジェクトを確保する。
     *       第1引数:インスタンスするDB名
     *       細かいパラメータは設定ファイルで行うものとする。
     *       成功時はインスタンスオブジェクトを、失敗時は メンバ変数
     *       db にエラー文を払い出しますのでis_stringでメンバ変数dbを
     *       チェックしてください。  以下呼び方
     *        $clsLogDB=new MyConnectDB('LOGDB');
     *        if(!is_object($clsLogDB->db))
     *            echo "ERROR\n";</pre>
     *
     * @param Array $コネクション情報
     * @return 成功:オブジェクト 失敗:false 呼び出し先にてエラーハンドルを行うこと。
     */
    public function __construct($connect_args) {
        //--------------------------------------------------------------------------
        // スーパークラスのコンストラクタ
        //--------------------------------------------------------------------------
        parent::__construct();
        //--------------------------------------------------------------------------
        // コネクションハンドルの初期設定
        //--------------------------------------------------------------------------
        $this->db = null;
        //--------------------------------------------------------------------------
        // ロギングの為、DB名を定義
        //--------------------------------------------------------------------------
        $this->DBName = $connect_args[Name];
        //--------------------------------------------------------------------------
        // DB接続を実施。postgressqlの場合 ※ポート番号で判定。sqlite3は課題。
        //--------------------------------------------------------------------------
        if ($connect_args[Port] == 5432) {
            try {
                $this->db = new PDO('pgsql:dbname=' . $connect_args[Name] . ' host=' . $connect_args[Host] . ' port=' . $connect_args[Port], $connect_args[User], $connect_args[Pass], array(PDO::ATTR_PERSISTENT => $connect_args[Persistent]));
                $this->db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
                $this->db->setAttribute(PDO::ATTR_TIMEOUT, isset($connect_args[Timeout]) ? $connect_args[Timeout] : 7);
                $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            }
            catch(PDOException $e) {
                $this->db_logging('◆ DB接続時例外発生=>', $e->getMessage());
                trigger_error($e->getMessage(), E_USER_WARNING);
            }
        }
        return;
    }
    /* デストラクタ */
    public function __destruct() {
        //--------------------------------------------------------------------------
        // スーパークラスのデストラクタ
        //--------------------------------------------------------------------------
        parent::__destruct();
        //--------------------------------------------------------------------------
        // コネクションハンドルのクローズ
        //--------------------------------------------------------------------------
        $this->db = null;
    }
    /**
     * テーブルのデータをリセット
     *
     * <pre> 概要:DBのログを取得していないという苦情があったので
     * 、db_loggingというメンバ名にして仕事しますアピールを実施。</pre>
     *
     * @author 2009/4/22 Yoshinori.Okamura(Miyako Corp)
     * @param String $debug_msg デバックメッセージ
     * @param Mix $output_args 値を出力させたい変数又は配列
     * @return true,false （余り意味は無い）
     */
    public function db_truncate($table_name = null) {
        //--------------------------------------------------------------------------
        // スーパークラスのデストラクタ
        //--------------------------------------------------------------------------
        $this->db->query('truncate table ' . $table_name . ' restart identity;');
        return;
    }
    /**
     * COPY文で挿入
     *
     * <pre> 概要:DBのログを取得していないという苦情があったので
     * 、db_loggingというメンバ名にして仕事しますアピールを実施。</pre>
     *
     * @author 2009/4/22 Yoshinori.Okamura(Miyako Corp)
     * @param String $debug_msg デバックメッセージ
     * @param Mix $output_args 値を出力させたい変数又は配列
     * @return true,false （余り意味は無い）
     */
    public function pgInsertByCopy($tableName, array $fields, array $records) {
        static $delimiter = "\t", $nullAs = '\\N';
        if (!$return = $this->db->pgsqlCopyFromArray($tableName, $records, $delimiter, addslashes($nullAs), implode(',', $fields))) return;
        $setval = $this->LoggingSQL('select max(plimary) from ' . $tableName . ';') ["max"] + 1;
        $this->db->query('select pg_catalog.setval(\'' . $tableName . '_plimary_seq\', ' . $setval . ', true);');
        return $return;
    }
    /**
     * DBのログを出力する
     *
     * <pre> 概要:DBのログを取得していないという苦情があったので
     * 、db_loggingというメンバ名にして仕事しますアピールを実施。</pre>
     *
     * @author 2009/4/22 Yoshinori.Okamura(Miyako Corp)
     * @param String $debug_msg デバックメッセージ
     * @param Mix $output_args 値を出力させたい変数又は配列
     * @return true,false （余り意味は無い）
     */
    protected function db_logging($debug_msg = null, $output_args = null, $ActionName = null) {
        //--------------------------------------------------------------------------
        // 実際はcom_debugのラッパー。個別に調査するときに使用する
        //--------------------------------------------------------------------------
        return $this->com_debug($debug_msg, $output_args, $this->DBName);
    }
    /**
     * ページャーを意識したselectを実行(フェッチオール前提)
     *
     * @param $page_in    : 何ページ目どうか nullだと件数取得 ※MAX ページは Service で処理! $maxPage=ceil($max/$content);
     * @param $content : ページあたりの表示件数 (初期値が5)
     * @param $order 並び順
     * @param $table : テーブル名
     * @param $select : セレクト部分
     * @param $option   : オプション部分
     * @param $
     * @return $return[検索結果]:[件数]:[現在のページ]:[最大ページ]
     */
    public function PageNate($page_in = 1, $content = 5, $order = null, $table = null, $select = null, $options = null) {
        //-----------------------------------------------------------------
        // 不正アクセス対策
        //-----------------------------------------------------------------
        if ((int)$page_in == 0 || (int)$content == 0 || strlen($table) < 1 || strlen($select) < 1 || $select == '*') return false;
        //----------------------------------------------------------------------
        // in() のエラー対策 in(NULL)にする
        //----------------------------------------------------------------------
        $options = str_replace('in()', 'in(NULL)', $options);
        //-----------------------------------------------------------------
        // 初期設定
        //-----------------------------------------------------------------
        $return = array();
        //-----------------------------------------------------------------
        // SQL文の組み立て カラム名[table_name].idは決め打ち
        //-----------------------------------------------------------------
        $sql = 'select ' . $select . ' from ' . $table . ' ' . $options . ' ' . $order . ' ';
        $count_sql = 'select count(distinct ' . $table . '.id) from ' . $table . ' ' . $options . ' ';
        $csv_sql = 'select ' . $select . ' from ' . $table . ' ' . $options;
        //-----------------------------------------------------------------
        // ぺーシャーの処理。maxとpage_inが数字であることが条件
        //-----------------------------------------------------------------
        $page_in = preg_match('/^[1-9][0-9]*$/', $page_in) ? $page_in : 1;
        //-----------------------------------------------------------------
        // 初期値
        //-----------------------------------------------------------------
        $set = $content * ($page_in - 1);
        //-----------------------------------------------------------------
        // 最大ページ数
        //-----------------------------------------------------------------
        try {
            $sth = $this->db->prepare($count_sql);
            $sth->execute();
            $count_result = $sth->fetch(PDO::FETCH_ASSOC);
        }
        catch(PDOException $e) {
            $this->db_logging('◆ ページネーションの最大ページ数調査時に例外発生=>', $e->getMessage());
            trigger_error($e->getMessage(), E_USER_WARNING);
            return false;
        }
        $return[rows] = array_shift($count_result);
        unset($count_resul);
        //-----------------------------------------------------------------
        // 正式なSQLを生成し、ログに記載
        //-----------------------------------------------------------------
        if ($content > 0) $sql = str_replace('  ', ' ', $sql . ' limit ' . $content . ' offset ' . $set . ';');
        $sql = str_replace('  ', ' ', $sql);
        $this->db_logging('PageNate SQL =>', $sql);
        //-----------------------------------------------------------------
        // SQLを実行して、結果を返す。検索結果が無い場合はnullではなく、array配列。nullやfalseはエラーの扱い
        //-----------------------------------------------------------------
        try {
            $sth = $this->db->prepare($sql);
            $sth->execute();
        }
        catch(PDOException $e) {
            $this->db_logging('◆ ページネーション時例外発生=>', $e->getMessage());
            trigger_error($e->getMessage(), E_USER_WARNING);
            return false;
        }
        $return['query_result'] = $return[rows] < $page_in || $page_in < 1 || $return[rows] == 0 ? array() : $sth->fetchall(PDO::FETCH_ASSOC);
        $return[current_page] = $page_in;
        $return[content] = $content;
        $return[max_page] = (int)ceil($return[rows] / $content);
        //-----------------------------------------------------------------
        // CSVダウンロード用のデータ
        //-----------------------------------------------------------------
        //$return['csv']=base64_encode($csv_sql);
        return $return;
    }
    /**
     * CSVデータを出力
     *
     * @sql 対象SQL
     * @sql 対象ID 一括削除を防止するために、新規以外でこれがないとエラーにする
     * @return $return[検索結果]:[件数]:[現在のページ]:[最大ページ]
     */
    public function CsvOutputSQL($sql = null) {
        if (!$sql) return false;
        $this->db_logging('PageNate SQL =>', $sql);
        //-----------------------------------------------------------------
        // SQLを実行して、結果を返す。検索結果が無い場合はnullではなく、array配列。nullやfalseはエラーの扱い
        //-----------------------------------------------------------------
        try {
            $sth = $this->db->prepare($sql);
            $sth->execute();
        }
        catch(PDOException $e) {
            $this->db_logging('◆ ページネーション時例外発生=>', $e->getMessage());
            trigger_error($e->getMessage(), E_USER_WARNING);
            return false;
        }
        return $sth->fetchall(PDO::FETCH_ASSOC);
    }
    /**
     * リカバリログ付きのSQLを実施 (フェッチオールさせないのが前提)
     *
     * @sql 対象SQL
     * @sql 対象ID 一括削除を防止するために、新規以外でこれがないとエラーにする
     * @return $return[検索結果]:[件数]:[現在のページ]:[最大ページ]
     */
    public function LoggingSQL($sql = null, $id = null) {
        //----------------------------------------------------------------------
        // in() のエラー対策 in(NULL)にする
        //----------------------------------------------------------------------
        $sql = str_replace('in()', 'in(NULL)', $sql);
        //----------------------------------------------------------------------
        // current transaction is aborted対策
        //----------------------------------------------------------------------
        //$this->db->query('rollback');
        //$this->db->query('begin');
        //$this->db->query('commit');
        //----------------------------------------------------------------------
        // 命令文を判別する。不正な命令文は排除
        //----------------------------------------------------------------------
        $kubun = explode(' ', $sql) [0];
        switch ($kubun) {
            case 'select':
                //----------------------------------------------------------------------
                // セレクト文の場合
                //----------------------------------------------------------------------
                try {
                    $this->db_logging('LoggingSQL(select) =>', $sql);
                    $sth = $this->db->prepare($sql);
                    $sth->execute();
                    return $sth->fetch(PDO::FETCH_ASSOC);
                }
                catch(PDOException $e) {
                    $this->db_logging('◆ 参照時例外発生=>', $e->getMessage());
                    trigger_error($e->getMessage(), E_USER_WARNING);
                    $result = false;
                }
            break;
            case 'update':
            case 'delete':
                //----------------------------------------------------------------------
                // delete,updateで対象のIDが無い場合はエラー
                //----------------------------------------------------------------------
                if ($id == null || strpos($sql, '### ID ###') === false) return false;
                //----------------------------------------------------------------------
                // テーブル名を取得
                //----------------------------------------------------------------------
                $table_tmp1 = explode($kubun == 'update' ? 'update ' : 'from ', $sql) [1];
                $table_name = explode(' ', $table_tmp1) [0];
                unset($table_tmp1);
                //----------------------------------------------------------------------
                // 変更前のデータを取得
                //----------------------------------------------------------------------
                $sth = $this->db->query('select * from ' . $table_name . ' where id=' . (int)$id);
                $save_key = array();
                $save_value = array();
                $backup_select = $sth->fetch(PDO::FETCH_ASSOC);
                //----------------------------------------------------------------------
                // 取得出来ない場合は終了
                //----------------------------------------------------------------------
                if (!is_array($backup_select)) {
                    $this->db_logging($table_name . 'に ID:' . $id . ' のレコードはありませんでした。');
                    return true;
                }
                //----------------------------------------------------------------------
                // バックアップ用のSQLを生成
                //----------------------------------------------------------------------
                foreach ($backup_select as $key => $value) {
                    if ($key == 'plimary') continue;
                    $save_key[] = '"' . $key . '"';
                    $save_value[] = '\'' . $value . '\'';
                }
                $save_sql = str_replace(',\'\',', ',null,', 'insert into ' . $table_name . ' (' . implode(",", $save_key) . ') values (' . implode(",", $save_value) . ');' . "\n");
                unset($sth, $save_value, $save_key);
                //----------------------------------------------------------------------
                // SQLをIDに対応
                //----------------------------------------------------------------------
                $sql = str_replace('### ID ###', $id, $sql);
                //----------------------------------------------------------------------
                // SQLを実行
                //----------------------------------------------------------------------
                try {
                    //----------------------------------------------------------------------
                    // ロギングしてSQLを実行
                    //----------------------------------------------------------------------
                    //$this->db->query('begin');
                    $sth = $this->db->prepare($sql);
                    $result = $sth->execute();
                    unset($sth);
                }
                catch(PDOException $e) {
                    $this->db_logging('◆ 変更/削除時例外発生=>', $e->getMessage());
                    $this->db_logging('◆ LoggingSQL Failed. =>', $sql);
                    trigger_error($e->getMessage(), E_USER_WARNING);
                    $result = false;
                }
                //----------------------------------------------------------------------
                // 実行結果を判定
                //----------------------------------------------------------------------
                if ($result == true) {
                    //----------------------------------------------------------------------
                    // セーブファイルに追記
                    //----------------------------------------------------------------------
                    //if (php_sapi_name()!='cli')
                    //    file_put_contents(RecoverSQLFile, $save_sql, FILE_APPEND | LOCK_EX);
                    //----------------------------------------------------------------------
                    // 正常終了
                    //----------------------------------------------------------------------
                    $this->db_logging('◇ LoggingSQL Complete. =>', $sql);
                    //$this->db->query('commit');
                    return true;
                } else {
                    //----------------------------------------------------------------------
                    // 異常終了 ロールバック
                    //----------------------------------------------------------------------
                    $this->db_logging('◆ LoggingSQL Failed. =>', $sql);
                    //$this->db->query('rollback');
                    return false;
                }
                break;
            case 'insert':
                //----------------------------------------------------------------------
                // テーブル名を取得
                //----------------------------------------------------------------------
                $table_tmp1 = explode('into ', $sql) [1];
                $table_name = explode(' ', $table_tmp1) [0];
                unset($table_tmp1);
                //----------------------------------------------------------------------
                // ロギングしてSQLを実行
                //----------------------------------------------------------------------
                try {
                    //$this->db->query('begin');
                    $sth = $this->db->prepare($sql);
                    $result = $sth->execute();
                    unset($sth);
                    if ($result == true) {
                        //----------------------------------------------------------------------
                        // プライマリの内容をIDに付与(アプリ的に重要!!!)
                        //----------------------------------------------------------------------
                        $plimary = $this->db->lastInsertId();
                        $this->db->query('update ' . $table_name . ' set id=' . $plimary . ' where plimary=' . $plimary);
                        unset($sth);
                        //----------------------------------------------------------------------
                        // 反映の確認
                        //----------------------------------------------------------------------
                        $sth = $this->db->query('select count(id) from ' . $table_name . ' where id=' . $plimary);
                        $result = $sth->fetch(PDO::FETCH_ASSOC);
                        unset($sth);
                        if ($result["count"] < 1) {
                            $this->db_logging('◆ LoggingSQL Failed. =>', $sql);
                            //$this->db->query('rollback');
                            return false;
                        }
                        //----------------------------------------------------------------------
                        // 正常終了
                        //----------------------------------------------------------------------
                        $this->db_logging('◇ LoggingSQL Complete. =>', $sql);
                        //$this->db->query('commit');
                        return $plimary;
                    } else {
                        $this->db_logging('◆ LoggingSQL Failed. =>', $sql);
                        //$this->db->query('rollback');
                        return false;
                    }
                }
                catch(PDOException $e) {
                    $this->db_logging('◆ 新規追加時例外発生=>', $e->getMessage());
                    $this->db_logging('◆ LoggingSQL Failed. =>', $sql);
                    trigger_error($e->getMessage(), E_USER_WARNING);
                    //$this->db->query('rollback');
                    return false;
                }
                break;
            default:
                return false;
                break;
            }
            return false;
        }
}
