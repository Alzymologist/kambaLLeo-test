#!/bin/sh

/usr/bin/convert "${1}" -resize 100x -colors 2 -normalize -colorspace Gray -dither FloydSteinberg -alpha off -background white -alpha Background "${1}.bmp"
