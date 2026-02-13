#!/usr/bin/env bash

set -e

test -n "$(docker image ls --quiet logreader:local)" || docker build --file configs/dockerfile --tag logreader:local .

test "$1" = '--script'\
&& docker run --rm --volume "$PWD:/app:rw" --workdir /app logreader:local "${@:2}"\
|| (docker run --interactive --rm --tty --volume "$PWD:/app:rw" --workdir /app logreader:local "$@" && echo OK || echo FAIL)
