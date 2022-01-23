#!/bin/bash
export PATH=/usr/sbin:/usr/bin:/usr/local/bin:/usr/local/sbin:/sbin:/bin:/usr/X11/bin:/root/.composer/vendor/bin
export JOBNAME=`basename $0`
cd `echo $0 |sed  "s/$JOBNAME//g"` && JOBDIR=`pwd`
export ULIB=$JOBDIR/../

#-------------------------------------------------------------------
# 入力チェック
#-------------------------------------------------------------------
if [[ $1 == "" ]];then
    echo "$0 \"アクション名\""
    exit 9;
fi

#-------------------------------------------------------------------
# 変数の初期設定
#-------------------------------------------------------------------
ActionName=$1
TemplateController="$ULIB/Util/MakeAction/TemplateController.php"
TemplateTPL="$ULIB/Util/MakeAction/Template.tpl"
TemplateModel="$ULIB/Util/MakeAction/BL_Template.php"
ActionOut="$ULIB/Action/${ActionName}Controller.php"
TplOut="$ULIB/templates/${ActionName}.tpl"
ModelOut="$ULIB/BissinesLogic/BL_${ActionName}.php"
InputCheckFile="$ULIB/Config/input_check.txt"
RoutingFile="$ULIB/Config/routing_define_template.txt"
tmpfile=/tmp/tmp.$$
tmpfile2=/tmp/tmp2.$$
tmp_eval=/tmp/tmp_eval.txt.$$

#-------------------------------------------------------------------
# Model,TPLは存在していれば処理をしない
#-------------------------------------------------------------------
if [[ ! -f $ModelOut ]];then
eval "cat $TemplateModel |sed -e 's@Template@$ActionName@g' > $ModelOut"
fi
if [[ ! -f $TplOut ]];then
eval "cat $TemplateTPL > $TplOut"
fi

#-------------------------------------------------------------------
# input_check.txtに書き足す
#-------------------------------------------------------------------
echo "$ActionName start_botton POST YES REG ([a-zA-Z\\'0-9\^\\+-/\,\.:\?=|&*\(\)_]+) M VARITETION_BACK" >>$InputCheckFile

#-------------------------------------------------------------------
# 入力チェックを反映させる
#-------------------------------------------------------------------
cat $InputCheckFile|grep -Ev "\#"|grep -E "^${ActionName}" > $tmpfile2
while read line_args
do
#for line_args in $(cat $InputCheckFile|grep -Ev "\#"|grep -E "^${ActionName}") ;do
Action=`echo $line_args|cut -d " " -f1`
Name=`echo $line_args|cut -d " " -f2`
Medhod=`echo $line_args|cut -d " " -f3`
SonzaiError=`echo $line_args|cut -d " " -f4`
Houshiki=`echo $line_args|cut -d " " -f5`
Regix=`echo $line_args|cut -d " " -f6`
Length=`echo $line_args|cut -d " " -f7`
Message=`echo $line_args|cut -d " " -f8-|sed 's/\n//g'`

#echo "$line_args"
echo "\"${Name}\" => \"$(echo $line_args|cut -d " " -f3-)\"" >> $tmpfile
#echo "            'sex'           => 'POST NO NOCHECK - M OK',"

done < $tmpfile2

R=`cat $tmpfile|sed 's/\r//g'`
INPUT_ARGS=$(echo $R|sed 's/\" \"/\"\\n            \"/g')

#-------------------------------------------------------------------
# .js .css を生成する
#-------------------------------------------------------------------
document_root="/home/sdl/dairiten.gateweb.me.uk/"
#touch "${document_root}js/${ActionName}.js"
#touch "${document_root}css/${ActionName}.css"

#-------------------------------------------------------------------
# Actionは内容固定なので 毎回作り直す
#-------------------------------------------------------------------
echo "cat $TemplateController |sed -e 's@Template@$ActionName@g'  > $ActionOut" >> $tmp_eval
echo "echo \"# <PREFIX>,<Direct|Reg>,<${ActionName}>,<コメント>\" >> $RoutingFile" >> $tmp_eval
dash -x  $tmp_eval

rm -f /tmp/tmp*.*

