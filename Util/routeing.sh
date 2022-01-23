#!/bin/bash
export PATH=/usr/sbin:/usr/bin:/usr/local/bin:/usr/local/sbin:/sbin:/bin:/usr/X11/bin:/root/.composer/vendor/bin
export JOBNAME=`basename $0`
cd `echo $0 |sed  "s/$JOBNAME//g"` && JOBDIR=`pwd`
export ULIB=$JOBDIR/../
input="$ULIB/Config/routing_define_template.txt"
output="$ULIB/Define/routeing.php"
test -f $output && rm -f $output
touch $output
echo "<?php " >> $output
echo "//------------------------------------------------------------------------------" >> $output
echo "// リクエストURI" >> $output
echo "//------------------------------------------------------------------------------" >> $output
echo "define('route_reqest_uri', array(" >> $output
for line_args in $(cat $input|grep -Ev "\#");do
prefix=`echo $line_args|cut -d "," -f1`
method=`echo $line_args|cut -d "," -f2`
actionname=`echo $line_args|cut -d "," -f3`
comment=`echo $line_args|cut -d "," -f4`
echo "    '$prefix',//$comment" >> $output
done
echo "));" >> $output
printf "\n" >> $output
echo "//------------------------------------------------------------------------------" >> $output
echo "// 対応するダイレクト、正規表現の区分 (Direct,Reg)" >> $output
echo "//------------------------------------------------------------------------------r" >> $output
echo "define('route_reqest_kubun', array(" >> $output
for line_args in $(cat $input|grep -Ev "\#");do
prefix=`echo $line_args|cut -d "," -f1`
method=`echo $line_args|cut -d "," -f2`
actionname=`echo $line_args|cut -d "," -f3`
comment=`echo $line_args|cut -d "," -f4`
echo "    '$method',//$comment" >> $output
done
echo "));" >> $output
printf "\n" >> $output
echo "//------------------------------------------------------------------------------" >> $output
echo "// 対応するアクション名" >> $output
echo "//------------------------------------------------------------------------------" >> $output
echo "define('route_action_name', array(" >> $output
for line_args in $(cat $input|grep -Ev "\#");do
prefix=`echo $line_args|cut -d "," -f1`
method=`echo $line_args|cut -d "," -f2`
actionname=`echo $line_args|cut -d "," -f3`
comment=`echo $line_args|cut -d "," -f4`
echo "    '$actionname',//$comment" >> $output
done
echo "));" >> $output
printf "\n" >> $output
exit


