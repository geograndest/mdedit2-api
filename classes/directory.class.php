<?php

namespace MdEditApi;

require_once __DIR__ . '/file.class.php';

/**
 * Class Directory permet de générer les fichiers d'un répertoire (liste, ajout, suppression, modification)
 * 
 * Exemple:
 * ```
 * require_once './directory.class.php';
 * 
 * ```
 * 
 */
class Directory
{
    protected $path;
    protected $root_path;
    protected $exts;

    public function __construct($path = false, $exts = false, $root_path = false)
    {
        $this->path = $path;
        $this->root_path = $root_path;
        $this->exts = $exts;
    }

    public function getFiles($path = false, $recursive = false, $exts = false, $root_path = false)
    {
        if ($path) {
            $this->path = $path;
        }
        if ($root_path) {
            $this->root_path = $root_path;
        }
        if ($exts) {
            $this->exts = $exts;
        }
        $response = [
            'path' => $this->path,
            'root_path' => $this->root_path,
            'extentions' => $this->exts,
            'files' => [],
            'message' => 'Erreur lors de la lecture du dossier spécifié.',
            'success' => false
        ];
        if ($recursive) {
            $files = new \RecursiveDirectoryIterator($this->root_path . $this->path);
            $iterator = new \RecursiveIteratorIterator($files, \RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $base_path = str_replace($this->root_path, "", $file->getPathname());
                    $f = new File();
                    // var_dump(
                    //     [
                    //         $base_path, $this->root_path
                    //     ]
                    // );
                    $fileInfo = $f->getFile($base_path, $this->root_path);
                    if (!$this->exts || (!!$this->exts and \in_array($fileInfo['ext'], $this->exts))) {
                        $response['files'][] = $f->getFile($base_path, $this->root_path);
                        $response['message'] = 'Les fichiers ont été listés avec succès';
                        $response['success'] = true;
                    }
                }
            }
        } else {
            $files = glob($this->root_path . $this->path . "*.*");
            // var_dump(
            //     [
            //         $files
            //     ]
            // );
            if (count($files) > 0) {
                foreach ($files as $file) {
                    if (is_file("$file")) {
                        $base_path = str_replace($this->root_path, "", $file);
                        $f = new File();
                        $fileInfo = $f->getFile($base_path, $this->root_path);
                        if (!$this->exts || (!!$this->exts and \in_array($fileInfo['ext'], $this->exts))) {
                            $response['files'][] = $f->getFile($base_path, $this->root_path);
                            $response['message'] = 'Les fichiers ont été listés avec succès';
                            $response['success'] = true;
                        }
                    }
                }
            }
        }
        return $response;
    }

    public function createDirectory($path = false)
    {
        if ($path) {
            $this->path = $path;
        }
        $response = [
            'path' => $this->path,
            'message' => 'Erreur lors de la création du dossier spécifié.',
            'success' => false
        ];
        if (!is_dir($path)) {
            if (mkdir($path, 0777, true)) {
                $response['message'] = "Le dossier a été créé avec succès.";
                $response['success'] = true;
            }
        } else {
            $response['message'] = "Le dossier existe déjà dans le chemin spécifié.";
        }
        return $response;
    }

    public function removeDirectory($path = false)
    {
        if ($path) {
            $this->path = $path;
        }
        $response = [
            'path' => $this->path,
            'message' => 'Erreur lors de la suppression du dossier spécifié.',
            'success' => false
        ];
        $files = array_diff(scandir($this->path), ['.', '..']);
        foreach ($files as $file) {
            if (is_dir($this->path . DIRECTORY_SEPARATOR . $file)) {
                $this->removeDirectory($this->path . DIRECTORY_SEPARATOR . $file);
            }
            unlink($this->path . DIRECTORY_SEPARATOR . $file);
        }
        if (rmdir($this->path)) {
            $response['success'] = true;
            $response['message'] = 'Le dossier a été supprimé avec succès';
        }
        return $response;
    }

    public function moveDirectory($old, $new, $path = false)
    {
        if ($path) {
            $this->path = $path;
        }
        $response = [
            'path' => $this->path,
            'old' => $old,
            'new' => $new,
            'message' => 'Erreur lors de la suppression du dossier',
            'success' => false
        ];
        if (rename($this->path . $old, $this->path . $new)) {
            $response['success'] = true;
            $response['message'] = 'Le dossier a été déplacé avec succès.';
        }

        return $response;
    }
}
