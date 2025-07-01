<?php
namespace App\Controller;

use App\Core\Controller;
use Throwable;

class ErrorController extends Controller
{

    public function index(Throwable $exception)
    {
        $this->render('error/500', [
            'exception' => $exception,
        ]);
    }
    
}