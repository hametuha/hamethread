#!/usr/bin/env bash

set -e

# Build files
composer install --no-dev --prefer-dist
npm install
npm start
# Make Readme
echo 'Generate readme.'
curl -L https://raw.githubusercontent.com/fumikito/wp-readme/master/wp-readme.php | php
# Remove files
rm -rf node_modules
rm -rf .distignore
rm -rf .editorconfig
rm -rf .git
rm -rf .github
rm -rf .gitignore
rm -rf bin
rm -rf tests
rm -rf phpcs.xml.dist
rm -rf phpunit.xml.dist
rm -rf README.md
