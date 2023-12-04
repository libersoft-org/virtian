#!/bin/sh

[ ! -d "./node_modules/" ] && bun i
bun virtian.js $1
