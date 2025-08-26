<?php
/**
 * BVOTE Authentication Interface
 */

namespace BVOTE\Core\Interfaces;

interface AuthInterface {
    public function authenticate($credentials);
    public function authorize($permission);
    public function logout();
    public function getCurrentUser();
    public function isAuthenticated();
}
