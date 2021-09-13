<?php

    namespace Exteon;

    use DirectoryIterator;
    use Exception;
    use Exteon\FileHelper\Exception\NotAPrefixException;
    use InvalidArgumentException;
    use RecursiveDirectoryIterator;
    use RecursiveIteratorIterator;

    abstract class FileHelper
    {
        public static function addTrailingSlash(string $path): string
        {
            return $path . (substr($path, -1) === '/' ? '' : '/');
        }

        /**
         * Recursively deletes a directory
         *
         * @param string $dir The directory to be deleted
         * @param bool $includingDir Whether to delete the directory referred to
         *   by $dir or only its contents
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
                $result = @rmdir($dir);
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
            foreach (new RecursiveIteratorIterator($iterator) as $ignored) {
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
                if (!$file->isDot()) {
                    $result[] = static::getDescendPath(
                        $path,
                        $file->getFilename()
                    );
                }
            }
            return $result;
        }

        /**
         * @throws Exception
         */
        public static function getRelativePath(
            string $path,
            string $basePath,
            bool $allowTranscendentPaths = false
        ): string {
            $pathComponents = self::normalizePathArray(
                explode('/', $path),
                true
            );
            $basePathComponents = self::normalizePathArray(
                explode('/', $basePath),
                true
            );
            $pathComponentsCount = count($pathComponents);
            $basePathComponentsCount = count($basePathComponents);

            /** @noinspection PhpStatementHasEmptyBodyInspection */
            for (
                $commonLength = 0;
                $commonLength < $pathComponentsCount &&
                $commonLength < $basePathComponentsCount &&
                $basePathComponents[$commonLength] === $pathComponents[$commonLength];
                $commonLength++
            ) {
            }

            if (
                !$commonLength &&
                $pathComponentsCount &&
                !$pathComponentsCount[0]
            ) {
                throw new NotAPrefixException(
                    'If path is rooted, basePath must also be rooted'
                );
            }

            if (
                !$allowTranscendentPaths &&
                $commonLength < $basePathComponentsCount
            ) {
                throw new Exception('Base path is not a prefix of path');
            }

            $constructedPath = array_slice($pathComponents, $commonLength);
            for ($i = 0; $i < $basePathComponentsCount - $commonLength; $i++) {
                array_unshift($constructedPath, '..');
            }

            return implode('/', $constructedPath);
        }

        /**
         * @param string[] $path
         * @param bool $allowDotRelative
         * @return string[]
         * @throws Exception
         */
        private static function normalizePathArray(
            array $path,
            bool $allowDotRelative
        ): array {
            $parsedPath = [];
            $hasLeadingTrail = false;
            $hasLeadingDot = false;
            foreach ($path as $key => $pathFrag) {
                switch ($pathFrag) {
                    case '':
                        if (!$key) {
                            $hasLeadingTrail = true;
                        }
                        break;
                    case '.':
                        if (!$allowDotRelative) {
                            throw new InvalidArgumentException(
                                'Dotfile path is not allowed'
                            );
                        }
                        if (!$key) {
                            $hasLeadingDot = true;
                        }
                        break;
                    case '..':
                        if (!$allowDotRelative) {
                            throw new InvalidArgumentException(
                                'Dotfile path is not allowed'
                            );
                        }
                        if (!$parsedPath) {
                            throw new Exception(
                                'Relative path goes above base path'
                            );
                        }
                        array_pop($parsedPath);
                        break;
                    default:
                        $parsedPath[] = $pathFrag;
                        break;
                }
            }
            if ($hasLeadingTrail) {
                array_unshift($parsedPath, '');
            } elseif ($hasLeadingDot) {
                array_unshift($parsedPath, '.');
            }
            // Re-add trailing slash
            if (
                $path &&
                !array_pop($path)
            ) {
                $parsedPath[] = '';
            }
            return $parsedPath;
        }

        /**
         * @throws Exception
         */
        public static function normalizePath(
            string $path,
            bool $allowDotRelative = false
        ): string {
            return implode(
                '/',
                self::normalizePathArray(
                    explode('/', $path),
                    $allowDotRelative
                )
            );
        }

        /**
         * @throws Exception
         */
        public static function applyRelativePath(
            string $basePath,
            string $relPath,
            bool $allowDotRelative = false
        ): string {
            $path = explode('/', $basePath);
            $relPathFrags = explode('/', $relPath);
            if (
                $relPathFrags &&
                !$relPathFrags[0]
            ) {
                throw new InvalidArgumentException(
                    'relPath is not a relative path'
                );
            }
            $path = array_merge($path, $relPathFrags);
            return implode(
                '/',
                self::normalizePathArray(
                    $path,
                    $allowDotRelative
                )
            );
        }

        public static function isAbsolutePath(string $getPath): bool
        {
            return (
                $getPath &&
                $getPath[0] === '/'
            );
        }
    }