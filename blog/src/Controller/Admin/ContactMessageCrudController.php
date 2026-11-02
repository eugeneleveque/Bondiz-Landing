<?php

namespace App\Controller\Admin;

use App\Entity\ContactMessage;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * Messages reçus depuis la page « Nous contacter ». On ne les crée pas à la
 * main : on les consulte et on les marque comme traités.
 */
class ContactMessageCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ContactMessage::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Message de contact')
            ->setEntityLabelInPlural('Messages de contact')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW)
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            DateTimeField::new('createdAt', 'Reçu le')->setFormat('dd/MM/yyyy HH:mm')->hideOnForm(),
            TextField::new('lastName', 'Nom')->setDisabled(),
            TextField::new('firstName', 'Prénom')->setDisabled(),
            EmailField::new('email', 'E-mail')->setDisabled(),
            TextField::new('subject', 'Objet')->setDisabled(),
            TextareaField::new('message', 'Message')->hideOnIndex()->setDisabled(),
            BooleanField::new('handled', 'Traité'),
        ];
    }
}
