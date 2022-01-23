#!/bin/bash
table_name=$1
colums=$2
if [[ ${table_name} == "" ||  ${colums} == "" ]];then
echo "\"table_name\" \"clum1 clum2\""
exit 5
fi



sql="'insert into ${table_name} ("
for line_args in ${colums} ;do
line=$line"\"${line_args}\","
done
sql="$(echo "${sql}${line}"|sed '$s/.$//')) values (E\\''"



for line_args in ${colums} ;do
#line=$line"\"${line_args}\","


line2="$line2.\$input[\"$line_args\"][\"value\"].'\',E\\''"


done




sql="$(echo $sql$line2|sed '$s/.$//'|sed '$s/.$//'|sed '$s/.$//'|sed '$s/.$//'|sed '$s/.$//')"
sql="$sql);'"
echo "[insert]"
echo ${sql}


sql2="'update ${table_name} set "
for line_args in ${colums} ;do
line3="$line3 $line_args=E\''.\$input[\"$line_args\"][\"value\"].'\' , "
done

line3=$(echo $line3|sed '$s/.$//'|sed '$s/.$//'|sed '$s/.$//'|sed '$s/.$//')
line3="$line3\' where id=\'### ID ###\';'"
sql2="$sql2${line3}"
echo "[update]"
echo ${sql2}





echo "[Controller]"
for line_args in ${colums} ;do
echo "            '$line_args'=>    \"POST NO NOCHECK - M OK\",//"
done


echo "[Select]"
for line_args in ${colums} ;do
echo "${table_name}.$line_args as $line_args , "
done
echo " from ${table_name}"


