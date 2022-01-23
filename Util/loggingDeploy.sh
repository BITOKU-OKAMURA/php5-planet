#!/bin/bash
export PATH="/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/games:/usr/local/games"
export JOBNAME=`basename $0`
cd `echo $0 |sed  "s/$JOBNAME//g"` && JOBDIR=`pwd`
export ULIB=$JOBDIR/../
prefix=$(eval "php -r \"require_once '$ULIB/Define/define.php';echo PREFIX;\"")
log_dir=$(eval "php -r \"require_once '$ULIB/Define/define.php';echo LOGDIR;\"")

