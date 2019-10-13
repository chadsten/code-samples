#!/bin/bash

# usage
# run from season directory
# ensure episodes are in order
# before you run this!

# arguments
# 1: Show name
# 2: Season number (xx)

i=1
for f in ./*; do
	# skip any directories 
  if [ -f "$f" ]
	then
		# don't rename script for obvious reasons
		if [ "$f" != "./rename.sh" ]
		then
			# preserve the extension for later
		  filename=$(basename -- "$f")
		  ext="${filename##*.}"

		  # fix e1-e9 to be e01-e09, for tvdb
		  	len=${#i}
			if [ $len -lt 2 ]
			then
				# add 0 to front
				ep="0${i}"
			else
				ep=${i}
			fi

		  # filename-SXX-EYY.ext
		  mv "$f" "./${1}-s${2}-e${ep}.${ext}"
		  i=$((i+1))
		fi
  fi
done

echo "Episodes renamed!"
