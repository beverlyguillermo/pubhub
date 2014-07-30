#!/bin/bash

HUB=$(cd $(dirname $(dirname $0)) && pwd)
DIR=$HUB/tmp_sync

# Make our tmp directory
mkdir -p $DIR

# Dump all data from manager database
mysqldump --host=esgwebmysql.win.ad.jhu.edu --user=hubmanager --password=nq4u4r3my37xv5kd hubmanager > $DIR/manager.sql && echo "Downloaded manager database from production"

# Import manager data into local
mysql --host=127.0.0.1 --user=root hubmanager < $DIR/manager.sql && "Imported manager database to local database 'hubmanager' from production"

# Download images
# scp ...

# Remove the tmp directory
rm -rf $DIR