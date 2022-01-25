# １．概要
このプロジェクトは動的出力するWebコンテンツの開発において、MVCという概念が浸透していなかった頃に
ポータル系Y社の社内規則を参考にして作ったPHP5向けバックエンドフレームワークとなります。
通信系最大手K社の案件でも採用され、エンド様が賞を頂くという栄誉を賜りました。
最近では最大手まで成長した営業職向け紹介案件プラットフォームにも採用されております。

# ２．フォルダの構成

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

# ３．ユースケースの追加  

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

# ４．DBの接続

①./DBに専用クラスを作る。内容は既存ファイルをコピペして名前を変更する。  
②./Define/db.phpに接続情報を既存のコードを参考に記載。  
③で作ったクラスのコンストラクタ無いにスーパークラスのコンストラクタを接続情報を  
引数にして呼び出す。既存のコードを参考に記載。  
④./Common/BL_Base.phpのコンストラクタで以下のように呼び出す。($db はBL_Baseクラスで宣言) 
$this->db=new 【①で作ったクラス】;  
 

# ５．メイル発報
①専用クラスを用意しているので下記を参考に必要なパラメタを指定してインスタンスする。
②メイルのテンプレートは ./templates/Mail/ に設置すること。

    //--------------------------------------------------------------------------
    // メイル送信オブジェクトの生成 代理店宛
    //--------------------------------------------------------------------------
    $mail = new MailBase($dairitenDB,//DBインスタンス
        $mail_honbun,//テンプレート名
        $input["mail"]["pg_value"],//宛先
        null,//CC
        '【'.$this->Data["status_view"]["costom"]["bland_name"].
        '】代理店アカウント発行完了の通知'//掲題
    );

    //--------------------------------------------------------------------------
    // メイル送信 代理店宛
    //--------------------------------------------------------------------------
    $mail->send_mail(str_replace('&nbsp;',' ',
        str_replace('### agent_corp_name ###',$agent_info["corporate_name"],
        str_replace('### master_corp_name ###',$_SESSION["LOGIN"]["corporate_name"],
        str_replace('### master_input_member_id ###',$dairitenDB->LoggingSQL('select 
        consignar_hq_member.name as name from consignar_hq_member where  consignar_hq_member.id='.(int)$input["consignar_hq_member"]["pg_value"])['name'],
        str_replace('### URL ###',protocol.'://'.hostname.'/agent/'.$uuid.'/first_agree',
        str_replace('### 企業ID ###',$agent_info["company_prefix"],
        str_replace('### ONCYUU ###',$agent_info["kigyou_syubetsu"]==1 ? '御中': '様',
        str_replace('### LOGIN_URL ###',protocol.'://'.hostname.'/agent/'.$uuid.'/login_agree',
        $mail->mail_template_body))))))))
    );
    unset($mail);


# ６．プログラミング
・チェック済ハンドラのメンバ→ $this->input  
・出力専用のメンバ→ $this->Data  
・処理はBL_【アクション名】クラスのexecuteメソッドに記述し、【アクション名】Controllerクラスにてjson出力されるか  
smartyに払い出しされる。
・データベースに関する処理はDB_Baseクラス内のLoggingSQLメソッドにて記載すること。また、ページネイト処理として  
PageNateメソッド、CSV出力としてCsvOutputSQLメソッドが用意されている。


# ７．SPAについて
①./public_html/json/common/route.json に記載する  
パラメータは左から URI ,アクション名 , テンプレート名 となる。  

②./public_html/js/common/com_envrironment.js をHTMLテンプレートから呼び出す。
③各種個別のアクションを実装し、com_envrironment.jsから呼び出されるようにする。
④./public_html/templateフォルダにはunderscoreのテンプレートを格納する。
