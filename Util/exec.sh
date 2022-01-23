#!/bin/bash
# $1 Action
# $2 Post
# $3 Get
# $4 Uri
#
#


if [[ $1="" ]];then
echo "exec.sh [Action] [Post] [Get] [URI]"
exit 5
fi

cd ~/dairiten.gateweb.me.uk
clear;echo $2|sudo php ./index.php KeiyakuKeiTai "_loginid=2" "agency_oprate/setting/account_staus"|grep -v "TRACE"


