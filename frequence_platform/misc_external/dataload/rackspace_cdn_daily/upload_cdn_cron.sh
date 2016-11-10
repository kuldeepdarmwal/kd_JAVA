#!/bin/bash

dir_name=`dirname $0`

path=$dir_name"/"

php -f $path"upload_cdn_data.php" $path 2>&1 | cat > $path"upload_cdn_data_log.txt"

# sends email if upload_cdn_data.php contains a php error (syntax error or others.)
php $path"handle_cdn_upload_errors.php" $path

# accumulate logs
date_string=`date +"%F %T %Z"`
echo -e $"\n\n""--------------" $date_string "--------------"$"\n\n" >> $path"accumulated_cdn_data_log.txt"
cat $path"upload_cdn_data_log.txt" >> $path"accumulated_cdn_data_log.txt"
