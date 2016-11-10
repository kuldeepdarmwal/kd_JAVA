#!/bin/bash

#SOURCE_DIR=/home/vlstage_user/vlstage.com/rollit
H=/var/review_servers/review-6/public

#if [ $NUM_CSV_SOURCE_FILES -gt 0 ]; then
if [ -f $H/vantagelocal.com/rollit/rollout.flag ]; then

#get the branch name
branch=$(cat $H/vantagelocal.com/rollit/rollout.flag)

# remove the flag to avoid the double rollout issue
/bin/rm -f $H/vantagelocal.com/rollit/rollout.flag

# echo "STARTING ROLLOUT TO PROD"

/usr/bin/curl -s --user 'api:key-1bsoo8wav8mfihe11j30qj602snztfe4' \
  https://api.mailgun.net/v2/mailgun.vantagelocaldev.com/messages \
  -F from='Tech Team <tech@vantagelocal.com>' \
  -F to='Tech Team <tech@vantagelocal.com>' \
  -F subject="ROLLOUT TO Review 6 DEV INITIATED - $branch" \
  -F text="Rollout Initiated"

# Let's clone it
#/usr/bin/git clone git@github.com:ScottHuber/VantageLocal-CI.git /home/vladmin/VantageLocal-CI

# Let's move the old website to another folder - for speed
# and do this before cloning incase it takes too long and kicks off another rollout.
# /bin/mv $H/vantagelocal.com $H/vantagelocal.com.OLD

/usr/bin/git clone $branch $H/VantageLocal-CI 

# to do: check if cloning went fine

# tbd

/bin/mv $H/vantagelocal.com $H/vantagelocal.com.OLD

# Move the cloned directory to the old vlstage
/bin/mv $H/VantageLocal-CI $H/vantagelocal.com

# Copy over specific files config and database php files
#/bin/rm -f $H/vantagelocal.com/application/config/config.php

#/bin/rm -f $H/vantagelocal.com/application/config/database.php

#/bin/cp $H/static/config.php $H/vantagelocal.com/application/config/config.php

#/bin/cp $H/static/database.php $H/vantagelocal.com/application/config/database.php

# Copyt over index.php file only
/bin/rm -f $H/vantagelocal.com/index.php

/bin/cp $H/static/index.php $H/vantagelocal.com/index.php
/bin/cp $H/static/robots.txt $H/vantagelocal.com/robots.txt

# Remove the old tickets and uploads folders
/bin/rm -r $H/vantagelocal.com/tickets
/bin/rm -r $H/vantagelocal.com/uploads
/bin/rm -r $H/vantagelocal.com/assets/img/uploads
/bin/rm -r $H/vantagelocal.com/assets/exports
/bin/rm -r $H/vantagelocal.com/autoscript


# Copy folders from OLD site to NEW Site
/bin/cp -rp $H/vantagelocal.com.OLD/tickets $H/vantagelocal.com/tickets
/bin/cp -rp $H/vantagelocal.com.OLD/uploads $H/vantagelocal.com/uploads
/bin/cp -rp $H/vantagelocal.com.OLD/assets/img/uploads $H/vantagelocal.com/assets/img/uploads
/bin/cp -rp $H/vantagelocal.com.OLD/assets/exports $H/vantagelocal.com/assets/exports
/bin/cp -rp $H/vantagelocal.com.OLD/autoscript $H/vantagelocal.com/autoscript

# Change permission
/bin/chmod -R 777 $H/vantagelocal.com/tickets
/bin/chmod -R 777 $H/vantagelocal.com/uploads
/bin/chmod -R 777 $H/vantagelocal.com/assets/img/uploads
/bin/chmod -R 777 $H/vantagelocal.com/assets/exports
/bin/chmod -R 777 $H/vantagelocal.com/assets/proposal_pdf
/bin/chmod -R 777 $H/vantagelocal.com/autoscript
/bin/chmod -R 777 $H/vantagelocal.com/assets/creative_upload

# If old folder then copy to new place
if [ -d $H/vantagelocal.com.OLD/assets/ad_link_3000/uploads ]; then
/bin/rm -r $H/vantagelocal.com/assets/ad_link_3000/uploads
/bin/cp -rp $H/vantagelocal.com.OLD/assets/ad_link_3000/uploads $H/vantagelocal.com/assets/ad_link_3000/uploads
fi

# Change permission
/bin/chmod -R 777 $H/vantagelocal.com/assets/ad_link_3000/uploads

# Chmod 777
/bin/chmod -R 777 $H/vantagelocal.com/rollit

# Date Time variables
DATE=$(date +%Y%m%d)
TIME=$(date +%T)

# Install AWS SDK
if [ -d $H/vantagelocal.com/libraries/external/php/aws-sdk-php-2.6.1 ];
then
(
cd $H/vantagelocal.com/libraries/external/php/aws-sdk-php-2.6.1
/usr/bin/curl -vS https://getcomposer.org/installer | php -- -q
/usr/bin/php composer.phar install
)
fi

# Delete old site
#/bin/rm -rf $H/vantagelocal.com.OLD
#/bin/mv $H/vantagelocal.com.OLD $H/archive/vantagelocal.com.OLD$DATE$TIME
/bin/rm -rf $H/vantagelocal.com.OLD
# echo "ROLLOUT TO STAGING SUCCESS"

/usr/bin/curl -s --user 'api:key-1bsoo8wav8mfihe11j30qj602snztfe4' \
	https://api.mailgun.net/v2/mailgun.vantagelocaldev.com/messages \
	-F from='Tech Team <tech@vantagelocal.com>' \
	-F to='Tech Team <tech@vantagelocal.com>' \
	-F subject="ROLLOUT TO Review 6 DEV COMPLETE - $branch" \
	-F text="Rollout Completed"

fi
