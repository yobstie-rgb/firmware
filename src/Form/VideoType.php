<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

class VideoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre de la vidéo',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Ex: Démonstration serveur SMS'],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez saisir un titre.'])
                ]
            ])
            ->add('videoFile', FileType::class, [
                'label' => 'Fichier Vidéo (MP4, WEBM)',
                'required' => true,
                'attr' => ['class' => 'form-control-file'],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez sélectionner un fichier vidéo.']),
                    new File([
                        'maxSize' => '100M',
                        'mimeTypes' => [
                            'video/mp4',
                            'video/webm',
                            'video/ogg',
                        ],
                        'mimeTypesMessage' => 'Veuillez téléverser un format vidéo valide (MP4, WEBM, OGG)',
                    ])
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Pas de data_class liée
        ]);
    }
}