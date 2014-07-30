#!/bin/bash

SERVERNAME=$(uname -n)

if [ $SERVERNAME = "esgjhumktgst.esg.johnshopkins.edu" ];
then
	echo "staging"
elif [ $SERVERNAME = "esgjhumktgprod.esg.johnshopkins.edu" ];
then
	echo "production"
else
	echo "local"
fi