<?php

namespace Exteon;

abstract class FileHelper
{

    /**
     * Recursively deletes a directory
     *
     * @param string $dir The directory to be deleted
     * @param bool $includingDir Whether to deleted the directory referred to by $dir or only its contents
     *
     * @return bool Whether the operation was successful
     */
    static function rmDir(string $dir, bool $includingDir = true) :bool
    {
        if (!file_exists($dir)) {
            return true;
        }
        $result = true;
        $handle = @opendir($dir);
        if (!$handle) {
            return false;
        }
        while (($file = readdir($handle)) || $file !== false) {
            if (
                $file != '.' &&
                $file != '..'
            ) {
                $filePath = "$dir/$file";
                switch (filetype($filePath)) {
                    case 'dir':
                        $result = ($result && self::rmDir($filePath));
                        break;
                    case 'file':
                    case 'link':
                        $result = ($result && @unlink($filePath));
                        break;
                }
            }
        }
        closedir($handle);
        if (
            $includingDir &&
            $result
        ) {
            $result = ($result && @rmdir($dir));
        }
        return $result;
    }

    /**
     * Recursively copies a directory to another directory
     *
     * @param string $sourceDir The source directory
     * @param string $destDir The destination directory
     *
     * @return bool Whether the operation was successful
     */
    static function copyDir(string $sourceDir, string $destDir) :bool
    {
        if (!is_dir($sourceDir)) {
            return false;
        }
        $handle = @opendir($sourceDir);
        if (!$handle) {
            return false;
        }
        if (
            !is_dir($destDir) &&
            !@mkdir($destDir)
        ) {
            return false;
        }
        $result = true;
        while (($file = readdir($handle)) || $file !== false) {
            if (
                $file != '.' &&
                $file != '..'
            ) {
                $sourcePath = "$sourceDir/$file";
                $destPath = "$destDir/$file";
                switch (filetype($sourcePath)) {
                    case 'dir':
                        $result = $result && self::copyDir($sourcePath, $destPath);
                        break;
                    case 'file':
                    case 'link':
                        $result = $result && @copy($sourcePath, $destPath);
                        break;
                }
            }
        }
        closedir($handle);
        return $result;
    }

    /**
     * Give a file path, creates all directories along the path
     *
     * @param string $path Path to be created
     * @param bool $excludeLast Whether to not create a directory for the last filename component in $path
     *
     * @return bool Whether the operation was successful
     */
    static function preparePath(string $path, bool $excludeLast = false) :bool
    {
        $pieces = explode('/', $path);
        if ($pieces[0] == '') {
            $path = '/';
            array_shift($pieces);
        } else {
            $path = '';
        }
        while (!$pieces[count($pieces) - 1]) {
            array_pop($pieces);
        }
        if ($excludeLast) {
            array_pop($pieces);
        }
        $createTrail = [];
        do {
            $checkPath = $path . implode('/', $pieces);
            if (@is_dir($checkPath)) {
                break;
            }
            if (!$pieces) {
                return false;
            }
            array_unshift($createTrail, array_pop($pieces));
        } while (true);
        foreach ($createTrail as $frag) {
            $checkPath .= '/' . $frag;
            if (!@mkdir($checkPath)) {
                return false;
            }
        }
        return true;
    }
}