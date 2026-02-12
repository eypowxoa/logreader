#!/usr/bin/env sh
mkdir -p build/logreader56

echo '<?php' > build/logreader56/logreader56.php
cat src/* logreader.php\
| grep -v '^<?php'\
| grep -Fv declare\
| grep -Fv interface\
| grep -Fv namespace\
| grep -Fv use\
| grep -Fv autoload.php\
>> build/logreader56/logreader56.php

cat logreader.config.example.php\
| grep -Fv declare\
| grep -Fv use\
> build/logreader56/logreader56.config.php

docker build --file configs/dockerfile --tag logparser:local .
docker run --interactive --rm --tty --volume "$PWD:/app:rw" --workdir /app logparser:local composer install
cat configs/rector.php | sed -E 's~withPhpSets\(\)~withDowngradeSets\(php71:true\)~' > build/rector71.php
docker run --interactive --rm --tty --volume "$PWD:/app:rw" --workdir /app logparser:local composer exec rector -- --config build/rector71.php build/logreader56/logreader56.php
docker run --interactive --rm --tty --volume "$PWD:/app:rw" --workdir /app logparser:local composer exec rector -- --config build/rector71.php build/logreader56/logreader56.config.php
rm build/rector71.php

cat build/logreader56/logreader56.php\
| sed -E 's~private const~const~'\
| sed -E 's~public private\(set\)~public~'\
| sed -E 's~\?*(bool|int|string) \$~\$~'\
| sed -E 's~\?*(bool|int|string) \$~\$~'\
| sed -E 's~\?*(bool|int|string) \$~\$~'\
| sed -E 's~\?*(bool|int|string) \$~\$~'\
| sed -E 's~\?*(bool|int|string) \$~\$~'\
| sed -E 's~\?\\\w+ \$~\$~'\
| sed -E 's~\?\?~\?:~'\
| sed -E 's~\?\?~\?:~'\
| sed -E 's~\?\?~\?:~'\
| sed -E 's~\): \??\w+~\)~'\
| sed -E 's~\[\$utf8Offset, \$utf8Length\] = (.*)~$a=\1\$utf8Offset=$a[0];\$utf8Length=$a[1];~'\
| sed -E 's~\(function~\[\$a=function~'\
| sed -E 's~\)\(\)~,\$a()\]\[1\]~'\
| sed -E 's~ extends \\Throwable~~'\
| sed -E 's~Throwable~Exception~'\
| sed -E 's~intdiv\(([^,]+), ([^)]+)\)~\(int\)floor\(\(\1\) / \2\)~'\
| sed -E 's~mb_ord\((.*), self::BYTE_ENCODING\)~ord\(\1\)~'\
| sed -E 's~Anu~Au~'\
| sed -E 's~, \$microsecond~~'\
| sed -E 's~CheckedException~Exception~'\
| sed -E 's~ implements Exception~~'\
> build/logreader56/logreader56.tmp
rm build/logreader56/logreader56.php
mv build/logreader56/logreader56.tmp build/logreader56/logreader56.php

cat build/logreader56/logreader56.config.php\
| sed -E 's~: bool~~'\
| sed -E 's~\w+: ~~'\
> build/logreader56/logreader56.config.tmp
rm build/logreader56/logreader56.config.php
mv build/logreader56/logreader56.config.tmp build/logreader56/logreader56.config.php

cp example.log build/logreader56/example.log

rm build/logreader56.tar.gz
tar --create --directory build --file build/logreader56.tar.gz --gzip logreader56
rm -r build/logreader56
