# Composer CodeSniffer Hooks

[![Build Status](https://img.shields.io/travis/MetaSyntactical/composer-codesniffer-hooks.svg?style=flat-square)](https://travis-ci.org/MetaSyntactical/composer-codesniffer-hooks)
[![Downloads this Month](https://img.shields.io/packagist/dm/metasyntactical/composer-codesniffer-hooks.svg?style=flat-square)](https://packagist.org/packages/metasyntactical/composer-codesniffer-hooks)
[![Latest stable](https://img.shields.io/packagist/v/metasyntactical/composer-codesniffer-hooks.svg?style=flat-square)](https://packagist.org/packages/metasyntactical/composer-codesniffer-hooks)

Hooks to enable PHP CodeSniffer with the Coding Standard configurable after running `composer install` or `composer update`.

## Install

Install dependency via composer:

```sh
$ composer require --dev metasyntactical/composer-codesniffer-hooks
```

Add scripts to composer post hooks:

```json
"scripts": {
    "post-install-cmd": [
        "MetaSyntactical\\CodeSniffer\\Composer\\ScriptHandler::addPhpCsToPreCommitHook"
    ],
    "post-update-cmd": [
        "MetaSyntactical\\CodeSniffer\\Composer\\ScriptHandler::addPhpCsToPreCommitHook"
    ]
}
```json

## Usage

Every time you try to commit, PHP_CodeSniffer will run on changed `.php` files only. There is nothing to do manually.
