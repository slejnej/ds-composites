#!/bin/bash

# first parameter is the source location
SOURCE=$1

# second parameter is the target location
TARGET_DIR=$1
TARGET_LIBRARY=$2

# create dir if not exists
mkdir -p $TARGET

unzip $SOURCE -d $TARGET/$TARGET_LIBRARY
