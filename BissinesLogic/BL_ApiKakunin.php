<?php
require PREFIX.'/Define/stdafx.php';
/**
* BL_ApiKakunin
*
* @package 
* @author
* @copyright
* @version CVS:
*/
class BL_ApiKakunin extends BL_Base
{

/**
* BissinesLogic 参照・基底クラス
* @package
* @author
* @copyright
* @version
*/
function __construct($ActionName){
    //--------------------------------------------------------------------------
    // スーパークラスのコンストラクタ
    //--------------------------------------------------------------------------
    parent::__construct($ActionName);
}

/**
* BissinesLogic メイン実行クラス
*
* @package 
* @author
* @copyright
* @version
*/
function execute($input){

}

/* デストラクタ */
function __destruct(){
    //--------------------------------------------------------------------------
    // スーパークラスのデストラクタ
    //--------------------------------------------------------------------------
    parent::__destruct();
}

}
