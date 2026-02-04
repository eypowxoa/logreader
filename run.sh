#!/usr/bin/env sh
docker run --interactive --rm --tty --volume "$PWD:/app:rw" --workdir /app logparser:local $@ && echo OK || echo FAIL
