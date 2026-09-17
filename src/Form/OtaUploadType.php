<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

class OtaUploadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('version', TextType::class, [
                'label' => 'Version du Firmware (ex: 1.0.2)',
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez saisir une version.']),
                ],
                'attr' => [
                    'placeholder' => '1.0.2',
                    'class' => 'form-control',
                ],
            ])
            ->add('firmwareFile', FileType::class, [
                'label' => 'Fichier binaire (.bin)',
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    new File([
                        'maxSize' => '8M',
                        'mimeTypes' => [
                            'application/octet-stream',
                            'application/x-dosexec',      // Type MIME détecté sous Windows
                            'application/x-binary',
                            'application/macbinary',
                        ],
                        'mimeTypesMessage' => 'Veuillez téléverser un fichier .bin valide.',
                    ])
                ],
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Téléverser le Firmware',
                'attr' => ['class' => 'btn btn-primary']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
