<?php

namespace MdEditApi;

require_once __DIR__ . '/json.class.php';
require_once __DIR__ . '/helpers.class.php';

/**
 * Class User permet de générer les fichiers d'un répertoire (liste, ajout, suppression, modification)
 * 
 * Exemple:
 * ```
 * require_once './user.class.php';
 * 
 * ```
 * 
 */
class User
{
    protected $config;
    protected $user;

    public function __construct()
    {
        $this->config = Json::get(__DIR__ . '/../config/config.json');
    }

    public function getUserInfo()
    {
        $this->user = [
            'editor' => false,
            'proxy' => Helpers::getHeader('HTTP_SEC_PROXY'),
            'username' => Helpers::getHeader('HTTP_SEC_USERNAME'),
            'email' => Helpers::getHeader('HTTP_SEC_EMAIL'),
            'orgs' => [],
            'roles' => explode(';', Helpers::getHeader('HTTP_SEC_ROLES')),
            'directories' => [],
            'root_directory' => $this->config['md_relative_path'],
            'root_url' => $this->config['md_root_url']
        ];
        // Get User MD Roles (= orgs) and check if user is editor
        foreach ($this->user['roles'] as $role) {
            if ($role) {
                $isRoleMd = (substr($role, 0, strlen($this->config['md_role_prefix'])) == $this->config['md_role_prefix']);
                $isNotExcludeRole = (!in_array($role, $this->config['md_role_exclude']));
                if ($isRoleMd and $isNotExcludeRole) {
                    $roleName = substr($role, strlen($this->config['md_role_prefix']));
                    $this->user['orgs'][] = $roleName;
                    $this->user['directories'][] = strtolower($roleName);
                }
                if (in_array($role, $this->config['editor_roles']) and count($this->user['orgs']) > 0) {
                    $this->user['editor'] = true;
                }
            }
        }
        return $this;
    }

    public function getUserInfoDebug()
    {
        $debug = Json::get(__DIR__ . '/../config/debug.json');
        $this->user = $debug['user'];
        return $this;
    }

    public function getInfo($param = false)
    {
        if ($param) {
            return $this->user[$param];
        }
        return $this->user;
    }

    public function isEditor()
    {
        return $this->user['editor'] == true;
    }
}
