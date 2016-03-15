#!/usr/bin/env bash
fswatch -o src/ | xargs -n1 -I{} cp -r src/* ../magento20/app/code/divido/dividofinancing
