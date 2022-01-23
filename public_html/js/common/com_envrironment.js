/*

どんどん共通関数を足していこう
関数内は厳格モード実施！！
'use strict';
*/



//-----------------------------------------------------//
// 画面の発火点【真のシーケンス】
//-----------------------------------------------------//
$(document).ready(function() {
    //-----------------------------------------------------//
    // URIの取得 
    //-----------------------------------------------------//
    g_uri=window.location.href.split(window.location.hostname)[1];

    //-----------------------------------------------------//
    // アクション名の初期定義。 ※値の取得をundefやnullに頼らない。
    //-----------------------------------------------------//
    g_Action='';

    //-----------------------------------------------------//
    // Template名の初期定義。 ※値の取得をundefやnullに頼らない。
    //-----------------------------------------------------//
    g_Template='';

    //-----------------------------------------------------//
    // QUERY_STRINGSの取得
    //-----------------------------------------------------//
    g_query_string=location.search.substring(1);

    //-----------------------------------------------------//
    // ユーザエージェントを判定
    //-----------------------------------------------------//
    var ua = window.navigator.userAgent.toLowerCase();
    g_os='mobile';
    if(ua.indexOf("windows nt") !== -1 || ua.indexOf("mac os x") !== -1) {
        g_os='pc';
    }

    //-----------------------------------------------------//
    // Actionで取得するJSONのグローバル化
    //-----------------------------------------------------//
    Data=[];

    //-----------------------------------------------------//
    // 端末の取得 PCはfalse MBはtrue
    //-----------------------------------------------------//
    var ua = navigator.userAgent.toLowerCase();
    g_uriisMobile = /iphone/.test(ua)||/android(.+)?mobile/.test(ua);

    //-----------------------------------------------------//
    // 本日の年月日を取得
    //-----------------------------------------------------//
    var cur_date=new Date(); 
    g_Year = cur_date.getFullYear();
    g_Month = cur_date.getMonth()+1;
    g_Week = cur_date.getDay();
    g_Day = cur_date.getDate();
    g_YYYYMMDD = g_Year + '年' + g_Month + '月' + g_Day + '日';

    //-----------------------------------------------------//
    // DataTable日本語化
    //-----------------------------------------------------//
    $.extend( $.fn.dataTable.defaults, { 
        language: {
            url: "https://cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Japanese.json"
        } 
    });

    //-----------------------------------------------------//
    // AJAXリクエストにて ルーティング情報をロード
    //-----------------------------------------------------//
    $.ajax({
        url: '/json/common/route.json',
        type: 'GET',
        timeout: 10000,
        async:true,
        dataType: 'json'
    })
    .always(function(json) {
        'use strict';
        //-----------------------------------------------------//
        // 【ロードリクエスト完了ブロック【真のシーケンス】
        //-----------------------------------------------------//
        if(json[g_uri]===undefined)
            location.href = g_uri;//404.htmlとか作ってそこに飛ばす
        else{
            g_Action=json[g_uri]['action_name'];
            g_Template=json[g_uri]['template_file'];
        }

        //-----------------------------------------------------//
        // 各種モジュールファイルのリスト
        //-----------------------------------------------------//
        var scripts = [
            '/template/common/'+g_Template+'.js',   //アクション「指定」の共通テンプレート
            '/template/action/'+g_Action+'.js',     //アクション「個別」のテンプレート
            '/js/action/'+g_Action+'.js'            //アクション個別のロジックファイル
        ];
        var cnt = 1;
        function onloaded(e){
            'use strict';
            //console.log(e.target.src + " is successfully loaded");
            if(cnt == scripts.length){
                //-----------------------------------------------------//
                // すべてのScriptの読み込み完了ブロック【真のシーケンス】
                //-----------------------------------------------------//

                //-----------------------------------------------------//
                // Action固有のJSONデータを取得
                //-----------------------------------------------------//
                $.ajax({
                    url: '/json/action/'+g_Action+'.json',//Action毎のjsonファイル
                    type: 'GET',
                    timeout: 10000,
                    async:true,
                    dataType: 'json'
                })
                .always(function(json) {
                    'use strict';
                    //-----------------------------------------------------//
                    // Action固有のJSONデータ完了ブロック【真のシーケンス】
                    //-----------------------------------------------------//
                    Data=json;
                    //-----------------------------------------------------//
                    // AJAXのエラーコールバックなどネットワーク的に信用ならん。チェックサム的なロードチェックを実施すること。
                    //-----------------------------------------------------//
                    //(決めごと待ち)コールバックのルールを決める。

                    //-----------------------------------------------------//
                    // 各種個別のアクションを実装
                    //-----------------------------------------------------//
                    Execute();
                    return;
                });//このブロックがシーケンス
                return;
            }//すべてのScriptの読み込み完了後の処理 ココマデ
            return;
        }
        var len = scripts.length;
        var i = 0;
        (function appendScript() {
            var script = document.createElement('script');
            script.src = scripts[i];
            if(typeof scripts[i] == "undefined"){
                return false;
            }
            document.head.appendChild(script);
            if (i++ < len) {
                script.onload = function(e){
                onloaded(e);
                 appendScript();
                cnt++;
                }
            }
        })();
        return;
    });
    return;
});

//-----------------------------------------------------//
// ローダー起動
//-----------------------------------------------------//
function startload(){
    var h = $(window).height();
    $('#wrap').delay(50).fadeIn(300);
    $("button").prop("disabled", true);
    $('#wrap').css('display','none');
    $('#loader-bg ,#loader').height(h).css('display','block');
}

function stopload(){
    $('wrap').css('display', 'block');
    $('#loader-bg').delay(100).fadeOut(600);
    $('#loader').delay(100).fadeOut(400);
    $("button").prop("disabled", false);
}

//-----------------------------------------------------//
// 並べ替え用の関数
//-----------------------------------------------------//
//Number(
function compareValues(key, order='asc') {
  return function(a, b) {
    if(!a.hasOwnProperty(key) || !b.hasOwnProperty(key)) {
      // property doesn't exist on either object
        return 0; 
    }

    const varA = (typeof a[key] === 'string') ? 
      a[key].toUpperCase() : a[key];
    const varB = (typeof b[key] === 'string') ? 
      b[key].toUpperCase() : b[key];

    let comparison = 0;
    if (varA > varB) {
      comparison = 1;
    } else if (varA < varB) {
      comparison = -1;
    }
    return (
      (order == 'desc') ? (comparison * -1) : comparison
    );
  };
}

//-----------------------------------------------------//
// YYMMDDを日本語に変換
//-----------------------------------------------------//
function yymmdd_to_japanese(hassou_jikoku){
    return hassou_jikoku.slice(2,4)+'年'+hassou_jikoku.slice(4,6)+'月'+hassou_jikoku.slice(6,8)+'日';
}
