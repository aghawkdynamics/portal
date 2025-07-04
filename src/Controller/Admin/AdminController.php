<?php
namespace App\Controller\Admin;

use App\Core\Controller;
use App\Model\Account\User;

abstract class AdminController extends Controller
{
    /**
     * AccountController constructor.
     * Ensures that only admin users can access this controller.
     */
    public function __construct()
    {
        parent::__construct();
        if (!User::isAdmin()) {
            $this->redirect('/error/404');
        }
    }

}