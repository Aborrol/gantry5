#!/bin/bash

SASS_PATH=../common/scss:../../gantry/prime/scss:../../gantry/common/scss:../../../engines/nucleus/scss

#====================================================================

if [ ! -d scss ]; then
    mkdir scss
fi
if [ ! -d css-compiled ]; then
    mkdir css-compiled
fi

export SASS_PATH
scss --sourcemap=auto --unix-newlines --watch scss:css-compiled
