<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MainController extends AbstractController{

    #[Route('/', name: 'app_home')]
    public function index(): Response{
        return $this->render('main/index.html.twig',[
            'nombre' => 'Borja Lozano',
            'descripcion_proyecto' => 'Aplicación Symfony con páginas públicas, login y una zona privada.',
            'fecha_actual' => (new \DateTime())->format('d/m/Y H:i')
        ]);
    }

    #[Route('/acerca-de', name: 'app_about')]
    public function about(): Response{
        return $this->render('main/about.html.twig',[
            'detalle_tecnico' => 'PHP 8.4 en Docker, Symfony 8.1, Twig y SQLite.'
        ]);
    }
}

?>