<?php
namespace App\Controller;

use App\Core\Controller;

class HomeController extends Controller
{
    public function index(): void
    {
        $this->forward('service','index');
    }
}
