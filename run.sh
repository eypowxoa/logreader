#!/usr/bin/env sh

set -e

test -n "$(docker image ls --quiet logreader:local)" || docker build --file configs/dockerfile --tag logreader:local .

if test "$1" = '--script'; then
    docker run --rm --volume "$PWD:/app:rw" --workdir /app logreader:local "${@:2}"
else
    docker run --interactive --rm --tty --volume "$PWD:/app:rw" --workdir /app logreader:local "$@" && echo OK
fi
