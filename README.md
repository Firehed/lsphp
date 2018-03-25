# PHP Language Server Protocol adapter

_Under development, not yet stable!_

This aims to be a bridge between various PHP analysis tools and the Language Server Protocol.
It does _not_ aim to directly provide code analysis.

## Installation

(This won't work yet)

`composer require --dev firehed/lsphp`

### Requirements

PHP 7.1 is required to run the language server (e.g. on your local machine).
The codebase using the project doesn't have any specific version requirements, but may be subject to requirements enforced by the tools the language server runs.
Since this should only ever be installed as a `--dev` requirement, there should be no impact to production requirements.

## Usage

Usage varies heavily on your editor.
Generally speaking, you will need to register `vendor/bin/lsphp` as a binary to start a Language Server.

## Compatible tools

* `php -l`

### Coming Soon

* PHP_CodeSniffer
