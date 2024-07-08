<?php

namespace App\Controller;

use App\Service\ImportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomepageController extends AbstractController
{
    #[Route('/', name: 'homepage')]
    public function index(Request $request, ImportService $converter): Response
    {
        $form = $this->createFormBuilder()
            ->add('content', TextareaType::class, [
                'attr' => ['rows' => '10'],
                'label' => 'Data to be imported:'
            ])
            ->add('display_ai', CheckboxType::class, [
                'label' => 'Display AI response',
                'required' => false,
            ])
            ->add('save', SubmitType::class, ['label' => 'Import'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $converter->import(
                $form->get('content')->getData(),
                $form->get('display_ai')->getData()
            );

            // Set a flash message
            $this->addFlash('success', 'Data imported successfully!');

            return $this->render('homepage.html.twig', [
                'form' => $form
            ]);
        }

        return $this->render('homepage.html.twig', [
            'form' => $form
        ]);
    }
}
