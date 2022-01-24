# １．フォルダの構成

1.Test:テストコードを記載  
2.Define:各種定数を記載した.phpファイルを格納  
3.DB:データベースを扱うクラスを記載した.phpファイルを格納  
4.Config:各種設定ファイルを格納  
5.Common:各フォルダ(Action,BissinesLogic,DB)のスーパークラスを記載した.phpファイルを格納  
6.Action:各種入力(GET,POSTリクエスト及びコンソール入力)と払い出し(smarty又はjson)を制御するクラスを記載した.phpファイルを格納  
7.BissinesLogic:ユースケース個別の実処理を制御するクラスを記載した.phpファイルを格納。  
8.Util:バッチ処理やメンテナンスに使用するシェルスクリプトやSPAのルーティングの設定ファイルが記載されたファイルを格納  
9.Log:各種ログを格納  
10.templates:smarty及びメールのテンプレートが記載されたファイルを格納  
11.vendor:composer,smartyが使用  
12.webroot:データベースのバックアップや、ユーザが用意したドキュメント等を格納。webサーバの公開領域と多数シンボリックリンクが張ってある  

# ２．ユースケースの追加  

①下記ファイルにルーティング情報(URI、アクション名)を追記。  
./Config/routing_define_template.txt  

②./Util/routeing.sh を打鍵して実行  

③./Action/【アクション名】Controller.php に下記の部分があるのでフォームハンドラーのチェック情報を記載  

        //--------------------------------------------------------------------------
        // 入力チェック 書き方は input_check.txtを参照
        //--------------------------------------------------------------------------
        $this->ChkParam = array(
            "【ハンドラ名】"=>"パラメータ",
        );
★パラメータ：メソッド(GETかPOST) 存在の強制 チェック方法 正規表現 文字列長の上限 エラー処理の種類  
例"backurl" =>"ALL NO REG (^[a-zA-Z0-9.!#$%&@*+/=:?_{|}-]+$) M OK  

# ３．DBの接続

①./DBに専用クラスを作る。内容は既存ファイルをコピペして名前を変更する。  
②./Define/db.phpに接続情報を既存のコードを参考に記載。  
③で作ったクラスのコンストラクタ無いにスーパークラスのコンストラクタを接続情報を  
引数にして呼び出す。既存のコードを参考に記載。  
④./Common/BL_Base.phpのコンストラクタで以下のように呼び出す。($db はBL_Baseクラスで宣言) 
$this->db=new 【①で作ったクラス】;  
 

# ４．プログラミング
・チェック済ハンドラのメンバ→ $this->input  
・出力専用のメンバ→ $this->Data  
・処理はBL_【アクション名】クラスのexecuteメソッドに記述し、【アクション名】Controllerクラスにてjson出力されるか  
smartyに払い出しされる。
・データベースに関する処理はDB_Baseクラス内のLoggingSQLメソッドにて記載すること。また、ページネイト処理として  
PageNateメソッド、CSV出力としてCsvOutputSQLメソッドが用意されている。


# ５．SPAについて
①./public_html/json/common/route.json に記載する  
パラメータは左から URI ,アクション名 , テンプレート名 となる。  

②./public_html/js/common/com_envrironment.js をHTMLテンプレートから呼び出す。
③各種個別のアクションを実装し、com_envrironment.jsから呼び出されるようにする。
④./public_html/templateフォルダにはunderscoreのテンプレートを格納する。
