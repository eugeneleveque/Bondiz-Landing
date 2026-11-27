<?php

namespace App\Controller\Admin;

use App\Entity\PartnershipRequest;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * Demandes reçues depuis la page « Devenir partenaire ». On ne les crée pas
 * à la main : on les consulte et on les marque comme traitées.
 */
class PartnershipRequestCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return PartnershipRequest::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Demande de partenariat')
            ->setEntityLabelInPlural('Demandes de partenariat')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        $creer = Action::new('creerPartenaire', 'Créer le partenaire', 'fas fa-store')
            ->linkToRoute('admin_partners_new', static fn (PartnershipRequest $demande) => ['demande' => $demande->getId()])
            ->displayIf(static fn (PartnershipRequest $demande) => !$demande->isHandled());

        return $actions
            ->disable(Action::NEW)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $creer)
            ->add(Crud::PAGE_DETAIL, $creer);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            DateTimeField::new('createdAt', 'Reçue le')->setFormat('dd/MM/yyyy HH:mm')->hideOnForm(),
            TextField::new('establishmentName', 'Établissement')->setDisabled(),
            TextField::new('activityType', 'Activité')->setDisabled(),
            TextField::new('city', 'Ville')->setDisabled(),
            TextField::new('contactName', 'Contact')->setDisabled(),
            EmailField::new('email', 'E-mail')->setDisabled(),
            TelephoneField::new('phone', 'Téléphone')->setDisabled(),
            TextareaField::new('message', 'Message')->hideOnIndex()->setDisabled(),
            BooleanField::new('handled', 'Traitée'),
        ];
    }
}
