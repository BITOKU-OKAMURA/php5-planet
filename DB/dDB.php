<?php
require PREFIX.'/Define/stdafx.php';
/**
 * 代理店DB用クラス DB名:dairiten
 *
 * @package BMS-ODMS
 * @author 2009/4/23 Yoshinori.Okamura(Miyako Corp)
 * @copyright KDDI
 * @version CVS:
 */
class dairitenDB extends DB_Base {

function __construct(){
    //--------------------------------------------------------------------------
    // スーパークラスのコンストラクタ 引数に接続情報の入った配列。db.phpを参照
    //--------------------------------------------------------------------------
    parent::__construct(dbconnect_dairiten);

    //--------------------------------------------------------------------------
    // 接続に失敗したら、ハンドルに明示的にnullが入るのでハンドリングも安心。
    //--------------------------------------------------------------------------
    return;
}

/* デストラクタ */
public function __destruct(){
    //--------------------------------------------------------------------------
    // スーパークラスのデストラクタ
    //--------------------------------------------------------------------------
    parent::__destruct();
}

}//DB class
