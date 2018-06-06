#!/bin/bash
# @copyright Copyright 2011-2018 City of Bloomington, Indiana
# @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.txt
APPLICATION_NAME="onboard"
PHP="/usr/bin/php"
MYSQLDUMP="/usr/bin/mysqldump"
MYSQL_DBNAME="{{ onboard_db.name }}"
MYSQL_CREDENTIALS="/etc/cron.daily/backup.d/${APPLICATION_NAME}.cnf"
BACKUP_DIR="{{ onboard_backup_path }}"
APPLICATION_HOME="{{ onboard_install_path }}"
SITE_HOME="{{ onboard_site_home }}"

#----------------------------------------------------------
# Data Warehouse export
#----------------------------------------------------------
export SITE_HOME=$SITE_HOME
{% if onboard_proxy %}
# Set this if your OnBoard install lives behind a reverse proxy
export HTTP_X_FORWARDED_HOST={{ onboard_proxy }}
{% endif %}
$PHP $APPLICATION_HOME/scripts/Ckan/updateCkan.php

#----------------------------------------------------------
# Backup
# Creates a tarball containing a full snapshot of the data in the site
#----------------------------------------------------------
# How many days worth of tarballs to keep around
num_days_to_keep=5

now=`date +%s`
today=`date +%F`

# Only do a nightly snapshot of the database.  Everything is backed up to
# tape, nightly.  The file storage for OnBoard is so large we do not want
# to multiply it doing nightly tarballs.  We will rely on our tape storage
# for any restorations of uploaded files.

# Dump the database
$MYSQLDUMP --defaults-extra-file=$MYSQL_CREDENTIALS $MYSQL_DBNAME > $SITE_HOME/$MYSQL_DBNAME.sql
cd $SITE_HOME
tar czf $today.tar.gz $MYSQL_DBNAME.sql
mv $today.tar.gz $BACKUP_DIR

# Purge any backup tarballs that are too old
cd $BACKUP_DIR
for file in `ls`
do
	atime=`stat -c %Y $file`
	if [ $(( $now - $atime >= $num_days_to_keep*24*60*60 )) = 1 ]
	then
		rm $file
	fi
done
