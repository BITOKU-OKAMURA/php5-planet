#!/bin/bash
export PATH=/usr/sbin:/usr/bin:/usr/local/bin:/usr/local/sbin:/sbin:/bin:/usr/X11/bin:/root/.composer/vendor/bin
export JOBNAME=`basename $0`
cd `echo $0 |sed  "s/$JOBNAME//g"` && JOBDIR=`pwd`
#------------------------------------------------------------------------------
# フレームワーク　基幹フォルダ $ULIB
#------------------------------------------------------------------------------
export ULIB=$JOBDIR/../

#------------------------------------------------------------------------------
# FQDNの取得。JNIの設定フォルダに合致させる jni_<FQDN>
#------------------------------------------------------------------------------
FQDN=$(find $ULIB/Util/Nginx/Template/ -type d |grep jni|cut -d "_" -f 2-)

#------------------------------------------------------------------------------
# Commonファイルを生成
#------------------------------------------------------------------------------
eval "sed -e 's@### FQDN ###@$FQDN@g' $ULIB/Util/Nginx/Template/jni_$FQDN/Common > $ULIB/Util/Nginx/jni_$FQDN/Common"

#------------------------------------------------------------------------------
# 404の処理があるため、Topページを決め打ちで生成
#------------------------------------------------------------------------------
Top_tmplate=$(cat $ULIB/Util/Nginx/route.txt |grep TopPage|cut -d "," -f3)
eval "sed -e 's@### FQDN ###@$FQDN@g' $ULIB/Util/Nginx/Template/jni_$FQDN/TopPage|sed 's@### ULIB ###@$ULIB@g'|sed 's@### Template ###@$Top_tmplate@g' > $ULIB/Util/Nginx/jni_$FQDN/TopPage"

#------------------------------------------------------------------------------
# 現在のconfファイルを削除してアップサイドを出力
#------------------------------------------------------------------------------
cat $ULIB/Util/Nginx/Template/conf_template_upside.txt > $ULIB/Util/Nginx/$FQDN

#------------------------------------------------------------------------------
# ルーティングを読み込み
#------------------------------------------------------------------------------
for line_args in $(cat $ULIB/Util/Nginx/route.txt|grep -Ev "#"|grep -v "TopPage") ;do
    #------------------------------------------------------------------------------
    # URIとアクション名を決定
    #------------------------------------------------------------------------------
    URI=$(echo $line_args|cut -d "," -f 1)
    Acrion=$(echo $line_args|cut -d "," -f 2)
    Template=$(echo $line_args|cut -d "," -f 3)
    Commnet=$(echo $line_args|cut -d "," -f 4)

    #------------------------------------------------------------------------------
    # アクションファイルを生成
    #------------------------------------------------------------------------------
    eval "sed -e 's@### FQDN ###@$FQDN@g' $ULIB/Util/Nginx/Template/jni_action_template.txt|sed 's@### ULIB ###@$ULIB@g'|sed 's@### Template ###@$Template@g'|sed 's@### Action Name ###@$Acrion@g' > $ULIB/Util/Nginx/jni_$FQDN/$Acrion"

    #------------------------------------------------------------------------------
    # ロケーションファイルを生成
    #------------------------------------------------------------------------------
    eval "sed -e 's@### FQDN ###@$FQDN@g' $ULIB/Util/Nginx/Template/location_template.txt|sed 's@### URI ###@$URI@g'|sed 's@### ULIB ###@$ULIB@g'|sed 's@### Commnet ###@$Commnet@g'|sed 's@### Action ###@$Acrion@g' >> $ULIB/Util/Nginx/$FQDN"

    #------------------------------------------------------------------------------
    # 存在しない場合は見本のファイルをコピー
    #------------------------------------------------------------------------------
    [[ ! -f /home/sdl/$FQDN/jnt/$Acrion.jnt ]] && cat $ULIB/Util/Nginx/Template/jni_template.txt > /home/sdl/$FQDN/jnt/$Acrion.jnt

    #------------------------------------------------------------------------------
    # cssファイルとjsファイルを用意してあげる
    #------------------------------------------------------------------------------
    [[ ! -f /home/sdl/$FQDN/js/Action/$Acrion.js ]] && cat $ULIB/Util/Nginx/Template/ActionJS.txt > /home/sdl/$FQDN/js/Action/$Acrion.js
    [[ ! -f /home/sdl/$FQDN/css/Action/$Acrion.css ]] && touch /home/sdl/$FQDN/css/Action/$Acrion.css


done

#------------------------------------------------------------------------------
# ダウンサイドを出力
#------------------------------------------------------------------------------
cat $ULIB/Util/Nginx/Template/conf_template_downside.txt >> $ULIB/Util/Nginx/$FQDN

