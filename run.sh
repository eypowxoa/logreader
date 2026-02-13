#!/usr/bin/env sh
test -n "$(docker image ls --quiet logreader:local)" || docker build --file configs/dockerfile --tag logreader:local .
docker run --interactive --rm --tty --volume "$PWD:/app:rw" --workdir /app logreader:local "$@" && echo OK || echo FAIL
