<?php
namespace App\Controller;

use App\Core\Controller;

class NotFoundController extends Controller
{

    public function index()
    {
        $this->render('error/404');
    }
}