<?php

    namespace Exteon;

    use DirectoryIterator;
    use Exception;
    use InvalidArgumentException;
    use IteratorIterator;
    use RecursiveDirectoryIterator;
    use RecursiveIteratorIterator;

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
        public static function rmDir(
            string $dir,
            bool $includingDir = true
        ): bool {
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
        public static function copyDir(string $sourceDir, string $destDir): bool
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
                            $result = $result && self::copyDir(
                                    $sourcePath,
                                    $destPath
                                );
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
        public static function preparePath(
            string $path,
            bool $excludeLast = false
        ): bool {
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
                    if ($path) {
                        return false;
                    }
                    break;
                }
                array_unshift($createTrail, array_pop($pieces));
            } while (true);
            foreach ($createTrail as $frag) {
                if ($checkPath) {
                    $checkPath .= '/';
                }
                $checkPath .= $frag;
                if (!@mkdir($checkPath)) {
                    return false;
                }
            }
            return true;
        }

        public static function getAscendPath(
            string $path,
            int $levels = 1
        ): string {
            if ($levels < 1) {
                throw new InvalidArgumentException('Invalid $levels argument');
            }
            $pathFrags = explode('/', $path);
            if ($levels > count($pathFrags)) {
                throw new InvalidArgumentException(
                    '$levels is too high for the $path'
                );
            }
            $frags = array_slice($pathFrags, 0, count($pathFrags) - $levels);
            return implode('/', $frags);
        }

        public static function getFileName(string $path): string
        {
            $info = pathinfo($path);
            return $info['filename'];
        }

        /**
         * @param string $path
         * @return string[]
         */
        public static function getDescendants(string $path): array
        {
            $result = [];
            $iterator = new RecursiveDirectoryIterator($path);
            foreach (new RecursiveIteratorIterator($iterator) as $file) {
                $result[] = static::getDescendPath(
                    $path,
                    $iterator->getSubPath()
                );
            }
            return $result;
        }

        public static function getDescendPath(
            string $path,
            string ...$descend
        ): string {
            $pathFrags = explode('/', $path);
            for ($i = 1; $i < count($pathFrags) - 1; $i++) {
                if (!$pathFrags[$i]) {
                    throw new InvalidArgumentException(
                        'Invalid $path argument'
                    );
                }
            }
            $descendFrags = array_merge(
                ...array_map(
                       function ($path) {
                           $descendFrags = explode('/', $path);
                           if (!$descendFrags) {
                               throw new InvalidArgumentException(
                                   'Invalid $descend argument'
                               );
                           }
                           return $descendFrags;
                       },
                       $descend
                   )
            );
            for ($i = 0; $i < count($descendFrags) - 1; $i++) {
                if (!$descendFrags[$i]) {
                    throw new InvalidArgumentException(
                        'Invalid $descend argument'
                    );
                }
            }
            if (
                $pathFrags &&
                !end($pathFrags)
            ) {
                array_pop($pathFrags);
            }
            if (
                $descendFrags &&
                !end($descendFrags)
            ) {
                array_pop($descendFrags);
            }
            $frags = array_merge($pathFrags, $descendFrags);
            return implode('/', $frags);
        }

        /**
         * @param string $path
         * @return string[]
         */
        public static function getChildren(string $path): array
        {
            $result = [];
            $iterator = new DirectoryIterator($path);
            foreach ($iterator as $file) {
                if(!$file->isDot()){
                    $result[] = static::getDescendPath(
                        $path,
                        $file->getFilename()
                    );
                }
            }
            return $result;
        }
    }