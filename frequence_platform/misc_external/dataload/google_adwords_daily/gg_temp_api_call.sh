#!/bin/bash

# Set the constants
SCRIPT_DIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )
BASE_DIR="$SCRIPT_DIR/GG"
TARGET_DIR_GG=$BASE_DIR"/googleads-php-lib-master/examples/AdWords/v201603"
LOG_FILE=$BASE_DIR"/gg_log.txt"

#BASE_DIR="/var/www_vl_tt/misc_external/dataload/google_adwords_daily" # for development

SN="processingfiles:"

# Let's start
echo "$SN Starting GG TEMP API"

echo "Hostname: $HOSTNAME
Directory: $SCRIPT_DIR
" > $TARGET_DIR_GG/output.tmp

# Now RUN the php files
echo "Account Hierarchy List<br><br>" >> $TARGET_DIR_GG/output.tmp
/usr/bin/php $TARGET_DIR_GG/AccountManagement/GetAccountHierarchy.php >> $TARGET_DIR_GG/output.tmp
echo "<br><br>Campaigns List<br>" >> $TARGET_DIR_GG/output.tmp
/usr/bin/php $TARGET_DIR_GG/BasicOperations/GetCampaigns.php >> $TARGET_DIR_GG/output.tmp

# Remove the blank lines
/usr/bin/perl -i -p -e 's/\n/<br>/' $TARGET_DIR_GG/output.tmp
echo '
' >> $TARGET_DIR_GG/output.tmp

# Mail the results
body=`cat $TARGET_DIR_GG/output.tmp`

#mail --append="Content-type: text/html" -s "RESULTS: TEMP GG PROCESSED" "tech@vantagelocal.com" < $TARGET_DIR_GG/output.tmp
#cat $TARGET_DIR_GG/output.tmp

/usr/bin/curl -s --user 'api:key-1bsoo8wav8mfihe11j30qj602snztfe4' \
  https://api.mailgun.net/v2/mg.brandcdn.com/messages \
  -F from='Tech Team <tech@frequence.com>' \
  -F to='Tech Team <tech@frequence.com>' \
  -F subject="RESULTS: TEMP GG PROCESSED" \
  -F html="$body" \
	>> $LOG_FILE

echo "$SN End of GG process"
