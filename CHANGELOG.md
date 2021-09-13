### 1.6.1

#### Bugfixes

Allow `''` as a relative path in `FileHelper::applyRelativePath()`

## 1.6.0

#### Bugfixes

* Fix `getRelativePath()`

#### New features

* `FileHelper::getRelativePath()` now throws `NotAPrefixException` when path is
  not a subpath of base path
* Introduce `FileHelper::isAbsolutePath()`

## 1.5.0

* Added `FileHelper::getRelativePath()`
* Added `FileHelper::applyRelativePath()`
* Added `FileHelper::normalizePath()`

## 1.4.0

#### New features

* Added `FileHelper::addTrailingSlash()`

### 1.3.1

#### Bugfixes

* FileHelper::getChildren() should not return dotfiles

## 1.3.0

#### New features

* `getDescendPath()` now takes multiple descend parameters
* Added `getChildren()` method

## 1.2.0

#### New features

* Added `getDescendants()` method

## 1.1.3

#### Bugfixes

* Allow `preparePath('')` 

## 1.1.0
* Added path handling functions: getAscendPath(), getDescendPath(), getFilename()