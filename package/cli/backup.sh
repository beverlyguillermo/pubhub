#!/bin/bash


#
# uploadToS3()
# 
# Uploads a file or folder to Amazon s3
# 
# $1 filename
# $2 filepath
# $3 amazon s3 bucket
# $4 reference (factory database, magager database, files directory)
# 
function uploadToS3()
{
	# Change filename and compress
	if [ -d $2 ]; # directory
	then
		FILENAME=$1.tgz
		FILEPATH=$2.tgz

		# tarball the directory
		tar -czf $FILENAME $1 && echo "[x] tarballed $4 to $FILENAME" && rm -fr $1

	elif [ -f $2 ]; # regular file
	then
		FILENAME=$1.gz
		FILEPATH=$2.gz

		# gzip the file
		gzip $2 && echo "[x] gzipped factory db to $FILEPATH"
	fi


	# Upload
	if [ -f $FILEPATH ];
	then
	   # upload file to amazon s3
	   $HUB/cli/s3upload --bucket=$3 --name=$FILENAME --file=$FILEPATH
	   rm $FILEPATH
	   echo "[x] $4 uploaded to s3 sucessfully."
	else
	    echo "Error: $4 backup at $NOW failed."
	    exit 0
	fi
}


#
# backupHubDatabase()
# 
# Performs a SQL dump of the manager database (either production
# or local) and uploads it to Amazon s3.
# 
function backupManagerDatabase()
{
	MANAGERFILE="hubmanager_${ENV}_${NOW}.sql"
	MANAGERPATH="$HUB/tmp/$MANAGERFILE"
	
	if [ $ENV == "production" ];
	then
		mysqldump -u hubmanager --password=nq4u4r3my37xv5kd -h esgwebmysql.win.ad.jhu.edu hubmanager > $MANAGERPATH && echo "[x] Downloaded manager database"
	elif [ $ENV == "staging" ];
	then
		mysqldump -u hubmanagerstage --password=ych4bj6ct686qgbf -h esgwebmysql.win.ad.jhu.edu hubmanagerstage > $MANAGERPATH && echo "[x] Downloaded manager database"
	else
		mysqldump -u root -h 127.0.0.1 hubmanager > $MANAGERPATH && echo "[x] Downloaded manager database"
	fi

	uploadToS3 $MANAGERFILE $MANAGERPATH "hubmanagerdb" "manager database"
}


function setup()
{
	HUB=$(cd $(dirname $(dirname $0)) && pwd)
	ENV=$($HUB/cli/getenv.sh)

	NOW=$(date +%Y-%m-%d)

	# make the tmp directory if it doesn't exist
	mkdir -p $HUB/tmp

	echo "" && echo "Beginning backup.sh script."
}



# run setup
setup

# Let the backup begin
backupManagerDatabase