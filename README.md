# Log Reader

Web page for viewing a short period of a big log files.

# Usage

Build `logreader56.tar.gz` for PHP 5.6

Extract `logreader56.tar.gz` to a web server root.

Edit `logreader56.config.php` as you need.

Open `logreader56.php` in a browser.

# Requirements for running

- PHP 5.6 with extensions:
- filter
- mbstring

# Requirements for building and testing

- docker

# Requirements for using git hooks

- bash

# Building fo PHP 5.6

Subfolder `logreader56` in the `build` folder `./build56.sh`

Archive `logreader56.tar.gz` in the `build` folder `./build56.sh --archive`

# Testing

All checks `./run.sh composer checklist`

Only code style `./run.sh composer cs`

Only static analysis `./run.sh composer stan`

Only tests `./run.sh composer test`

# Other commands

Run composer

`./run.sh composer`

Fix code style

`./run.sh composer fix`

Install git hooks

`./run.sh composer exec captainhook -- install --configuration configs/captainhook.json --force --run-exec 'bash ./run.sh --script' --run-git /app/.git --run-mode docker`

Run PHP 8.5 web page http://localhost:8000/logreader.php (login `example` password `elpmaxe`)

`./run.sh pwd && docker run --interactive --publish 8000:8000 --rm --tty --volume "$PWD:/app:rw" --workdir /app logreader:local php -S 0.0.0.0:8000 -t .`

Run PHP 5.6 web page http://localhost:8001/logreader56.php (login `example` password `elpmaxe`)

`./build56.sh && docker run --interactive --publish 8001:8001 --rm --tty --volume "$PWD/build/logreader56:/app:rw" --workdir /app logreader56:local php -S 0.0.0.0:8001 -t .`
