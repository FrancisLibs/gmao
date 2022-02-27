<?php

namespace App\Controller;

use DateInterval;
use App\Entity\Connexion;
use App\Entity\Workorder;
use App\Repository\UserRepository;
use App\Repository\ParamsRepository;
use App\Repository\TemplateRepository;
use App\Repository\WorkorderRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\WorkorderStatusRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class DefaultController extends AbstractController
{
    private $paramsRepository;
    private $workorderRepository;
    private $templateRepository;
    private $workorderStatusRepository;
    private $userRepository;
    private $manager;


    public function __construct(
        EntityManagerInterface $manager,
        TemplateRepository $templateRepository,
        WorkorderRepository $workorderRepository,
        ParamsRepository $paramsRepository,
        WorkorderStatusRepository $workorderStatusRepository,
        UserRepository $userRepository
    ) {
        $this->paramsRepository = $paramsRepository;
        $this->workorderRepository = $workorderRepository;
        $this->templateRepository = $templateRepository;
        $this->manager = $manager;
        $this->workorderStatusRepository = $workorderStatusRepository;
        $this->userRepository = $userRepository;
    }

    /**
     * @Route("/", name="home")
     * @Security("is_granted('ROLE_USER')")
     */
    public function index(): Response
    {
        $user = $this->getUser();
        $organisation = $user->getOrganisation();
        $organisationId = $organisation->getId();
        $serviceId = $user->getService()->getId();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Création d'un enregistrement des connexions
        if ($user) {
            $connexion = new Connexion();
            $connexionDate = (new \DateTime());
            $user = $this->getUser();
            $connexion
                ->setDate($connexionDate)
                ->setUser($user);
            $manager = $this->getDoctrine()->getManager();
            $manager->persist($connexion);
            $manager->flush();
        }

        // Gestion des bons de travail préventifs-------------------------------------
        $today = (new \DateTime())->getTimestamp();
       
        // Dernière date de vérification cherchée dans le fichiers des paramètres
        $params = $this->paramsRepository->find(1);
        $lastPreventiveDate = $params->getLastPreventiveDate()->getTimestamp();
        
        // On vérifie tous les jours puis rajout d'1 jour à la date enregistrée
        $lastPreventiveDate = $lastPreventiveDate + 24 * 60 * 60;
        if ($lastPreventiveDate <= $today) {
            // Définition de la prochaine date à celle d'aujourd'hui
            $params->setLastPreventiveDate(new \DateTime());
            $this->manager->persist($params);

            $this->preventiveProcessing($organisationId, $today);

            $this->setpreventiveStatus($organisationId, $today);
        }

        // ------------------------------------------------------------------------------
        // Récupération des utilisateurs pour l'affichage des photos
        // Par organisation ET service
        $users = $this->userRepository->findBy(
            [
                'organisation' => $organisationId,
                'service' => $serviceId,
            ],
        );
        return $this->render('default/index.html.twig', [
            'users'         => $users,
        ]);
    }

    private function preventiveProcessing($organisationId, $today)
    {
        // Recherche des templates préventifs
        $templates = $this->templateRepository->findAllTemplates($organisationId);

        foreach ($templates as $template) {
            // Prochaine date en secondes
            $nextDate = $template->getNextDate()->getTimestamp(); // Date de réalisation
            // Jours avant la date en secondes
            $secondsBefore = $template->getDaysBefore() * 24 * 60 * 60; // Jours avant réalisation
            // Date finale à prende en compte
            $nextCalculateDate = $nextDate - $secondsBefore; // Date finale d'activation en secondes
            
            // Test si template éligible
            if ($nextCalculateDate <= $today) {
                // Contrôle si BT préventif n'est pas déjà actif
                if (!$this->workorderRepository->countPreventiveWorkorder($template->getTemplateNumber())) {
                    // Création du BT préventif, en récupérant les infos sur le template préventif
                    $workorder = new Workorder();
                    $workorder->setCreatedAt(new \DateTime())
                        ->setPreventiveDate($template->getNextDate())
                        ->setRequest($template->getRequest())
                        ->setRemark($template->getRemark())
                        ->setOrganisation($template->getOrganisation())
                        ->setTemplateNumber($template->getTemplateNumber())
                        ->setUser($template->getUser())
                        ->setType(Workorder::PREVENTIF)
                        ->setPreventive(true)
                        ->setDaysBeforeLate($template->getDaysBeforeLate());
                    if ($template->getDaysBefore() > 0) {
                        $status = $this->workorderStatusRepository->findOneBy(['name' => 'EN_PREP.']);
                    } else {
                        $status = $this->workorderStatusRepository->findOneBy(['name' => 'EN_COURS']);
                    }
                    $workorder->setWorkorderStatus($status);
                    $machines = $template->getMachines();
                    foreach ($machines as $machine) {
                        $workorder->addMachine($machine);
                    }

                    $this->manager->persist($workorder);
                }
                $this->manager->flush();
            }
        }
        return;
    }

    // Pour l'évolution du BT dans le temps et gérer son état : modification du statut...
    private function setpreventiveStatus($organisation, $today)
    {
        $preventiveWorkorders = $this->workorderRepository->findAllPreventiveWorkorders($organisation);
        if ($preventiveWorkorders) {
            foreach ($preventiveWorkorders as $workorder) {
                $today = (new \Datetime())->getTimeStamp();
                $preventiveDate = $workorder->getPreventiveDate()->getTimeStamp();
                $daysBeforeLate = $workorder->getDaysBeforeLate() * 24 * 60 * 60;
                $dateBerforeLate = $preventiveDate + $daysBeforeLate;

                if ($today < $preventiveDate) {
                    $status = $this->workorderStatusRepository->findOneByName('EN_PREP.');
                } elseif ($today >= $preventiveDate && $today < $dateBerforeLate) {
                    $status = $this->workorderStatusRepository->findOneByName('EN_COURS');
                } elseif ($today > $dateBerforeLate) {
                    $status = $this->workorderStatusRepository->findOneByName('EN_RETARD');
                }

                $workorder->setWorkorderStatus($status);
                $this->manager->persist($workorder);
            }
            $this->manager->flush();
        }
    }
}
