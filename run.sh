#!/usr/bin/env sh
docker run --interactive --rm --tty --volume "$PWD:/app:rw" --workdir /app logreader:local "$@" && echo OK || echo FAIL
