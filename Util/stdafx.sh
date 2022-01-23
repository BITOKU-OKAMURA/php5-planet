#!/bin/bash
export PATH="/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/games:/usr/local/games"
export JOBNAME=`basename $0`
cd `echo $0 |sed  "s/$JOBNAME//g"` && JOBDIR=`pwd`
export ULIB=$JOBDIR/../

cd $ULIB
recire_once="require_once '"
file=$ULIB/Define/stdafx.php
echo "<?php">> $file.tmp
for line_args in $(eval "find ./* -name \"*.php\" |grep -v \"Test\" |grep -v \"Define\"| grep -v \"ODMS2\"|grep -v \"Util\"|grep -v \"composer\" |grep -v \"public_html\"|grep -v \"template\"|grep -v \"vendor\"|grep -v \"/Controller.php\"") ;do
echo $recire_once$ULIB$line_args\'\; >>$file.tmp
done
mv $file.tmp $file || exit 9

