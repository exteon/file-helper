# FileHelper

This is a library implementing common filesystem operations.

## Requirements

* PHP 7.2

## Usage

### Installing with `composer`

```shell script
composer require exteon/file-helper
```

### Examples
```php
use Exteon\FileHelper;

// Delete a directory's contents, but preserve the directory itself
FileHelper::rmDir('/foo/bar', false);

// Copy a directory's contents
FileHelper::copyDir('/foo/bar','/foo/baz');

// Create all directories alongside a filesystem path
FileHelper::preparePath('/foo/bar');
```