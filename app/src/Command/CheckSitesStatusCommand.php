<?php

namespace App\Command;

use App\Entity\Website;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CheckSitesStatusCommand extends Command
{
    protected static $defaultName = 'check-sites-status';

    private $entityManager;
    private $client;
    /**
     * @var MailerInterface
     */
    private $mailer;

    public function __construct(EntityManagerInterface $entityManager,HttpClientInterface $client, MailerInterface $mailer)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->client = $client;
        $this->mailer = $mailer;
    }

    protected function configure()
    {
        $this
            ->setDescription('check-status')
            ->addOption('force-ko', 'ko', InputOption::VALUE_OPTIONAL, 'force to change all sites to ko')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $websites = $this->entityManager->getRepository(Website::class)->findAll();
        $forceKo = $input->hasOption('force-ko');
        /** @var Website $website */
        foreach ($websites as $website)
        {
            if ($forceKo) {
                $statusCode = 400;
            } else {
                $statusCode = $this->client->request('GET', (string)$website->getUrl())->getStatusCode();
            }
            if ($statusCode !== $website->getStatus()) {
                $website->setStatus((string) $statusCode);
                $this->entityManager->flush();

                if ($statusCode !== 200 && $website->getUser()) {
                    $email = new TemplatedEmail();
                    $email->from(new Address('tracking.sites.symfony@gmail.com', 'Tracking site mail'))
                        ->to($website->getUser()->getEmail())
                        ->subject(sprintf('Votre site %s est n\'est plus en ligne', $website->getName()))
                        ->htmlTemplate('email/website_down.html.twig');
                    $context = $email->getContext();
                    $context['website'] = $website;
                    $email->context($context);
                    $this->mailer->send($email);
                    // to avoid mailer surcharge
                    sleep(2);
                }
            }
        }
        $io->success('les status des sites sont mis à jour');

        return 0;
    }
}
