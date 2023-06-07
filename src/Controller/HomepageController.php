<?php

namespace App\Controller;

use App\Service\Converter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomepageController extends AbstractController
{
    #[Route('/', name: 'homepage')]
    public function index(Request $request, Converter $converter): Response
    {
        $form = $this->createFormBuilder()
            ->add('file', FileType::class)
            ->add('save', SubmitType::class, ['label' => 'Generate CSV'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('file')->getData();

            // Convert Bradesco CSV to Mobills CSV
            $result = $converter->convertBradesco($file->getPathname());

            // Create a temp CSV file
            $tmpFile = tempnam(sys_get_temp_dir(), 'prefix_');
            file_put_contents($tmpFile, $result);

            // Create a file model and delete the temp file
            $file = new File($tmpFile);
            register_shutdown_function('unlink', $tmpFile);

            // Return the new CSV file as a download
            return $this->file(
                $file, 'Mobills_Bradesco_' . date('d-m-Y') . '.csv'
            );
        }

        return $this->render('homepage.html.twig', [
            'form' => $form
        ]);
    }
}
