#!/bin/bash

ROOT_DIR=`pwd`
SRC_DIR="public/webfonts"
DIST_DIR="public/css/themes/default/assets/fonts"
FONTS=`ls $SRC_DIR | grep solid`

for i in ${FONTS[*]}
do
    EXTENSION=${i#*.}

    if [[ ! -f "$DIST_DIR/icons.$EXTENSION" ]]; then
        echo "ln -s $SRC_DIR/$i $DIST_DIR/icons.$EXTENSION"
        ln -s "$ROOT_DIR/$SRC_DIR/$i" "$DIST_DIR/icons.$EXTENSION"
    fi
done
