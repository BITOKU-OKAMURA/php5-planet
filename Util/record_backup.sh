#!/bin/bash
export PATH=/usr/sbin:/usr/bin:/usr/local/bin:/usr/local/sbin:/sbin:/bin:/usr/X11/bin:/root/.composer/vendor/bin
export JOBNAME=`basename $0`
cd `echo $0 |sed  "s/$JOBNAME//g"` && JOBDIR=`pwd`
export ULIB=$JOBDIR/../
sqlfile="./RecoverSQL.$(date +%Y%m%d)"
tarfile="./record_backup.tar"
cd $ULIB/webroot/ || exit 9
[[ ! -f $sqlfile ]] && exit 5
tar cpf $tarfile $sqlfile || exit 9
rm -f $sqlfile || exit 9
exit 0

